<?php

use App\Http\Controllers\Api\V1\CustomerNotificationController;
use App\Http\Controllers\Api\V1\NotificationCampaignController;
use App\Http\Controllers\Api\V1\NotificationDeliveryController;
use App\Http\Controllers\Api\V1\NotificationPreferenceController;
use App\Http\Controllers\Api\V1\NotificationProviderWebhookController;
use App\Http\Controllers\Api\V1\NotificationRecipientController;
use App\Http\Controllers\Api\V1\NotificationTemplateController;
use App\Http\Controllers\Api\V1\StaffNotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('auth:customer')->prefix('customer')->name('customer.')->group(function () {
        Route::get('/notifications', [CustomerNotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [CustomerNotificationController::class, 'unreadCount']);
        Route::patch('/notifications/{notification}/read', [CustomerNotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [CustomerNotificationController::class, 'markAllRead']);
        Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index'])->name('preferences.index');
        Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('preferences.update');
    });

    Route::middleware('auth:staff')->prefix('staff')->name('staff.')->group(function () {
        Route::get('/notifications', [StaffNotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [StaffNotificationController::class, 'unreadCount']);
        Route::patch('/notifications/{notification}/read', [StaffNotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [StaffNotificationController::class, 'markAllRead']);
        Route::get('/notification-preferences', [NotificationPreferenceController::class, 'index'])->name('preferences.index');
        Route::put('/notification-preferences', [NotificationPreferenceController::class, 'update'])->name('preferences.update');
    });

    Route::middleware(['auth:staff', 'permission:notification.template.read'])->prefix('admin/notification-templates')->group(function () {
        Route::get('/', [NotificationTemplateController::class, 'index']);
        Route::get('/{template}', [NotificationTemplateController::class, 'show']);
    });
    Route::middleware(['auth:staff', 'permission:notification.template.create'])->post('/admin/notification-templates', [NotificationTemplateController::class, 'store']);
    Route::middleware(['auth:staff', 'permission:notification.template.update'])->put('/admin/notification-templates/{template}', [NotificationTemplateController::class, 'update']);

    Route::middleware(['auth:staff', 'permission:campaign.read'])->group(function () {
        Route::get('/admin/notification-campaigns', [NotificationCampaignController::class, 'index']);
        Route::get('/admin/notification-campaigns/{campaign}', [NotificationCampaignController::class, 'show']);
        Route::post('/admin/notification-campaigns/preview-audience', [NotificationCampaignController::class, 'previewAudience']);
        Route::get('/admin/notification-recipients', [NotificationRecipientController::class, 'index']);
        Route::post('/admin/notification-recipients/preview', [NotificationRecipientController::class, 'preview']);
        Route::get('/admin/notification-deliveries', [NotificationDeliveryController::class, 'index']);
        Route::get('/admin/notification-deliveries/{delivery}', [NotificationDeliveryController::class, 'show']);
    });

    Route::middleware(['auth:staff', 'permission:campaign.create'])->post('/admin/notification-campaigns', [NotificationCampaignController::class, 'store']);
    Route::middleware(['auth:staff', 'permission:campaign.send'])->group(function () {
        Route::post('/admin/notification-campaigns/{campaign}/test-send', [NotificationCampaignController::class, 'testSend']);
        Route::post('/admin/notification-campaigns/{campaign}/schedule', [NotificationCampaignController::class, 'schedule']);
        Route::post('/admin/notification-campaigns/{campaign}/send', [NotificationCampaignController::class, 'send']);
    });
    Route::middleware(['auth:staff', 'permission:campaign.manage'])->group(function () {
        Route::post('/admin/notification-campaigns/{campaign}/pause', [NotificationCampaignController::class, 'pause']);
        Route::post('/admin/notification-campaigns/{campaign}/resume', [NotificationCampaignController::class, 'resume']);
        Route::post('/admin/notification-campaigns/{campaign}/cancel', [NotificationCampaignController::class, 'cancel']);
    });
    Route::middleware(['auth:staff', 'permission:notification.delivery.retry'])->post('/admin/notification-deliveries/{delivery}/retry', [NotificationDeliveryController::class, 'retry']);
});

// Provider-specific signature verification is required inside this endpoint/controller.
Route::post('/v1/webhooks/notifications/{provider}', [NotificationProviderWebhookController::class, 'handle']);
