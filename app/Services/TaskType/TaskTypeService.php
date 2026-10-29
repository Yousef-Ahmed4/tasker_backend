<?php

namespace App\Services\TaskType;

use App\Models\TaskType;
use Illuminate\Database\Eloquent\Collection;

class TaskTypeService
{
    /**
     * Get all task types (optionally filtered by project).
     */
    public function getAll(?int $projectId = null): Collection
    {
        $query = TaskType::with('project')->latest();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->get();
    }

    /**
     * Get task type by instance.
     */
    public function getById(TaskType $taskType): TaskType
    {
        return $taskType->loadMissing('project');
    }

    /**
     * Create a task type.
     */
    public function create(array $data): TaskType
    {
        return TaskType::create($data)->loadMissing('project');
    }

    /**
     * Update task type.
     */
    public function update(TaskType $taskType, array $data): TaskType
    {
        $taskType->update($data);

        return $taskType->fresh()->loadMissing('project');
    }

    /**
     * Delete task type.
     */
    public function delete(TaskType $taskType): bool
    {
        return (bool) $taskType->delete();
    }
}
