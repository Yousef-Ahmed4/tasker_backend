<?php

namespace App\Services\Task;

use App\Models\Attachment;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\User;
use App\Models\Workspace;
use App\Services\File\FileService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class TaskService
{
    public function __construct(
        protected FileService $fileService = new FileService()
    ) {}

    /**
     * Get all tasks with optional filters, parent filter, children eagerly loaded, and pagination.
     */
    public function getAll(array $filters = [], ?User $user = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Task::with(['creator', 'fixedBy', 'project', 'workspace', 'status', 'taskType', 'attachments', 'children'])
            ->withCount('subtasks')
            ->orderBy('position');

        // Filter where null parent_id by default, unless explicitly filtered or requested all
        if (array_key_exists('parent_task_id', $filters) && $filters['parent_task_id'] !== null && $filters['parent_task_id'] !== '') {
            if ($filters['parent_task_id'] !== 'all' && $filters['parent_task_id'] !== '*') {
                $query->where('parent_task_id', $filters['parent_task_id']);
            }
        } elseif (array_key_exists('parent_id', $filters) && $filters['parent_id'] !== null && $filters['parent_id'] !== '') {
            if ($filters['parent_id'] !== 'all' && $filters['parent_id'] !== '*') {
                $query->where('parent_task_id', $filters['parent_id']);
            }
        } elseif (empty($filters['all_tasks']) && empty($filters['include_subtasks'])) {
            $query->whereNull('parent_task_id');
        }

        if (!empty($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        $statusFilter = $filters['status_id'] ?? $filters['status_ids'] ?? $filters['task_status_id'] ?? null;
        if (!empty($statusFilter)) {
            if (is_array($statusFilter)) {
                $query->whereIn('status_id', array_filter($statusFilter));
            } elseif (is_string($statusFilter) && str_contains($statusFilter, ',')) {
                $query->whereIn('status_id', array_filter(array_map('trim', explode(',', $statusFilter))));
            } else {
                $query->where('status_id', $statusFilter);
            }
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        // Role & Project visibility rules:
        if ($user) {
            $user->loadMissing('userType');
            if (! $user->userType) {
                $query->where('created_by', $user->id);
            }

            // In tasks, if querying a project that is not the user's project, only return tasks created by this user
            if ($user->project_id) {
                if (!empty($filters['project_id'])) {
                    if ((int) $filters['project_id'] !== (int) $user->project_id) {
                        $query->where('created_by', $user->id);
                    }
                } elseif (!empty($filters['workspace_id'])) {
                    $workspace = Workspace::find($filters['workspace_id']);
                    if ($workspace && $workspace->project_id && (int) $workspace->project_id !== (int) $user->project_id) {
                        $query->where('created_by', $user->id);
                    }
                } else {
                    $query->where(function ($q) use ($user) {
                        $q->where('project_id', $user->project_id)
                          ->orWhere('created_by', $user->id);
                    });
                }
            } elseif (! $user->hasRole('admin')) {
                // If user has no assigned project and is not an admin, only show tasks created by them
                $query->where('created_by', $user->id);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Get task by instance with creator, project, workspace, and children eager loaded.
     */
    public function getById(Task $task): Task
    {
        return $task->loadMissing([
            'creator',
            'fixedBy',
            'project',
            'workspace',
            'status',
            'taskType',
            'attachments',
            'children',
            'children.status',
            'children.taskType',
        ])->loadCount('subtasks');
    }

    /**
     * Create a task with auto-position, stage tracking, and optional attachments.
     */
    public function create(array $data, int $userId): Task
    {
        $data['created_by'] = $userId;

        // Extract attachments before creating task model
        $attachments = $data['attachments'] ?? [];
        if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
            $attachments[] = $data['attachment'];
        }
        unset($data['attachments'], $data['attachment']);

        // Auto position: max(position) + 1 in the workspace
        if (!isset($data['position']) || $data['position'] === null) {
            $maxPosition = Task::where('workspace_id', $data['workspace_id'])->max('position') ?? 0;
            $data['position'] = $maxPosition + 1;
        }

        // Apply status stage logic
        if (!empty($data['status_id'])) {
            $status = TaskStatus::find($data['status_id']);
            if ($status) {
                if ($status->stage === 'working' && empty($data['working_at'])) {
                    $data['working_at'] = now();
                } elseif ($status->stage === 'completed') {
                    $completedAt = !empty($data['completed_at']) ? Carbon::parse($data['completed_at']) : now();
                    $data['completed_at'] = $completedAt;

                    $workingAt = !empty($data['working_at'])
                        ? Carbon::parse($data['working_at'])
                        : now();

                    $data['actual_minutes'] = (int) max(0, $workingAt->diffInMinutes($completedAt));
                }
            }
        }

        $task = Task::create($data);

        // Store attachments using Task::ATTACHMENT_PATH if provided
        if (!empty($attachments)) {
            $this->storeAttachments($task, $attachments, $userId);
        }

        return $task->loadMissing(['creator', 'fixedBy', 'project', 'workspace', 'status', 'taskType', 'attachments']);
    }

    /**
     * Store attachments for a task using Task::ATTACHMENT_PATH.
     */
    public function storeAttachments(Task $task, array|UploadedFile $files, int $userId): Collection
    {
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        $created = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $storedPath = $this->fileService->upload($file, Task::ATTACHMENT_PATH);
                $created[] = $task->attachments()->create([
                    'file'       => $file->getClientOriginalName(),
                    'path'       => $storedPath,
                    'type'       => $file->getClientMimeType() ?: ($file->getClientOriginalExtension() ?: 'unknown'),
                    'size'       => $file->getSize() ?: 0,
                    'created_by' => $userId,
                ]);
            }
        }

        return new Collection($created);
    }

    /**
     * Update task attributes, handling stage transitions and actual_minutes.
     */
    public function update(Task $task, array $data): Task
    {
        if (isset($data['status_id']) && $data['status_id'] !== $task->status_id) {
            $status = TaskStatus::find($data['status_id']);
            if ($status) {
                if ($status->stage === 'working') {
                    if (empty($task->working_at) && empty($data['working_at'])) {
                        $data['working_at'] = now();
                    }
                } elseif ($status->stage === 'completed') {
                    $completedAt = !empty($data['completed_at']) ? Carbon::parse($data['completed_at']) : now();
                    $data['completed_at'] = $completedAt;

                    $workingAt = !empty($data['working_at'])
                        ? Carbon::parse($data['working_at'])
                        : ($task->working_at ?? $task->created_at ?? now());

                    $data['actual_minutes'] = (int) max(0, $workingAt->diffInMinutes($completedAt));
                }
            }
        }

        $task->update($data);

        return $task->fresh(['creator', 'fixedBy', 'project', 'workspace', 'status', 'taskType', 'attachments'])
            ->loadCount('subtasks');
    }

    /**
     * Soft delete a task.
     */
    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }

    /**
     * Get soft-deleted tasks with optional filters and pagination.
     */
    public function getTrashed(array $filters = [], ?User $user = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Task::onlyTrashed()
            ->with(['creator', 'fixedBy', 'project', 'workspace', 'status', 'taskType', 'children'])
            ->withCount('subtasks')
            ->latest('deleted_at');

        if (!empty($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        $statusFilter = $filters['status_id'] ?? $filters['status_ids'] ?? $filters['task_status_id'] ?? null;
        if (!empty($statusFilter)) {
            if (is_array($statusFilter)) {
                $query->whereIn('status_id', array_filter($statusFilter));
            } elseif (is_string($statusFilter) && str_contains($statusFilter, ',')) {
                $query->whereIn('status_id', array_filter(array_map('trim', explode(',', $statusFilter))));
            } else {
                $query->where('status_id', $statusFilter);
            }
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if ($user) {
            $user->loadMissing('userType');
            if (! $user->userType) {
                $query->where('created_by', $user->id);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Restore a soft-deleted task.
     */
    public function restore(int|string $id): Task
    {
        $task = Task::onlyTrashed()->findOrFail($id);
        $task->restore();

        return $task->loadMissing([
            'creator',
            'fixedBy',
            'project',
            'workspace',
            'status',
            'taskType',
            'attachments',
            'children',
        ])->loadCount('subtasks');
    }
}
