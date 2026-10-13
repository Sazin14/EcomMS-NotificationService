<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationRecipientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationRecipient::query()->select([
            'id', 'recipient_type', 'external_id', 'name', 'email', 'phone', 'language', 'active', 'marketing_consent_at',
        ]);
        if ($request->filled('recipient_type')) {
            $query->where('recipient_type', $request->string('recipient_type')->toString());
        }
        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return response()->json($query->paginate(min(max($request->integer('per_page', 30), 1), 100)));
    }

    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipient_type' => ['nullable', 'in:customer,staff'],
            'marketing_only' => ['sometimes', 'boolean'],
            'active_only' => ['sometimes', 'boolean'],
        ]);
        $query = NotificationRecipient::query();
        if (! empty($data['recipient_type'])) $query->where('recipient_type', $data['recipient_type']);
        if (($data['active_only'] ?? true) === true) $query->where('active', true);
        if (($data['marketing_only'] ?? false) === true) $query->whereNotNull('marketing_consent_at');

        return response()->json(['estimated_recipients' => $query->count()]);
    }
}
