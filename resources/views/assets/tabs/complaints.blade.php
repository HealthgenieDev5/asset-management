@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Complaints</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $asset->complaints->count() }} {{ Str::plural('complaint', $asset->complaints->count()) }}
                @php $open = $asset->complaints->where('status', 'open')->count(); @endphp
                @if ($open > 0)
                    &nbsp;·&nbsp; <span class="text-blue-400">{{ $open }} open</span>
                @endif
            </flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-add-complaint')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Log Complaint
        </button>
    </div>

    {{-- Add Modal --}}
    <x-modal name="add-complaint" title="New Complaint" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'complaint' && !old('_complaint_id')">
        <form method="POST" action="{{ route('assets.complaints.store', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="complaint">

            @include('assets.tabs._complaint-form', ['complaint' => null])

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Submit Complaint</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-add-complaint')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Modals (one per complaint) --}}
    @foreach ($asset->complaints->sortByDesc('created_at') as $cmp)
        <x-modal name="edit-complaint-{{ $cmp->id }}" title="Edit Complaint" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'complaint' && (int) old('_complaint_id') === $cmp->id">
            <form method="POST" action="{{ route('assets.complaints.update', [$asset, $cmp]) }}"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="complaint">
                <input type="hidden" name="_complaint_id" value="{{ $cmp->id }}">

                @include('assets.tabs._complaint-form', ['complaint' => $cmp])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-complaint-{{ $cmp->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>
    @endforeach

    {{-- View Modals (one per complaint) --}}
    @foreach ($asset->complaints->sortByDesc('created_at') as $cmp)
        @php
            $viewVideosBefore = $cmp->documents->where('document_type', 'complaint_video_before');
            $viewVideosAfter  = $cmp->documents->where('document_type', 'complaint_video_after');
            $viewComments     = $cmp->comments->sortByDesc('created_at')->values();
        @endphp
        <x-modal name="view-complaint-{{ $cmp->id }}" title="Complaint Details">
            <div class="space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $cmp->priority_color }}">
                                {{ $cmp->priority_label }}
                            </span>
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $cmp->status_color }}">
                                {{ $cmp->status_label }}
                            </span>
                        </div>
                        <h3 class="mt-2 truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $cmp->title }}</h3>
                        <p class="mt-1 text-xs text-zinc-500">Reported {{ $cmp->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    @if ($cmp->resolved_at)
                        <span class="rounded-full bg-green-400/10 px-2 py-0.5 text-xs font-medium text-green-400">Resolved {{ $cmp->resolved_at->format('d M Y') }}</span>
                    @endif
                </div>

                <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Location</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->location ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Department</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->department ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Category</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->category?->name ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Reporter</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->reported_by_name ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Email</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->reported_by_email ?: '--' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-zinc-500">Phone</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->reported_by_phone ?: '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Description</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->description ?: '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Resolution</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->resolution_summary ?: '--' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-xs font-medium text-zinc-500">Remarks</dt>
                        <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-100">{{ $cmp->remarks ?: '--' }}</dd>
                    </div>
                </dl>

                @if ($cmp->details->isNotEmpty())
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <p class="mb-2 text-xs font-medium text-zinc-500">Additional Data</p>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-3">
                            @foreach ($cmp->details as $detail)
                                <div>
                                    <dt class="text-[10px] text-zinc-500">{{ $detail->label }}</dt>
                                    <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $detail->value ?: '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="mb-2 text-xs font-medium text-zinc-500">Linked Service Record</p>
                    @if ($cmp->service)
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900/40">
                            <flux:icon.cog-6-tooth class="size-4 shrink-0 text-zinc-400" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $cmp->service->service_type_label }} - {{ $cmp->service->service_date->format('d M Y') }}
                                @if ($cmp->service->service_agency) ({{ $cmp->service->service_agency }}) @endif
                            </span>
                            <a href="{{ route('assets.show', [$asset, 'tab' => 'services']) }}"
                               class="ml-auto text-xs text-accent hover:underline">Open</a>
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">No service record linked.</p>
                    @endif
                </div>

                @if ($viewVideosBefore->isNotEmpty() || $viewVideosAfter->isNotEmpty())
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <p class="mb-2 text-xs font-medium text-zinc-500">Videos</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach ($viewVideosBefore as $vid)
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                                    <p class="mb-2 text-xs font-medium text-zinc-500">Before Repair</p>
                                    @if ($vid->isVideo())
                                        <video controls class="max-h-48 w-full rounded" preload="metadata">
                                            <source src="{{ Storage::url($vid->file_path) }}" type="{{ $vid->file_mime_type }}">
                                        </video>
                                    @endif
                                    <a href="{{ Storage::url($vid->file_path) }}" target="_blank"
                                       class="mt-1 block text-xs text-accent hover:underline">{{ $vid->file_original_name }}</a>
                                </div>
                            @endforeach
                            @foreach ($viewVideosAfter as $vid)
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
                                    <p class="mb-2 text-xs font-medium text-zinc-500">After Repair</p>
                                    @if ($vid->isVideo())
                                        <video controls class="max-h-48 w-full rounded" preload="metadata">
                                            <source src="{{ Storage::url($vid->file_path) }}" type="{{ $vid->file_mime_type }}">
                                        </video>
                                    @endif
                                    <a href="{{ Storage::url($vid->file_path) }}" target="_blank"
                                       class="mt-1 block text-xs text-accent hover:underline">{{ $vid->file_original_name }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="mb-2 text-xs font-medium text-zinc-500">Conversation</p>
                    @if ($viewComments->isNotEmpty())
                        <div class="divide-y divide-zinc-200/60 overflow-hidden rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                            @foreach ($viewComments->take(5) as $comment)
                                <div class="px-3 py-2 {{ $comment->is_internal ? 'bg-yellow-400/5' : 'bg-white dark:bg-zinc-900' }}">
                                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                        <span class="text-[11px] font-bold uppercase tracking-wide text-sky-600 dark:text-sky-300">{{ $comment->user?->name ?? 'Unknown' }}</span>
                                        @if ($comment->is_internal)
                                            <span class="rounded-full bg-yellow-400/10 px-1.5 py-0.5 text-[10px] font-medium text-yellow-400 leading-none">Staff Note</span>
                                        @endif
                                        <span class="ml-auto text-[10px] text-zinc-400">{{ $comment->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                    <p class="mt-0.5 whitespace-pre-line text-[13px] leading-5 text-zinc-800 dark:text-zinc-100">{{ $comment->comment }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-zinc-500">No conversation yet.</p>
                    @endif
                </div>
            </div>
        </x-modal>
    @endforeach

    {{-- Cards Grid --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach ($asset->complaints->sortByDesc('created_at') as $cmp)
            @php
                $patchUrl  = route('assets.complaints.patch-field', [$asset, $cmp]);
                $pencilSvg = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>';
                $checkSvg  = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
                $xSvg      = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>';
                $inpInline = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
                $btnCheck  = 'rounded p-0.5 text-green-400 hover:text-green-300 transition-colors';
                $btnX      = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';
            @endphp

            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">

                {{-- Card Header --}}
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-5 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex items-center gap-2 min-w-0 flex-wrap">
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $cmp->priority_color }}">
                            {{ $cmp->priority_label }}
                        </span>
                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $cmp->status_color }}">
                            {{ $cmp->status_label }}
                        </span>
                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 truncate">{{ $cmp->title }}</span>
                        <span class="text-xs text-zinc-500">{{ $cmp->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <button type="button"
                                x-on:click="$dispatch('open-modal-view-complaint-{{ $cmp->id }}')"
                                aria-label="View complaint"
                                title="View complaint"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        <button type="button"
                                x-on:click="$dispatch('open-modal-edit-complaint-{{ $cmp->id }}')"
                                aria-label="Edit complaint"
                                title="Edit complaint"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <form method="POST" action="{{ route('assets.complaints.destroy', [$asset, $cmp]) }}"
                              onsubmit="return confirm('Delete this complaint and all its comments?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    aria-label="Delete complaint"
                                    title="Delete complaint"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Card Body --}}
                <div class="px-5 py-4">

                    {{-- Info grid --}}
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">

                        {{-- Location (editable) --}}
                        <div x-data="{ editing: false }">
                            <dt class="text-xs font-medium text-zinc-500">Location</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                                <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-200">{{ $cmp->location ?: '—' }}</span>
                                <button x-show="!editing" type="button" @click="editing = true"
                                        class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                                <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="field" value="location">
                                    <input type="text" name="value" value="{{ $cmp->location }}" class="{{ $inpInline }}"
                                           @keydown.escape="editing = false"
                                           x-ref="loc{{ $cmp->id }}"
                                           x-init="$watch('editing', v => v && $nextTick(() => $refs['loc{{ $cmp->id }}'].focus()))">
                                    <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                                    <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                                </form>
                            </dd>
                        </div>

                        {{-- Department (editable) --}}
                        <div x-data="{ editing: false }">
                            <dt class="text-xs font-medium text-zinc-500">Department</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                                <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-200">{{ $cmp->department ?: '—' }}</span>
                                <button x-show="!editing" type="button" @click="editing = true"
                                        class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                                <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="field" value="department">
                                    <input type="text" name="value" value="{{ $cmp->department }}" class="{{ $inpInline }}"
                                           @keydown.escape="editing = false"
                                           x-ref="dept{{ $cmp->id }}"
                                           x-init="$watch('editing', v => v && $nextTick(() => $refs['dept{{ $cmp->id }}'].focus()))">
                                    <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                                    <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                                </form>
                            </dd>
                        </div>

                        {{-- Category --}}
                        @if ($cmp->category)
                            <div>
                                <dt class="text-xs font-medium text-zinc-500">Category</dt>
                                <dd class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $cmp->category->name }}</dd>
                            </div>
                        @endif

                        {{-- Reporter Name (editable) --}}
                        <div x-data="{ editing: false }">
                            <dt class="text-xs font-medium text-zinc-500">Reporter</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                                <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-200">{{ $cmp->reported_by_name ?: '—' }}</span>
                                <button x-show="!editing" type="button" @click="editing = true"
                                        class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                                <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="field" value="reported_by_name">
                                    <input type="text" name="value" value="{{ $cmp->reported_by_name }}" class="{{ $inpInline }}"
                                           @keydown.escape="editing = false"
                                           x-ref="rname{{ $cmp->id }}"
                                           x-init="$watch('editing', v => v && $nextTick(() => $refs['rname{{ $cmp->id }}'].focus()))">
                                    <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                                    <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                                </form>
                            </dd>
                        </div>

                        {{-- Reporter Email (editable) --}}
                        <div x-data="{ editing: false }">
                            <dt class="text-xs font-medium text-zinc-500">Email</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                                <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-200 truncate">{{ $cmp->reported_by_email ?: '—' }}</span>
                                <button x-show="!editing" type="button" @click="editing = true"
                                        class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                                <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="field" value="reported_by_email">
                                    <input type="email" name="value" value="{{ $cmp->reported_by_email }}" class="{{ $inpInline }}"
                                           @keydown.escape="editing = false"
                                           x-ref="remail{{ $cmp->id }}"
                                           x-init="$watch('editing', v => v && $nextTick(() => $refs['remail{{ $cmp->id }}'].focus()))">
                                    <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                                    <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                                </form>
                            </dd>
                        </div>

                        {{-- Reporter Phone (editable) --}}
                        <div x-data="{ editing: false }">
                            <dt class="text-xs font-medium text-zinc-500">Phone</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5 min-w-0">
                                <span x-show="!editing" class="text-sm text-zinc-800 dark:text-zinc-200">{{ $cmp->reported_by_phone ?: '—' }}</span>
                                <button x-show="!editing" type="button" @click="editing = true"
                                        class="rounded p-0.5 text-zinc-400 hover:text-accent transition-colors shrink-0">{!! $pencilSvg !!}</button>
                                <form x-show="editing" x-cloak method="POST" action="{{ $patchUrl }}" class="flex items-center gap-1 min-w-0">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="field" value="reported_by_phone">
                                    <input type="text" name="value" value="{{ $cmp->reported_by_phone }}" class="{{ $inpInline }}"
                                           @keydown.escape="editing = false"
                                           x-ref="rphone{{ $cmp->id }}"
                                           x-init="$watch('editing', v => v && $nextTick(() => $refs['rphone{{ $cmp->id }}'].focus()))">
                                    <button type="submit" class="{{ $btnCheck }}">{!! $checkSvg !!}</button>
                                    <button type="button" @click="editing = false" class="{{ $btnX }}">{!! $xSvg !!}</button>
                                </form>
                            </dd>
                        </div>

                    </dl>

                    {{-- Description --}}
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                        <p class="mb-1 text-xs font-medium text-zinc-500">Description</p>
                        <p class="text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $cmp->description }}</p>
                    </div>

                    {{-- Additional Data --}}
                    @if ($cmp->details->isNotEmpty())
                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Additional Data</p>
                            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-3">
                                @foreach ($cmp->details as $detail)
                                    <div>
                                        <dt class="text-[10px] text-zinc-500">{{ $detail->label }}</dt>
                                        <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ $detail->value ?: '—' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    {{-- Resolution --}}
                    @if ($cmp->resolution_summary)
                        <div class="mt-3 rounded-lg border border-green-500/20 bg-green-400/5 px-4 py-3">
                            <p class="text-xs font-medium text-green-400 mb-1">Resolution</p>
                            <p class="text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $cmp->resolution_summary }}</p>
                            @if ($cmp->resolved_at)
                                <p class="mt-1 text-xs text-zinc-500">Resolved on {{ $cmp->resolved_at->format('d M Y') }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- Videos --}}
                    @php
                        $videosBefore = $cmp->documents->where('document_type', 'complaint_video_before');
                        $videosAfter  = $cmp->documents->where('document_type', 'complaint_video_after');
                    @endphp
                    @if ($videosBefore->isNotEmpty() || $videosAfter->isNotEmpty())
                        <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <p class="mb-2 text-xs font-medium text-zinc-500">Videos</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($videosBefore as $vid)
                                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                                        <p class="text-xs font-medium text-zinc-500 mb-2">Before Repair</p>
                                        @if ($vid->isVideo())
                                            <video controls class="w-full rounded max-h-48" preload="metadata">
                                                <source src="{{ Storage::url($vid->file_path) }}" type="{{ $vid->file_mime_type }}">
                                            </video>
                                        @endif
                                        <a href="{{ Storage::url($vid->file_path) }}" target="_blank"
                                           class="mt-1 block text-xs text-accent hover:underline">{{ $vid->file_original_name }}</a>
                                    </div>
                                @endforeach
                                @foreach ($videosAfter as $vid)
                                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                                        <p class="text-xs font-medium text-zinc-500 mb-2">After Repair</p>
                                        @if ($vid->isVideo())
                                            <video controls class="w-full rounded max-h-48" preload="metadata">
                                                <source src="{{ Storage::url($vid->file_path) }}" type="{{ $vid->file_mime_type }}">
                                            </video>
                                        @endif
                                        <a href="{{ Storage::url($vid->file_path) }}" target="_blank"
                                           class="mt-1 block text-xs text-accent hover:underline">{{ $vid->file_original_name }}</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Link to Service --}}
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800" x-data="{ linking: false }">
                        <p class="mb-2 text-xs font-medium text-zinc-500">Linked Service Record</p>
                        @if ($cmp->service)
                            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-800 dark:bg-zinc-800/40">
                                <flux:icon.cog-6-tooth class="size-4 text-zinc-400 shrink-0" />
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $cmp->service->service_type_label }}
                                    &mdash; {{ $cmp->service->service_date->format('d M Y') }}
                                    @if ($cmp->service->service_agency) ({{ $cmp->service->service_agency }}) @endif
                                </span>
                                <a href="{{ route('assets.show', [$asset, 'tab' => 'services']) }}"
                                   class="ml-auto text-xs text-accent hover:underline">View →</a>
                            </div>
                        @else
                            <flux:button variant="ghost" size="sm" icon="link" @click="linking = !linking">
                                Link to Service Record
                            </flux:button>
                            <div x-show="linking" x-cloak class="mt-2">
                                @if ($asset->services->isEmpty())
                                    <p class="text-xs text-zinc-500">No service records exist yet.
                                        <a href="{{ route('assets.show', [$asset, 'tab' => 'services']) }}" class="text-accent hover:underline">Add one →</a>
                                    </p>
                                @else
                                    <form method="POST" action="{{ route('assets.complaints.link-service', [$asset, $cmp]) }}"
                                          class="flex items-center gap-2">
                                        @csrf
                                        <select name="asset_service_id"
                                                class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                            @foreach ($asset->services->sortByDesc('service_date') as $svc)
                                                <option value="{{ $svc->id }}">
                                                    {{ $svc->service_type_label }} — {{ $svc->service_date->format('d M Y') }}
                                                    @if ($svc->service_agency) ({{ $svc->service_agency }}) @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <flux:button type="submit" variant="primary" size="sm">Link</flux:button>
                                        <flux:button type="button" variant="ghost" size="sm" @click="linking = false">Cancel</flux:button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Conversation History --}}
                    @php
                        $allComments   = $cmp->comments->sortByDesc('created_at')->values();
                        $totalComments = $allComments->count();
                        $initialShow   = 3;
                        $hasMore       = $totalComments > $initialShow;
                        $hiddenCount   = $totalComments - $initialShow;
                        // Newest-first thread: show the latest comments first, then load older below.
                        $recentComments = $allComments->take($initialShow);
                        $olderComments = $hasMore ? $allComments->slice($initialShow)->values() : collect();
                    @endphp
                    <div class="mt-4 border-t border-zinc-200 dark:border-zinc-800" x-data="{ expanded: false }">

                        {{-- Thread header --}}
                        <div class="flex items-center gap-2 py-3">
                            <flux:icon.chat-bubble-left-right class="size-3.5 text-zinc-400" />
                            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wide">Conversation</span>
                            @if ($totalComments)
                                <span class="rounded-full bg-zinc-200 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                    {{ $totalComments }}
                                </span>
                            @endif
                        </div>

                        {{-- Reply form --}}
                        <form method="POST" action="{{ route('assets.complaints.comments.store', [$asset, $cmp]) }}"
                              class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 space-y-2.5 dark:border-zinc-800 dark:bg-zinc-800/30 mb-3">
                            @csrf
                            <textarea name="comment" rows="2" placeholder="Add a comment or note…"
                                      class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500 resize-none"></textarea>
                            <div class="flex items-center justify-between gap-3">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="is_internal" value="1"
                                           class="rounded border-zinc-400 text-yellow-400 focus:ring-yellow-400" />
                                    <span class="text-xs text-zinc-500">Staff note only</span>
                                </label>
                                <flux:button type="submit" variant="filled" size="sm" icon="paper-airplane">Send</flux:button>
                            </div>
                        </form>

                        {{-- Thread messages --}}
                        @if ($allComments->isNotEmpty())
                            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden divide-y divide-zinc-200/60 dark:divide-zinc-800/60">

                                {{-- Latest N comments --}}
                                @foreach ($recentComments as $comment)
                                    @php $isInternal = $comment->is_internal; @endphp
                                    <div class="px-3 py-3 {{ $isInternal ? 'bg-yellow-400/5' : 'bg-white dark:bg-zinc-900' }}">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 mb-1">
                                                <span class="text-[11px] font-bold uppercase tracking-wide text-sky-600 dark:text-sky-300">{{ $comment->user?->name ?? 'Unknown' }}</span>
                                                @if ($isInternal)
                                                    <span class="rounded-full bg-yellow-400/10 px-1.5 py-0.5 text-[10px] font-medium text-yellow-400 leading-none">Staff Note</span>
                                                @endif
                                                <span class="ml-auto text-[10px] text-zinc-400 shrink-0">{{ $comment->created_at->format('d M Y, H:i') }}</span>
                                            </div>
                                            <p class="mt-0.5 text-[13px] font-normal text-zinc-800 whitespace-pre-line leading-5 dark:text-zinc-100">{{ $comment->comment }}</p>
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Older comments behind "load more" --}}
                                @if ($hasMore)
                                    <div x-show="!expanded" class="bg-zinc-50 dark:bg-zinc-800/30 px-3 py-2 text-center">
                                        <button type="button" @click="expanded = true"
                                                class="text-xs font-medium text-accent hover:underline">
                                            Load {{ $hiddenCount }} older {{ Str::plural('message', $hiddenCount) }}
                                        </button>
                                    </div>
                                    <div x-show="expanded" x-cloak class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                                        @foreach ($olderComments as $comment)
                                            @php $isInternal = $comment->is_internal; @endphp
                                            <div class="px-3 py-3 {{ $isInternal ? 'bg-yellow-400/5' : 'bg-white dark:bg-zinc-900' }}">
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 mb-1">
                                                        <span class="text-[11px] font-bold uppercase tracking-wide text-sky-600 dark:text-sky-300">{{ $comment->user?->name ?? 'Unknown' }}</span>
                                                        @if ($isInternal)
                                                            <span class="rounded-full bg-yellow-400/10 px-1.5 py-0.5 text-[10px] font-medium text-yellow-400 leading-none">Staff Note</span>
                                                        @endif
                                                        <span class="ml-auto text-[10px] text-zinc-400 shrink-0">{{ $comment->created_at->format('d M Y, H:i') }}</span>
                                                    </div>
                                                    <p class="mt-0.5 text-[13px] font-normal text-zinc-800 whitespace-pre-line leading-5 dark:text-zinc-100">{{ $comment->comment }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                        <div class="bg-zinc-50 dark:bg-zinc-800/30 px-3 py-2 text-center">
                                            <button type="button" @click="expanded = false"
                                                    class="text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 hover:underline">
                                                Show less
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        @endforeach

        {{-- Always-visible placeholder --}}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.exclamation-triangle class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $asset->complaints->isEmpty() ? 'No Complaints' : 'Log Another Complaint' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Log asset problems, breakdowns, or performance issues here.</flux:text>
            <div class="mt-4">
                <button type="button" x-on:click="$dispatch('open-modal-add-complaint')"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                    {{ $asset->complaints->isEmpty() ? 'Log First Complaint' : 'Log Complaint' }}
                </button>
            </div>
        </div>
    </div>

</div>
