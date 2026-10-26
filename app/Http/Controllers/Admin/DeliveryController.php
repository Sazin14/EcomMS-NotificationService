<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailDelivery;
use App\Models\NotificationDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** "Did the customer get the e-mail?" and "send the failed ones again". */
class DeliveryController extends Controller
{
    // GET /api/admin/notification-deliveries?status=failed&order_number=ORD-...
    public function index(Request $request): JsonResponse
    {
        $query = NotificationDelivery::query()->with('notification:id,event_type,recipient_type,recipient_id,data')->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('order_number')) {
            $query->whereHas('notification', fn ($q) => $q->where('data->order_number', $request->query('order_number')));
        }

        return response()->json($query->paginate(min((int) $request->query('per_page', 20), 100)));
    }

    // POST /api/admin/notification-deliveries/{id}/retry   (only failed ones)
    public function retry(int $id): JsonResponse
    {
        $delivery = NotificationDelivery::findOrFail($id);

        abort_unless($delivery->status === NotificationDelivery::FAILED, 422, 'Only failed deliveries can be retried.');

        $delivery->update(['status' => NotificationDelivery::PENDING, 'last_error' => null]);
        SendEmailDelivery::dispatch($delivery->id);

        return response()->json(['message' => 'Queued again.', 'data' => $delivery->fresh()]);
    }
}
