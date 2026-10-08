<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\ForcePasswordResetController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommentUploadController;
use App\Http\Controllers\ExecutorController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PlanRecordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\RiskManagementController;
use App\Http\Controllers\TargetObjectiveController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('executors.index') : redirect()->route('login');
});

Auth::routes();

// Serve public storage files directly (works across WSL/Windows development environments without relying on OS symlink support)
Route::get('storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/'.$path);
    $realBase = realpath(storage_path('app/public'));
    $realPath = realpath($fullPath);

    if (! $realPath || ! str_starts_with($realPath, $realBase) || ! File::exists($realPath) || is_dir($realPath)) {
        abort(404);
    }

    return response()->file($realPath);
})->where('path', '.*')->name('storage.file');

Route::get('/home', function () {
    return redirect()->route('executors.index');
})->name('home');

Route::middleware('auth')->group(function () {
    // Forced Password Reset upon First Login (Temporary Password)
    Route::get('/password/first-reset', [ForcePasswordResetController::class, 'show'])->name('password.force_reset');
    Route::post('/password/first-reset', [ForcePasswordResetController::class, 'update'])->name('password.force_reset.update');

    // Dedicated Operatives Performance Leaderboard
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/executors/leaderboard', fn () => redirect()->route('leaderboard.index'))->name('executors.leaderboard');

    // Executors Directory & Details
    Route::get('/executors', [ExecutorController::class, 'index'])->name('executors.index');
    Route::get('/executors/create', [ExecutorController::class, 'create'])->name('executors.create');
    Route::post('/executors', [ExecutorController::class, 'store'])->name('executors.store');
    Route::get('/executors/{executor}', [PlanRecordController::class, 'showExecutor'])
        ->where('executor', '[0-9]+')
        ->name('executors.show');

    // Projects Module
    Route::resource('projects', ProjectController::class);

    // Plan Records CRUD
    Route::resource('plans', PlanRecordController::class);
    Route::patch('plans/{plan}/status', [PlanRecordController::class, 'updateStatus'])->name('plans.status.update');
    Route::patch('plans/{plan}/weights', [PlanRecordController::class, 'updateWeights'])->name('plans.weights.update');

    // Target Objectives
    Route::post('plans/{plan}/targets', [TargetObjectiveController::class, 'store'])->name('targets.store');
    Route::put('targets/{targetObjective}', [TargetObjectiveController::class, 'update'])->name('targets.update');
    Route::patch('targets/{targetObjective}/status', [TargetObjectiveController::class, 'updateStatus'])->name('targets.status.update');
    Route::patch('targets/{targetObjective}/priority', [TargetObjectiveController::class, 'updatePriority'])->name('targets.priority.update');
    Route::patch('targets/{targetObjective}/actual', [TargetObjectiveController::class, 'updateActual'])->name('targets.actual.update');
    Route::delete('targets/{targetObjective}', [TargetObjectiveController::class, 'destroy'])->name('targets.destroy');

    // Resources
    Route::post('plans/{plan}/resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::put('resources/{resource}', [ResourceController::class, 'update'])->name('resources.update');
    Route::patch('resources/{resource}/actual', [ResourceController::class, 'updateActual'])->name('resources.actual.update');
    Route::patch('resources/{resource}/status', [ResourceController::class, 'updateStatus'])->name('resources.status.update');
    Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');

    // Risk Management
    Route::post('plans/{plan}/risks', [RiskManagementController::class, 'store'])->name('risks.store');
    Route::put('risks/{riskManagement}', [RiskManagementController::class, 'update'])->name('risks.update');
    Route::patch('risks/{riskManagement}/status', [RiskManagementController::class, 'updateStatus'])->name('risks.status.update');
    Route::delete('risks/{riskManagement}', [RiskManagementController::class, 'destroy'])->name('risks.destroy');

    // Budgets (Marshall-only CRUD)
    Route::post('plans/{plan}/budgets', [BudgetController::class, 'store'])->name('budgets.store');
    Route::put('budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::patch('budgets/{budget}/status', [BudgetController::class, 'updateStatus'])->name('budgets.status.update');
    Route::patch('budgets/{budget}/actual', [BudgetController::class, 'updateActual'])->name('budgets.actual.update');
    Route::delete('budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

    // Comments / Remarks (Polymorphic)
    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('comments/upload/chunk', [CommentUploadController::class, 'uploadChunk'])->name('comments.upload.chunk');
    Route::post('comments/upload/assemble', [CommentUploadController::class, 'assemble'])->name('comments.upload.assemble');
    Route::get('comments/attachments/{attachment}/download', [CommentUploadController::class, 'download'])->name('comments.attachments.download');

    // Profile Picture (Staging, Chunk Upload & Zoom/Crop)
    Route::post('profile/avatar/assemble', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.destroy');

    // Operative Profile Page & Account Management
    Route::get('profile/{user?}', [ProfileController::class, 'show'])
        ->where('user', '[0-9]+')
        ->name('profile.show');
    Route::get('users/{user}', [ProfileController::class, 'show'])->name('users.show');
    Route::get('users/{user}/profile', [ProfileController::class, 'show'])->name('users.profile');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // System Administration Module (Admin-only)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('users', [AdminController::class, 'index'])->name('users.index');
        Route::get('users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::put('users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::get('marshalls/create', [AdminController::class, 'createMarshall'])->name('marshalls.create');
        Route::post('marshalls', [AdminController::class, 'storeMarshall'])->name('marshalls.store');
        Route::get('executors/create', [AdminController::class, 'createExecutor'])->name('executors.create');
        Route::post('executors', [AdminController::class, 'storeExecutor'])->name('executors.store');
    });
});
