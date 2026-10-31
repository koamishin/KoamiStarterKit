<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UserOverviewWidget;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-user-plus'),
        ];
    }

    #[\Override]
    protected function getHeaderWidgets(): array
    {
        return [
            UserOverviewWidget::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    #[\Override]
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users')
                ->badge(User::query()->count()),

            'super_admins' => Tab::make('Super Admins')
                ->icon('heroicon-m-key')
                ->badge(User::query()->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))),

            'verified' => Tab::make('Verified')
                ->icon('heroicon-m-check-circle')
                ->badge(User::query()->whereNotNull('email_verified_at')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

            'unverified' => Tab::make('Unverified')
                ->icon('heroicon-m-x-circle')
                ->badge(User::query()->whereNull('email_verified_at')->count())
                ->badgeColor(User::query()->whereNull('email_verified_at')->count() > 0 ? 'danger' : 'gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('email_verified_at')),

            'mfa' => Tab::make('MFA Protected')
                ->icon('heroicon-m-shield-check')
                ->badge(User::query()->where(function ($q): void {
                    $q->whereNotNull('app_authentication_secret')
                        ->orWhere('has_email_authentication', true)
                        ->orWhereNotNull('two_factor_secret')
                        ->orWhereHas('passkeys');
                })->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q): void {
                    $q->whereNotNull('app_authentication_secret')
                        ->orWhere('has_email_authentication', true)
                        ->orWhereNotNull('two_factor_secret')
                        ->orWhereHas('passkeys');
                })),
        ];
    }
}
