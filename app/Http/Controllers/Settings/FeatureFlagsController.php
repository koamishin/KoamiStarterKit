<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Features\FeatureEvaluator;
use App\Features\FeatureManifest;
use App\Features\FeatureRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\FeaturePreferenceRequest;
use App\Models\FeatureAssignment;
use App\Models\FeatureFlag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeatureFlagsController extends Controller
{
    public function edit(Request $request, FeatureEvaluator $featureEvaluator): Response
    {
        FeatureRegistry::initialize();
        $flags = FeatureFlag::query()->get()->keyBy('key');
        $keys = array_unique([...FeatureRegistry::keys(), ...$flags->keys()->all()]);
        $features = [];
        foreach ($keys as $key) {
            $flag = $flags->get($key);
            $controllable = $flag->user_controllable ?? in_array($key, ['email-notifications', 'marketing-emails', 'activity-tracking'], true);
            if (! $controllable) {
                continue;
            }
            $result = $featureEvaluator->evaluate($key, $request->user());
            $features[] = [
                'key' => $key,
                'name' => $flag->name ?? FeatureRegistry::get($key)?->get('name'),
                'description' => $flag->description ?? FeatureRegistry::get($key)?->get('description'),
                'value' => $result['active'],
                'available' => $result['available'],
                'stage' => $result['stage'],
                'reason' => $result['reason'],
            ];
        }

        return Inertia::render('settings/Features', [
            'features' => $features,
            'betaOptedIn' => $request->user()->beta_opted_in_at !== null,
        ]);
    }

    public function update(FeaturePreferenceRequest $featurePreferenceRequest, FeatureEvaluator $featureEvaluator): RedirectResponse
    {
        $data = $featurePreferenceRequest->validated();
        $flag = FeatureFlag::query()->where('key', $data['feature'])->first();
        $controllable = $flag->user_controllable ?? in_array($data['feature'], ['email-notifications', 'marketing-emails', 'activity-tracking'], true);
        abort_unless($controllable && $featureEvaluator->evaluate($data['feature'], $featurePreferenceRequest->user(), false)['available'], 403);
        $flag ??= FeatureManifest::ensure($data['feature']);
        FeatureAssignment::query()->updateOrCreate([
            'feature_flag_id' => $flag->id,
            'user_id' => $featurePreferenceRequest->user()->id,
            'kind' => 'preference',
        ], ['active' => $data['active']]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Feature preference updated.']);

        return back();
    }
}
