<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public function features()
    {
        return $this->hasMany(RoleFeature::class, 'role_id');
    }
}
