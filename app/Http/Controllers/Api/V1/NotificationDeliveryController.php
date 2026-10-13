<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationDeliveryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationDelivery::query()->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->string('channel')->toString());
        }

        return response()->json($query->paginate(min(max($request->integer('per_page', 30), 1), 100)));
    }

    public function show(NotificationDelivery $delivery): JsonResponse
    {
        return response()->json(['data' => $delivery]);
    }

    public function retry(NotificationDelivery $delivery): JsonResponse
    {
        abort_unless(in_array($delivery->status, ['failed'], true), 409, 'Only failed deliveries can be retried.');
        // TODO: dispatch the appropriate channel-specific job after suppression and rate-limit checks.
        return response()->json(['message' => 'Manual retry must be connected to the channel job dispatcher.'], 501);
    }
}
