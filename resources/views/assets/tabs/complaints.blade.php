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
        <flux:modal.trigger name="add-complaint">
            <flux:button variant="primary" size="sm" icon="plus">Log Complaint</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Add Modal --}}
    <flux:modal name="add-complaint" :show="$errors->any() && old('_form') === 'complaint' && !old('_complaint_id')" focusable :dismissible="false">
        <flux:heading class="font-semibold">New Complaint</flux:heading>

        <form method="POST" action="{{ route('assets.complaints.store', $asset) }}"
              enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="_form" value="complaint">

            @include('assets.tabs._complaint-form', ['complaint' => null])

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Submit Complaint</flux:button>
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" size="sm">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Modals (one per complaint) --}}
    @foreach ($asset->complaints->sortByDesc('created_at') as $cmp)
        <flux:modal name="edit-complaint-{{ $cmp->id }}"
                    :show="$errors->any() && old('_form') === 'complaint' && (int) old('_complaint_id') === $cmp->id"
                    focusable :dismissible="false">
            <flux:heading class="font-semibold">Edit Complaint</flux:heading>

            <form method="POST" action="{{ route('assets.complaints.update', [$asset, $cmp]) }}"
                  enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="complaint">
                <input type="hidden" name="_complaint_id" value="{{ $cmp->id }}">

                @include('assets.tabs._complaint-form', ['complaint' => $cmp])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost" size="sm">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </form>
        </flux:modal>
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
                    <div class="flex shrink-0 items-center gap-2">
                        <flux:modal.trigger name="edit-complaint-{{ $cmp->id }}">
                            <button type="button"
                                    class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                                Edit
                            </button>
                        </flux:modal.trigger>
                        <form method="POST" action="{{ route('assets.complaints.destroy', [$asset, $cmp]) }}"
                              onsubmit="return confirm('Delete this complaint and all its comments?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-500 hover:border-red-500/60 hover:text-red-400 transition-colors dark:border-zinc-700">
                                Delete
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

                    {{-- Comments --}}
                    <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                        <p class="mb-3 text-xs font-medium text-zinc-500">
                            Conversation History ({{ $cmp->comments->count() }})
                        </p>

                        @if ($cmp->comments->isNotEmpty())
                            <div class="space-y-2 mb-3">
                                @foreach ($cmp->comments as $comment)
                                    <div class="rounded-lg border px-3 py-2.5 {{ $comment->is_internal ? 'border-yellow-400/30 bg-yellow-400/5' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/30' }}">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                                {{ $comment->user?->name ?? 'Unknown' }}
                                            </span>
                                            @if ($comment->is_internal)
                                                <span class="rounded-full bg-yellow-400/10 px-1.5 py-0.5 text-[10px] font-medium text-yellow-400">Staff Note</span>
                                            @endif
                                            <span class="ml-auto text-[10px] text-zinc-400">
                                                {{ $comment->created_at->format('d M Y, H:i') }}
                                            </span>
                                            <form method="POST" action="{{ route('assets.complaints.comments.destroy', [$asset, $cmp, $comment]) }}"
                                                  onsubmit="return confirm('Delete this comment?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-[10px] text-zinc-400 hover:text-red-400 transition-colors">Remove</button>
                                            </form>
                                        </div>
                                        <p class="text-sm text-zinc-800 whitespace-pre-line dark:text-zinc-200">{{ $comment->comment }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('assets.complaints.comments.store', [$asset, $cmp]) }}"
                              class="space-y-2">
                            @csrf
                            <textarea name="comment" rows="2" placeholder="Add a comment or note…"
                                      class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 placeholder-zinc-400 shadow-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:placeholder-zinc-500"></textarea>
                            <div class="flex items-center gap-3">
                                <flux:button type="submit" variant="filled" size="sm">Add Comment</flux:button>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="is_internal" value="1"
                                           class="rounded border-zinc-400 text-yellow-400 focus:ring-yellow-400" />
                                    <span class="text-xs text-zinc-500">Staff note only</span>
                                </label>
                            </div>
                        </form>
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
                <flux:modal.trigger name="add-complaint">
                    <flux:button variant="ghost" size="sm" icon="plus">
                        {{ $asset->complaints->isEmpty() ? 'Log First Complaint' : 'Log Complaint' }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    </div>

</div>
