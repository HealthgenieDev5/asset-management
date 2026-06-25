<x-layouts::app :title="__('Complaint Escalation Rules')">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Complaint <span class="text-accent">Escalation Rules</span>
            </flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                Define who gets notified when a complaint is raised for a given location and asset category.
            </flux:text>
        </div>
    </div>

    @include('partials.flash')

    <div x-data="{ showForm: false, editId: null }" class="space-y-5">

        {{-- Add Rule Button --}}
        <div class="flex justify-end">
            <flux:button variant="primary" size="sm" icon="plus" @click="showForm = !showForm; editId = null">
                Add Rule
            </flux:button>
        </div>

        {{-- Add Form --}}
        <div x-show="showForm && editId === null" x-transition x-cloak
             class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading class="mb-4 font-semibold text-zinc-800 dark:text-zinc-300">New Escalation Rule</flux:heading>
            <form method="POST" action="{{ route('complaint-escalation-rules.store') }}" class="space-y-4">
                @csrf
                @php
                $inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
                $sel = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
                $lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
                $lbs = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
                $txa = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
                $err = 'mt-0.5 text-[11px] text-red-400';
                @endphp

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="relative">
                        <input type="text" name="location" id="location" value="{{ old('location') }}" placeholder=" " class="{{ $inp }}" required />
                        <label for="location" class="{{ $lbl }}">Location <span class="text-red-400">*</span></label>
                        <p class="mt-0.5 text-[10px] text-zinc-400">Must match the asset's location exactly (case-sensitive)</p>
                        @error('location')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                    <div class="relative">
                        <select name="asset_category_id" id="asset_category_id" class="{{ $sel }}" required>
                            <option value=""></option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('asset_category_id') == $cat->id)>{{ $cat->name }} ({{ $cat->code }})</option>
                            @endforeach
                        </select>
                        <label for="asset_category_id" class="{{ $lbs }}">Asset Category <span class="text-red-400">*</span></label>
                        @error('asset_category_id')<p class="{{ $err }}">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="relative">
                    <input type="text" name="notify_emails" id="notify_emails" value="{{ old('notify_emails') }}" placeholder=" " class="{{ $inp }}" required />
                    <label for="notify_emails" class="{{ $lbl }}">Notify Emails <span class="text-red-400">*</span></label>
                    <p class="mt-0.5 text-[10px] text-zinc-400">Comma-separated email addresses, e.g. <em>it@company.com, manager@company.com</em></p>
                    @error('notify_emails')<p class="{{ $err }}">{{ $message }}</p>@enderror
                </div>
                <div class="relative">
                    <textarea name="remarks" id="remarks" rows="2" placeholder=" " class="{{ $txa }}">{{ old('remarks') }}</textarea>
                    <label for="remarks" class="{{ $lbl }}">Remarks</label>
                </div>
                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Save Rule</flux:button>
                    <flux:button type="button" variant="ghost" size="sm" @click="showForm = false">Cancel</flux:button>
                </div>
            </form>
        </div>

        {{-- Rules Table --}}
        @if ($rules->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <flux:icon.exclamation-triangle class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">No Escalation Rules</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">
                    Add a rule to automatically notify the right team when a complaint is raised.
                </flux:text>
            </div>
        @else
            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/40 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Location</th>
                            <th class="px-4 py-3 text-left">Category</th>
                            <th class="px-4 py-3 text-left">Notify Emails</th>
                            <th class="px-4 py-3 text-left">Added By</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach ($rules as $rule)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/20">
                                <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-200">{{ $rule->location }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $rule->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($rule->notify_emails as $email)
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">{{ $email }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-zinc-500 text-xs">{{ $rule->createdBy?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="editId = editId === {{ $rule->id }} ? null : {{ $rule->id }}"
                                                class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('complaint-escalation-rules.destroy', $rule) }}"
                                              onsubmit="confirmDelete(this, 'Delete this escalation rule?'); return false;">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-500 hover:border-red-500/60 hover:text-red-400 transition-colors dark:border-zinc-700">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Inline Edit Row --}}
                            <tr x-show="editId === {{ $rule->id }}" x-cloak>
                                <td colspan="5" class="bg-zinc-50/80 px-4 py-4 dark:bg-zinc-950/40">
                                    <form method="POST" action="{{ route('complaint-escalation-rules.update', $rule) }}" class="space-y-3">
                                        @csrf @method('PUT')
                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            <div class="relative">
                                                <input type="text" name="location" value="{{ $rule->location }}" placeholder=" "
                                                       class="peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent" required />
                                                <label class="pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400">Location</label>
                                            </div>
                                            <div class="relative">
                                                <select name="asset_category_id" class="peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent" required>
                                                    @foreach ($categories as $cat)
                                                        <option value="{{ $cat->id }}" @selected($rule->asset_category_id == $cat->id)>{{ $cat->name }} ({{ $cat->code }})</option>
                                                    @endforeach
                                                </select>
                                                <label class="pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Category</label>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input type="text" name="notify_emails" value="{{ implode(', ', $rule->notify_emails) }}" placeholder=" "
                                                   class="peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent" required />
                                            <label class="pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400">Notify Emails (comma-separated)</label>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <flux:button type="submit" variant="primary" size="sm" icon="check">Save Changes</flux:button>
                                            <flux:button type="button" variant="ghost" size="sm" @click="editId = null">Cancel</flux:button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</x-layouts::app>
