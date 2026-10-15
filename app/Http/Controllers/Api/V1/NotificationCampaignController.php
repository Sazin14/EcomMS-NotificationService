<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationCampaignController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(NotificationCampaign::query()->latest()->paginate(30));
    }

    public function show(NotificationCampaign $campaign): JsonResponse
    {
        return response()->json(['data' => $campaign->loadCount('recipients')]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'channel' => ['required', Rule::in(['email', 'sms', 'push'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'audience' => ['required', 'array'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);
        abort_unless($request->user(), 401);
        $data['created_by_staff_id'] = (int) $request->user()->getAuthIdentifier();
        $data['status'] = 'draft';
        $campaign = NotificationCampaign::query()->create($data);

        return response()->json(['data' => $campaign], 201);
    }

    public function schedule(Request $request, NotificationCampaign $campaign): JsonResponse
    {
        abort_unless($request->user(), 401);
        abort_unless(in_array($campaign->status, ['draft', 'scheduled'], true), 409, 'Campaign cannot be scheduled in its current state.');
        $data = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']]);
        $campaign->forceFill(['scheduled_at' => $data['scheduled_at'], 'status' => 'scheduled'])->save();

        return response()->json(['data' => $campaign->refresh()]);
    }

    public function pause(Request $request, NotificationCampaign $campaign): JsonResponse
    {
        abort_unless($request->user(), 401);
        abort_unless($campaign->status === 'processing', 409, 'Only processing campaigns can be paused.');
        $campaign->forceFill(['status' => 'paused'])->save();
        return response()->json(['data' => $campaign->refresh()]);
    }

    public function resume(Request $request, NotificationCampaign $campaign): JsonResponse
    {
        abort_unless($request->user(), 401);
        abort_unless($campaign->status === 'paused', 409, 'Only paused campaigns can be resumed.');
        $campaign->forceFill(['status' => 'processing'])->save();
        return response()->json(['data' => $campaign->refresh()]);
    }

    public function cancel(Request $request, NotificationCampaign $campaign): JsonResponse
    {
        abort_unless($request->user(), 401);
        abort_unless(in_array($campaign->status, ['draft', 'scheduled', 'processing', 'paused'], true), 409, 'Campaign cannot be cancelled.');
        $campaign->forceFill(['status' => 'cancelled'])->save();
        return response()->json(['data' => $campaign->refresh()]);
    }

    public function previewAudience(Request $request): JsonResponse
    {
        // TODO: resolve filters against NotificationRecipient, then apply marketing consent
        // and suppression checks. Do not return full addresses to unauthorized staff.
        $request->validate(['audience' => ['required', 'array']]);
        return response()->json(['message' => 'Audience resolver must be implemented before sending.', 'estimated_recipients' => null], 501);
    }

    public function testSend(Request $request, NotificationCampaign $campaign): JsonResponse
    {
        // TODO: enqueue a test message to an explicitly validated test destination.
        $request->validate(['destination' => ['required', 'email']]);
        return response()->json(['message' => 'Test-send provider adapter is not configured yet.'], 501);
    }

    public function send(Request $request, NotificationCampaign $campaign): JsonResponse
    {
        // Sending is intentionally blocked until audience expansion, consent checks, suppression,
        // rate limiting, approval policy, and campaign queue dispatch are implemented.
        return response()->json(['message' => 'Campaign dispatch is not enabled until audience and consent safeguards are configured.'], 501);
    }
}
