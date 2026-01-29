<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function takeRedirect(): RedirectResponse
    {
        if (($user = Auth::user()) instanceof User && $user->hasRole('admin')) {
            return redirect()->to('/admin');
        }

        return redirect()->to('/dashboard');
    }

    public function leaveRedirect(): RedirectResponse
    {
        if (($user = Auth::user()) instanceof User && $user->hasRole('admin')) {
            return redirect()->to('/admin');
        }

        return redirect()->to('/');
    }
}
