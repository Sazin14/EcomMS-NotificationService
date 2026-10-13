<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        [$type, $id] = $this->identity($request);

        return response()->json(NotificationPreference::query()
            ->where('recipient_type', $type)->where('recipient_id', $id)->orderBy('category')->get());
    }

    public function update(Request $request): JsonResponse
    {
        [$type, $id] = $this->identity($request);
        $validated = $request->validate([
            'preferences' => ['required', 'array', 'min:1', 'max:50'],
            'preferences.*.category' => ['required', 'string', 'max:80'],
            'preferences.*.channel' => ['required', Rule::in(['email', 'sms', 'push', 'in_app'])],
            'preferences.*.is_enabled' => ['required', 'boolean'],
        ]);

        foreach ($validated['preferences'] as $preference) {
            // Marketing consent is additionally checked by the suppression/consent service at send time.
            NotificationPreference::query()->updateOrCreate([
                'recipient_type' => $type,
                'recipient_id' => $id,
                'category' => $preference['category'],
                'channel' => $preference['channel'],
            ], ['is_enabled' => $preference['is_enabled']]);
        }

        return $this->index($request);
    }

    private function identity(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 401);
        $guard = $request->routeIs('api.v1.customer.preferences.*') ? 'customer' : 'staff';

        return [$guard, (int) $user->getAuthIdentifier()];
    }
}
