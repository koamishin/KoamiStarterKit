<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeveloperLoginController extends Controller
{
    /**
     * Quickly log in as a developer user in local environment.
     */
    public function login(Request $request): RedirectResponse
    {
        abort_unless(app()->environment('local'), 404);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->firstOrFail();

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
