@props(['label', 'value', 'icon', 'color' => 'primary'])

<div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
    <div class="flex items-center gap-3">
        <span @class([
            'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
            "bg-{$color}-50 text-{$color}-600 dark:bg-{$color}-400/10 dark:text-{$color}-400",
        ])>
            <x-filament::icon :icon="$icon" class="h-5 w-5" />
        </span>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="truncate text-lg font-bold text-gray-950 dark:text-white">{{ $value }}</p>
        </div>
    </div>
</div>