import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginPdfPreview from 'filepond-plugin-pdf-preview';

FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginPdfPreview);

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
        server: {
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
