<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700;800&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css" id="flatpickr-dark-theme" disabled>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    function syncFlatpickrTheme() {
        var darkSheet = document.getElementById('flatpickr-dark-theme');
        if (darkSheet) darkSheet.disabled = !document.documentElement.classList.contains('dark');
    }

    function initDatePickers(root) {
        (root || document).querySelectorAll('[data-datepicker]:not([data-fp-init])').forEach(function (el) {
            el.setAttribute('data-fp-init', '1');
            var opts = { dateFormat: 'Y-m-d', allowInput: true, disableMobile: true };
            if (el.dataset.minDate) opts.minDate = el.dataset.minDate;
            if (el.dataset.maxDate) opts.maxDate = el.dataset.maxDate;
            flatpickr(el, opts);
        });
    }

    function setupObservers() {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                var el = mutation.target;
                if (mutation.type === 'attributes' && el.style && el.style.display !== 'none') {
                    initDatePickers(el);
                }
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) initDatePickers(node);
                    });
                }
            });
        }).observe(document.body, {
            attributes: true,
            attributeFilter: ['style'],
            childList: true,
            subtree: true,
        });

        new MutationObserver(syncFlatpickrTheme).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    function bootDatepickers() {
        syncFlatpickrTheme();
        initDatePickers();
        setupObservers();
    }

    document.addEventListener('alpine:initialized', bootDatepickers);
    document.addEventListener('livewire:navigated', bootDatepickers);
</script>
