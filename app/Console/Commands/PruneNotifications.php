<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\ProcessedMessage;
use Illuminate\Console\Command;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Delete old notifications (with their deliveries) and old processed-event markers';

    public function handle(): int
    {
        $days = (int) config('notifications.keep_days', 90);

        $notifications = Notification::where('created_at', '<', now()->subDays($days))->delete();   // deliveries / reads cascade
        $markers = ProcessedMessage::where('processed_at', '<', now()->subDays(30))->delete();

        $this->info("Deleted {$notifications} notification(s) and {$markers} processed-event marker(s).");

        return self::SUCCESS;
    }
}
