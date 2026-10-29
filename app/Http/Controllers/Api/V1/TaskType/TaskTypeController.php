<?php

namespace App\Http\Controllers\Api\V1\TaskType;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TaskType\StoreTaskTypeRequest;
use App\Http\Requests\Api\V1\TaskType\UpdateTaskTypeRequest;
use App\Http\Resources\Api\V1\TaskType\TaskTypeResource;
use App\Models\TaskType;
use App\Services\TaskType\TaskTypeService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TaskTypeController extends Controller implements HasMiddleware
{
    use ApiResponse;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show_task_types', only: ['index', 'show']),
            new Middleware('permission:create_task_types', only: ['store']),
            new Middleware('permission:update_task_types', only: ['update']),
            new Middleware('permission:delete_task_types', only: ['destroy']),
        ];
    }

    public function __construct(
        protected TaskTypeService $taskTypeService
    ) {}

    /**
     * Display a listing of task types.
     */
    public function index(Request $request): JsonResponse
    {
        $projectId = $request->query('project_id') ? (int) $request->query('project_id') : null;
        $taskTypes = $this->taskTypeService->getAll($projectId);

        return $this->successResponse(
            TaskTypeResource::collection($taskTypes),
            __('messages.task_type_list')
        );
    }

    /**
     * Store a newly created task type.
     */
    public function store(StoreTaskTypeRequest $request): JsonResponse
    {
        $taskType = $this->taskTypeService->create($request->validated());

        return $this->successResponse(
            new TaskTypeResource($taskType),
            __('messages.task_type_created'),
            201
        );
    }

    /**
     * Display the specified task type.
     */
    public function show(TaskType $taskType): JsonResponse
    {
        $loadedTaskType = $this->taskTypeService->getById($taskType);

        return $this->successResponse(
            new TaskTypeResource($loadedTaskType),
            __('messages.task_type_retrieved')
        );
    }

    /**
     * Update the specified task type.
     */
    public function update(UpdateTaskTypeRequest $request, TaskType $taskType): JsonResponse
    {
        $updated = $this->taskTypeService->update($taskType, $request->validated());

        return $this->successResponse(
            new TaskTypeResource($updated),
            __('messages.task_type_updated')
        );
    }

    /**
     * Remove the specified task type.
     */
    public function destroy(TaskType $taskType): JsonResponse
    {
        $this->taskTypeService->delete($taskType);

        return $this->successResponse(
            null,
            __('messages.task_type_deleted')
        );
    }
}
