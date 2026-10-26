<?php

use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\StaffNotificationController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PreferenceController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'service' => 'notification-service']));

// ---- Customer ---------------------------------------------------------------------------------------------------
Route::middleware(['auth.jwt', 'customer'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');

    Route::get('/notification-preferences', [PreferenceController::class, 'show']);
    Route::put('/notification-preferences', [PreferenceController::class, 'update']);
});

// ---- Staff ------------------------------------------------------------------------------------------------------
Route::prefix('admin')->middleware(['auth.jwt', 'authz'])->group(function () {
    // The staff bell: no extra permission needed; each person sees only alerts for permissions they hold.
    Route::get('/notifications', [StaffNotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [StaffNotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [StaffNotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [StaffNotificationController::class, 'markRead'])->whereNumber('id');

    Route::middleware('permission:notification.view')->group(function () {
        Route::get('/notification-templates', [TemplateController::class, 'index']);
        Route::get('/notification-deliveries', [DeliveryController::class, 'index']);
    });

    Route::middleware('permission:notification.manage')->group(function () {
        Route::post('/notification-templates', [TemplateController::class, 'store']);
        Route::put('/notification-templates/{id}', [TemplateController::class, 'update'])->whereNumber('id');
        Route::post('/notification-deliveries/{id}/retry', [DeliveryController::class, 'retry'])->whereNumber('id');
    });
});
