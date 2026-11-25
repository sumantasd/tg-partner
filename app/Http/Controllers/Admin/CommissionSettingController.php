<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\CommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionSettingController extends Controller
{
    /**
     * Display Commission Configuration & Letterhead Settings page.
     */
    public function index(CommissionService $service): View
    {
        $currentRate = $service->getDefaultCommissionRate();
        $setting = SystemSetting::with('updatedBy')->where('key', 'default_commission_rate')->first();

        $companySettings = [
            'company_name'    => SystemSetting::where('key', 'company_name')->value('value') ?? 'Tech Googly',
            'company_tagline' => SystemSetting::where('key', 'company_tagline')->value('value') ?? 'Innovation For You',
            'company_website' => SystemSetting::where('key', 'company_website')->value('value') ?? 'techgoogly.com',
            'company_address' => SystemSetting::where('key', 'company_address')->value('value') ?? '',
            'company_phone'   => SystemSetting::where('key', 'company_phone')->value('value') ?? '',
            'company_email'   => SystemSetting::where('key', 'company_email')->value('value') ?? '',
        ];

        return view('admin.settings.commission', compact('currentRate', 'setting', 'companySettings'));
    }

    /**
     * Update default commission rate.
     */
    public function update(Request $request, CommissionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'default_commission_rate' => ['required', 'numeric', 'between:0,100'],
        ]);

        $updatedRate = $service->updateDefaultCommissionRate(
            (float) $validated['default_commission_rate'],
            $request->user()->id
        );

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::CommissionSettingsUpdated,
            module: \App\Enums\AuditLogModule::Settings,
            description: "Default Commission Rate updated to {$updatedRate}% by Admin {$request->user()->name}.",
            newValues: ['default_commission_rate' => $updatedRate],
            actor: $request->user()
        );

        return redirect()->route('admin.settings.commission.index')
            ->with('success', "Default Commission Rate successfully updated to {$updatedRate}%. This rate applies to subsequently verified payments.");
    }

    /**
     * Update official company letterhead and branding details for agreements.
     */
    public function updateLetterhead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name'    => ['required', 'string', 'max:150'],
            'company_tagline' => ['required', 'string', 'max:200'],
            'company_website' => ['required', 'string', 'max:100'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone'   => ['nullable', 'string', 'max:50'],
            'company_email'   => ['nullable', 'email', 'max:100'],
        ]);

        foreach ($validated as $key => $val) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $val, 'updated_by' => $request->user()->id]
            );
        }

        return redirect()->route('admin.settings.commission.index')
            ->with('success', 'Company letterhead and corporate identity details updated successfully.');
    }
}
