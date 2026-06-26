<?php

use App\Http\Controllers\AssetAmcContractController;
use App\Http\Controllers\AssetAuditLogController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetComplaintCommentController;
use App\Http\Controllers\AssetComplaintController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDocumentController;
use App\Http\Controllers\AssetExtendedWarrantyController;
use App\Http\Controllers\AssetInsurancePolicyController;
use App\Http\Controllers\AssetMaintenanceScheduleController;
use App\Http\Controllers\AssetMeterLogController;
use App\Http\Controllers\AssetReminderController;
use App\Http\Controllers\AssetServiceController;
use App\Http\Controllers\AssetSmartReminderController;
use App\Http\Controllers\AssetServicePartController;
use App\Http\Controllers\AssetSubcategoryController;
use App\Http\Controllers\AssetWarrantyController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ComplaintEscalationRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VendorController;
use App\Models\AssetSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// API-style helper for dependent dropdowns (auth required)
Route::middleware(['auth'])->get('/api/subcategories', function (Request $request) {
    $subs = AssetSubcategory::where('asset_category_id', $request->category_id)
        ->where('status', 'active')
        ->orderBy('name')
        ->get(['id', 'name']);

    return response()->json($subs);
});

Route::middleware(['auth'])->post('/api/locations', function (Request $request) {
    $request->validate(['name' => ['required', 'string', 'max:255']]);
    $loc = \App\Models\Location::firstOrCreate(
        ['name' => trim($request->name)],
        ['is_active' => true, 'created_by' => auth()->id()]
    );
    return response()->json($loc);
});

Route::middleware(['auth'])->post('/api/departments', function (Request $request) {
    $request->validate(['name' => ['required', 'string', 'max:255']]);
    $dept = \App\Models\Department::firstOrCreate(
        ['name' => trim($request->name)],
        ['is_active' => true, 'created_by' => auth()->id()]
    );
    return response()->json($dept);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('asset-categories', AssetCategoryController::class);
    Route::resource('asset-subcategories', AssetSubcategoryController::class);
    Route::get('assets/export', [AssetController::class, 'export'])->name('assets.export');
    Route::resource('assets', AssetController::class);
    Route::patch('assets/{asset}/field', [AssetController::class, 'patchField'])->name('assets.patch-field');
    Route::get('assets/{asset}/history', [AssetAuditLogController::class, 'index'])->name('assets.history');
    Route::post('assets/{asset}/documents', [AssetDocumentController::class, 'store'])->name('assets.documents.store');
    Route::delete('assets/{asset}/documents/{document}', [AssetDocumentController::class, 'destroy'])->name('assets.documents.destroy');

    // AMC Contracts (nested under asset)
    Route::post('assets/{asset}/amc', [AssetAmcContractController::class, 'store'])->name('assets.amc.store');
    Route::put('assets/{asset}/amc/{amc}', [AssetAmcContractController::class, 'update'])->name('assets.amc.update');
    Route::patch('assets/{asset}/amc/{amc}/field', [AssetAmcContractController::class, 'patchField'])->name('assets.amc.patch-field');
    Route::post('assets/{asset}/amc/{amc}/documents', [AssetAmcContractController::class, 'storeDocument'])->name('assets.amc.documents.store');
    Route::delete('assets/{asset}/amc/documents/revert', [AssetAmcContractController::class, 'revertDocument'])->name('assets.amc.documents.revert');
    Route::delete('assets/{asset}/amc/{amc}', [AssetAmcContractController::class, 'destroy'])->name('assets.amc.destroy');
    Route::delete('assets/{asset}/amc/documents/{document}', [AssetAmcContractController::class, 'destroyDocument'])->name('assets.amc.documents.destroy');

    // Unified Warranty Entries
    Route::post('assets/{asset}/warranties',                          [AssetWarrantyController::class, 'store'])->name('assets.warranties.store');
    Route::put('assets/{asset}/warranties/{warranty}',                [AssetWarrantyController::class, 'update'])->name('assets.warranties.update');
    Route::delete('assets/{asset}/warranties/{warranty}',             [AssetWarrantyController::class, 'destroy'])->name('assets.warranties.destroy');
    Route::patch('assets/{asset}/warranties/{warranty}/field',        [AssetWarrantyController::class, 'patchField'])->name('assets.warranties.patch-field');
    Route::patch('assets/{asset}/warranties/{warranty}/dispose',      [AssetWarrantyController::class, 'dispose'])->name('assets.warranties.dispose');
    Route::post('assets/{asset}/warranties/{warranty}/documents',      [AssetWarrantyController::class, 'storeDocument'])->name('assets.warranties.documents.store');
    Route::delete('assets/{asset}/warranties/documents/revert',       [AssetWarrantyController::class, 'revertDocument'])->name('assets.warranties.documents.revert');
    Route::delete('assets/{asset}/warranties/documents/{document}',   [AssetWarrantyController::class, 'destroyDocument'])->name('assets.warranties.documents.destroy');


    // Insurance Policies (nested under asset)
    Route::post('assets/{asset}/insurance', [AssetInsurancePolicyController::class, 'store'])->name('assets.insurance.store');
    Route::put('assets/{asset}/insurance/{insurance}', [AssetInsurancePolicyController::class, 'update'])->name('assets.insurance.update');
    Route::patch('assets/{asset}/insurance/{insurance}/field', [AssetInsurancePolicyController::class, 'patchField'])->name('assets.insurance.patch-field');
    Route::post('assets/{asset}/insurance/{insurance}/documents', [AssetInsurancePolicyController::class, 'storeDocument'])->name('assets.insurance.documents.store');
    Route::delete('assets/{asset}/insurance/documents/revert', [AssetInsurancePolicyController::class, 'revertDocument'])->name('assets.insurance.documents.revert');
    Route::delete('assets/{asset}/insurance/{insurance}', [AssetInsurancePolicyController::class, 'destroy'])->name('assets.insurance.destroy');
    Route::delete('assets/{asset}/insurance/documents/{document}', [AssetInsurancePolicyController::class, 'destroyDocument'])->name('assets.insurance.documents.destroy');

    // Services (nested under asset)
    Route::post('assets/{asset}/services', [AssetServiceController::class, 'store'])->name('assets.services.store');
    Route::put('assets/{asset}/services/{service}', [AssetServiceController::class, 'update'])->name('assets.services.update');
    Route::patch('assets/{asset}/services/{service}/field', [AssetServiceController::class, 'patchField'])->name('assets.services.patch-field');
    Route::post('assets/{asset}/services/{service}/documents', [AssetServiceController::class, 'storeDocument'])->name('assets.services.documents.store');
    Route::delete('assets/{asset}/services/documents/revert', [AssetServiceController::class, 'revertDocument'])->name('assets.services.documents.revert');
    Route::delete('assets/{asset}/services/{service}', [AssetServiceController::class, 'destroy'])->name('assets.services.destroy');
    Route::delete('assets/{asset}/services/documents/{document}', [AssetServiceController::class, 'destroyDocument'])->name('assets.services.documents.destroy');

    // Service Parts (nested under asset > service)
    Route::post('assets/{asset}/services/{service}/parts', [AssetServicePartController::class, 'store'])->name('assets.services.parts.store');
    Route::put('assets/{asset}/services/{service}/parts/{part}', [AssetServicePartController::class, 'update'])->name('assets.services.parts.update');
    Route::patch('assets/{asset}/services/{service}/parts/{part}/field', [AssetServicePartController::class, 'patchField'])->name('assets.services.parts.patch-field');
    Route::delete('assets/{asset}/services/{service}/parts/{part}', [AssetServicePartController::class, 'destroy'])->name('assets.services.parts.destroy');
    Route::post('assets/{asset}/services/parts/{part}/documents', [AssetServicePartController::class, 'storeDocument'])->name('assets.services.parts.documents.store');
    Route::delete('assets/{asset}/services/parts/documents/revert', [AssetServicePartController::class, 'revertDocument'])->name('assets.services.parts.documents.revert');
    Route::delete('assets/{asset}/services/parts/documents/{document}', [AssetServicePartController::class, 'destroyDocument'])->name('assets.services.parts.documents.destroy');

    // Extended Warranty (nested under asset)
    Route::post('assets/{asset}/ext-warranty', [AssetExtendedWarrantyController::class, 'store'])->name('assets.ext-warranty.store');
    Route::put('assets/{asset}/ext-warranty/{ew}', [AssetExtendedWarrantyController::class, 'update'])->name('assets.ext-warranty.update');
    Route::delete('assets/{asset}/ext-warranty/{ew}', [AssetExtendedWarrantyController::class, 'destroy'])->name('assets.ext-warranty.destroy');
    Route::post('assets/{asset}/ext-warranty/{ew}/documents', [AssetExtendedWarrantyController::class, 'storeDocument'])->name('assets.ext-warranty.documents.store');
    Route::delete('assets/{asset}/ext-warranty/documents/revert', [AssetExtendedWarrantyController::class, 'revertDocument'])->name('assets.ext-warranty.documents.revert');
    Route::delete('assets/{asset}/ext-warranty/documents/{document}', [AssetExtendedWarrantyController::class, 'destroyDocument'])->name('assets.ext-warranty.documents.destroy');

    // Smart Reminders (nested under asset)
    Route::post('assets/{asset}/smart-reminders', [AssetSmartReminderController::class, 'store'])->name('assets.smart-reminders.store');
    Route::put('assets/{asset}/smart-reminders/{reminder}', [AssetSmartReminderController::class, 'update'])->name('assets.smart-reminders.update');
    Route::delete('assets/{asset}/smart-reminders/{reminder}', [AssetSmartReminderController::class, 'destroy'])->name('assets.smart-reminders.destroy');

    // Maintenance Schedules (nested under asset)
    Route::post('assets/{asset}/maintenance-schedules', [AssetMaintenanceScheduleController::class, 'store'])->name('assets.maintenance-schedules.store');
    Route::put('assets/{asset}/maintenance-schedules/{schedule}', [AssetMaintenanceScheduleController::class, 'update'])->name('assets.maintenance-schedules.update');
    Route::patch('assets/{asset}/maintenance-schedules/{schedule}/field', [AssetMaintenanceScheduleController::class, 'patchField'])->name('assets.maintenance-schedules.patch-field');
    Route::delete('assets/{asset}/maintenance-schedules/{schedule}', [AssetMaintenanceScheduleController::class, 'destroy'])->name('assets.maintenance-schedules.destroy');
    Route::patch('assets/{asset}/maintenance-schedules/{schedule}/complete', [AssetMaintenanceScheduleController::class, 'complete'])->name('assets.maintenance-schedules.complete');

    // Meter Logs (nested under asset)
    Route::post('assets/{asset}/meter-logs', [AssetMeterLogController::class, 'store'])->name('assets.meter-logs.store');
    Route::put('assets/{asset}/meter-logs/{log}', [AssetMeterLogController::class, 'update'])->name('assets.meter-logs.update');
    Route::delete('assets/{asset}/meter-logs/{log}', [AssetMeterLogController::class, 'destroy'])->name('assets.meter-logs.destroy');

    // Complaints (nested under asset)
    Route::post('assets/{asset}/complaints', [AssetComplaintController::class, 'store'])->name('assets.complaints.store');
    Route::put('assets/{asset}/complaints/{complaint}', [AssetComplaintController::class, 'update'])->name('assets.complaints.update');
    Route::delete('assets/{asset}/complaints/{complaint}', [AssetComplaintController::class, 'destroy'])->name('assets.complaints.destroy');
    Route::post('assets/{asset}/complaints/{complaint}/link-service', [AssetComplaintController::class, 'linkService'])->name('assets.complaints.link-service');
    Route::patch('assets/{asset}/complaints/{complaint}/field', [AssetComplaintController::class, 'patchField'])->name('assets.complaints.patch-field');
    Route::post('assets/{asset}/complaints/{complaint}/documents', [AssetComplaintController::class, 'storeDocument'])->name('assets.complaints.documents.store');
    Route::delete('assets/{asset}/complaints/documents/revert', [AssetComplaintController::class, 'revertDocument'])->name('assets.complaints.documents.revert');
    Route::delete('assets/{asset}/complaints/documents/{document}', [AssetComplaintController::class, 'destroyDocument'])->name('assets.complaints.documents.destroy');

    // Complaint Comments
    Route::post('assets/{asset}/complaints/{complaint}/comments', [AssetComplaintCommentController::class, 'store'])->name('assets.complaints.comments.store');
    Route::delete('assets/{asset}/complaints/{complaint}/comments/{comment}', [AssetComplaintCommentController::class, 'destroy'])->name('assets.complaints.comments.destroy');

    // Complaints (global)
    Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::post('complaints', [ComplaintController::class, 'store'])->name('complaints.store');

    // Complaint Escalation Rules
    Route::get('complaint-escalation-rules', [ComplaintEscalationRuleController::class, 'index'])->name('complaint-escalation-rules.index');
    Route::post('complaint-escalation-rules', [ComplaintEscalationRuleController::class, 'store'])->name('complaint-escalation-rules.store');
    Route::put('complaint-escalation-rules/{complaintEscalationRule}', [ComplaintEscalationRuleController::class, 'update'])->name('complaint-escalation-rules.update');
    Route::delete('complaint-escalation-rules/{complaintEscalationRule}', [ComplaintEscalationRuleController::class, 'destroy'])->name('complaint-escalation-rules.destroy');

    Route::resource('asset-reminders', AssetReminderController::class);

    // Vendors
    Route::get('vendors/export', [VendorController::class, 'export'])->name('vendors.export');
    Route::resource('vendors', VendorController::class)->except(['create', 'edit']);
    Route::get('vendors/create', fn () => redirect()->route('vendors.index'))->name('vendors.create');
    Route::get('vendors/{vendor}/edit', fn () => redirect()->route('vendors.index'))->name('vendors.edit');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/asset-register', [ReportController::class, 'assetRegister'])->name('reports.asset-register');
    Route::get('reports/purchase-bills', [ReportController::class, 'purchaseBills'])->name('reports.purchase-bills');
    Route::get('reports/expiry', [ReportController::class, 'expiry'])->name('reports.expiry');
    Route::get('reports/warranty-expiry', [ReportController::class, 'warrantyExpiry'])->name('reports.warranty-expiry');
    Route::get('reports/insurance-expiry', [ReportController::class, 'insuranceExpiry'])->name('reports.insurance-expiry');
    Route::get('reports/puc-expiry', [ReportController::class, 'pucExpiry'])->name('reports.puc-expiry');
    Route::get('reports/amc-expiry', [ReportController::class, 'amcExpiry'])->name('reports.amc-expiry');
    Route::get('reports/fitness-expiry', [ReportController::class, 'fitnessExpiry'])->name('reports.fitness-expiry');
    Route::get('reports/road-tax-expiry', [ReportController::class, 'roadTaxExpiry'])->name('reports.road-tax-expiry');
    Route::get('reports/inspection-due', [ReportController::class, 'inspectionDue'])->name('reports.inspection-due');
    Route::get('reports/certification-expiry', [ReportController::class, 'certificationExpiry'])->name('reports.certification-expiry');
    Route::get('reports/service-due', [ReportController::class, 'serviceDue'])->name('reports.service-due');
    Route::get('reports/service-history', [ReportController::class, 'serviceHistory'])->name('reports.service-history');
    Route::get('reports/maintenance-cost', [ReportController::class, 'maintenanceCost'])->name('reports.maintenance-cost');
    Route::get('reports/vehicle-depreciation', [ReportController::class, 'vehicleDepreciation'])->name('reports.vehicle-depreciation');

    // Report CSV exports
    Route::get('reports/asset-register/export', [ReportController::class, 'exportAssetRegister'])->name('reports.asset-register.export');
    Route::get('reports/purchase-bills/export', [ReportController::class, 'exportPurchaseBills'])->name('reports.purchase-bills.export');
    Route::get('reports/warranty-expiry/export', [ReportController::class, 'exportWarrantyExpiry'])->name('reports.warranty-expiry.export');
    Route::get('reports/amc-expiry/export', [ReportController::class, 'exportAmcExpiry'])->name('reports.amc-expiry.export');
    Route::get('reports/insurance-expiry/export', [ReportController::class, 'exportInsuranceExpiry'])->name('reports.insurance-expiry.export');
    Route::get('reports/puc-expiry/export', [ReportController::class, 'exportPucExpiry'])->name('reports.puc-expiry.export');
    Route::get('reports/fitness-expiry/export', [ReportController::class, 'exportFitnessExpiry'])->name('reports.fitness-expiry.export');
    Route::get('reports/road-tax-expiry/export', [ReportController::class, 'exportRoadTaxExpiry'])->name('reports.road-tax-expiry.export');
    Route::get('reports/inspection-due/export', [ReportController::class, 'exportInspectionDue'])->name('reports.inspection-due.export');
    Route::get('reports/certification-expiry/export', [ReportController::class, 'exportCertificationExpiry'])->name('reports.certification-expiry.export');
    Route::get('reports/service-due/export', [ReportController::class, 'exportServiceDue'])->name('reports.service-due.export');
    Route::get('reports/service-history/export', [ReportController::class, 'exportServiceHistory'])->name('reports.service-history.export');
    Route::get('reports/maintenance-cost/export', [ReportController::class, 'exportMaintenanceCost'])->name('reports.maintenance-cost.export');
    Route::get('reports/vehicle-depreciation/export', [ReportController::class, 'exportVehicleDepreciation'])->name('reports.vehicle-depreciation.export');
    Route::get('reports/vendor-performance', [ReportController::class, 'vendorPerformance'])->name('reports.vendor-performance');
    Route::get('reports/vendor-performance/export', [ReportController::class, 'exportVendorPerformance'])->name('reports.vendor-performance.export');
});

require __DIR__ . '/settings.php';
