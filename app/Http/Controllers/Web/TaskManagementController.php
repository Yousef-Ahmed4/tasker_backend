<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskManagementController extends Controller
{
    /**
     * Display tasks list, task types, task statuses, and handle trashed filter.
     */
    public function index(Request $request): View
    {
        $isTrashed = (bool) $request->boolean('trashed');

        $taskTypes = TaskType::with('project')->withCount('tasks')->get();
        $taskStatuses = TaskStatus::withCount('tasks')->orderBy('order')->get();
        $workspaces = Workspace::all();
        $projects = Project::all();

        $query = $isTrashed ? Task::onlyTrashed() : Task::query();
        $query->with(['status', 'taskType', 'creator', 'fixedBy', 'workspace.project', 'children'])
            ->latest('id');

        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', $request->input('workspace_id'));
        }

        if ($request->filled('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        if ($request->filled('task_type_id')) {
            $query->where('task_type_id', $request->input('task_type_id'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $tasks = $query->paginate(15)->withQueryString();
        $activeCount = Task::count();
        $trashedCount = Task::onlyTrashed()->count();

        return view('dashboard.tasks.index', compact(
            'tasks',
            'taskTypes',
            'taskStatuses',
            'workspaces',
            'projects',
            'isTrashed',
            'activeCount',
            'trashedCount'
        ));
    }

    /**
     * Soft delete a task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back()->with('success', __('dashboard.task_deleted_success') ?: 'Task deleted successfully.');
    }

    /**
     * Restore a soft-deleted task.
     */
    public function restore(int|string $id): RedirectResponse
    {
        $task = Task::onlyTrashed()->findOrFail($id);
        $task->restore();

        return back()->with('success', __('dashboard.task_restored_success') ?: 'Task restored successfully.');
    }

    /**
     * Store new task type.
     */
    public function storeType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'type'       => ['required', 'string', Rule::in(['network', 'device', 'focus', 'other'])],
            'project_id' => ['nullable', 'exists:projects,id'],
        ]);

        TaskType::create($validated);

        return back()->with('success', 'Task type created successfully.');
    }

    /**
     * Store new task status.
     */
    public function storeStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string', Rule::in(['pending', 'working', 'completed'])],
            'order' => ['required', 'integer', 'min:1'],
        ]);

        TaskStatus::create($validated);

        return back()->with('success', 'Task status created successfully.');
    }
}
