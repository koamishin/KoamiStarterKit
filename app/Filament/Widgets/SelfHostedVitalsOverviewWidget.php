<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\FeatureFlag;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class SelfHostedVitalsOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected ?string $pollingInterval = '30s';

    #[\Override]
    protected function getStats(): array
    {
        $totalUsers = User::query()->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();

        // 7-day registration trend
        $startDate = Carbon::today()->subDays(6)->startOfDay();
        $driver = DB::getDriverName();
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => 'DATE(created_at)',
        };

        $userCounts = User::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw("{$dateExpr} as date_str, count(*) as count")
            ->groupBy('date_str')
            ->pluck('count', 'date_str')
            ->all();

        $userTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateKey = Carbon::today()->subDays($i)->format('Y-m-d');
            $userTrend[] = (float) ($userCounts[$dateKey] ?? 0);
        }

        // MFA Adoption
        $mfaUsers = User::query()->where(function ($q): void {
            $q->whereNotNull('app_authentication_secret')
                ->orWhere('has_email_authentication', true)
                ->orWhereNotNull('two_factor_secret')
                ->orWhereHas('passkeys');
        })->count();
        $mfaPercent = $totalUsers > 0 ? (int) round(($mfaUsers / $totalUsers) * 100) : 0;

        // Activity Stats
        $todayActivities = Activity::query()->whereDate('created_at', Carbon::today())->count();
        $totalActivities = Activity::query()->count();

        // Activity trend over last 7 days
        $activityCounts = Activity::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw("{$dateExpr} as date_str, count(*) as count")
            ->groupBy('date_str')
            ->pluck('count', 'date_str')
            ->all();

        $activityTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateKey = Carbon::today()->subDays($i)->format('Y-m-d');
            $activityTrend[] = (float) ($activityCounts[$dateKey] ?? 0);
        }

        // Feature flags
        $activeFlags = FeatureFlag::query()->where('enabled', true)->count();
        $totalFlags = FeatureFlag::query()->count();

        return [
            Stat::make('Total Users', (string) $totalUsers)
                ->description("{$verifiedUsers} verified accounts")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary')
                ->chart($userTrend)
                ->icon('heroicon-o-users')
                ->url(route('filament.admin.resources.users.index')),

            Stat::make('Security & MFA', "{$mfaPercent}%")
                ->description("{$mfaUsers} of {$totalUsers} with 2FA / Passkeys")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($mfaPercent > 50 ? 'success' : ($mfaPercent > 0 ? 'warning' : 'gray'))
                ->icon('heroicon-o-finger-print'),

            Stat::make('System Audit Events', (string) $totalActivities)
                ->description("{$todayActivities} events recorded today")
                ->descriptionIcon('heroicon-m-clock')
                ->color('info')
                ->chart($activityTrend)
                ->icon('heroicon-o-clipboard-document-list')
                ->url(route('filament.admin.resources.activity-logs.index')),

            Stat::make('Feature Flags', "{$activeFlags} / {$totalFlags}")
                ->description('Dynamic toggles active')
                ->descriptionIcon('heroicon-m-flag')
                ->color($activeFlags > 0 ? 'success' : 'gray')
                ->icon('heroicon-o-adjustments-horizontal')
                ->url(route('filament.admin.resources.feature-flag.feature-flags.index')),
        ];
    }
}
