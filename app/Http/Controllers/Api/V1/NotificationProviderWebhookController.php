<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationProviderWebhookController extends Controller
{
    public function handle(Request $request, string $provider): JsonResponse
    {
        // TODO: verify the provider-specific signature before accepting the payload.
        // Then persist provider + provider_event_id under a unique constraint and process asynchronously.
        abort(501, 'Provider webhook adapter is not configured yet.');
    }
}
