<div class="space-y-4">
    @forelse ($changes as $change)
        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <p class="text-sm font-medium">{{ $change->event }} · {{ $change->causer?->name ?? 'System / CLI' }} · {{ $change->created_at }}</p>
            <pre class="mt-2 overflow-auto text-xs">{{ json_encode($change->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @empty
        <p>No changes recorded yet.</p>
    @endforelse
</div>
