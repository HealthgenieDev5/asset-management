<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMaintenanceSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AssetMaintenanceScheduleController extends Controller
{
    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $type = $request->input('schedule_type', 'date');

        $rules = $this->baseRules();
        $rules += $this->typeRules($type);

        $validated = $request->validate($rules);

        $days = $this->parseThresholds($request->input('reminder_thresholds_input', ''));

        $schedule = $asset->maintenanceSchedules()->create([
            'service_type'        => $validated['service_type'],
            'schedule_name'       => $validated['schedule_name'],
            'description'         => $validated['description'] ?? null,
            'schedule_type'       => $type,
            'interval_value'      => $validated['interval_value'] ?? null,
            'interval_unit'       => $validated['interval_unit'] ?? null,
            'last_done_date'      => $validated['last_done_date'] ?? null,
            'interval_km'         => $validated['interval_km'] ?? null,
            'last_done_km'        => $validated['last_done_km'] ?? null,
            'interval_hours'      => $validated['interval_hours'] ?? null,
            'last_done_hours'     => $validated['last_done_hours'] ?? null,
            'reminder_thresholds' => $days,
            'reminder_unit'       => $this->reminderUnit($type),
            'is_active'           => $request->boolean('is_active', true),
            'notes'               => $validated['notes'] ?? null,
            'created_by'          => auth()->id(),
            'updated_by'          => auth()->id(),
        ]);

        if ($type === 'date' && $schedule->last_done_date) {
            $next = $schedule->computeNextDueDate();
            if ($next) {
                $schedule->update(['next_due_date' => $next]);
            }
        }

        return redirect()->route('assets.show', [$asset, 'tab' => 'schedules'])
            ->with('success', 'Maintenance schedule created.');
    }

    public function update(Request $request, Asset $asset, AssetMaintenanceSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->asset_id !== $asset->id, 403);

        $type = $request->input('schedule_type', 'date');

        $rules = $this->baseRules();
        $rules += $this->typeRules($type);

        $validated = $request->validate($rules);

        $days = $this->parseThresholds($request->input('reminder_thresholds_input', ''));

        $schedule->update([
            'service_type'        => $validated['service_type'],
            'schedule_name'       => $validated['schedule_name'],
            'description'         => $validated['description'] ?? null,
            'schedule_type'       => $type,
            'interval_value'      => $validated['interval_value'] ?? null,
            'interval_unit'       => $validated['interval_unit'] ?? null,
            'last_done_date'      => $validated['last_done_date'] ?? null,
            'interval_km'         => $validated['interval_km'] ?? null,
            'last_done_km'        => $validated['last_done_km'] ?? null,
            'interval_hours'      => $validated['interval_hours'] ?? null,
            'last_done_hours'     => $validated['last_done_hours'] ?? null,
            'reminder_thresholds' => $days,
            'reminder_unit'       => $this->reminderUnit($type),
            'is_active'           => $request->boolean('is_active', true),
            'notes'               => $validated['notes'] ?? null,
            'updated_by'          => auth()->id(),
        ]);

        if ($type === 'date') {
            $schedule->refresh();
            $next = $schedule->computeNextDueDate();
            $schedule->update(['next_due_date' => $next]);
        }

        return redirect()->route('assets.show', [$asset, 'tab' => 'schedules'])
            ->with('success', 'Maintenance schedule updated.');
    }

    public function destroy(Asset $asset, AssetMaintenanceSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->asset_id !== $asset->id, 403);

        $schedule->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'schedules'])
            ->with('success', 'Maintenance schedule deleted.');
    }

    public function complete(Request $request, Asset $asset, AssetMaintenanceSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->asset_id !== $asset->id, 403);

        $request->validate([
            'completed_date'  => ['nullable', 'date'],
            'completed_km'    => ['nullable', 'integer', 'min:0'],
            'completed_hours' => ['nullable', 'integer', 'min:0'],
        ]);

        $updates = ['completed_at' => now(), 'updated_by' => auth()->id()];

        if ($request->filled('completed_date')) {
            $updates['last_done_date'] = $request->input('completed_date');
        }
        if ($request->filled('completed_km')) {
            $updates['last_done_km'] = $request->integer('completed_km');
        }
        if ($request->filled('completed_hours')) {
            $updates['last_done_hours'] = $request->integer('completed_hours');
        }

        $schedule->update($updates);

        if ($schedule->schedule_type === 'date') {
            $schedule->refresh();
            $next = $schedule->computeNextDueDate();
            if ($next) {
                $schedule->update(['next_due_date' => $next]);
            }
        }

        return redirect()->route('assets.show', [$asset, 'tab' => 'schedules'])
            ->with('success', 'Schedule marked as completed. Next due date recalculated.');
    }

    private function baseRules(): array
    {
        return [
            'service_type'  => ['required', 'in:preventive_maintenance,corrective_maintenance,inspection,repair,calibration,cleaning,other'],
            'schedule_name' => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'is_active'     => ['boolean'],
            'notes'         => ['nullable', 'string'],
        ];
    }

    private function typeRules(string $type): array
    {
        return match ($type) {
            'mileage' => [
                'interval_km'    => ['required', 'integer', 'min:1'],
                'last_done_km'   => ['nullable', 'integer', 'min:0'],
            ],
            'operating_hours' => [
                'interval_hours'  => ['required', 'integer', 'min:1'],
                'last_done_hours' => ['nullable', 'integer', 'min:0'],
            ],
            default => [
                'interval_value' => ['required', 'integer', 'min:1'],
                'interval_unit'  => ['required', 'in:days,weeks,months,years'],
                'last_done_date' => ['nullable', 'date'],
            ],
        };
    }

    private function reminderUnit(string $scheduleType): string
    {
        return match ($scheduleType) {
            'mileage'         => 'km',
            'operating_hours' => 'hours',
            default           => 'days',
        };
    }

    private function parseThresholds(string $input): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', preg_split('/[\s,]+/', trim($input))),
            fn($d) => $d > 0
        )));
    }
}
