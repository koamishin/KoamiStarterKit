<?php

namespace App\Http\Controllers\Settings;

use App\Features\FeatureRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Pennant\FeatureManager;

class FeatureFlagsController extends Controller
{
    public function edit(Request $request): Response
    {
        $request->user();
        $featureManager = app(FeatureManager::class);

        FeatureRegistry::initialize();

        $features = [];

        foreach (FeatureRegistry::all() as $feature) {
            $isAvailable = FeatureRegistry::isFeatureAvailableForUser($user, $feature->key);

            $features[] = [
                'key' => $feature->key,
                'name' => $feature->name,
                'description' => $feature->description,
                'value' => $featureManager->value($feature->key) === true,
                'available' => $isAvailable,
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

        if (! FeatureRegistry::isFeatureAvailableForUser($user, $validated['feature'])) {
            return response()->json(['success' => false, 'message' => 'Feature not available for your role'], 403);
        }

        $featureManager = app(FeatureManager::class);

        if ($validated['active']) {
            $featureManager->activateFor($user, $validated['feature']);
        } else {
            $featureManager->deactivateFor($user, $validated['feature']);
        }

        return response()->json(['success' => true]);
    }
}
