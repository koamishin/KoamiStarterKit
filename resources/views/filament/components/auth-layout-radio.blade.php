@php
    use Filament\Support\Enums\GridDirection;
    use Illuminate\View\ComponentAttributeBag;

    $fieldWrapperView = $getFieldWrapperView();
    $gridDirection = $getGridDirection() ?? GridDirection::Row;
    $id = $getId();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
    $extraInputAttributeBag = $getExtraInputAttributeBag();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div 
        x-data="{ selected: '{{ $getState() }}' }"
        x-init="$watch('selected', value => $wire.set('{{ $statePath }}', value))"
        class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"
    >
        @foreach ($getLayoutOptions() as $value => $option)
            @php
                $inputId = "{$id}-{$value}";
                $shouldOptionBeDisabled = $isDisabled || $isOptionDisabled($value, $option['label']);
                $isSelected = $getState() === $value;
            @endphp

            <label
                for="{{ $inputId }}"
                x-bind:class="selected === '{{ $value }}' 
                    ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/50 ring-2 ring-primary-500/20' 
                    : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-gray-600'"
                class="fi-fo-radio-label group relative flex flex-1 flex-col overflow-hidden rounded-xl border-2 transition-all duration-200 cursor-pointer"
            >
                <input
                    @disabled($shouldOptionBeDisabled)
                    id="{{ $inputId }}"
                    name="{{ $id }}"
                    type="radio"
                    value="{{ $value }}"
                    {{ $wireModelAttribute }}="{{ $statePath }}"
                    @checked($isSelected)
                    x-model="selected"
                    {{
                        $extraInputAttributeBag->class([
                            'fi-radio-input sr-only',
                            'fi-valid' => ! $errors->has($statePath),
                            'fi-invalid' => $errors->has($statePath),
                        ])
                    }}
                />

                <div class="relative h-28 bg-gray-50 dark:bg-gray-800/50 p-3 border-b border-gray-100 dark:border-gray-700 pointer-events-none">
                    @if ($option['preview']['type'] === 'simple')
                        <div class="flex h-full flex-col items-center justify-center gap-2">
                            <div class="h-4 w-4 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                            <div class="space-y-1 text-center">
                                <div class="h-2 w-16 rounded bg-gray-300 dark:bg-gray-600"></div>
                                <div class="h-1.5 w-20 rounded bg-gray-200 dark:bg-gray-700"></div>
                            </div>
                            <div class="mt-1 h-10 w-20 rounded-lg border border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-700 shadow-sm"></div>
                        </div>
                    @elseif ($option['preview']['type'] === 'card')
                        <div class="flex h-full items-center justify-center">
                            <div class="h-16 w-20 rounded-lg border border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-700 shadow-sm">
                                <div class="flex h-full flex-col items-center justify-center gap-1 p-2">
                                    <div class="h-2 w-10 rounded bg-gray-300 dark:bg-gray-600"></div>
                                    <div class="h-1.5 w-12 rounded bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="mt-1 h-5 w-14 rounded bg-gray-100 dark:bg-gray-600"></div>
                                </div>
                            </div>
                        </div>
                    @elseif ($option['preview']['type'] === 'split')
                        <div class="grid h-full grid-cols-2 gap-0 overflow-hidden rounded-lg">
                            <div class="flex items-center justify-center bg-gray-700 dark:bg-gray-900">
                                <div class="h-2 w-6 rounded bg-gray-500"></div>
                            </div>
                            <div class="flex items-center justify-center bg-gray-50 dark:bg-gray-800">
                                <div class="space-y-1 text-center">
                                    <div class="h-2 w-8 rounded bg-gray-300 dark:bg-gray-600"></div>
                                    <div class="h-1.5 w-10 rounded bg-gray-200 dark:bg-gray-700"></div>
                                    <div class="mt-1 h-4 w-12 rounded bg-gray-200 dark:bg-gray-700"></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div 
                        x-bind:class="selected === '{{ $value }}' 
                            ? 'bg-primary-500 text-white scale-100' 
                            : 'bg-gray-200 dark:bg-gray-700 text-transparent scale-90'"
                        class="absolute right-2 top-2 flex h-5 w-5 items-center justify-center rounded-full transition-all duration-200"
                    >
                        <svg 
                            x-bind:class="selected === '{{ $value }}' ? 'opacity-100' : 'opacity-0'" 
                            class="h-3 w-3 transition-opacity duration-200" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor" 
                            stroke-width="3"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <div class="fi-fo-radio-label-text p-3 pointer-events-none">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $option['label'] }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $option['description'] }}
                    </p>
                </div>
            </label>
        @endforeach
    </div>
</x-dynamic-component>
