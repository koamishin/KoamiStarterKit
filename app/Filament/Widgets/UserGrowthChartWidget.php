<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserGrowthChartWidget extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'User Registrations & Growth';

    protected ?string $description = 'Historical account creations on this instance';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    protected static ?int $sort = 4;

    public ?string $filter = '30days';

    protected ?string $maxHeight = '275px';

    protected string $color = 'primary';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 Days',
            '30days' => 'Last 30 Days',
            '90days' => 'Last 90 Days',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    protected function getData(): array
    {
        $days = match ($this->filter) {
            '7days' => 7,
            '90days' => 90,
            default => 30,
        };

        $labels = [];
        $userData = [];

        // For 90 days, group by weeks to keep chart clean; otherwise daily
        if ($days === 90) {
            for ($i = 12; $i >= 0; $i--) {
                $startOfWeek = Carbon::today()->subWeeks($i)->startOfWeek();
                $endOfWeek = Carbon::today()->subWeeks($i)->endOfWeek();
                $labels[] = $startOfWeek->format('M d');
                $userData[] = User::query()->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
            }
        } else {
            $startDate = Carbon::today()->subDays($days - 1)->startOfDay();
            $driver = DB::getDriverName();
            $dateExpr = match ($driver) {
                'sqlite' => "strftime('%Y-%m-%d', created_at)",
                'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
                default => 'DATE(created_at)',
            };

            $dailyCounts = User::query()
                ->where('created_at', '>=', $startDate)
                ->selectRaw("{$dateExpr} as date_str, count(*) as count")
                ->groupBy('date_str')
                ->pluck('count', 'date_str')
                ->all();

            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $labels[] = $date->format($days === 7 ? 'D M d' : 'M d');
                $dateKey = $date->format('Y-m-d');
                $userData[] = (int) ($dailyCounts[$dateKey] ?? 0);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => $userData,
                    'fill' => 'start',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
