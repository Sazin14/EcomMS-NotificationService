<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ReadsIdentity;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "Do not e-mail me." Only the e-mail channel can be switched off, and only for non-critical events
 * (payment, cancellation, refund ... are always sent). In-app notifications are not switchable.
 */
class PreferenceController extends Controller
{
    use ReadsIdentity;

    // GET /api/notification-preferences
    public function show(Request $request): JsonResponse
    {
        $enabled = NotificationPreference::where('customer_id', $this->customerId($request))
            ->where('channel', 'email')->where('category', 'transactional')->value('enabled');

        return response()->json(['data' => ['email' => $enabled ?? true]]);
    }

    // PUT /api/notification-preferences   { "email": false }
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'boolean']]);

        NotificationPreference::updateOrCreate(
            ['customer_id' => $this->customerId($request), 'channel' => 'email', 'category' => 'transactional'],
            ['enabled' => (bool) $data['email']],
        );

        return response()->json(['data' => ['email' => (bool) $data['email']]]);
    }
}
