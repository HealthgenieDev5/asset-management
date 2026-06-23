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

    $allDocs = $asset->documents->sortByDesc('created_at');

    $inputCls    = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
    $selectCls   = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
    $labelCls    = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400 dark:peer-focus:text-zinc-400';
    $labelSelCls = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
    $textareaCls = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
@endphp

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-200">Documents</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $allDocs->count() }} {{ Str::plural('file', $allDocs->count()) }} attached
            </flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-upload-document')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Upload Document
        </button>
    </div>

    <style>
        .doc-upload .filepond--panel-root {
            border: 1px dashed #4b4b4c;
            border-radius: 10px;
        }
    </style>

    {{-- Upload Modal --}}
    <x-modal name="upload-document" title="Upload Document" :dismissible="false"
        :auto-open="$errors->any() && old('document_type')">
        <form method="POST" action="{{ route('assets.documents.store', $asset) }}"
              enctype="multipart/form-data" class="space-y-4">
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
                    @error('document_type') <p class="mt-0.5 text-[11px] text-red-400">{{ $message }}</p> @enderror
                </div>

                <div class="relative">
                    <input type="text" name="document_title" id="document_title"
                        value="{{ old('document_title') }}" placeholder=" "
                        class="{{ $inputCls }}" />
                    <label for="document_title" class="{{ $labelCls }}">Title / Description</label>
                    @error('document_title') <p class="mt-0.5 text-[11px] text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="doc-upload"
                x-data="{
                    pond: null,
                    mountPond() {
                        this.$nextTick(() => {
                            const el = this.$refs.fileInput;
                            if (el && !this.pond) {
                                this.pond = initUploadPond(el, {
                                    acceptedFileTypes: ['application/pdf','image/jpeg','image/png','image/webp','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                                    labelIdle: 'Drag & Drop your file or <span class=\'filepond--label-action\'>Browse</span>',
                                });
                            }
                        });
                    },
                    destroy() {
                        if (this.pond) { destroyUploadPond(this.pond); this.pond = null; }
                    },
                    init() {
                        @if($errors->any() && old('document_type'))
                            this.mountPond();
                        @endif
                    }
                }"
                x-on:close-modal-upload-document.window="destroy()"
                x-on:open-modal-upload-document.window="mountPond()"
            >
                <p class="mb-1.5 text-xs font-medium text-zinc-500">File <span class="text-red-400">*</span>
                    <span class="ml-1 font-normal">(PDF, JPG, PNG, WEBP, DOC, DOCX, XLS, XLSX — max 10 MB)</span>
                </p>
                <input type="file" name="file" x-ref="fileInput"
                       accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" />
                @error('file') <p class="mt-0.5 text-[11px] text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="relative">
                <textarea name="remarks" id="doc_remarks" rows="2" placeholder=" " class="{{ $textareaCls }}">{{ old('remarks') }}</textarea>
                <label for="doc_remarks" class="{{ $labelCls }}">Remarks</label>
                @error('remarks') <p class="mt-0.5 text-[11px] text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="arrow-up-tray">Upload</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-upload-document')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Document List --}}
    @if ($allDocs->isEmpty())
        <div class="grid grid-cols-5 gap-4">
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                <flux:icon.paper-clip class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">No documents yet</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">Upload purchase bills, warranty cards, insurance copies, or any other asset documents.</flux:text>
                <div class="mt-4">
                    <button type="button" x-on:click="$dispatch('open-modal-upload-document')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        Upload First Document
                    </button>
                </div>
            </div>
        </div>
    @else
        @php
            $partBillDocs    = $allDocs->where('document_type', 'service_part_bill');
            $otherTypedDocs  = $allDocs->where('document_type', '!=', 'service_part_bill');
            $grouped         = $otherTypedDocs->groupBy('document_type');
            $partBillsByPart = $partBillDocs->groupBy('documentable_id');
            $partModels      = \App\Models\AssetServicePart::whereIn('id', $partBillsByPart->keys())->get()->keyBy('id');
        @endphp

        <div class="grid grid-cols-5 gap-4">
            {{-- One card per service part that has documents --}}
            @foreach ($partBillsByPart as $partId => $docs)
                @php
                    $part      = $partModels->get($partId);
                    $cardLabel = $part ? ($part->part_name ?: 'Part #' . $partId) : 'Part Document';
                    $type      = 'service_part_bill_' . $partId;
                    $isImageType = false;
                @endphp
                <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <flux:icon.document-text class="size-4 text-zinc-400" />
                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $cardLabel }}</span>
                        <span class="ml-auto rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                            {{ $docs->count() }}
                        </span>
                    </div>

                    @php
                        $previewDocs = $docs->filter(fn($d) => $d->isImage() || str_contains($d->file_mime_type ?? '', 'pdf'))->values();
                        $otherDocs   = $docs->reject(fn($d) => $d->isImage() || str_contains($d->file_mime_type ?? '', 'pdf'));
                    @endphp

                    @if ($previewDocs->isNotEmpty())
                        @php $pondId = 'filepond-' . Str::slug($type); @endphp
                        <div id="pond-wrap-{{ $pondId }}" class="px-3 pt-3 pb-1"
                             x-data
                             x-init="
                                 const wrap     = document.getElementById('pond-wrap-{{ $pondId }}');
                                 const mount    = document.getElementById('pond-mount-{{ $pondId }}');
                                 const files    = {{ Js::from($previewDocs->map(fn($d) => ['source' => Storage::url($d->file_path), 'options' => ['type' => 'local']])) }};
                                 const fileMeta = {{ Js::from($previewDocs->map(fn($d) => ['src' => Storage::url($d->file_path), 'title' => $d->document_title ?: $d->file_original_name, 'isPdf' => str_contains($d->file_mime_type ?? '', 'pdf')])) }};
                                 let pond = null;
                                 const mountPond = () => {
                                     if (pond) { try { pond.destroy(); } catch(e) {} }
                                     mount.innerHTML = '';
                                     const input = document.createElement('input');
                                     input.type = 'file';
                                     mount.appendChild(input);
                                     pond = initDocImageViewer(input, files);
                                 };
                                 wrap.addEventListener('click', (e) => {
                                     if (wrap.offsetParent === null) return;
                                     const item = e.target.closest('.filepond--item');
                                     if (!item) return;
                                     const idx = Array.from(wrap.querySelectorAll('.filepond--item')).indexOf(item);
                                     if (fileMeta[idx]) $dispatch('docs-lightbox-open', fileMeta[idx]);
                                 });
                                 window.addEventListener('tab-visible', (e) => {
                                     if (e.detail === 'documents') setTimeout(mountPond, 50);
                                 });
                                 if (document.readyState === 'complete') { setTimeout(mountPond, 50); }
                                 else { window.addEventListener('load', () => setTimeout(mountPond, 50), { once: true }); }
                             ">
                            <div id="pond-mount-{{ $pondId }}"></div>
                        </div>
                        <div class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                            @foreach ($previewDocs as $doc)
                                <div class="flex items-center gap-2 px-4 py-2">
                                    <span class="min-w-0 flex-1 truncate text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $doc->document_title ?: $doc->file_original_name }}
                                    </span>
                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                       class="shrink-0 text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Download</a>
                                    <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                    <form method="POST" action="{{ route('assets.documents.destroy', [$asset, $doc]) }}"
                                          onsubmit="return confirm('Delete this document? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="Delete document" title="Delete document"
                                                class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                            <flux:icon.trash class="size-3" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($otherDocs->isNotEmpty())
                        <div class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                            @foreach ($otherDocs as $doc)
                                <div class="flex items-start gap-3 px-4 py-3">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        @if (str_contains($doc->file_mime_type ?? '', 'pdf'))
                                            <flux:icon.document class="size-4 text-red-400" />
                                        @else
                                            <flux:icon.document-text class="size-4 text-zinc-400" />
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $doc->document_title ?: $doc->file_original_name }}
                                        </p>
                                        <p class="text-[11px] text-zinc-500">
                                            {{ number_format($doc->file_size / 1024, 0) }} KB
                                            · {{ $doc->created_at->format('d M Y') }}
                                            @if ($doc->uploader) · {{ $doc->uploader->name }} @endif
                                        </p>
                                        @if ($doc->remarks)
                                            <p class="mt-0.5 text-[11px] text-zinc-500 italic">{{ $doc->remarks }}</p>
                                        @endif
                                        <div class="mt-2 flex items-center gap-2">
                                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                               class="text-xs text-accent hover:underline">View</a>
                                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                            <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                               class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Download</a>
                                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                            <form method="POST" action="{{ route('assets.documents.destroy', [$asset, $doc]) }}"
                                                  onsubmit="return confirm('Delete this document? This cannot be undone.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" aria-label="Delete document" title="Delete document"
                                                        class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                                    <flux:icon.trash class="size-3" />
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- All other document types, one card per type --}}
            @foreach ($grouped as $type => $docs)
                @php
                    $isImageType = in_array($type, ['warranty_activation_image', 'extended_warranty_image', 'asset_photo', 'puc_copy', 'rc_copy']);
                @endphp
                <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                    {{-- Card header --}}
                    <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-800/40">
                        @if ($isImageType)
                            <flux:icon.photo class="size-4 text-zinc-400" />
                        @else
                            <flux:icon.document-text class="size-4 text-zinc-400" />
                        @endif
                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                            {{ $docTypes[$type] ?? ucwords(str_replace('_', ' ', $type)) }}
                        </span>
                        <span class="ml-auto rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                            {{ $docs->count() }}
                        </span>
                    </div>

                    {{-- Files --}}
                    @php
                        $previewDocs = $docs->filter(fn($d) => $d->isImage() || str_contains($d->file_mime_type ?? '', 'pdf'))->values();
                        $otherDocs   = $docs->reject(fn($d) => $d->isImage() || str_contains($d->file_mime_type ?? '', 'pdf'));
                    @endphp

                    {{-- Image + PDF documents: FilePond thumbnail strip --}}
                    @if ($previewDocs->isNotEmpty())
                        @php $pondId = 'filepond-' . Str::slug($type); @endphp
                        <div id="pond-wrap-{{ $pondId }}" class="px-3 pt-3 pb-1"
                             x-data
                             x-init="
                                 const wrap     = document.getElementById('pond-wrap-{{ $pondId }}');
                                 const mount    = document.getElementById('pond-mount-{{ $pondId }}');
                                 const files    = {{ Js::from($previewDocs->map(fn($d) => ['source' => Storage::url($d->file_path), 'options' => ['type' => 'local']])) }};
                                 const fileMeta = {{ Js::from($previewDocs->map(fn($d) => ['src' => Storage::url($d->file_path), 'title' => $d->document_title ?: $d->file_original_name, 'isPdf' => str_contains($d->file_mime_type ?? '', 'pdf')])) }};
                                 let pond = null;
                                 const mountPond = () => {
                                     if (pond) { try { pond.destroy(); } catch(e) {} }
                                     mount.innerHTML = '';
                                     const input = document.createElement('input');
                                     input.type = 'file';
                                     mount.appendChild(input);
                                     pond = initDocImageViewer(input, files);
                                 };
                                 wrap.addEventListener('click', (e) => {
                                     if (wrap.offsetParent === null) return;
                                     const item = e.target.closest('.filepond--item');
                                     if (!item) return;
                                     const idx = Array.from(wrap.querySelectorAll('.filepond--item')).indexOf(item);
                                     if (fileMeta[idx]) $dispatch('docs-lightbox-open', fileMeta[idx]);
                                 });
                                 window.addEventListener('tab-visible', (e) => {
                                     if (e.detail === 'documents') setTimeout(mountPond, 50);
                                 });
                                 if (document.readyState === 'complete') { setTimeout(mountPond, 50); }
                                 else { window.addEventListener('load', () => setTimeout(mountPond, 50), { once: true }); }
                             ">
                            <div id="pond-mount-{{ $pondId }}"></div>
                        </div>

                        {{-- Download / Delete rows for preview docs --}}
                        <div class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                            @foreach ($previewDocs as $doc)
                                <div class="flex items-center gap-2 px-4 py-2">
                                    <span class="min-w-0 flex-1 truncate text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $doc->document_title ?: $doc->file_original_name }}
                                    </span>
                                    <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                       class="shrink-0 text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Download</a>
                                    <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                    <form method="POST" action="{{ route('assets.documents.destroy', [$asset, $doc]) }}"
                                          onsubmit="return confirm('Delete this document? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" aria-label="Delete document" title="Delete document"
                                                class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                            <flux:icon.trash class="size-3" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Non-image documents: original list markup --}}
                    @if ($otherDocs->isNotEmpty())
                        <div class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                            @foreach ($otherDocs as $doc)
                                <div class="flex items-start gap-3 px-4 py-3">
                                    <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        @if (str_contains($doc->file_mime_type ?? '', 'pdf'))
                                            <flux:icon.document class="size-4 text-red-400" />
                                        @else
                                            <flux:icon.document-text class="size-4 text-zinc-400" />
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $doc->document_title ?: $doc->file_original_name }}
                                        </p>
                                        <p class="text-[11px] text-zinc-500">
                                            {{ number_format($doc->file_size / 1024, 0) }} KB
                                            · {{ $doc->created_at->format('d M Y') }}
                                            @if ($doc->uploader)
                                                · {{ $doc->uploader->name }}
                                            @endif
                                        </p>
                                        @if ($doc->remarks)
                                            <p class="mt-0.5 text-[11px] text-zinc-500 italic">{{ $doc->remarks }}</p>
                                        @endif
                                        <div class="mt-2 flex items-center gap-2">
                                            <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                                               class="text-xs text-accent hover:underline">View</a>
                                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                            <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_original_name }}"
                                               class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">Download</a>
                                            <span class="text-zinc-300 dark:text-zinc-600">·</span>
                                            <form method="POST"
                                                  action="{{ route('assets.documents.destroy', [$asset, $doc]) }}"
                                                  onsubmit="return confirm('Delete this document? This cannot be undone.')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                        aria-label="Delete document"
                                                        title="Delete document"
                                                        class="inline-flex size-5 items-center justify-center rounded border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                                    <flux:icon.trash class="size-3" />
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Upload another placeholder --}}
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                <flux:icon.paper-clip class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">Add Document</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">Upload another document for this asset.</flux:text>
                <div class="mt-4">
                    <button type="button" x-on:click="$dispatch('open-modal-upload-document')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        Upload Document
                    </button>
                </div>
            </div>
        </div>

        <p class="text-xs text-zinc-600">
            Showing all {{ $allDocs->count() }} {{ Str::plural('document', $allDocs->count()) }} for this asset.
            Warranty and extended warranty documents are also accessible from their respective tabs.
        </p>
    @endif

    {{-- Shared image lightbox (z-[60] sits above upload modal z-50) --}}
    <div x-data="docLightbox()"
         x-on:docs-lightbox-open.window="show($event.detail.src, $event.detail.title, $event.detail.isPdf)"
         x-show="open"
         x-cloak
         x-on:keydown.escape.window="if (open) close()"
         class="fixed inset-0 z-60 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/85" x-on:click="close()"></div>
        <div class="relative z-10 flex max-w-5xl w-full flex-col rounded-xl overflow-hidden shadow-2xl" x-on:click.stop>
            {{-- Header bar with title + close --}}
            <div class="flex items-center justify-between bg-zinc-900 px-4 py-2 shrink-0">
                <span x-text="title" class="truncate text-sm text-zinc-300"></span>
                <button type="button" x-on:click="close()"
                    class="ml-4 flex shrink-0 items-center gap-1 text-sm text-zinc-400 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                    </svg>
                    Close
                </button>
            </div>
            {{-- Content --}}
            <template x-if="!isPdf">
                <div class="flex items-center justify-center bg-zinc-950 w-full" style="height:82vh;">
                    <img :src="src" :alt="title"
                         class="max-h-full max-w-full object-contain rounded-lg shadow-xl">
                </div>
            </template>
            <template x-if="isPdf">
                <object :data="src" type="application/pdf"
                        class="w-full bg-white" style="height:82vh;">
                    <p class="text-center p-4">
                        <a :href="src" target="_blank" class="underline text-accent">Open PDF in new tab</a>
                    </p>
                </object>
            </template>
        </div>
    </div>

</div>
