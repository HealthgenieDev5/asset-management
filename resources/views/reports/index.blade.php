<x-layouts::app title="Reports">
    <div class="mb-6">
        <flux:heading size="xl" class="font-extrabold">Reports <span class="text-accent">Hub</span></flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">Browse all reports. Click any card to open the report with filters.</flux:text>
    </div>

    {{-- Asset Register --}}
    <div class="mb-8">
        <h2 class="mb-3 text-xs font-bold uppercase tracking-widest text-zinc-500">Asset Register</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <x-reports.card href="{{ route('reports.asset-register') }}" title="Asset Register" description="Complete list of all assets with status and details." icon="table-cells" />
            <x-reports.card href="{{ route('reports.purchase-bills') }}" title="Purchase Bills" description="Assets and their purchase/invoice records." icon="receipt-percent" />
            <x-reports.card href="{{ route('reports.vehicle-depreciation') }}" title="Vehicle Depreciation" description="Original and current book value for vehicles." icon="arrow-trending-down" />
        </div>
    </div>

    {{-- Expiry & Compliance --}}
    <div class="mb-8">
        <h2 class="mb-3 text-xs font-bold uppercase tracking-widest text-zinc-500">Warranty & Contracts</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <x-reports.card href="{{ route('reports.warranty-expiry') }}" title="Warranty Expiry" description="Manufacturer warranty expiry dates." icon="shield-check" color="yellow" />
            <x-reports.card href="{{ route('reports.extended-warranty-expiry') }}" title="Extended Warranty" description="Extended warranty contract expiry." icon="shield-exclamation" color="yellow" />
            <x-reports.card href="{{ route('reports.amc-expiry') }}" title="AMC Expiry" description="Annual maintenance contract expiry dates." icon="wrench-screwdriver" color="yellow" />
            <x-reports.card href="{{ route('reports.insurance-expiry') }}" title="Insurance Expiry" description="Insurance policy expiry and premium details." icon="building-library" color="yellow" />
        </div>
    </div>

    {{-- Vehicle Compliance --}}
    <div class="mb-8">
        <h2 class="mb-3 text-xs font-bold uppercase tracking-widest text-zinc-500">Vehicle Compliance</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <x-reports.card href="{{ route('reports.puc-expiry') }}" title="PUC / Emission" description="Pollution under control certificate expiry." icon="beaker" color="red" />
            <x-reports.card href="{{ route('reports.fitness-expiry') }}" title="Fitness Certificate" description="Vehicle fitness certificate expiry." icon="check-badge" color="red" />
            <x-reports.card href="{{ route('reports.road-tax-expiry') }}" title="Road Tax" description="Road tax renewal dates." icon="map" color="red" />
        </div>
    </div>

    {{-- Service & Maintenance --}}
    <div class="mb-8">
        <h2 class="mb-3 text-xs font-bold uppercase tracking-widest text-zinc-500">Service & Maintenance</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <x-reports.card href="{{ route('reports.inspection-due') }}" title="Inspection Due" description="Assets with upcoming or overdue inspections." icon="magnifying-glass" color="purple" />
            <x-reports.card href="{{ route('reports.certification-expiry') }}" title="Certification Expiry" description="Service certifications from inspection records." icon="academic-cap" color="purple" />
            <x-reports.card href="{{ route('reports.service-due') }}" title="Service Due" description="Assets with upcoming or overdue next service." icon="clock" color="purple" />
            <x-reports.card href="{{ route('reports.service-history') }}" title="Service History" description="Complete service, inspection, and repair history." icon="clipboard-document-list" color="cyan" />
            <x-reports.card href="{{ route('reports.maintenance-cost') }}" title="Maintenance Cost" description="Labour and parts cost analysis by asset." icon="currency-rupee" color="cyan" />
        </div>
    </div>
</x-layouts::app>
