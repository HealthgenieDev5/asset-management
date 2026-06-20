<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetSmartReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GlobalSmartReminderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $mode = $request->input('reminder_mode', 'time');

        $validated = $request->validate(array_merge(
            ['asset_id' => ['required', 'exists:assets,id']],
            $this->rules($mode)
        ));

        $days = $this->parseDays($validated['reminder_days_input'] ?? '');
        abort_if(empty($days), 422, 'At least one reminder threshold is required.');

        $asset = Asset::findOrFail($validated['asset_id']);

        $asset->smartReminders()->create([
            'reminder_name'  => $validated['reminder_name'],
            'reminder_type'  => $validated['reminder_type'],
            'reminder_mode'  => $mode,
            'expiry_date'    => $mode === 'time' ? $validated['expiry_date'] : null,
            'counter_limit'  => $mode !== 'time' ? $validated['counter_limit'] : null,
            'threshold_unit' => $mode !== 'time' ? $validated['threshold_unit'] : null,
            'reminder_days'  => $days,
            'is_active'      => $request->boolean('is_active', true),
            'notes'          => $validated['notes'] ?? null,
            'created_by'     => auth()->id(),
            'updated_by'     => auth()->id(),
        ]);

        return redirect()->route('asset-reminders.index')
            ->with('success', 'Smart reminder created.');
    }

    public function update(Request $request, AssetSmartReminder $smartReminder): RedirectResponse
    {
        abort_unless(Asset::find($smartReminder->asset_id), 404);

        $mode = $request->input('reminder_mode', 'time');

        $validated = $request->validate($this->rules($mode));

        $days = $this->parseDays($validated['reminder_days_input'] ?? '');
        abort_if(empty($days), 422, 'At least one reminder threshold is required.');

        $smartReminder->update([
            'reminder_name'  => $validated['reminder_name'],
            'reminder_type'  => $validated['reminder_type'],
            'reminder_mode'  => $mode,
            'expiry_date'    => $mode === 'time' ? $validated['expiry_date'] : null,
            'counter_limit'  => $mode !== 'time' ? $validated['counter_limit'] : null,
            'threshold_unit' => $mode !== 'time' ? $validated['threshold_unit'] : null,
            'reminder_days'  => $days,
            'is_active'      => $request->boolean('is_active', true),
            'notes'          => $validated['notes'] ?? null,
            'updated_by'     => auth()->id(),
        ]);

        return redirect()->route('asset-reminders.index')
            ->with('success', 'Smart reminder updated.');
    }

    public function destroy(AssetSmartReminder $smartReminder): RedirectResponse
    {
        abort_unless(Asset::find($smartReminder->asset_id), 404);

        $smartReminder->delete();

        return redirect()->route('asset-reminders.index')
            ->with('success', 'Smart reminder deleted.');
    }

    private function rules(string $mode): array
    {
        $typeEnum = 'in:warranty,extended_warranty,amc,insurance,puc,fitness,road_tax,service_due,certification,part_warranty,maintenance_schedule,custom';

        return [
            'reminder_name'       => ['required', 'string', 'max:255'],
            'reminder_type'       => ['required', $typeEnum],
            'reminder_mode'       => ['required', 'in:time,meter,count'],
            'expiry_date'         => $mode === 'time' ? ['required', 'date'] : ['nullable', 'date'],
            'counter_limit'       => $mode !== 'time' ? ['required', 'integer', 'min:1'] : ['nullable', 'integer'],
            'threshold_unit'      => $mode !== 'time' ? ['required', 'string', 'max:30'] : ['nullable', 'string'],
            'reminder_days_input' => ['required', 'string'],
            'is_active'           => ['boolean'],
            'notes'               => ['nullable', 'string'],
        ];
    }

    private function parseDays(string $input): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', preg_split('/[\s,]+/', trim($input))),
            fn($d) => $d > 0
        )));
    }
}
