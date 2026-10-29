<?php

namespace App\Services\Workspace;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class WorkspaceService
{
    /**
     * Get all workspaces (optionally filtered by project or assigned user).
     */
    public function getAll(?int $userId = null, ?int $projectId = null): Collection
    {
        $query = Workspace::with(['project', 'creator', 'users'])->withCount('tasks')->latest();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereHas('users', fn($uq) => $uq->where('users.id', $userId))
                  ->orWhere('created_by', $userId);
            });
        }

        return $query->get();
    }

    /**
     * Get workspace details.
     */
    public function getById(Workspace $workspace): Workspace
    {
        return $workspace->loadMissing(['project', 'creator', 'users'])->loadCount('tasks');
    }

    /**
     * Create workspace and attach users.
     */
    public function create(array $data, int $userId): Workspace
    {
        $usersData = $data['users'] ?? [];
        unset($data['users']);

        $data['created_by'] = $userId;
        $workspace = Workspace::create($data);

        // Always attach creator as 'creator' role
        $syncData = [$userId => ['role' => 'creator']];

        foreach ($usersData as $item) {
            $syncData[$item['user_id']] = ['role' => $item['role']];
        }

        $workspace->users()->sync($syncData);

        return $workspace->loadMissing(['project', 'creator', 'users']);
    }

    /**
     * Update workspace and optionally sync users.
     */
    public function update(Workspace $workspace, array $data): Workspace
    {
        if (array_key_exists('users', $data)) {
            $usersData = $data['users'] ?? [];
            unset($data['users']);

            $syncData = [];
            foreach ($usersData as $item) {
                $syncData[$item['user_id']] = ['role' => $item['role']];
            }
            $workspace->users()->sync($syncData);
        }

        $workspace->update($data);

        return $workspace->fresh(['project', 'creator', 'users'])->loadCount('tasks');
    }

    /**
     * Delete workspace.
     */
    public function delete(Workspace $workspace): bool
    {
        return (bool) $workspace->delete();
    }
}
