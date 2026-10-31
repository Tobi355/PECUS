<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SourceController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\MovementController;
use App\Http\Controllers\API\ReceiptController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\GoalController;
use App\Http\Controllers\API\BudgetController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserPreferenceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are stateless, which means they are intended to be used for
| APIs that do not require maintaining state between requests.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Sources
    Route::apiResource('/sources', SourceController::class)->except(['show', 'update', 'destroy']);
    // Explicit routes for the remaining resource actions.
    Route::get('/sources/{source}', [SourceController::class, 'show']);
    Route::put('/sources/{source}', [SourceController::class, 'update']);
    Route::delete('/sources/{source}', [SourceController::class, 'destroy']);

    // Categories
    Route::apiResource('/categories', CategoryController::class)->except(['show', 'update', 'destroy']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Movements
    Route::apiResource('/movements', MovementController::class)->except(['show', 'update', 'destroy']);
    Route::get('/movements/{movement}', [MovementController::class, 'show']);
    Route::put('/movements/{movement}', [MovementController::class, 'update']);
    Route::delete('/movements/{movement}', [MovementController::class, 'destroy']);

    // Receipts
    Route::apiResource('/receipts', ReceiptController::class)->except(['show', 'update', 'destroy']);
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show']);
    Route::put('/receipts/{receipt}', [ReceiptController::class, 'update']);
    Route::delete('/receipts/{receipt}', [ReceiptController::class, 'destroy']);
    // Manual review and confirmation; receipt files remain protected by authentication.
    Route::get('/receipts/{receipt}/file', [ReceiptController::class, 'file']);
    Route::post('/receipts/{receipt}/confirm', [ReceiptController::class, 'confirm']);

    // Dashboard
    Route::get('/dashboard', DashboardController::class);

    // Goals
    Route::apiResource('/goals', GoalController::class)->except(['show', 'update', 'destroy']);
    Route::get('/goals/{goal}', [GoalController::class, 'show']);
    Route::put('/goals/{goal}', [GoalController::class, 'update']);
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy']);

    // Budgets
    Route::apiResource('/budgets', BudgetController::class)->except(['show', 'update', 'destroy']);
    Route::get('/budgets/{budget}', [BudgetController::class, 'show']);
    Route::put('/budgets/{budget}', [BudgetController::class, 'update']);
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // User Preferences
    Route::get('/preferences', [UserPreferenceController::class, 'show']);
    Route::put('/preferences', [UserPreferenceController::class, 'update']);
});

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
