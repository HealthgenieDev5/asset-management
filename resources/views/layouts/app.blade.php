<x-layouts::app.sidebar :title="$title ?? null">
    <div class="p-6">
        {{ $slot }}
    </div>
</x-layouts::app.sidebar>
