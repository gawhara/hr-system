<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSyncToken
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('hr.sync.token');
        $provided = (string) $request->bearerToken();

        abort_if($expected === '', 503, 'Sync token is not configured on this node.');
        abort_unless($provided !== '' && hash_equals($expected, $provided), 401, 'Invalid sync token.');
        abort_unless(config('hr.sync.role') === 'central', 403, 'This node does not accept sync requests.');

        return $next($request);
    }
}
