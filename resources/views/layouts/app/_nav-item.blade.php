<a href="{{ route($route) }}" wire:navigate
   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
          {{ $active
              ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100'
              : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/60 dark:hover:text-zinc-200' }}">
    <flux:icon :icon="$icon"
        class="size-4 shrink-0 {{ $active ? 'text-accent' : 'text-zinc-400 dark:text-zinc-500' }}" />
    <span class="truncate">{{ $label }}</span>
    @if ($active)
        <span class="ml-auto size-1.5 shrink-0 rounded-full bg-accent"></span>
    @endif
</a>
