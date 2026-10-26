<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Staff edit the wording of messages. Plain text with {{ placeholders }}; nothing is executed. */
class TemplateController extends Controller
{
    // GET /api/admin/notification-templates?key=order.placed
    public function index(Request $request): JsonResponse
    {
        $query = NotificationTemplate::query()->orderBy('key')->orderBy('channel')->orderBy('locale');

        if ($request->filled('key')) {
            $query->where('key', $request->query('key'));
        }

        return response()->json(['data' => $query->get()]);
    }

    // POST /api/admin/notification-templates
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'channel' => ['required', Rule::in(['in_app', 'email'])],
            'locale' => ['required', 'string', 'max:10'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        abort_if(
            NotificationTemplate::where(['key' => $data['key'], 'channel' => $data['channel'], 'locale' => $data['locale']])->exists(),
            422, 'A template for this key, channel and locale already exists. Update it instead.'
        );

        return response()->json(['data' => NotificationTemplate::create($data)], 201);
    }

    // PUT /api/admin/notification-templates/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        $template->update($request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]));

        return response()->json(['data' => $template]);
    }
}
