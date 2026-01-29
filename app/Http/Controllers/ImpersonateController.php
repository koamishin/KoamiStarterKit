<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ImpersonateController extends Controller
{
    public function takeRedirect(): RedirectResponse
    {
        if (auth()->user()->hasRole('admin')) {
            return redirect()->to('/admin');
        }

        return redirect()->to('/dashboard');
    }

    public function leaveRedirect(): RedirectResponse
    {
        if (auth()->user() && auth()->user()->hasRole('admin')) {
            return redirect()->to('/admin');
        }

        return redirect()->to('/');
    }
}
