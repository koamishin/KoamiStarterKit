<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Passkeys\Passkey;

/**
 * @extends Factory<Passkey>
 */
class PasskeyFactory extends Factory
{
    /**
     * @var class-string<Passkey>
     */
    protected $model = Passkey::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $credentialId = Str::random(40);

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['MacBook Touch ID', 'iPhone 15', 'Windows Hello', 'YubiKey 5']),
            'credential_id' => $credentialId,
            'credential' => [
                'id' => $credentialId,
                'aaguid' => Str::uuid()->toString(),
                'publicKey' => Str::random(64),
            ],
            'last_used_at' => null,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->getKey(),
        ]);
    }

    public function usedRecently(): static
    {
        return $this->state(fn (): array => [
            'last_used_at' => now()->subMinutes(5),
        ]);
    }
}
