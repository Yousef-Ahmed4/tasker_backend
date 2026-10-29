<?php

namespace App\Http\Controllers\Api\V1\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Project\StoreProjectRequest;
use App\Http\Requests\Api\V1\Project\UpdateProjectRequest;
use App\Http\Resources\Api\V1\Project\ProjectResource;
use App\Models\Project;
use App\Services\Project\ProjectService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProjectController extends Controller implements HasMiddleware
{
    use ApiResponse;

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show_projects', only: ['index', 'show']),
            new Middleware('permission:create_projects', only: ['store']),
            new Middleware('permission:update_projects', only: ['update']),
            new Middleware('permission:delete_projects', only: ['destroy']),
        ];
    }

    public function __construct(
        protected ProjectService $projectService
    ) {}

    /**
     * Display a listing of projects.
     */
    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->getAll();

        return $this->successResponse(
            ProjectResource::collection($projects),
            __('messages.project_list')
        );
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated(), $request->user()->id);

        return $this->successResponse(
            new ProjectResource($project),
            __('messages.project_created'),
            201
        );
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): JsonResponse
    {
        $loadedProject = $this->projectService->getById($project);

        return $this->successResponse(
            new ProjectResource($loadedProject),
            __('messages.project_retrieved')
        );
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $updated = $this->projectService->update($project, $request->validated());

        return $this->successResponse(
            new ProjectResource($updated),
            __('messages.project_updated')
        );
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->projectService->delete($project);

        return $this->successResponse(
            null,
            __('messages.project_deleted')
        );
    }
}
