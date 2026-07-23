<?php

use App\Http\Controllers\TaskAttachmentController;
use App\Livewire\Actions\Logout;
use App\Models\Project;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Public Routes
Route::redirect('/', '/login');

// Logout Route (must be authenticated)
Route::post('/logout', function (Request $request, Logout $logout) {
    $logout();
    return redirect('/');
})->middleware('auth')->name('logout');

// Authenticated Routes
Route::middleware(['auth', 'verified', 'active'])->group(function () {
    
    // Dashboard
    Route::view('/dashboard', 'pages.dashboard')->name('dashboard');

    // Profile
    Route::view('profile', 'profile')->name('profile');

    // Master Data Routes
    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::view('/companies', 'master-data.companies')->middleware('can:companies_view')->name('companies');
    });

    // Notifications
    Route::view('/notifications', 'notifications.index')->middleware('can:notifications_view')->name('notifications.index');
    Route::view('/notifications/send', 'notifications.send')->middleware('can:notifications_send')->name('notifications.send');

    // Chat
    Route::view('/chat', 'chat.index')->middleware('can:chat_view')->name('chat.index');

    // Task Management
    Route::prefix('task-management')->name('task-management.')->group(function () {
        Route::get('/projects', function () {
            return view('task-management.projects');
        })->middleware('can:projects_view')->name('projects');

        Route::get('/projects/create', function () {
            return view('task-management.project-form');
        })->middleware('can:projects_create')->name('projects.create');

        Route::get('/projects/{project}/edit', function (Project $project) {
            return view('task-management.project-form', ['project' => $project]);
        })->middleware('can:projects_update')->name('projects.edit');

        Route::get('/projects/{project}/board', function (Project $project) {
            abort_unless(auth()->user()->can('view', $project), 403);
            return view('task-management.board', ['project' => $project]);
        })->middleware('can:projects_view')->name('board');

        Route::get('/attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])
            ->middleware('can:tasks_view')
            ->name('attachments.download');
    });

    // Settings Routes - each route checks its own permission
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::view('/system', 'settings.system')->middleware('can:configuration_view')->name('system');
        Route::view('/users', 'settings.users')->middleware('can:users_view')->name('users');
        Route::view('/roles', 'settings.roles')->middleware('can:roles_view')->name('roles');
    });
});

require __DIR__.'/auth.php';
