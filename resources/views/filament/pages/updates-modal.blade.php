<div class="flex flex-col gap-4 text-xs">
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Current Installed Version</div>
            <div class="mt-1 font-mono text-base font-bold text-gray-950 dark:text-white">v{{ $currentVersion }}</div>
        </div>

        <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Latest Available Release</div>
            <div class="mt-1 flex items-center gap-2">
                <span class="font-mono text-base font-bold text-gray-950 dark:text-white">v{{ $latestVersion }}</span>
                @if($isUpdateAvailable)
                    <span class="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-600 dark:text-amber-400">
                        Update Available
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
                        Up to Date
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-lg bg-gray-50/50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
        <div class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Release Title</div>
        <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $releaseTitle }}</div>
        @if($publishedAt)
            <div class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">Published {{ \Illuminate\Support\Carbon::parse($publishedAt)->diffForHumans() }}</div>
        @endif
    </div>

    <div>
        <div class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Release Notes
        </div>
        <div class="max-h-48 overflow-y-auto whitespace-pre-wrap rounded-lg bg-gray-50/50 p-3 font-mono text-[11px] text-gray-700 ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
{{ $releaseNotes }}
        </div>
    </div>

    <div class="flex justify-end pt-2">
        <a
            href="{{ $htmlUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
        >
            View on GitHub Releases
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
        </a>
    </div>
</div>
