<?php

namespace App\Http\Controllers\Settings;

use App\Features\FeatureRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeatureFlagsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $featureManager = app(\Laravel\Pennant\FeatureManager::class);

        FeatureRegistry::initialize();

        $features = [];

        foreach (FeatureRegistry::all() as $feature) {
            $features[] = [
                'key' => $feature->key,
                'name' => $feature->name,
                'description' => $feature->description,
                'value' => $featureManager->value($feature->key, $user) === true,
            ];
        }

        return Inertia::render('settings/Features', [
            'features' => $features,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'feature' => 'required|string',
            'active' => 'required|boolean',
        ]);

        $user = $request->user();
        $featureManager = app(\Laravel\Pennant\FeatureManager::class);

        if ($validated['active']) {
            $featureManager->activateFor($user, $validated['feature']);
        } else {
            $featureManager->deactivateFor($user, $validated['feature']);
        }

        return response()->json(['success' => true]);
    }
}
