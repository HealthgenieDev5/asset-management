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

    document.addEventListener('alpine:initialized', syncFlatpickrTheme);
    document.addEventListener('livewire:navigated', syncFlatpickrTheme);

    new MutationObserver(syncFlatpickrTheme).observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
</script>
