<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SocialLoginProvider;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => SocialLoginProvider::Github->value,
            'provider_id' => (string) fake()->unique()->numberBetween(100000, 9999999),
            'nickname' => fake()->userName(),
            'avatar_url' => fake()->imageUrl(),
            'access_token' => 'fake-access-token',
            'refresh_token' => null,
            'expires_at' => null,
        ];
    }
}
