import * as FilePond from "filepond";
import FilePondPluginImagePreview from "filepond-plugin-image-preview";
import FilePondPluginImageExifOrientation from "filepond-plugin-image-exif-orientation";
import FilePondPluginPdfPreview from "filepond-plugin-pdf-preview";
import FilePondPluginFileValidateType from "filepond-plugin-file-validate-type";
import toastr from "toastr";
import "toastr/build/toastr.min.css";
import Swal from "sweetalert2";

window.confirmDelete = function (form, message) {
    const dark = document.documentElement.classList.contains("dark");
    Swal.fire({
        title: "Are you sure?",
        text: message || "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#52525b",
        background: dark ? "#18181b" : "#ffffff",
        color: dark ? "#f4f4f5" : "#18181b",
    }).then(function (result) {
        if (result.isConfirmed) form.submit();
    });
};

FilePond.registerPlugin(
    FilePondPluginImageExifOrientation,
    FilePondPluginImagePreview,
    FilePondPluginPdfPreview,
    FilePondPluginFileValidateType,
);

toastr.options = {
    positionClass: "toast-top-right",
    timeOut: 3000,
    closeButton: true,
    progressBar: true,
    newestOnTop: true,
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
    // pond may be a real FilePond instance OR the raw inputEl (when initUploadPond
    // is still pending its double-rAF). Resolve the FilePond root in either case.
    const instance =
        pond && typeof pond.destroy === "function" ? pond : pond._pond;
    if (instance && typeof instance.destroy === "function") {
        try {
            instance.destroy();
        } catch (e) {}
    } else {
        // FilePond hasn't mounted yet — remove whatever DOM it created, if any.
        const root = (instance && instance.element) ?? pond.element;
        if (root && root.parentNode) root.parentNode.removeChild(root);
    }
};

window.initUploadPond = function (inputEl, options = {}) {
    if (!inputEl) return null;
    // Mark immediately so duplicate-init guards in callers work synchronously.
    inputEl._pond = true;

    const useServerMode = !!options.server;

    // Shared load handler — used in both non-server mode and merged into server mode.
    const loadHandler = (source, load, error, progress, abort) => {
        fetch(source)
            .then((r) => r.blob())
            .then((blob) => {
                const meta = options.fileMetaBySource?.[source];
                load(
                    meta
                        ? new File([blob], meta.name, { type: blob.type })
                        : blob,
                );
            })
            .catch(error);
        return { abort };
    };

    // Shared remove handler — fires DELETE to deleteUrl for pre-existing (local) files.
    const removeHandler = options.deleteUrl
        ? (source, load, error) => {
              const data = new FormData();
              data.append("_token", options.csrfToken);
              data.append("_method", "DELETE");
              fetch(options.deleteUrl, {
                  method: "POST",
                  headers: {
                      "X-Requested-With": "XMLHttpRequest",
                      Accept: "application/json",
                  },
                  body: data,
              })
                  .then((r) => {
                      if (r.ok) {
                          load();
                          toastr.success(
                              options.deleteSuccessMessage ??
                                  "Document deleted.",
                          );
                          const wrap = document.getElementById(
                              "overview-bill-actions",
                          );
                          if (wrap) wrap.style.display = "none";
                      } else {
                          error("Failed to delete file.");
                          toastr.error("Failed to delete file.");
                      }
                  })
                  .catch(() => {
                      error("Network error.");
                      toastr.error("Failed to delete file.");
                  });
          }
        : null;

    // Revert handler — fires DELETE to revertUrl (the just-uploaded doc's destroy URL).
    // In server mode the server.process onload callback receives the doc ID; callers
    // must supply revertUrlTemplate as a function (id) => url so we can build the URL.
    // The controller reads the doc ID from the raw request body via $request->getContent(),
    // so we must send the ID as plain text — not FormData.
    const revertHandler = options.revertUrlTemplate
        ? (uniqueFileId, load, error) => {
              const url = options.revertUrlTemplate(uniqueFileId);
              if (!url) {
                  load();
                  return;
              }
              fetch(url, {
                  method: "DELETE",
                  headers: {
                      "X-CSRF-TOKEN": options.csrfToken,
                      "X-Requested-With": "XMLHttpRequest",
                      "Content-Type": "text/plain",
                      Accept: "application/json",
                  },
                  body: String(uniqueFileId),
              })
                  .then((r) => {
                      if (r.ok) {
                          load();
                          toastr.success(
                              options.revertSuccessMessage ?? "Document removed.",
                          );
                      } else {
                          error("Failed to revert upload.");
                          toastr.error("Failed to revert upload.");
                      }
                  })
                  .catch(() => {
                      error("Network error.");
                      toastr.error("Failed to revert upload.");
                  });
          }
        : null;

    // Build the final server config.
    // In server mode: merge load + remove + revert into caller's server object.
    // In non-server (storeAsFile) mode: supply only load + remove.
    const serverConfig = useServerMode
        ? {
              ...options.server,
              load: loadHandler,
              remove: removeHandler,
              revert: revertHandler,
          }
        : {
              load: loadHandler,
              remove: removeHandler,
          };

    // Double-rAF: defer past Alpine's sync init tick AND the browser's first paint,
    // so FilePond CSS is fully applied before it measures the container.
    requestAnimationFrame(() =>
        requestAnimationFrame(() => {
            if (!inputEl.isConnected) return;
            const pond = FilePond.create(inputEl, {
                files: options.files ?? undefined,
                allowMultiple: false,
                allowProcess: useServerMode,
                allowRevert: true,
                allowRemove: true,
                allowBrowse: true,
                allowDrop: true,
                allowPaste: true,
                storeAsFile: !useServerMode,
                name: useServerMode ? "file" : undefined,
                credits: false,
                labelIdle:
                    options.labelIdle ??
                    'Drag & Drop your file or <span class="filepond--label-action">Browse</span>',
                imagePreviewHeight: 220,
                allowPdfPreview: true,
                pdfPreviewHeight: 220,
                pdfComponentExtraParams: "toolbar=0&navpanes=0&scrollbar=0",
                stylePanelAspectRatio: null,
                styleItemPanelAspectRatio: null,
                acceptedFileTypes: options.acceptedFileTypes ?? undefined,
                fileValidateTypeDetectType: (source, type) =>
                    new Promise((resolve) => {
                        if (type) return resolve(type);
                        const ext = (source.name || "")
                            .split(".")
                            .pop()
                            .toLowerCase();
                        const map = {
                            pdf: "application/pdf",
                            jpg: "image/jpeg",
                            jpeg: "image/jpeg",
                            png: "image/png",
                            webp: "image/webp",
                            gif: "image/gif",
                            mp4: "video/mp4",
                            mov: "video/quicktime",
                            avi: "video/x-msvideo",
                            webm: "video/webm",
                            doc: "application/msword",
                            docx: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                            xls: "application/vnd.ms-excel",
                            xlsx: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                        };
                        resolve(map[ext] || type);
                    }),
                onaddfile: options.onaddfile ?? undefined,
                onremovefile: options.onremovefile ?? undefined,
                beforeRemoveFile:
                    options.beforeRemoveFile ??
                    (() => {
                        const dark =
                            document.documentElement.classList.contains("dark");
                        return Swal.fire({
                            title: "Remove file?",
                            text: "This will delete the file permanently.",
                            icon: "warning",
                            showCancelButton: true,
                            confirmButtonText: "Yes, remove it",
                            cancelButtonText: "Cancel",
                            confirmButtonColor: "#ef4444",
                            cancelButtonColor: "#52525b",
                            background: dark ? "#18181b" : "#ffffff",
                            color: dark ? "#f4f4f5" : "#18181b",
                        }).then((r) => r.isConfirmed);
                    }),
                server: serverConfig,
            });
            pond.element.classList.add("fp-upload");
            inputEl._pond = pond;

            // Click on image preview thumbnail → fullscreen <dialog> lightbox.
            pond.element.addEventListener("click", (e) => {
                if (!e.target.closest(".filepond--image-preview-wrapper"))
                    return;
                const item = e.target.closest(".filepond--item");
                if (!item) return;

                const fileId = item.dataset.filepond;
                const files = pond.getFiles();
                const fp = fileId
                    ? files.find((f) => f.id === fileId)
                    : files[0];
                if (!fp) return;

                // Resolve URL: local files have a string source, new files have a File object.
                let src = "";
                if (fp.origin === 3 && typeof fp.source === "string") {
                    src = fp.source;
                } else if (
                    fp.file instanceof File &&
                    fp.fileType &&
                    fp.fileType.startsWith("image/")
                ) {
                    src = URL.createObjectURL(fp.file);
                }
                if (!src) return;

                // Lazily create one shared <dialog> for the whole page.
                let dlg = document.getElementById("fp-preview-dialog");
                if (!dlg) {
                    dlg = document.createElement("dialog");
                    dlg.id = "fp-preview-dialog";
                    dlg.style.cssText =
                        "padding:0;border:none;background:transparent;max-width:100vw;max-height:100vh;width:100vw;height:100vh;outline:none;";
                    dlg.innerHTML = `
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.88);cursor:zoom-out;">
                        <img id="fp-preview-img" src="" alt="" style="max-width:92vw;max-height:92vh;object-fit:contain;border-radius:8px;box-shadow:0 8px 48px rgba(0,0,0,0.7);">
                        <button id="fp-preview-close" aria-label="Close" style="position:fixed;top:16px;right:20px;background:rgba(255,255,255,0.12);border:none;color:#fff;font-size:24px;line-height:1;padding:6px 10px;border-radius:6px;cursor:pointer;">&times;</button>
                    </div>`;
                    document.body.appendChild(dlg);
                    dlg.addEventListener("click", (ev) => {
                        if (
                            ev.target === dlg ||
                            (ev.target.closest("div") ===
                                dlg.firstElementChild &&
                                ev.target.tagName !== "IMG") ||
                            ev.target.id === "fp-preview-close"
                        ) {
                            dlg.close();
                        }
                    });
                    dlg.addEventListener("close", () => {
                        const img = dlg.querySelector("#fp-preview-img");
                        if (img.src.startsWith("blob:"))
                            URL.revokeObjectURL(img.src);
                        img.src = "";
                    });
                }

                dlg.querySelector("#fp-preview-img").src = src;
                dlg.showModal();
            });
        }),
    );

    return inputEl;
};

window.initDocImageViewer = function (inputEl, files) {
    return FilePond.create(inputEl, {
        files,
        allowProcess: false,
        allowRevert: false,
        allowRemove: false,
        allowBrowse: false,
        allowDrop: false,
        allowPaste: false,
        allowMultiple: true,
        credits: false,
        labelIdle: "",
        imagePreviewHeight: 320,
        allowPdfPreview: true,
        pdfPreviewHeight: 320,
        pdfComponentExtraParams: "toolbar=0&navpanes=0&scrollbar=0",
        stylePanelAspectRatio: null,
        styleItemPanelAspectRatio: null,
        server: {
            load: (source, load, error, progress, abort) => {
                fetch(source)
                    .then((r) => r.blob())
                    .then(load)
                    .catch(error);
                return { abort };
            },
        },
    });
};

document.addEventListener("alpine:init", () => {
    Alpine.data("inlineEdit", () => ({
        editing: false,
        saving: false,

        save(form) {
            if (this.saving) return;
            this.saving = true;
            const data = new FormData(form);
            fetch(form.action, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
                body: data,
            })
                .then((r) => {
                    if (r.ok) {
                        const input = form.querySelector('[name="value"]');
                        const span =
                            form
                                .closest("dd, div, p")
                                ?.parentElement?.querySelector(
                                    "[data-display]",
                                ) ??
                            form
                                .closest("dd, div")
                                ?.querySelector("[data-display]");
                        if (input && span) {
                            const newVal =
                                input.tagName === "SELECT"
                                    ? input.options[input.selectedIndex].text
                                    : input.value || "—";
                            span.textContent = newVal;
                            this.editing = false;
                            toastr.success("Updated successfully");
                        } else {
                            // Complex display (badge, colors) — reload to reflect correctly
                            window.location.reload();
                        }
                    } else {
                        toastr.error("Failed to save. Please try again.");
                    }
                })
                .finally(() => {
                    this.saving = false;
                });
        },
    }));
});

document.addEventListener("alpine:init", () => {
    Alpine.data("reminderDaysPicker", (initial = []) => ({
        days: initial.filter((d) => d > 0),
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

    Alpine.data("docLightbox", () => ({
        open: false,
        src: "",
        title: "",
        isPdf: false,
        show(src, title, isPdf = false) {
            this.src = src;
            this.title = title;
            this.isPdf = isPdf;
            this.open = true;
            document.body.classList.add("overflow-hidden");
        },
        close() {
            this.open = false;
            this.src = "";
            this.isPdf = false;
            document.body.classList.remove("overflow-hidden");
        },
    }));
});
