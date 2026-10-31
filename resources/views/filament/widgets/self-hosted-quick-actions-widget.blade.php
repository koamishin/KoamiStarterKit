<x-filament-widgets::widget class="fi-wi-self-hosted-quick-actions">
    <x-filament::section icon="heroicon-o-command-line" heading="Instance Operations" description="Self-hosted maintenance tools">
        <div class="flex flex-col gap-2">
            <x-filament::button
                color="gray"
                icon="heroicon-m-arrow-path"
                wire:click="clearCache"
                wire:loading.attr="disabled"
                class="w-full justify-start text-left"
            >
                Flush System Caches
            </x-filament::button>

            <x-filament::button
                color="gray"
                icon="heroicon-m-bolt"
                wire:click="optimizeApp"
                wire:loading.attr="disabled"
                class="w-full justify-start text-left"
            >
                Cache Routes & Config
            </x-filament::button>

            <x-filament::button
                color="gray"
                icon="heroicon-m-arrow-path-rounded-square"
                wire:click="restartQueue"
                wire:loading.attr="disabled"
                class="w-full justify-start text-left"
            >
                Restart Queue Workers
            </x-filament::button>

            <x-filament::button
                color="gray"
                icon="heroicon-m-circle-stack"
                wire:click="runMigrations"
                wire:confirm="Execute all pending migrations now?"
                wire:loading.attr="disabled"
                class="w-full justify-start text-left"
            >
                Run Database Migrations
            </x-filament::button>

            <x-filament::button
                :color="$isDown ? 'warning' : 'gray'"
                icon="heroicon-m-power"
                wire:click="toggleMaintenance"
                wire:confirm="{{ $isDown ? 'Deactivate maintenance mode?' : 'Activate maintenance mode? Non-admins will receive 503.' }}"
                wire:loading.attr="disabled"
                class="w-full justify-start text-left"
            >
                Maintenance Mode: {{ $isDown ? 'ACTIVE' : 'OFF' }}
            </x-filament::button>
        </div>

        <div class="mt-4 border-t border-gray-200 pt-3 dark:border-white/10">
            <div class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Quick Shortcuts
            </div>
            <div class="grid grid-cols-2 gap-2">
                @foreach($links as $link)
                    <x-filament::button
                        tag="a"
                        :href="$link['url']"
                        color="gray"
                        size="xs"
                        :icon="$link['icon']"
                        outlined="true"
                        class="w-full justify-start truncate text-left"
                    >
                        {{ $link['label'] }}
                    </x-filament::button>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
