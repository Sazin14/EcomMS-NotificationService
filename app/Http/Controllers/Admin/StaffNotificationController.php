<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReadsIdentity;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Staff bell icon. A staff alert is ONE row shown to everybody holding the permission in `audience`
 * (e.g. inventory.view); "read" is tracked per staff member in notification_reads.
 */
class StaffNotificationController extends Controller
{
    use ReadsIdentity;

    private function visible(Request $request)
    {
        return Notification::query()
            ->where('recipient_type', Notification::STAFF)
            ->where('in_app', true)
            ->whereIn('audience', $this->permissions($request) ?: ['__none__']);
    }

    private function unreadOf(Request $request)
    {
        $staffId = $this->staffId($request);

        return $this->visible($request)->whereNotExists(function ($q) use ($staffId) {
            $q->selectRaw('1')->from('notification_reads')
                ->whereColumn('notification_reads.notification_id', 'notifications.id')
                ->where('notification_reads.staff_id', $staffId);
        });
    }

    // GET /api/admin/notifications?unread=1
    public function index(Request $request): JsonResponse
    {
        $staffId = $this->staffId($request);
        $query = ($request->boolean('unread') ? $this->unreadOf($request) : $this->visible($request))->orderByDesc('id');

        $page = $query->paginate(min((int) $request->query('per_page', 20), 100));

        $readIds = NotificationRead::where('staff_id', $staffId)
            ->whereIn('notification_id', $page->pluck('id'))->pluck('notification_id')->all();

        $page->getCollection()->transform(function ($n) use ($readIds) {
            $n->setAttribute('is_read', in_array($n->id, $readIds, true));

            return $n;
        });

        return response()->json($page);
    }

    // GET /api/admin/notifications/unread-count
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['unread' => $this->unreadOf($request)->count()]);
    }

    // POST /api/admin/notifications/{id}/read
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = $this->visible($request)->findOrFail($id);

        NotificationRead::firstOrCreate(
            ['notification_id' => $notification->id, 'staff_id' => $this->staffId($request)],
            ['read_at' => now()],
        );

        return response()->json(['message' => 'Marked as read.']);
    }

    // POST /api/admin/notifications/read-all
    public function readAll(Request $request): JsonResponse
    {
        $staffId = $this->staffId($request);
        $ids = $this->unreadOf($request)->pluck('notifications.id');

        NotificationRead::insertOrIgnore($ids->map(fn ($id) => [
            'notification_id' => $id, 'staff_id' => $staffId, 'read_at' => now(),
        ])->all());

        return response()->json(['marked' => $ids->count()]);
    }
}
