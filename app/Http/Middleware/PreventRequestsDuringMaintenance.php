<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as BaseMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreventRequestsDuringMaintenance extends BaseMiddleware
{
    /**
     * The URIs that should be accessible during maintenance.
     *
     * @var array<int, string>
     */
    protected $except = [
        'admin',
        'admin/*',
        'livewire/*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    #[\Override]
    public function handle($request, Closure $next)
    {
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        if ($this->app->maintenanceMode()->active()) {
            if ($this->hasAdminAccess($request)) {
                return $next($request);
            }
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine if the request is from an authenticated admin or super_admin.
     */
    protected function hasAdminAccess(Request $request): bool
    {
        // 1. Direct user on request (e.g. in tests with actingAs)
        $user = $request->user();

        if ($user) {
            if ($user->hasRole('super_admin')) {
                return true;
            }

            return (bool) $user->hasRole('admin');
        }

        // 2. Resolve from session cookie if present
        try {
            $session = $request->hasSession() ? $request->session() : null;

            if (! $session && $request->hasCookie(config('session.cookie'))) {
                $rawCookie = $request->cookies->get(config('session.cookie'));

                if (is_string($rawCookie)) {
                    $sessionId = $this->decryptSessionCookie($rawCookie);

                    if ($sessionId) {
                        $session = $this->app['session']->driver();
                        $session->setId($sessionId);
                        $session->start();
                    }
                }
            }

            if ($session) {
                $userId = $session->get(Auth::getName());

                if ($userId) {
                    $user = User::query()->find($userId);

                    return $user && ($user->hasRole('super_admin') || $user->hasRole('admin'));
                }
            }
        } catch (\Throwable) {
            // fallback
        }

        return false;
    }

    /**
     * Decrypt the session cookie value.
     */
    protected function decryptSessionCookie(string $cookie): ?string
    {
        try {
            $encrypter = $this->app['encrypter'];
            $decrypted = $encrypter->decrypt($cookie, false);

            if (is_string($decrypted)) {
                return CookieValuePrefix::validate(
                    config('session.cookie'),
                    $decrypted,
                    $encrypter->getAllKeys()
                );
            }
        } catch (DecryptException) {
            return null;
        }

        return null;
    }
}
