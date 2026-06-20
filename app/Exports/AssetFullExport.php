<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AssetFullExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle
{
    public function __construct(private array $filters = []) {}

    public function title(): string
    {
        return 'Asset Register';
    }

    public function collection()
    {
        $q = Asset::with([
            'category',
            'subcategory',
            'documents',
            'amcContracts',
            'amcContracts.documents',
            'insurancePolicies',
        ])->withoutTrashed();

        if (! empty($this->filters['status'])) {
            $q->where('status', $this->filters['status']);
        }
        if (! empty($this->filters['category_id'])) {
            $q->where('asset_category_id', $this->filters['category_id']);
        }
        if (! empty($this->filters['department'])) {
            $q->where('department', 'like', '%' . $this->filters['department'] . '%');
        }

        return $q->orderBy('asset_code')->get();
    }

    public function headings(): array
    {
        return [
            'Asset Cat',
            'Sub Cat',
            'Asset ID',
            'Asset Name',
            'Asset Description',
            'Warranty Details',
            'Warranty Activation Image',
            'Warranty Expiry Reminder (Days)',
            'Vendor / Supplier',
            'Bill No',
            'Bill Amount (₹)',
            'Bill Date',
            'Bill Image',
            'Warranty Lapse Date',
            'Serial Number',
            'Manufacturer',
            'Model / Year',
            'Location',
            'Department',
            'Custodian',
            'Asset Status',
            'Needs AMC After Warranty (Y/N)',
            'AMC Vendor',
            'AMC Date From',
            'AMC Date To',
            'AMC Bill No',
            'AMC Amount (₹)',
            'AMC Terms',
            'AMC Expiry Reminder (Days)',
            'AMC Image',
            'Maintenance Schedule Type',
            'Inspection Required (Y/N)',
            'Inspection Frequency',
            'Insurance (Y/N)',
            'Insurance Vendor / Insurer',
            'Premium Amount (₹)',
            'Policy Number',
            'Insurance From',
            'Insurance To',
            'Insurance Expiry Reminder (Days)',
            'Vehicle OBV (₹)',
            'Vehicle Dep %',
            'Vehicle Dep Book Value (₹)',
            'PUC Expiry',
            'Fitness Expiry',
            'Road Tax Expiry',
        ];
    }

    public function map($asset): array
    {
        $amc = $asset->amcContracts->first();
        $ins = $asset->insurancePolicies->first();

        // Warranty activation image - check document exists
        $hasWarrantyImg = $asset->documents->where('document_type', 'warranty_activation_image')->isNotEmpty();
        $hasBillImg     = $asset->documents->where('document_type', 'bill_image')->isNotEmpty();
        $hasAmcImg      = $amc ? $amc->documents->isNotEmpty() : false;

        // Inspection frequency label
        $inspFreq = '';
        if ($asset->inspection_required && $asset->inspection_frequency_value) {
            $inspFreq = $asset->inspection_frequency_value . ' ' . ($asset->inspection_frequency_unit ?? '');
        }

        return [
            $asset->category?->name,
            $asset->subcategory?->name,
            $asset->asset_code,
            $asset->asset_name,
            $asset->asset_description,
            $asset->warranty_details,
            $hasWarrantyImg ? 'Yes' : 'No',
            $asset->warranty_reminder_before_days,
            $asset->vendor_supplier,
            $asset->bill_no,
            $asset->bill_amount ? number_format($asset->bill_amount, 2) : '',
            $asset->bill_date?->format('d/m/Y'),
            $hasBillImg ? 'Yes' : 'No',
            $asset->warranty_lapse_date?->format('d/m/Y'),
            $asset->serial_number,
            $asset->manufacturer,
            trim(($asset->model ?? '') . ($asset->model_year ? ' / ' . $asset->model_year : '')),
            $asset->location,
            $asset->department,
            $asset->custodian,
            ucfirst(str_replace('_', ' ', $asset->status)),
            $amc ? 'Y' : 'N',
            $amc?->vendor_name,
            $amc?->amc_date_from?->format('d/m/Y'),
            $amc?->amc_date_to?->format('d/m/Y'),
            $amc?->amc_bill_no,
            $amc?->amc_amount ? number_format($amc->amc_amount, 2) : '',
            $amc?->amc_terms,
            $amc?->reminder_before_days,
            $hasAmcImg ? 'Yes' : 'No',
            $asset->maintenance_schedule_type !== 'none' ? ucfirst(str_replace('_', ' ', $asset->maintenance_schedule_type)) : '',
            $asset->inspection_required ? 'Yes' : 'No',
            $inspFreq,
            $ins ? 'Y' : 'N',
            $ins?->insurer_name,
            $ins?->premium_amount ? number_format($ins->premium_amount, 2) : '',
            $ins?->policy_number,
            $ins?->policy_date_from?->format('d/m/Y'),
            $ins?->policy_date_to?->format('d/m/Y'),
            $ins?->reminder_before_days,
            $asset->vehicle_obv ? number_format($asset->vehicle_obv, 2) : '',
            $asset->vehicle_depreciation_percent,
            $asset->vehicle_depreciation_book_value ? number_format($asset->vehicle_depreciation_book_value, 2) : '',
            $asset->puc_expiry_date?->format('d/m/Y'),
            $asset->fitness_expiry_date?->format('d/m/Y'),
            $asset->road_tax_expiry_date?->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'AT'; // column 46
        return [
            // Header row: dark bg, white bold text
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF18181B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            ],
            // Data rows
            'A2:' . $lastCol . '10000' => [
                'font'      => ['size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, // Asset Cat
            'B' => 16, // Sub Cat
            'C' => 12, // Asset ID
            'D' => 28, // Asset Name
            'E' => 30, // Description
            'F' => 25, // Warranty Details
            'G' => 10, // Warranty Img
            'H' => 12, // Warranty Reminder
            'I' => 22, // Vendor
            'J' => 14, // Bill No
            'K' => 14, // Bill Amount
            'L' => 12, // Bill Date
            'M' => 10, // Bill Image
            'N' => 14, // Warranty Lapse
            'O' => 18, // Serial No
            'P' => 18, // Manufacturer
            'Q' => 18, // Model/Year
            'R' => 18, // Location
            'S' => 18, // Department
            'T' => 20, // Custodian
            'U' => 14, // Status
            'V' => 12, // Needs AMC
            'W' => 22, // AMC Vendor
            'X' => 13, // AMC From
            'Y' => 13, // AMC To
            'Z' => 14, // AMC Bill No
            'AA'=> 14, // AMC Amount
            'AB'=> 20, // AMC Terms
            'AC'=> 12, // AMC Reminder
            'AD'=> 10, // AMC Image
            'AE'=> 18, // Maint Schedule
            'AF'=> 12, // Inspection Y/N
            'AG'=> 16, // Insp Frequency
            'AH'=> 10, // Ins Y/N
            'AI'=> 22, // Ins Vendor
            'AJ'=> 14, // Premium
            'AK'=> 18, // Policy No
            'AL'=> 13, // Ins From
            'AM'=> 13, // Ins To
            'AN'=> 12, // Ins Reminder
            'AO'=> 14, // OBV
            'AP'=> 12, // Dep %
            'AQ'=> 16, // Book Value
            'AR'=> 13, // PUC Expiry
            'AS'=> 14, // Fitness Expiry
            'AT'=> 13, // Road Tax
        ];
    }
}
