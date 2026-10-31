<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BetaAccessRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Laravel\Pennant\Feature;

class BetaAccessController extends Controller
{
    public function update(BetaAccessRequest $betaAccessRequest): RedirectResponse
    {
        $user = $betaAccessRequest->user();
        $user->forceFill([
            'beta_opted_in_at' => $betaAccessRequest->boolean('enabled') ? ($user->beta_opted_in_at ?? now()) : null,
        ])->save();
        Feature::flushCache();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Beta access preference updated.']);

        return back();
    }
}
