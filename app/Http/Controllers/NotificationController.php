<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReadsIdentity;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The customer's bell icon: their own in-app notifications. */
class NotificationController extends Controller
{
    use ReadsIdentity;

    private function mine(Request $request)
    {
        return Notification::query()
            ->where('recipient_type', Notification::CUSTOMER)
            ->where('recipient_id', $this->customerId($request))
            ->where('in_app', true);
    }

    // GET /api/notifications?unread=1&per_page=20
    public function index(Request $request): JsonResponse
    {
        $query = $this->mine($request)->orderByDesc('id');

        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return response()->json($query->paginate(min((int) $request->query('per_page', 20), 100)));
    }

    // GET /api/notifications/unread-count
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['unread' => $this->mine($request)->whereNull('read_at')->count()]);
    }

    // POST /api/notifications/{id}/read
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = $this->mine($request)->findOrFail($id);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['data' => $notification]);
    }

    // POST /api/notifications/read-all
    public function readAll(Request $request): JsonResponse
    {
        $count = $this->mine($request)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['marked' => $count]);
    }
}
