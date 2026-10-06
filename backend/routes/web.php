<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['application' => 'Scan & Save', 'status' => 'running']));

Route::prefix('api')->group(function (): void {
    Route::get('auth/csrf', [AuthController::class, 'csrf']);
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:registration');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');
    Route::middleware(['auth', EnsureActiveUser::class])->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('profile', [ProfileController::class, 'show']);
        Route::patch('profile', [ProfileController::class, 'update']);
        Route::delete('profile', [ProfileController::class, 'destroy']);
        Route::put('profile/password', [ProfileController::class, 'password']);
        Route::patch('profile/preferences', [ProfileController::class, 'preferences']);
        Route::get('profile/export', [ProfileController::class, 'export']);
        Route::get('dashboard', DashboardController::class);
        Route::get('documents/{document}/file', [DocumentController::class, 'file'])->name('api.documents.file');
        Route::get('documents/{document}/download', [DocumentController::class, 'file'])->name('api.documents.download');
        Route::apiResource('documents', DocumentController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        Route::patch('notifications/{notification}', [NotificationController::class, 'update']);
        Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function (): void {
            Route::get('users', [AdminController::class, 'users']);
            Route::patch('users/{user}', [AdminController::class, 'updateUser']);
            Route::delete('users/{user}', [AdminController::class, 'deleteUser']);
            Route::get('documents', [DocumentController::class, 'index']);
            Route::get('documents/{document}', [DocumentController::class, 'show']);
            Route::patch('documents/{document}', [DocumentController::class, 'update']);
            Route::delete('documents/{document}', [DocumentController::class, 'destroy']);
            Route::get('documents/{document}/file', [DocumentController::class, 'file'])->name('api.admin.documents.file');
            Route::get('documents/{document}/download', [DocumentController::class, 'file'])->name('api.admin.documents.download');
            Route::get('stats', [AdminController::class, 'stats']);
            Route::get('report', [AdminController::class, 'report']);
            Route::get('settings', [AdminController::class, 'settings']);
            Route::patch('settings', [AdminController::class, 'updateSettings']);
            Route::get('health', [AdminController::class, 'health']);
        });
    });
});
