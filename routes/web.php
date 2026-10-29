<?php

use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PersonalSettingController;
use App\Http\Controllers\Web\RolePermissionController;
use App\Http\Controllers\Web\TaskManagementController;
use App\Http\Controllers\Web\UserManagementController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\WorkspaceProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Authentication Routes
|--------------------------------------------------------------------------
*/
Route::controller(WebAuthController::class)->group(function () {
    Route::get('/login', 'showLoginForm')->name('login');
    Route::post('/login', 'login')->name('login.post');
    Route::post('/login/quick', 'quickLogin')->name('login.quick');
    Route::post('/logout', 'logout')->name('logout');
});

/*
|--------------------------------------------------------------------------
| Protected Web Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:web')->group(function () {
    // Root redirects to dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard.index');
    });

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // 1. Home / Statistics & Charts
        Route::get('/', [DashboardController::class, 'index'])->name('index');

        // 2. User Management
        Route::get('/users', [UserManagementController::class, 'index'])->name('users');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/update', [UserManagementController::class, 'update'])->name('users.update');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update.put');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [UserManagementController::class, 'restore'])->name('users.restore');
        Route::post('/users/{user}/type', [UserManagementController::class, 'updateType'])->name('users.type');

        // 3. Role & Permission Management
        Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles');

        // 4. Workspace (المواسم) & Project (الأقسام) Management
        Route::get('/workspaces', [WorkspaceProjectController::class, 'index'])->name('workspaces');
        Route::post('/projects', [WorkspaceProjectController::class, 'storeProject'])->name('projects.store');
        Route::post('/projects/{project}/update', [WorkspaceProjectController::class, 'updateProject'])->name('projects.update');
        Route::delete('/projects/{project}', [WorkspaceProjectController::class, 'destroyProject'])->name('projects.destroy');
        Route::post('/workspaces', [WorkspaceProjectController::class, 'storeWorkspace'])->name('workspaces.store');
        Route::post('/workspaces/{workspace}/update', [WorkspaceProjectController::class, 'updateWorkspace'])->name('workspaces.update');
        Route::delete('/workspaces/{workspace}', [WorkspaceProjectController::class, 'destroyWorkspace'])->name('workspaces.destroy');

        // 5. Tasks Management (with Task Types & Statuses)
        Route::get('/tasks', [TaskManagementController::class, 'index'])->name('tasks');
        Route::delete('/tasks/{task}', [TaskManagementController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/tasks/{id}/restore', [TaskManagementController::class, 'restore'])->name('tasks.restore');
        Route::post('/tasks/types', [TaskManagementController::class, 'storeType'])->name('tasks.type.store');
        Route::post('/tasks/statuses', [TaskManagementController::class, 'storeStatus'])->name('tasks.status.store');

        // 6. Personal Settings & Theme
        Route::get('/settings', [PersonalSettingController::class, 'index'])->name('settings');
        Route::post('/settings', [PersonalSettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/toggle-theme', [PersonalSettingController::class, 'toggleTheme'])->name('settings.toggle_theme');
        Route::post('/settings/switch-locale', [PersonalSettingController::class, 'switchLocale'])->name('settings.switch_locale');
    });
});
