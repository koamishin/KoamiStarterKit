<?php

namespace App\Filament\Resources\FeatureFlag;

use App\Enums\FeatureStage;
use App\Features\FeatureEvaluator;
use App\Features\FeatureManifest;
use App\Features\FeatureRegistry;
use App\Filament\Resources\FeatureFlag\Pages\ListFeatureFlags;
use App\Models\FeatureFlag;
use App\Models\RoleFeature;
use App\Models\User;
use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexDateTimePicker;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Pennant\Feature;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationLabel = 'Feature Management';

    protected static ?int $navigationSort = 10;

    #[\Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Definition')->description('Ship code once. Control who receives it independently.')
                ->schema([
                    TextInput::make('key')->required()->maxLength(100)->regex('/^[a-z][a-z0-9_.-]*$/')->unique(ignoreRecord: true)
                        ->disabled(fn (?FeatureFlag $featureFlag): bool => $featureFlag instanceof FeatureFlag)->dehydrated()
                        ->helperText('Permanent key used by backend checks and frontend flags.'),
                    FlexTextInput::make('name')->required()->maxLength(255),
                    FlexTextareaField::make('description')->maxLength(5000)->columnSpanFull(),
                    FlexTextInput::make('owner')->maxLength(255),
                    TagsInput::make('tags'),
                ])->columns(2)->columnSpanFull(),
            Section::make('Release & rollout')->schema([
                SelectField::make('stage')->options(FeatureStage::options())->default('draft')->required(),
                FlexTextInput::make('release_version')->maxLength(80)->helperText('Promote matching beta flags with features:release <version>.'),
                Toggle::make('enabled')->default(false)->helperText('Global kill switch. Off always denies access.'),
                Toggle::make('default_value')->default(false)->label('Active by default')->helperText('Enable for eligible users without a personal preference.'),
                Toggle::make('user_controllable')->default(false)->label('Allow user preferences')->helperText('Expose in profile feature settings; never allows bypassing audience restrictions.'),
                FlexSlider::make('rollout_percentage')->minValue(0)->maxValue(100)->step(5)->default(100)->required()
                    ->helperText('Deterministic user cohorts. Increasing rollout keeps existing recipients.'),
                TagsInput::make('environments')->helperText('Empty means all environments. Values match APP_ENV, e.g. production.'),
                SelectField::make('target_roles')->multiple()->options(fn (): array => Role::query()->pluck('name', 'name')->all())
                    ->helperText('Empty means all roles; explicit legacy role denies still apply.'),
                FlexDateTimePicker::make('starts_at'),
                FlexDateTimePicker::make('ends_at')->after('starts_at'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Targeting rules')->description('All rules must match. Rules only use explicitly supported attributes.')
                ->schema([
                    Repeater::make('rules')->schema([
                        Select::make('attribute')->options([
                            'email_domain' => 'Email domain', 'user_id' => 'User ID', 'verified' => 'Email verified',
                        ])->required(),
                        TagsInput::make('values')->required()->helperText('Exact matches; verified accepts true or false. Email domains must be lowercase.'),
                    ])->defaultItems(0)->maxItems(20)->columns(2),
                ])->columnSpanFull(),
        ]);
    }

    #[\Override]
    public static function table(Table $table): Table
    {
        return $table->defaultSort('updated_at', 'desc')->columns([
            TextColumn::make('name')->searchable()->sortable()->description(fn (FeatureFlag $featureFlag): string => $featureFlag->key),
            TextColumn::make('stage')->badge()->color(fn (FeatureStage|string|null $state): string => match ($state instanceof FeatureStage ? $state : FeatureStage::tryFrom((string) $state)) {
                FeatureStage::Stable => 'success', FeatureStage::Beta => 'warning', default => 'gray',
            }),
            IconColumn::make('enabled')->boolean()->label('Live'),
            IconColumn::make('default_value')->boolean()->label('Default on'),
            TextColumn::make('rollout_percentage')->suffix('%')->label('Rollout')->sortable(),
            TextColumn::make('release_version')->placeholder('Unassigned')->searchable(),
            TextColumn::make('owner')->searchable()->toggleable(),
            TextColumn::make('assignments_count')->counts('assignments')->label('User rules')->toggleable(),
            TextColumn::make('updated_at')->since()->sortable(),
        ])->filters([
            SelectFilter::make('stage')->options(FeatureStage::options()),
        ])->recordActions([
            EditAction::make()->mutateDataUsing(function (array $data, FeatureFlag $featureFlag): array {
                Gate::authorize('update', $featureFlag);
                $data['key'] = $featureFlag->key;

                return Validator::make($data, FeatureManifest::rules())->validate();
            }),
            ActionGroup::make([
                Action::make('promote')->label('Promote to stable')->icon('heroicon-o-rocket-launch')
                    ->visible(fn (FeatureFlag $featureFlag): bool => $featureFlag->stage === FeatureStage::Beta)
                    ->authorize('update')->requiresConfirmation()
                    ->modalDescription('Removes the beta opt-in requirement. Kill switch, audience, schedule, and rollout remain unchanged.')
                    ->action(function (FeatureFlag $featureFlag): void {
                        Gate::authorize('update', $featureFlag);
                        $featureFlag->update(['stage' => FeatureStage::Stable]);
                    }),
                Action::make('kill')->label('Pause / resume')->icon('heroicon-o-pause')->authorize('update')->requiresConfirmation()
                    ->action(function (FeatureFlag $featureFlag): void {
                        Gate::authorize('update', $featureFlag);
                        $featureFlag->update(['enabled' => ! $featureFlag->enabled]);
                    }),
                Action::make('override')->label('User override')->authorize('update')->schema([
                    self::userSelect(),
                    Select::make('decision')->options(['on' => 'Force on (within safety gates)', 'off' => 'Force off', 'inherit' => 'Remove override'])->required(),
                ])->action(function (FeatureFlag $featureFlag, array $data): void {
                    Gate::authorize('update', $featureFlag);
                    $user = User::query()->findOrFail($data['user_id']);
                    $builder = $featureFlag->assignments()->where('user_id', $user->id)->where('kind', 'override');
                    if ($data['decision'] === 'inherit') {
                        $builder->get()->each->delete();
                    } else {
                        $featureFlag->assignments()->updateOrCreate(['user_id' => $user->id, 'kind' => 'override'], ['active' => $data['decision'] === 'on']);
                    }
                }),
                Action::make('role')->label('Role override')->authorize('update')->schema([
                    Select::make('role_id')->options(fn (): array => Role::query()->pluck('name', 'id')->all())->required(),
                    Select::make('decision')->options(['on' => 'Enable', 'off' => 'Deny', 'inherit' => 'Inherit rollout'])->required(),
                ])->action(function (FeatureFlag $featureFlag, array $data): void {
                    Gate::authorize('update', $featureFlag);
                    $role = Role::query()->findOrFail($data['role_id']);
                    if ($data['decision'] === 'inherit') {
                        RoleFeature::query()->where('role_id', $role->id)->where('feature', $featureFlag->key)->get()->each->delete();
                    } else {
                        FeatureRegistry::toggleForRole($role, $featureFlag->key, $data['decision'] === 'on');
                    }
                    Feature::flushCache();
                }),
                Action::make('preview')->label('Explain access')->authorize('view')->schema([self::userSelect()])
                    ->action(function (FeatureFlag $featureFlag, array $data): void {
                        Gate::authorize('view', $featureFlag);
                        $result = app(FeatureEvaluator::class)->evaluate($featureFlag->key, User::query()->findOrFail($data['user_id']));
                        Notification::make()->title($result['active'] ? 'Feature active' : 'Feature inactive')
                            ->body('Decision: '.$result['reason'].' · Stage: '.$result['stage'])->info()->send();
                    }),
                Action::make('history')->label('Audit history')->authorize('view')->modalSubmitAction(false)
                    ->modalContent(function (FeatureFlag $featureFlag): Factory|View {
                        Gate::authorize('view', $featureFlag);

                        return view('filament.features.history', [
                            'changes' => Activity::query()->where('log_name', 'feature-flags')->where(function ($query) use ($featureFlag): void {
                                $query->where(fn ($query) => $query->where('subject_type', $featureFlag->getMorphClass())->where('subject_id', $featureFlag->id))
                                    ->orWhere('properties->feature_key', $featureFlag->key);
                            })->with('causer')->latest()->limit(50)->get(),
                        ]);
                    }),
                Action::make('rollback')->label('Restore previous configuration')->authorize('update')->requiresConfirmation()
                    ->schema([
                        Select::make('revision')->label('Recorded revision')->required()->options(fn (FeatureFlag $featureFlag): array => Activity::query()->where('subject_type', $featureFlag->getMorphClass())->where('subject_id', $featureFlag->id)
                            ->where('event', 'updated')->get()->sortByDesc('id')->take(50)
                            ->mapWithKeys(fn ($activity): array => [$activity->getKey() => ($activity->created_at?->toDateTimeString() ?? '').' · Before change #'.$activity->getKey()])->all()),
                    ])->action(function (FeatureFlag $featureFlag, array $data): void {
                        Gate::authorize('update', $featureFlag);
                        $model = Activity::query()->where('subject_type', $featureFlag->getMorphClass())->where('subject_id', $featureFlag->id)
                            ->where('event', 'updated')->where('id', $data['revision'])->firstOrFail();
                        $properties = $model instanceof Activity ? $model->properties : null;
                        $old = $properties instanceof Collection ? $properties->get('old', []) : [];
                        $previous = array_intersect_key(is_array($old) ? $old : [], array_flip($featureFlag->getFillable()));
                        unset($previous['key']);
                        $featureFlag->fill($previous)->save();
                        Notification::make()->success()->title('Previous configuration restored')->send();
                    }),
                Action::make('archive')->authorize('update')->requiresConfirmation()->action(function (FeatureFlag $featureFlag): void {
                    Gate::authorize('update', $featureFlag);
                    $featureFlag->update(['stage' => FeatureStage::Archived, 'enabled' => false]);
                }),
            ])->label('Manage')->icon('heroicon-o-ellipsis-horizontal')->button(),
        ])->headerActions([
            CreateAction::make()->mutateDataUsing(function (array $data): array {
                Gate::authorize('create', FeatureFlag::class);

                return Validator::make($data, FeatureManifest::rules())->validate();
            }),
            Action::make('sync')->label('Sync code definitions')->authorize(fn (): bool => Gate::allows('create', FeatureFlag::class))
                ->action(function (): void {
                    Gate::authorize('create', FeatureFlag::class);
                    FeatureManifest::sync();
                }),
            Action::make('export')->label('Export manifest')->authorize(fn (): bool => Gate::allows('viewAny', FeatureFlag::class))
                ->action(function () {
                    Gate::authorize('viewAny', FeatureFlag::class);

                    return response()->streamDownload(fn (): int => print (json_encode(FeatureManifest::export(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)), 'features.json');
                }),
            Action::make('import')->label('Import manifest')->authorize(fn (): bool => Gate::allows('create', FeatureFlag::class))
                ->schema([Textarea::make('manifest')->required()->rows(12), Toggle::make('overwrite')->default(false)])
                ->action(function (array $data): void {
                    Gate::authorize('create', FeatureFlag::class);
                    $manifest = json_decode($data['manifest'], true);
                    if (! is_array($manifest)) {
                        throw ValidationException::withMessages(['manifest' => 'Provide a valid JSON manifest.']);
                    }
                    $count = FeatureManifest::import($manifest, (bool) $data['overwrite']);
                    Notification::make()->success()->title("Imported {$count} feature definitions")->send();
                }),
            Action::make('release')->label('Publish release')->authorize(fn (): bool => Gate::allows('create', FeatureFlag::class))
                ->requiresConfirmation()->schema([TextInput::make('version')->required()->maxLength(80)])
                ->action(function (array $data): void {
                    Gate::authorize('create', FeatureFlag::class);
                    $count = FeatureManifest::release($data['version']);
                    Notification::make()->success()->title("Promoted {$count} beta features to stable")->send();
                }),
        ]);
    }

    private static function userSelect(): Select
    {
        return Select::make('user_id')->label('User')->searchable()->required()
            ->getSearchResultsUsing(fn (string $search): array => User::query()->where('email', 'like', '%'.$search.'%')->limit(30)->pluck('email', 'id')->all())
            ->getOptionLabelUsing(fn ($value): ?string => User::query()->find($value)?->email);
    }

    #[\Override]
    public static function getPages(): array
    {
        return ['index' => ListFeatureFlags::route('/')];
    }
}
