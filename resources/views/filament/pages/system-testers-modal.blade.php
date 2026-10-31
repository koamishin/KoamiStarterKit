<div class="flex flex-col gap-3 text-xs">
    <p class="text-gray-500 dark:text-gray-400">
        Live automated connection, latency, and read/write diagnostics across core instance subsystems.
    </p>

    <div class="space-y-2.5">
        @foreach($tests as $key => $test)
            <div class="flex items-center justify-between rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                <div class="flex flex-col gap-0.5">
                    <div class="flex items-center gap-2">
                        @if($test['status'] === 'ok')
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        @elseif($test['status'] === 'warning')
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        @else
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                        @endif
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $test['name'] }}</span>
                    </div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $test['message'] }}</span>
                </div>

                <div class="flex items-center gap-3">
                    @if($test['latency_ms'] !== null)
                        <span class="font-mono text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $test['latency_ms'] }}ms
                        </span>
                    @endif

                    @if($test['status'] === 'ok')
                        <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
                            Passed
                        </span>
                    @elseif($test['status'] === 'warning')
                        <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-[11px] font-medium text-amber-600 dark:text-amber-400">
                            Warning
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-rose-500/10 px-2 py-0.5 text-[11px] font-medium text-rose-600 dark:text-rose-400">
                            Failed
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
