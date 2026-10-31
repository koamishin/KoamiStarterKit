<?php

namespace App\Http\Middleware;

use App\Features\FeatureRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        abort_unless(FeatureRegistry::isEnabledForUser($request->user(), $key), 404);

        return $next($request);
    }
}
