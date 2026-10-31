<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class UserOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '30s';

    #[\Override]
    protected function getStats(): array
    {
        $totalUsers = User::query()->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();
        $unverifiedUsers = $totalUsers - $verifiedUsers;

        $mfaUsers = User::query()->where(function ($q): void {
            $q->whereNotNull('app_authentication_secret')
                ->orWhere('has_email_authentication', true)
                ->orWhereNotNull('two_factor_secret')
                ->orWhereHas('passkeys');
        })->count();

        $adminUsers = User::query()->whereHas('roles', function ($q): void {
            $q->whereIn('name', ['super_admin', 'admin']);
        })->count();

        // 7-day registration trend
        $userTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $userTrend[] = (float) User::query()->whereDate('created_at', $day)->count();
        }

        $verifiedPercentage = $totalUsers > 0 ? (int) round(($verifiedUsers / $totalUsers) * 100) : 0;
        $mfaPercentage = $totalUsers > 0 ? (int) round(($mfaUsers / $totalUsers) * 100) : 0;

        return [
            Stat::make('Total Users', (string) $totalUsers)
                ->description("{$verifiedUsers} verified, {$unverifiedUsers} unverified")
                ->descriptionIcon('heroicon-m-user-group')
                ->chart($userTrend)
                ->color('primary'),

            Stat::make('Email Verified', "{$verifiedPercentage}%")
                ->description("{$verifiedUsers} of {$totalUsers} verified")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Security & MFA', "{$mfaPercentage}%")
                ->description("{$mfaUsers} accounts with 2FA / Passkeys")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($mfaPercentage > 0 ? 'info' : 'gray'),

            Stat::make('Staff & Admins', (string) $adminUsers)
                ->description('Privileged management roles')
                ->descriptionIcon('heroicon-m-key')
                ->color('warning'),
        ];
    }
}
