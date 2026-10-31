<div class="flex flex-col gap-4 text-xs">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">PHP Version</div>
            <div class="mt-1 font-mono text-sm font-semibold text-gray-950 dark:text-white">{{ $phpVersion }}</div>
        </div>

        <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Laravel Version</div>
            <div class="mt-1 font-mono text-sm font-semibold text-gray-950 dark:text-white">{{ $laravelVersion }}</div>
        </div>

        <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Memory Limit</div>
            <div class="mt-1 font-mono text-sm font-semibold text-gray-950 dark:text-white">{{ $memoryLimit === '-1' ? 'Unlimited' : $memoryLimit }}</div>
        </div>

        <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Max Execution</div>
            <div class="mt-1 font-mono text-sm font-semibold text-gray-950 dark:text-white">{{ $maxExecutionTime }}</div>
        </div>

        <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Upload Max Size</div>
            <div class="mt-1 font-mono text-sm font-semibold text-gray-950 dark:text-white">{{ $uploadMaxFilesize }}</div>
        </div>

        <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">OPcache Status</div>
            <div class="mt-1 text-sm font-semibold {{ $opcacheEnabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                {{ $opcacheEnabled ? 'Enabled' : 'Disabled' }}
            </div>
        </div>
    </div>

    <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Operating System & Kernel</div>
        <div class="mt-1 font-mono text-xs text-gray-950 dark:text-white">{{ $os }}</div>
    </div>

    <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Core Subsystem Drivers</div>
        <div class="grid grid-cols-3 gap-2 text-xs">
            <div>Database: <strong class="text-gray-950 dark:text-white">{{ $dbDriver }}</strong></div>
            <div>Cache: <strong class="text-gray-950 dark:text-white">{{ $cacheDriver }}</strong></div>
            <div>Queue: <strong class="text-gray-950 dark:text-white">{{ $queueDriver }}</strong></div>
        </div>
    </div>

    <div>
        <div class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Loaded PHP Extensions ({{ count($extensions) }})
        </div>
        <div class="flex max-h-36 flex-wrap gap-1.5 overflow-y-auto rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            @foreach($extensions as $ext)
                <span class="inline-block rounded bg-gray-200/70 px-1.5 py-0.5 font-mono text-[11px] text-gray-700 dark:bg-white/10 dark:text-gray-300">
                    {{ $ext }}
                </span>
            @endforeach
        </div>
    </div>
</div>
