<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(NotificationTemplate::query()->orderBy('key')->orderBy('channel')->paginate(50));
    }

    public function show(NotificationTemplate $template): JsonResponse
    {
        return response()->json(['data' => $template]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:120'],
            'channel' => ['required', Rule::in(['email', 'sms', 'push', 'in_app'])],
            'locale' => ['required', 'string', 'max:12'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'version' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $template = NotificationTemplate::query()->create($data);
        return response()->json(['data' => $template], 201);
    }

    public function update(Request $request, NotificationTemplate $template): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $template->fill($data)->save();
        return response()->json(['data' => $template->refresh()]);
    }
}
