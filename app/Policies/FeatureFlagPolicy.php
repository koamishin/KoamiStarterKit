<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FeatureFlag;
use App\Models\User;

class FeatureFlagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, FeatureFlag $featureFlag): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FeatureFlag $featureFlag): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, FeatureFlag $featureFlag): bool
    {
        return false;
    }
}
