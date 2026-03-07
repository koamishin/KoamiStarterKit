<?php

namespace App\Filament\Resources\FeatureFlag;

use App\Features\FeatureRegistry;
use App\Models\Role;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Feature Flags';

    protected static ?string $label = 'Feature Flags';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('features_count')
                    ->label('Active Features')
                    ->counts('features')
                    ->badge()
                    ->color('success'),
            ])
            ->recordActions([
                Action::make('configure')
                    ->label('Configure Features')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->slideOver()
                    ->form(function (Role $record): array {
                        FeatureRegistry::initialize();

                        $toggles = [];

                        foreach (FeatureRegistry::all() as $feature) {
                            $toggles[] = Toggle::make('feature_'.$feature->key)
                                ->label($feature->name)
                                ->hint($feature->description)
                                ->onColor('success')
                                ->offColor('gray');
                        }

                        return $toggles;
                    })
                    ->fillForm(function (Role $record): array {
                        FeatureRegistry::initialize();
                        $data = [];

                        foreach (FeatureRegistry::keys() as $key) {
                            $data['feature_'.$key] = FeatureRegistry::isEnabledForRole($record, $key);
                        }

                        return $data;
                    })
                    ->action(function (Role $record, array $data): void {
                        FeatureRegistry::initialize();

                        $enabled = [];
                        $disabled = [];

                        foreach (FeatureRegistry::keys() as $key) {
                            $isEnabled = $data['feature_'.$key] ?? false;
                            FeatureRegistry::toggleForRole($record, $key, $isEnabled);

                            if ($isEnabled) {
                                $enabled[] = FeatureRegistry::get($key)?->name;
                            } else {
                                $disabled[] = FeatureRegistry::get($key)?->name;
                            }
                        }

                        $body = [];
                        if (! empty($enabled)) {
                            $body[] = 'Enabled: '.implode(', ', $enabled);
                        }
                        if (! empty($disabled)) {
                            $body[] = 'Disabled: '.implode(', ', $disabled);
                        }

                        Notification::make()
                            ->success()
                            ->title('Features Updated')
                            ->body(implode(' | ', $body))
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('rollout')
                    ->label('Rollout to All Users')
                    ->icon('heroicon-o-globe-alt')
                    ->slideOver()
                    ->form(function (): array {
                        FeatureRegistry::initialize();

                        return [
                            Select::make('feature')
                                ->label('Feature')
                                ->options(FeatureRegistry::options())
                                ->required(),
                            Toggle::make('active')
                                ->label('Enable for All Users')
                                ->onColor('success')
                                ->offColor('danger'),
                        ];
                    })
                    ->action(function (array $data): void {
                        $feature = FeatureRegistry::get($data['feature']);
                        $active = $data['active'];

                        $count = FeatureRegistry::rolloutForAllUsers($data['feature'], $active);

                        Notification::make()
                            ->success()
                            ->title('Rollout Complete')
                            ->body(($active ? 'Enabled' : 'Disabled')." '{$feature->name}' for {$count} users.")
                            ->send();
                    }),
                Action::make('clear_overrides')
                    ->label('Clear All Overrides')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->form(function (): array {
                        FeatureRegistry::initialize();

                        return [
                            Select::make('feature')
                                ->label('Feature')
                                ->options(FeatureRegistry::options())
                                ->required(),
                        ];
                    })
                    ->action(function (array $data): void {
                        $feature = FeatureRegistry::get($data['feature']);
                        $count = FeatureRegistry::clearUserOverrides($data['feature']);

                        Notification::make()
                            ->success()
                            ->title('Overrides Cleared')
                            ->body("Cleared {$count} user overrides for '{$feature->name}'. Users will now use role-based settings.")
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\FeatureFlag\Pages\ListFeatureFlags::route('/'),
        ];
    }
}
