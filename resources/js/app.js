import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginPdfPreview from 'filepond-plugin-pdf-preview';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginPdfPreview);

toastr.options = {
    positionClass:   'toast-top-right',
    timeOut:         3000,
    closeButton:     true,
    progressBar:     true,
    newestOnTop:     true,
    preventDuplicates: true,
};

window.toastr = toastr;

window.destroyDocImageViewer = function (pond) {
    if (!pond) return;
    const root = pond.element;
    if (root && root.parentNode) {
        root.parentNode.removeChild(root);
    }
};

window.destroyUploadPond = function (pond) {
    if (!pond) return;
    const root = pond.element;
    if (root && root.parentNode) {
        root.parentNode.removeChild(root);
    }
};

window.initUploadPond = function (inputEl, options = {}) {
    const pond = FilePond.create(inputEl, {
        files:              options.files ?? undefined,
        allowMultiple:      false,
        allowProcess:       false,
        allowRevert:        true,
        allowRemove:        true,
        allowBrowse:        true,
        allowDrop:          true,
        allowPaste:         true,
        storeAsFile:        true,
        credits:            false,
        labelIdle:          options.labelIdle ?? 'Drag & Drop your file or <span class="filepond--label-action">Browse</span>',
        imagePreviewHeight: 220,
        allowPdfPreview:    true,
        pdfPreviewHeight:   220,
        pdfComponentExtraParams: 'toolbar=0&navpanes=0&scrollbar=0',
        stylePanelAspectRatio: null,
        styleItemPanelAspectRatio: null,
        acceptedFileTypes: options.acceptedFileTypes ?? undefined,
        onaddfile: options.onaddfile ?? undefined,
        onremovefile: options.onremovefile ?? undefined,
        beforeRemoveFile: options.beforeRemoveFile ?? (() => window.confirm('Remove this file?')),
        server: {
            remove: options.deleteUrl
                ? (source, load, error) => {
                    const data = new FormData();
                    data.append('_token',  options.csrfToken);
                    data.append('_method', 'DELETE');
                    fetch(options.deleteUrl, {
                        method:  'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body:    data,
                    }).then(r => {
                        if (r.ok) {
                            load();
                            toastr.success('Purchase bill deleted.');
                            const wrap = document.getElementById('overview-bill-actions');
                            if (wrap) wrap.style.display = 'none';
                        } else {
                            error('Failed to delete file.');
                            toastr.error('Failed to delete file.');
                        }
                    });
                }
                : null,
            load: (source, load, error, progress, abort) => {
                fetch(source)
                    .then(r => r.blob())
                    .then(blob => {
                        const meta = options.fileMetaBySource?.[source];
                        load(meta ? new File([blob], meta.name, { type: blob.type }) : blob);
                    })
                    .catch(error);
                return { abort };
            },
        },
    });
    pond.element.classList.add('fp-upload');
    return pond;
};

window.initDocImageViewer = function (inputEl, files) {
    return FilePond.create(inputEl, {
        files,
        allowProcess:  false,
        allowRevert:   false,
        allowRemove:   false,
        allowBrowse:   false,
        allowDrop:     false,
        allowPaste:    false,
        allowMultiple: true,
        credits:            false,
        labelIdle:          '',
        imagePreviewHeight: 320,
        allowPdfPreview:    true,
        pdfPreviewHeight:   320,
        pdfComponentExtraParams: 'toolbar=0&navpanes=0&scrollbar=0',
        stylePanelAspectRatio: null,
        styleItemPanelAspectRatio: null,
        server: {
            load: (source, load, error, progress, abort) => {
                fetch(source)
                    .then(r => r.blob())
                    .then(load)
                    .catch(error);
                return { abort };
            },
        },
    });
};

document.addEventListener('alpine:init', () => {
    Alpine.data('inlineEdit', () => ({
        editing: false,
        saving:  false,

        save(form) {
            if (this.saving) return;
            this.saving = true;
            const data = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: data,
            }).then(r => {
                if (r.ok) {
                    const input = form.querySelector('[name="value"]');
                    const span  = form.closest('dd, div, p')?.parentElement?.querySelector('[data-display]') ?? form.closest('dd, div')?.querySelector('[data-display]');
                    if (input && span) {
                        const newVal = input.tagName === 'SELECT'
                            ? input.options[input.selectedIndex].text
                            : (input.value || '—');
                        span.textContent = newVal;
                        this.editing = false;
                        toastr.success('Updated successfully');
                    } else {
                        // Complex display (badge, colors) — reload to reflect correctly
                        window.location.reload();
                    }
                } else {
                    toastr.error('Failed to save. Please try again.');
                }
            }).finally(() => { this.saving = false; });
        },
    }));
});

document.addEventListener('alpine:init', () => {
    Alpine.data('reminderDaysPicker', (initial = []) => ({
        days:     initial.filter(d => d > 0),
        inputVal: null,
        add() {
            const v = parseInt(this.inputVal);
            if (v > 0 && !this.days.includes(v)) {
                this.days.push(v);
                this.days.sort((a, b) => b - a);
            }
            this.inputVal = null;
        },
        addPreset(v) {
            if (!this.days.includes(v)) {
                this.days.push(v);
                this.days.sort((a, b) => b - a);
            }
        },
        remove(i) {
            this.days.splice(i, 1);
        },
    }));

    Alpine.data('docLightbox', () => ({
        open:  false,
        src:   '',
        title: '',
        isPdf: false,
        show(src, title, isPdf = false) {
            this.src   = src;
            this.title = title;
            this.isPdf = isPdf;
            this.open  = true;
            document.body.classList.add('overflow-hidden');
        },
        close() {
            this.open  = false;
            this.src   = '';
            this.isPdf = false;
            document.body.classList.remove('overflow-hidden');
        },
    }));
});
