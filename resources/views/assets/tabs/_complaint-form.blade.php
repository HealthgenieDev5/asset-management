@php
$v   = fn($f) => old($f, $complaint?->{$f});
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
            <div class="relative">
                <input type="text" name="title" id="title" value="{{ $v('title') }}" placeholder=" " class="{{ $inp }}" required />
                <label for="title" class="{{ $lbl }}">Complaint Title <span class="text-red-400">*</span></label>
                @error('title')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative">
                <textarea name="description" id="description" rows="3" placeholder=" " class="{{ $txa }}">{{ $v('description') }}</textarea>
                <label for="description" class="{{ $lbl }}">Description <span class="text-red-400">*</span></label>
                @error('description')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div class="relative w-full sm:w-48">
                <select name="priority" id="priority" class="{{ $sel }}">
                    @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $pLabel)
                        <option value="{{ $val }}" @selected($v('priority') === $val || (! $v('priority') && $val === 'medium'))>{{ $pLabel }}</option>
                    @endforeach
                </select>
                <label for="priority" class="{{ $lbs }}">Priority</label>
                @error('priority')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Asset Context (read-only snapshot) ── --}}
    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
        <p class="{{ $sec }} mb-2">Asset Context (auto-captured)</p>
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

    {{-- ── Videos ── --}}
    <div>
        <p class="{{ $sec }}">Videos</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">Before-Repair Video</label>
                <input type="file" name="video_before" accept="video/mp4,video/quicktime,video/x-msvideo,video/webm"
                       class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-200 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-700 hover:file:bg-zinc-300 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-300" />
                <p class="mt-0.5 text-[10px] text-zinc-400">MP4, MOV, AVI, WEBM · max 50 MB</p>
                @error('video_before')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-500 mb-1">After-Repair Video</label>
                <input type="file" name="video_after" accept="video/mp4,video/quicktime,video/x-msvideo,video/webm"
                       class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-200 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-zinc-700 hover:file:bg-zinc-300 dark:text-zinc-400 dark:file:bg-zinc-700 dark:file:text-zinc-300" />
                <p class="mt-0.5 text-[10px] text-zinc-400">MP4, MOV, AVI, WEBM · max 50 MB</p>
                @error('video_after')<p class="{{ $err }}">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Remarks ── --}}
    <div class="relative">
        <textarea name="remarks" id="remarks" rows="2" placeholder=" " class="{{ $txa }}">{{ $v('remarks') }}</textarea>
        <label for="remarks" class="{{ $lbl }}">Remarks</label>
        @error('remarks')<p class="{{ $err }}">{{ $message }}</p>@enderror
    </div>
</div>
