@include('assets.tabs._complaint-form-fields')

<div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Asset Context (auto-captured)</p>
    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-4">
        <div>
            <dt class="text-[10px] text-zinc-500">Location</dt>
            <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $asset->location ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-[10px] text-zinc-500">Department</dt>
            <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $asset->department ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-[10px] text-zinc-500">Category</dt>
            <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $asset->category?->name ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-[10px] text-zinc-500">Subcategory</dt>
            <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $asset->subcategory?->name ?: '—' }}</dd>
        </div>
    </dl>
</div>
