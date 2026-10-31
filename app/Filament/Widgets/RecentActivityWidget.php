<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Spatie\Activitylog\Models\Activity;

class RecentActivityWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    #[\Override]
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()->with(['causer'])->latest()
            )
            ->heading('Recent Security & System Events')
            ->description('Live audit trail of user activities, model changes, and system events')
            ->columns([
                TextColumn::make('causer.name')
                    ->label('Initiator')
                    ->default('System')
                    ->icon('heroicon-m-user')
                    ->searchable(),

                TextColumn::make('event')
                    ->label('Action')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'login' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Event'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(45)
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('Target Entity')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->since()
                    ->sortable(),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->headerActions([
                Action::make('viewAuditLogs')
                    ->label('Full Audit Stream')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(route('filament.admin.resources.activity-logs.index')),
            ])
            ->recordActions([
                Action::make('inspect')
                    ->label('Inspect')
                    ->icon('heroicon-m-eye')
                    ->modalHeading(fn (Activity $activity): string => "Activity #{$activity->id} Inspection")
                    ->modalDescription(fn (Activity $activity): string => "Logged at {$activity->created_at?->toDayDateTimeString()}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Action Description'),
                        TextEntry::make('causer.name')
                            ->label('Initiated By')
                            ->default('System'),
                        TextEntry::make('subject_type')
                            ->label('Subject Model')
                            ->formatStateUsing(fn (?string $state): string => $state ?: 'N/A'),
                        KeyValueEntry::make('properties.attributes')
                            ->label('New / Updated Attributes')
                            ->visible(fn (Activity $activity): bool => ! empty($activity->properties['attributes'] ?? null)),
                        KeyValueEntry::make('properties.old')
                            ->label('Previous Attributes')
                            ->visible(fn (Activity $activity): bool => ! empty($activity->properties['old'] ?? null)),
                    ]),
            ]);
    }
}
