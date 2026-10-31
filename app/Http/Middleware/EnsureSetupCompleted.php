<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\RoleEnums;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureSetupCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        if ($request->is('setup*', 'up', 'healthcheck', 'build/*', 'assets/*', 'livewire/*', 'developer-login*')) {
            return $next($request);
        }

        if (file_exists(storage_path('installed'))) {
            return $next($request);
        }

        try {
            $hasSuperAdmin = User::query()->whereHas('roles', fn ($q) => $q->where('name', RoleEnums::SUPER_ADMIN->value))->exists();

            if ($hasSuperAdmin) {
                @touch(storage_path('installed'));

                return $next($request);
            }
        } catch (Throwable) {
            // Database is uninitialized or table does not exist
        }

        return redirect()->route('setup');
    }
}
