<?php

namespace App\Http\Controllers\Api\V1\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspace\StoreWorkspaceRequest;
use App\Http\Requests\Api\V1\Workspace\UpdateWorkspaceRequest;
use App\Http\Resources\Api\V1\Workspace\WorkspaceResource;
use App\Models\Workspace;
use App\Services\Workspace\WorkspaceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WorkspaceController extends Controller implements HasMiddleware
{
    use ApiResponse;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show_workspaces', only: ['index', 'show']),
            new Middleware('permission:create_workspaces', only: ['store']),
            new Middleware('permission:update_workspaces', only: ['update']),
            new Middleware('permission:delete_workspaces', only: ['destroy']),
        ];
    }

    public function __construct(
        protected WorkspaceService $workspaceService
    ) {}

    /**
     * Display a listing of workspaces.
     */
    public function index(Request $request): JsonResponse
    {
        $projectId = $request->query('project_id') ? (int) $request->query('project_id') : null;
        $workspaces = $this->workspaceService->getAll(null, $projectId);

        return $this->successResponse(
            WorkspaceResource::collection($workspaces),
            __('messages.workspace_list')
        );
    }

    /**
     * Store a newly created workspace.
     */
    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $workspace = $this->workspaceService->create(
            $request->validated(),
            $request->user()->id
        );

        return $this->successResponse(
            new WorkspaceResource($workspace),
            __('messages.workspace_created'),
            201
        );
    }

    /**
     * Display the specified workspace.
     */
    public function show(Workspace $workspace): JsonResponse
    {
        $loadedWorkspace = $this->workspaceService->getById($workspace);

        return $this->successResponse(
            new WorkspaceResource($loadedWorkspace),
            __('messages.workspace_retrieved')
        );
    }

    /**
     * Update the specified workspace.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        $updated = $this->workspaceService->update($workspace, $request->validated());

        return $this->successResponse(
            new WorkspaceResource($updated),
            __('messages.workspace_updated')
        );
    }

    /**
     * Remove the specified workspace.
     */
    public function destroy(Workspace $workspace): JsonResponse
    {
        $this->workspaceService->delete($workspace);

        return $this->successResponse(
            null,
            __('messages.workspace_deleted')
        );
    }
}
