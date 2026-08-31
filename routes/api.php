<?php
// routes/api.php
use App\Http\Controllers\Api\{
    UserController, ItemController,
    TransactionController, DashboardController, ReportController, SettingController
};
use Illuminate\Support\Facades\Route;

// User management lives on the account-picker screen, so it is public
// (users are just named profiles — there is no password auth).
Route::apiResource('users', UserController::class);

Route::middleware('resolve.user')->group(function () {
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