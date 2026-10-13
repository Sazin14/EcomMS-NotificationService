<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $identity = $request->user();
        abort_unless($identity, 401);

        return response()->json($this->notifications->paginateFor('customer', (int) $identity->getAuthIdentifier(), (int) $request->integer('per_page', 20)));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $identity = $request->user();
        abort_unless($identity, 401);

        return response()->json(['unread_count' => $this->notifications->unreadCount('customer', (int) $identity->getAuthIdentifier())]);
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $identity = $request->user();
        abort_unless($identity, 401);

        return response()->json(['data' => $this->notifications->markRead('customer', (int) $identity->getAuthIdentifier(), $notification)]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $identity = $request->user();
        abort_unless($identity, 401);

        return response()->json(['updated' => $this->notifications->markAllRead('customer', (int) $identity->getAuthIdentifier())]);
    }
}
