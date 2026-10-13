<?php

namespace App\Services\Notifications;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function paginateFor(string $recipientType, int $recipientId, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->latest('created_at')
            ->paginate(min(max($perPage, 1), 100));
    }

    public function unreadCount(string $recipientType, int $recipientId): int
    {
        return Notification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(string $recipientType, int $recipientId, int $notificationId): Notification
    {
        return DB::transaction(function () use ($recipientType, $recipientId, $notificationId) {
            $notification = Notification::query()
                ->whereKey($notificationId)
                ->where('recipient_type', $recipientType)
                ->where('recipient_id', $recipientId)
                ->lockForUpdate()
                ->first();

            if (! $notification) {
                throw (new ModelNotFoundException())->setModel(Notification::class, [$notificationId]);
            }

            if (! $notification->read_at) {
                $notification->forceFill(['read_at' => now()])->save();
            }

            return $notification;
        });
    }

    public function markAllRead(string $recipientType, int $recipientId): int
    {
        return Notification::query()
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }
}
