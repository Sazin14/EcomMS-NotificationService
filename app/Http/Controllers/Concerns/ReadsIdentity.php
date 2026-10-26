<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/** Reads who is calling (set by the auth.jwt middleware). */
trait ReadsIdentity
{
    protected function customerId(Request $request): int
    {
        return (int) $request->attributes->get('user_id');
    }

    protected function staffId(Request $request): int
    {
        return (int) $request->attributes->get('user_id');
    }

    /** @return string[] permissions of the calling staff member (set by the authz middleware) */
    protected function permissions(Request $request): array
    {
        return (array) ($request->attributes->get('authorization')['permissions'] ?? []);
    }
}
