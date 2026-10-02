<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\SourceController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\MovementController;
use App\Http\Controllers\API\ReceiptController;
use App\Http\Controllers\API\DashboardController;

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
    // We'll add specific routes for show, update, destroy if needed, but for demo we can keep only index and store.
    // Actually, we need show, update, destroy for review? Not for sources. We'll keep them for completeness.
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
    // Custom routes for processing and confirming
    Route::post('/receipts/{receipt}/process', [ReceiptController::class, 'process']);
    Route::post('/receipts/{receipt}/confirm', [ReceiptController::class, 'confirm']);

    // Dashboard
    Route::get('/dashboard', DashboardController::class);
});

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);