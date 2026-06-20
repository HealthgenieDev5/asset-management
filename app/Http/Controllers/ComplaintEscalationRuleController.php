<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\ComplaintEscalationRule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ComplaintEscalationRuleController extends Controller
{
    public function index()
    {
        $rules      = ComplaintEscalationRule::with('category', 'createdBy')->latest()->get();
        $categories = AssetCategory::where('status', 'active')->orderBy('name')->get();

        return view('complaints.escalation-rules.index', compact('rules', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location'          => ['required', 'string', 'max:255'],
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'notify_emails'     => ['required', 'string'],
            'remarks'           => ['nullable', 'string'],
        ]);

        $emails = $this->parseAndValidateEmails($request, $validated['notify_emails']);

        ComplaintEscalationRule::create([
            'location'          => $validated['location'],
            'asset_category_id' => $validated['asset_category_id'],
            'notify_emails'     => $emails,
            'remarks'           => $validated['remarks'] ?? null,
            'created_by'        => auth()->id(),
        ]);

        return redirect()->route('complaint-escalation-rules.index')
            ->with('success', 'Escalation rule added.');
    }

    public function update(Request $request, ComplaintEscalationRule $complaintEscalationRule)
    {
        $validated = $request->validate([
            'location'          => ['required', 'string', 'max:255'],
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'notify_emails'     => ['required', 'string'],
            'remarks'           => ['nullable', 'string'],
        ]);

        $emails = $this->parseAndValidateEmails($request, $validated['notify_emails']);

        $complaintEscalationRule->update([
            'location'          => $validated['location'],
            'asset_category_id' => $validated['asset_category_id'],
            'notify_emails'     => $emails,
            'remarks'           => $validated['remarks'] ?? null,
            'updated_by'        => auth()->id(),
        ]);

        return redirect()->route('complaint-escalation-rules.index')
            ->with('success', 'Escalation rule updated.');
    }

    private function parseAndValidateEmails(Request $request, string $raw): array
    {
        $emails = array_values(array_filter(array_map('trim', explode(',', $raw))));

        $invalid = array_filter($emails, fn($e) => ! filter_var($e, FILTER_VALIDATE_EMAIL));
        if (! empty($invalid)) {
            throw ValidationException::withMessages([
                'notify_emails' => 'Invalid email address(es): ' . implode(', ', $invalid),
            ]);
        }

        return $emails;
    }

    public function destroy(ComplaintEscalationRule $complaintEscalationRule)
    {
        $complaintEscalationRule->delete();

        return redirect()->route('complaint-escalation-rules.index')
            ->with('success', 'Escalation rule deleted.');
    }
}
