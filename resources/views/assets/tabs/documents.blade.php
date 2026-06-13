@php
    use Illuminate\Support\Facades\Storage;

    $docTypes = [
        'purchase_bill'             => 'Purchase Bill',
        'invoice'                   => 'Invoice',
        'warranty_card'             => 'Warranty Card',
        'warranty_activation_image' => 'Warranty Activation Image',
        'extended_warranty_bill'    => 'Extended Warranty Bill',
        'extended_warranty_image'   => 'Extended Warranty Image',
        'insurance_copy'            => 'Insurance Copy',
        'insurance_policy'          => 'Insurance Policy',
        'puc_copy'                  => 'PUC Copy',
        'rc_copy'                   => 'RC Copy',
        'service_bill'              => 'Service Bill',
        'amc_bill'                  => 'AMC Bill',
        'amc_image'                 => 'AMC Image',
        'inspection_certificate'    => 'Inspection Certificate',
        'compliance_certificate'    => 'Compliance Certificate',
        'vehicle_document'          => 'Vehicle Document',
        'asset_photo'               => 'Asset Photo',
        'other'                     => 'Other',
    ];

    // All docs on the asset (documentable = Asset)
    $allDocs = $asset->documents->sortByDesc('created_at');
@endphp

<div class="space-y-5" x-data="{ showUpload: {{ $errors->any() && old('document_type') ? 'true' : 'false' }} }">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Documents</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $allDocs->count() }} {{ Str::plural('file', $allDocs->count()) }} attached
            </flux:text>
        </div>
        <flux:button variant="primary" size="sm" icon="plus" @click="showUpload = !showUpload">
            Upload Document
        </flux:button>
    </div>

    {{-- Upload Form --}}
    <div x-show="showUpload" x-transition x-cloak
         class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:heading class="mb-4 font-semibold text-zinc-800 dark:text-zinc-300">Upload New Document</flux:heading>

        @php
        $inputCls    = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
        $selectCls   = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
        $labelCls    = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
        $labelSelCls = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
        $textareaCls = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
        @endphp

        <form method="POST"
              action="{{ route('assets.documents.store', $asset) }}"
              enctype="multipart/form-data"
              class="space-y-4">
            @csrf

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="relative">
                    <select name="document_type" id="document_type" required class="{{ $selectCls }}">
                        <option value=""></option>
                        @foreach ($docTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('document_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <label for="document_type" class="{{ $labelSelCls }}">Document Type <span class="text-red-400">*</span></label>
                    @error('document_type') <flux:error>{{ $message }}</flux:error> @enderror
                </div>

                <div class="relative">
                    <input type="text" name="document_title" id="document_title"
                        value="{{ old('document_title') }}" placeholder=" "
                        class="{{ $inputCls }}" />
                    <label for="document_title" class="{{ $labelCls }}">Title / Description</label>
                    @error('document_title') <flux:error>{{ $message }}</flux:error> @enderror
                </div>
            </div>

            <div>
                <p class="mb-1.5 text-xs font-medium text-zinc-500">File <span class="text-red-400">*</span>
                    <span class="ml-1 font-normal">(PDF, JPG, PNG, WEBP, DOC, DOCX, XLS, XLSX — max 10 MB)</span>
                </p>
                <input type="file" name="file" required
                       accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                       class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700
                              file:mr-3 file:rounded file:border-0 file:bg-zinc-100 file:px-3 file:py-1 file:text-xs file:text-zinc-700
                              focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent
                              dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-200" />
                @error('file') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div class="relative">
                <textarea name="remarks" id="doc_remarks" rows="2" placeholder=" " class="{{ $textareaCls }}">{{ old('remarks') }}</textarea>
                <label for="doc_remarks" class="{{ $labelCls }}">Remarks</label>
                @error('remarks') <flux:error>{{ $message }}</flux:error> @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="arrow-up-tray">
                    Upload
                </flux:button>
                <flux:button type="button" variant="ghost" size="sm" @click="showUpload = false">
                    Cancel
                </flux:button>
            </div>
        </form>
    </div>

    {{-- Document List --}}
    @if ($allDocs->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 py-14 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.paper-clip class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">No documents yet</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">
                Upload purchase bills, warranty cards, insurance copies, or any other asset documents.
            </flux:text>
            <div class="mt-4">
                <flux:button variant="ghost" size="sm" icon="plus" @click="showUpload = true">
                    Upload First Document
                </flux:button>
            </div>
        </div>
    @else
        {{-- Group documents by type --}}
        @php
            $grouped = $allDocs->groupBy('document_type');
        @endphp

        <div class="space-y-4">
            @foreach ($grouped as $type => $docs)
                <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                    {{-- Group header --}}
                    <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                        @php
                            $isImageType = in_array($type, ['warranty_activation_image', 'extended_warranty_image', 'asset_photo', 'puc_copy', 'rc_copy']);
                        @endphp
                        @if ($isImageType)
                            <flux:icon.photo class="size-4 text-zinc-400" />
                        @else
                            <flux:icon.document-text class="size-4 text-zinc-400" />
                        @endif
                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            {{ $docTypes[$type] ?? ucwords(str_replace('_', ' ', $type)) }}
                        </span>
                        <span class="ml-auto text-xs text-zinc-600">{{ $docs->count() }}</span>
                    </div>

                    {{-- Files in this group --}}
                    <div class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                        @foreach ($docs as $doc)
                            <div class="flex items-center gap-3 px-4 py-3">
                                {{-- File type icon --}}
                                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                    @if ($doc->isImage())
                                        <flux:icon.photo class="size-5 text-accent" />
                                    @elseif (str_contains($doc->file_mime_type ?? '', 'pdf'))
                                        <flux:icon.document class="size-5 text-red-400" />
                                    @else
                                        <flux:icon.document-text class="size-5 text-zinc-400" />
                                    @endif
                                </div>

                                {{-- File info --}}
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $doc->document_title ?: $doc->file_original_name }}
                                    </p>
                                    <p class="text-xs text-zinc-500">
                                        {{ $doc->file_original_name }}
                                        · {{ number_format($doc->file_size / 1024, 0) }} KB
                                        · {{ $doc->created_at->format('d M Y') }}
                                        @if ($doc->uploader)
                                            · by {{ $doc->uploader->name }}
                                        @endif
                                    </p>
                                    @if ($doc->remarks)
                                        <p class="mt-0.5 text-xs text-zinc-500 italic">{{ $doc->remarks }}</p>
                                    @endif
                                </div>

                                {{-- Actions --}}
                                <div class="flex shrink-0 items-center gap-2">
                                    <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                       class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600
                                              hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                                        View
                                    </a>
                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                       class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-600
                                              hover:border-zinc-400 hover:text-zinc-900 transition-colors dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100">
                                        Download
                                    </a>
                                    <form method="POST"
                                          action="{{ route('assets.documents.destroy', [$asset, $doc]) }}"
                                          onsubmit="return confirm('Delete this document? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-md border border-zinc-300 px-2.5 py-1 text-xs font-medium text-zinc-500
                                                       hover:border-red-500/60 hover:text-red-400 transition-colors dark:border-zinc-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-xs text-zinc-600">
            Showing all {{ $allDocs->count() }} {{ Str::plural('document', $allDocs->count()) }} for this asset.
            Warranty and extended warranty documents are also accessible from their respective tabs.
        </p>
    @endif
</div>
