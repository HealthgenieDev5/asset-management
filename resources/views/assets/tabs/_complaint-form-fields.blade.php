@php
$v   = fn($f) => old($f, $complaint?->{$f} ?? null);
$oldLabels = old('detail_labels', []);
$oldValues = old('detail_values', []);
$initialDetailRows = $oldLabels
    ? array_map(fn ($i) => ['label' => $oldLabels[$i] ?? '', 'value' => $oldValues[$i] ?? ''], array_keys($oldLabels))
    : [['label' => '', 'value' => '']];
$inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$sel = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
$lbs = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
$txa = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
$sec = 'mb-1 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500';
$err = 'mt-0.5 text-[11px] text-red-400';
@endphp

<div class="space-y-4">

    {{-- ── Reporter Info ── --}}
    <div>
        <p class="{{ $sec }}">Reporter Info</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="relative">
                <input type="text" name="reported_by_name" id="reported_by_name" value="{{ $v('reported_by_name') }}" placeholder=" " class="{{ $inp }}" required />
                <label for="reported_by_name" class="{{ $lbl }}">Reported By <span class="text-red-400">*</span></label>
                @error('reported_by_name')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="email" name="reported_by_email" id="reported_by_email" value="{{ $v('reported_by_email') }}" placeholder=" " class="{{ $inp }}" />
                <label for="reported_by_email" class="{{ $lbl }}">Email</label>
                @error('reported_by_email')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <input type="text" name="reported_by_phone" id="reported_by_phone" value="{{ $v('reported_by_phone') }}" placeholder=" " class="{{ $inp }}" />
                <label for="reported_by_phone" class="{{ $lbl }}">Phone</label>
                @error('reported_by_phone')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Complaint Details ── --}}
    <div>
        <p class="{{ $sec }}">Complaint Details</p>
        <div class="space-y-3">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="relative sm:col-span-3">
                    <input type="text" name="title" id="title" value="{{ $v('title') }}" placeholder=" " class="{{ $inp }}" required />
                    <label for="title" class="{{ $lbl }}">Complaint Title <span class="text-red-400">*</span></label>
                    @error('title')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div class="relative sm:col-span-1">
                    <select name="priority" id="priority" class="{{ $sel }}">
                        @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $pLabel)
                            <option value="{{ $val }}" @selected($v('priority') === $val || (! $v('priority') && $val === 'medium'))>{{ $pLabel }}</option>
                        @endforeach
                    </select>
                    <label for="priority" class="{{ $lbs }}">Priority</label>
                    @error('priority')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="relative">
                <textarea name="description" id="description" rows="3" placeholder=" " class="{{ $txa }}">{{ $v('description') }}</textarea>
                <label for="description" class="{{ $lbl }}">Description/Remmarks <span class="text-red-400">*</span></label>
                @error('description')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>

            {{-- ── Additional Data (repeater) ── --}}
            <div x-data="{ rows: {{ json_encode($initialDetailRows) }} }">
                <p class="{{ $sec }} mb-2">Additional Data</p>
                <div class="space-y-2">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="flex items-center gap-2">
                            <input type="text" :name="`detail_labels[${index}]`" x-model="row.label" placeholder="Label (e.g. Live Meter Reading)"
                                   class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm transition placeholder:text-zinc-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                            <input type="text" :name="`detail_values[${index}]`" x-model="row.value" placeholder="Value (e.g. 245 kWh)"
                                   class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm transition placeholder:text-zinc-400 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                            <button type="button" x-show="rows.length > 1" @click="rows.splice(index, 1)"
                                    class="shrink-0 rounded-md p-1.5 text-zinc-400 hover:text-red-400 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M3.5 7.25a.75.75 0 0 0 0 1.5h9a.75.75 0 0 0 0-1.5h-9Z"/></svg>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="rows.push({ label: '', value: '' })"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        Add Row
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Videos ── --}}
    <div>
        <style>
            .complaint-video-upload .filepond--panel-root {
                border: 1px dashed #4b4b4c;
                border-radius: 10px;
            }
        </style>
        <p class="{{ $sec }}">Videos</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="complaint-video-upload" x-data x-init="initUploadPond($refs.videoBefore, { acceptedFileTypes: ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'] })">
                <label class="block text-xs font-medium text-zinc-500 mb-1">Before-Repair Video</label>
                <input type="file" name="video_before" x-ref="videoBefore" accept="video/mp4,video/quicktime,video/x-msvideo,video/webm" />
                <p class="mt-0.5 text-[10px] text-zinc-400">MP4, MOV, AVI, WEBM · max 100 MB</p>
                @error('video_before')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="complaint-video-upload" x-data x-init="initUploadPond($refs.videoAfter, { acceptedFileTypes: ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/webm'] })">
                <label class="block text-xs font-medium text-zinc-500 mb-1">After-Repair Video</label>
                <input type="file" name="video_after" x-ref="videoAfter" accept="video/mp4,video/quicktime,video/x-msvideo,video/webm" />
                <p class="mt-0.5 text-[10px] text-zinc-400">MP4, MOV, AVI, WEBM · max 100 MB</p>
                @error('video_after')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

</div>
