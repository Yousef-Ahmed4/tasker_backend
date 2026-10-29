<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\UserType;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    /**
     * Display users list with user types, project, workspaces, and soft delete filter.
     */
    public function index(Request $request): View
    {
        $isTrashed = (bool) $request->boolean('trashed');

        $query = $isTrashed ? User::onlyTrashed() : User::query();

        $users = $query->with(['userType', 'roles', 'project', 'workspaces'])
            ->withCount(['createdTasks', 'fixedTasks'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $activeCount = User::count();
        $trashedCount = User::onlyTrashed()->count();
        $projects = Project::orderBy('name')->get();
        $workspaces = Workspace::with('project')->orderBy('name')->get();
        $roles = Role::all();

        return view('dashboard.users.index', compact(
            'users',
            'isTrashed',
            'activeCount',
            'trashedCount',
            'projects',
            'workspaces',
            'roles'
        ));
    }

    /**
     * Store new user with project, workspaces, roles, and responsibilities.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'        => ['required', 'string', 'min:8'],
            'code'            => ['nullable', 'string', 'max:50', 'unique:users,code'],
            'project_id'      => ['required', 'exists:projects,id'],
            'role'            => ['nullable', 'string', 'exists:roles,name'],
            'workspaces'      => ['nullable', 'array'],
            'workspaces.*'    => ['exists:workspaces,id'],
            'workspace_roles' => ['nullable', 'array'],
            'type'            => ['nullable', 'string', 'max:50'],
            'respnsapity'     => ['nullable', 'string'],
        ], [
            'project_id.required' => 'برجاء اختيار القسم / المشروع',
            'project_id.exists'   => 'برجاء اختيار القسم / المشروع',
        ]);

        $role = $validated['role'] ?? null;
        $workspaces = $validated['workspaces'] ?? [];
        $workspaceRoles = $request->input('workspace_roles', []);
        $userType = $validated['type'] ?? null;
        $respnsapity = $validated['respnsapity'] ?? null;

        unset($validated['role'], $validated['workspaces'], $validated['workspace_roles'], $validated['type'], $validated['respnsapity']);

        $validated['password'] = Hash::make($validated['password']);
        if (isset($validated['code']) && empty($validated['code'])) {
            $validated['code'] = null;
        }

        $user = User::create($validated);

        if ($role) {
            $user->assignRole($role);
        }

        if (!empty($workspaces)) {
            $syncData = [];
            foreach ($workspaces as $wsId) {
                $syncData[$wsId] = ['role' => $workspaceRoles[$wsId] ?? 'viewer'];
            }
            $user->workspaces()->sync($syncData);
        }

        if (!empty($userType)) {
            $responsibilities = [];
            if (!empty($respnsapity)) {
                $responsibilities = array_values(array_filter(array_map('trim', explode(',', $respnsapity))));
            }
            UserType::create([
                'user_id'     => $user->id,
                'type'        => $userType,
                'respnsapity' => $responsibilities,
            ]);
        }

        return back()->with('success', __('dashboard.user_created_success') ?: 'تم إنشاء المستخدم بنجاح');
    }

    /**
     * Update existing user details, project, workspaces, roles, and responsibilities.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password'        => ['nullable', 'string', 'min:8'],
            'code'            => ['nullable', 'string', 'max:50', 'unique:users,code,' . $user->id],
            'project_id'      => ['required', 'exists:projects,id'],
            'role'            => ['nullable', 'string', 'exists:roles,name'],
            'workspaces'      => ['nullable', 'array'],
            'workspaces.*'    => ['exists:workspaces,id'],
            'workspace_roles' => ['nullable', 'array'],
            'type'            => ['nullable', 'string', 'max:50'],
            'respnsapity'     => ['nullable', 'string'],
        ], [
            'project_id.required' => 'برجاء اختيار القسم / المشروع',
            'project_id.exists'   => 'برجاء اختيار القسم / المشروع',
        ]);

        $role = $validated['role'] ?? null;
        $workspaces = $request->input('workspaces', []);
        $workspaceRoles = $request->input('workspace_roles', []);
        $userType = $validated['type'] ?? null;
        $respnsapity = $validated['respnsapity'] ?? null;

        unset($validated['role'], $validated['workspaces'], $validated['workspace_roles'], $validated['type'], $validated['respnsapity']);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (array_key_exists('code', $validated) && empty($validated['code'])) {
            $validated['code'] = null;
        }

        $user->update($validated);

        if ($role) {
            $user->syncRoles([$role]);
        }

        // Sync workspaces (مواسم)
        $syncData = [];
        foreach ($workspaces as $wsId) {
            $syncData[$wsId] = ['role' => $workspaceRoles[$wsId] ?? 'viewer'];
        }
        $user->workspaces()->sync($syncData);

        // Sync user type and responsibilities
        if (!empty($userType)) {
            $responsibilities = [];
            if (!empty($respnsapity)) {
                $responsibilities = array_values(array_filter(array_map('trim', explode(',', $respnsapity))));
            }
            UserType::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'type'        => $userType,
                    'respnsapity' => $responsibilities,
                ]
            );
        }

        return back()->with('success', __('dashboard.user_updated_success') ?: 'تم تحديث بيانات المستخدم بنجاح');
    }

    /**
     * Soft delete user.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return back()->with('success', __('dashboard.user_deleted_success') ?: 'تم حذف المستخدم بنجاح');
    }

    /**
     * Restore soft-deleted user.
     */
    public function restore(int|string $id): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return back()->with('success', __('dashboard.user_restored_success') ?: 'تم استعادة المستخدم بنجاح');
    }

    /**
     * Update user type and responsibilities directly.
     */
    public function updateType(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'type'        => ['required', 'string', 'max:50'],
            'respnsapity' => ['nullable', 'string'],
        ]);

        $responsibilities = [];
        if (!empty($validated['respnsapity'])) {
            $responsibilities = array_values(array_filter(array_map('trim', explode(',', $validated['respnsapity']))));
        }

        UserType::updateOrCreate(
            ['user_id' => $user->id],
            [
                'type'        => $validated['type'],
                'respnsapity' => $responsibilities,
            ]
        );

        return back()->with('success', 'تم حفظ المسؤوليات ونوع المستخدم بنجاح');
    }
}
