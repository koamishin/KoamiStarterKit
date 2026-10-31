<x-filament-widgets::widget class="fi-wi-self-hosted-health">
    <x-filament::section icon="heroicon-o-server-stack">
        <x-slot name="heading">
            <div class="flex items-center gap-x-2">
                <span>Self-Hosted Node Telemetry</span>
                @if($isMaintenance)
                    <x-filament::badge color="warning" icon="heroicon-m-power">Maintenance Mode</x-filament::badge>
                @endif
            </div>
        </x-slot>

        <x-slot name="description">
            <span>Host: <strong class="text-gray-950 dark:text-white">{{ $hostname }}</strong></span>
            @if($uptimeFormatted)
                &bull; <span>Uptime: <strong class="text-gray-950 dark:text-white">{{ $uptimeFormatted }}</strong></span>
            @endif
            &bull; <span>Timezone: <strong class="text-gray-950 dark:text-white">{{ $timezone }}</strong></span>
        </x-slot>

        <x-slot name="afterHeader">
            <div class="flex flex-wrap items-center gap-2">
                <x-filament::badge :color="$isHealthy ? 'success' : 'warning'" :icon="$isHealthy ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle'">
                    {{ $isHealthy ? 'All Systems Operational' : 'Attention Recommended' }}
                </x-filament::badge>
                <x-filament::badge color="info">
                    PHP {{ $phpVersion }}
                </x-filament::badge>
                <x-filament::badge color="gray">
                    Laravel {{ $laravelVersion }}
                </x-filament::badge>
                @if($octaneServer)
                    <x-filament::badge color="warning">
                        Octane: {{ ucfirst($octaneServer) }}
                    </x-filament::badge>
                @endif
                <x-filament::badge :color="$environment === 'production' ? 'success' : 'info'">
                    {{ ucfirst($environment) }}
                </x-filament::badge>
                @if($isDebug)
                    <x-filament::badge :color="$environment === 'production' ? 'danger' : 'gray'">
                        Debug: On
                    </x-filament::badge>
                @endif
            </div>
        </x-slot>

        @if(count($warnings) > 0)
            <div class="mb-4">
                <x-filament::callout color="warning" icon="heroicon-m-exclamation-triangle" heading="Self-Hosted Environment Alerts">
                    <ul class="ms-4 mt-1 list-disc text-xs">
                        @foreach($warnings as $warn)
                            <li>{{ $warn }}</li>
                        @endforeach
                    </ul>
                </x-filament::callout>
            </div>
        @endif

        {{-- Resource Meters Grid --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-5">
            {{-- Memory Meter --}}
            <div class="relative rounded-xl bg-gray-50/50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-medium">Memory Usage</span>
                    <span class="font-mono text-gray-700 dark:text-gray-300">{{ $memUsageFormatted }}</span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $memUsageFormatted }}
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">/ {{ $memLimitFormatted }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Peak: {{ $memPeakFormatted }}</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">Optimal</span>
                </div>
            </div>

            {{-- Disk Storage Meter --}}
            <div class="relative rounded-xl bg-gray-50/50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-medium">Disk Storage</span>
                    <span class="font-mono text-gray-700 dark:text-gray-300">{{ $diskPercentage }}%</span>
                </div>
                <div class="mt-2 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                    {{ $usedDiskFormatted }}
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">/ {{ $totalDiskFormatted }}</span>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                    <div
                        class="h-full rounded-full transition-all duration-500 @if($diskPercentage > 85) bg-rose-500 dark:bg-rose-400 @elseif($diskPercentage > 70) bg-amber-500 dark:bg-amber-400 @else bg-emerald-500 dark:bg-emerald-400 @endif"
                        style="width: {{ min(100, $diskPercentage) }}%;"
                    ></div>
                </div>
                <div class="mt-1.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Free: {{ $freeDiskFormatted }}</span>
                    <span>{{ $diskPercentage }}% Used</span>
                </div>
            </div>

            {{-- Database Engine --}}
            <div class="relative rounded-xl bg-gray-50/50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-medium">Database Engine</span>
                    <span class="text-xs font-semibold {{ $dbStatus ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        &bull; {{ $dbStatus ? 'Connected' : 'Offline' }}
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold capitalize tracking-tight text-gray-950 dark:text-white">
                    {{ $dbDriver }}
                    @if($dbSizeFormatted)
                        <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $dbSizeFormatted }})</span>
                    @endif
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Query Latency</span>
                    <span class="font-mono font-semibold text-gray-950 dark:text-white">{{ $dbLatency ?? '—' }} ms</span>
                </div>
            </div>

            {{-- System Load / Queue --}}
            <div class="relative rounded-xl bg-gray-50/50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-medium">System Load</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">1m, 5m, 15m</span>
                </div>
                <div class="mt-2 font-mono text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    @if($cpuLoad)
                        {{ round($cpuLoad[0], 2) }}, {{ round($cpuLoad[1], 2) }}, {{ round($cpuLoad[2], 2) }}
                    @else
                        N/A (Virtual Host)
                    @endif
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Queue Status</span>
                    <span class="font-semibold {{ $failedJobs > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $pendingJobs }} pending &bull; {{ $failedJobs }} failed
                    </span>
                </div>
            </div>
        </div>

        {{-- Infrastructure Connectivity Matrix --}}
        <div class="grid grid-cols-2 gap-3 border-t border-gray-200 pt-4 sm:grid-cols-3 lg:grid-cols-6 dark:border-white/10">
            <div class="flex items-center gap-x-2.5 rounded-lg bg-gray-50/70 p-2.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <x-filament::icon icon="heroicon-m-server-stack" class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cache Driver</div>
                    <div class="truncate text-xs font-semibold text-gray-950 dark:text-white">{{ ucfirst($cacheDriver) }}</div>
                </div>
            </div>

            <div class="flex items-center gap-x-2.5 rounded-lg bg-gray-50/70 p-2.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <x-filament::icon icon="heroicon-m-queue-list" class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Queue Driver</div>
                    <div class="truncate text-xs font-semibold text-gray-950 dark:text-white">{{ ucfirst($queueDriver) }}</div>
                </div>
            </div>

            <div class="flex items-center gap-x-2.5 rounded-lg bg-gray-50/70 p-2.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <x-filament::icon
                    icon="heroicon-m-folder"
                    @class([
                        'h-4 w-4 shrink-0',
                        'text-emerald-500 dark:text-emerald-400' => $storageWritable,
                        'text-rose-500 dark:text-rose-400' => ! $storageWritable,
                    ])
                />
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Storage Dir</div>
                    <div @class([
                        'truncate text-xs font-semibold',
                        'text-emerald-600 dark:text-emerald-400' => $storageWritable,
                        'text-rose-600 dark:text-rose-400' => ! $storageWritable,
                    ])>
                        {{ $storageWritable ? 'Writable' : 'Locked' }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-x-2.5 rounded-lg bg-gray-50/70 p-2.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <x-filament::icon
                    icon="heroicon-m-bolt"
                    @class([
                        'h-4 w-4 shrink-0',
                        'text-emerald-500 dark:text-emerald-400' => $bootstrapCacheWritable,
                        'text-rose-500 dark:text-rose-400' => ! $bootstrapCacheWritable,
                    ])
                />
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Bootstrap</div>
                    <div @class([
                        'truncate text-xs font-semibold',
                        'text-emerald-600 dark:text-emerald-400' => $bootstrapCacheWritable,
                        'text-rose-600 dark:text-rose-400' => ! $bootstrapCacheWritable,
                    ])>
                        {{ $bootstrapCacheWritable ? 'Writable' : 'Locked' }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-x-2.5 rounded-lg bg-gray-50/70 p-2.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <x-filament::icon
                    icon="heroicon-m-cpu-chip"
                    @class([
                        'h-4 w-4 shrink-0',
                        'text-purple-500 dark:text-purple-400' => $aiEnabled,
                        'text-gray-400 dark:text-gray-500' => ! $aiEnabled,
                    ])
                />
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">AI Engine</div>
                    <div class="truncate text-xs font-semibold text-gray-950 dark:text-white">{{ $aiEnabled ? ucfirst($aiProvider) : 'Off' }}</div>
                </div>
            </div>

            <div class="flex items-center gap-x-2.5 rounded-lg bg-gray-50/70 p-2.5 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <x-filament::icon icon="heroicon-m-shield-check" class="h-4 w-4 shrink-0 text-emerald-500 dark:text-emerald-400" />
                <div class="min-w-0">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Shield RBAC</div>
                    <div class="truncate text-xs font-semibold text-emerald-600 dark:text-emerald-400">Active</div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
