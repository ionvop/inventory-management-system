<?php
// routes/api.php
use App\Http\Controllers\Api\{
    AuthController, UserController, ItemController,
    TransactionController, DashboardController, ReportController, SettingController
};
use Illuminate\Support\Facades\Route;

Route::get('/auth/users', [AuthController::class, 'users']);
Route::post('/auth/users', [AuthController::class, 'store']);

Route::middleware('resolve.user')->group(function () {
    Route::apiResource('users', UserController::class);

    Route::get('items/low-stock', [ItemController::class, 'lowStock']);
    Route::apiResource('items', ItemController::class);
    Route::get('items/{item}/transactions', [TransactionController::class, 'byItem']);

    Route::apiResource('transactions', TransactionController::class)->except(['index'])
        ; // index below, to keep filters in one method
    Route::get('transactions', [TransactionController::class, 'index']);

    Route::get('dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('reports/inventory', [ReportController::class, 'inventory']);

    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);
});