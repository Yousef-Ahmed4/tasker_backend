<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceProjectController extends Controller
{
    /**
     * Display workspaces and projects overview.
     */
    public function index(): View
    {
        $projects = Project::with(['creator', 'workspaces'])
            ->withCount(['workspaces', 'tasks'])
            ->latest()
            ->get();

        $workspaces = Workspace::with(['project', 'creator', 'users'])
            ->withCount('tasks')
            ->latest()
            ->paginate(10);

        $users = User::select('id', 'name', 'email', 'project_id')->orderBy('name')->get();

        return view('dashboard.workspaces.index', compact('projects', 'workspaces', 'users'));
    }

    /**
     * Store a new Project (القسم).
     */
    public function storeProject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:projects,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'اسم القسم / المشروع مطلوب',
            'name.unique'   => 'اسم القسم مسجل مسبقاً',
        ]);

        $validated['created_by'] = auth()->id();

        Project::create($validated);

        return back()->with('success', 'تم إنشاء القسم / المشروع بنجاح');
    }

    /**
     * Update an existing Project (القسم).
     */
    public function updateProject(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:projects,name,' . $project->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'اسم القسم / المشروع مطلوب',
            'name.unique'   => 'اسم القسم مسجل مسبقاً',
        ]);

        $project->update($validated);

        return back()->with('success', 'تم تحديث بيانات القسم / المشروع بنجاح');
    }

    /**
     * Delete a Project (القسم).
     */
    public function destroyProject(Project $project): RedirectResponse
    {
        $project->delete();

        return back()->with('success', 'تم حذف القسم / المشروع بنجاح');
    }

    /**
     * Store a new Workspace (الموسم / مساحة العمل).
     */
    public function storeWorkspace(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'project_id'  => ['required', 'exists:projects,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'users'       => ['nullable', 'array'],
            'users.*'     => ['exists:users,id'],
            'roles'       => ['nullable', 'array'],
        ], [
            'name.required'       => 'اسم الموسم / مساحة العمل مطلوب',
            'project_id.required' => 'برجاء اختيار القسم / المشروع التابع له',
            'project_id.exists'   => 'القسم المختار غير صحيح',
        ]);

        $workspace = Workspace::create([
            'name'        => $validated['name'],
            'project_id'  => $validated['project_id'],
            'description' => $validated['description'] ?? null,
            'created_by'  => auth()->id(),
        ]);

        if (!empty($validated['users'])) {
            $syncData = [];
            $roles = $request->input('roles', []);
            foreach ($validated['users'] as $uId) {
                $syncData[$uId] = ['role' => $roles[$uId] ?? 'viewer'];
            }
            $workspace->users()->sync($syncData);
        }

        return back()->with('success', 'تم إنشاء الموسم / مساحة العمل بنجاح');
    }

    /**
     * Update an existing Workspace (الموسم / مساحة العمل).
     */
    public function updateWorkspace(Request $request, Workspace $workspace): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'project_id'  => ['required', 'exists:projects,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'users'       => ['nullable', 'array'],
            'users.*'     => ['exists:users,id'],
            'roles'       => ['nullable', 'array'],
        ], [
            'name.required'       => 'اسم الموسم / مساحة العمل مطلوب',
            'project_id.required' => 'برجاء اختيار القسم / المشروع التابع له',
            'project_id.exists'   => 'القسم المختار غير صحيح',
        ]);

        $workspace->update([
            'name'        => $validated['name'],
            'project_id'  => $validated['project_id'],
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->has('users')) {
            $syncData = [];
            $roles = $request->input('roles', []);
            foreach ($request->input('users', []) as $uId) {
                $syncData[$uId] = ['role' => $roles[$uId] ?? 'viewer'];
            }
            $workspace->users()->sync($syncData);
        }

        return back()->with('success', 'تم تحديث بيانات الموسم / مساحة العمل بنجاح');
    }

    /**
     * Delete a Workspace (الموسم / مساحة العمل).
     */
    public function destroyWorkspace(Workspace $workspace): RedirectResponse
    {
        $workspace->delete();

        return back()->with('success', 'تم حذف الموسم / مساحة العمل بنجاح');
    }
}
