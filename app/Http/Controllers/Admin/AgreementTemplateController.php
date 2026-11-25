<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgreementTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgreementTemplateController extends Controller
{
    /**
     * List all agreement templates.
     */
    public function index(): View
    {
        $templates = AgreementTemplate::with('creator')->latest('id')->paginate(15);
        $activeTemplate = AgreementTemplate::getActiveTemplate();

        return view('admin.settings.agreement-templates.index', compact('templates', 'activeTemplate'));
    }

    /**
     * Show form to create new agreement template.
     */
    public function create(): View
    {
        return view('admin.settings.agreement-templates.create');
    }

    /**
     * Store new agreement template.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template_code'   => ['required', 'string', 'max:50', 'unique:agreement_templates,template_code'],
            'title'           => ['required', 'string', 'max:255'],
            'version'         => ['required', 'string', 'max:20'],
            'content'         => ['required', 'string'],
            'status'          => ['required', 'in:draft,active,archived'],
            'is_active'       => ['nullable', 'boolean'],
            'effective_from'  => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $isActive = (bool) ($validated['is_active'] ?? false);
        if ($isActive) {
            AgreementTemplate::where('is_active', true)->update(['is_active' => false]);
        }

        $template = AgreementTemplate::create([
            'template_code'   => $validated['template_code'],
            'title'           => $validated['title'],
            'version'         => $validated['version'],
            'content'         => $validated['content'],
            'status'          => $validated['status'],
            'is_active'       => $isActive,
            'effective_from'  => $validated['effective_from'],
            'effective_until' => $validated['effective_until'] ?? null,
            'created_by'      => $request->user()->id,
            'notes'           => $validated['notes'] ?? null,
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::AgreementTemplateCreated,
            module: \App\Enums\AuditLogModule::Agreement,
            description: "Agreement Template #{$template->template_code} (v{$template->version}) created by Admin.",
            subject: $template,
            metadata: ['template_code' => $template->template_code, 'version' => $template->version],
            actor: $request->user()
        );

        return redirect()->route('admin.settings.agreement-templates.show', $template)
            ->with('success', "Agreement Template #{$template->template_code} (v{$template->version}) created successfully.");
    }

    /**
     * View agreement template and sample rendered preview.
     */
    public function show(AgreementTemplate $agreementTemplate): View
    {
        $agreementTemplate->load('creator');

        $sampleData = [
            'agreement_number' => 'TGA-2026-DEMO',
            'effective_date'   => now()->format('d M Y'),
            'company_name'     => 'Acme Digital Agency Pvt Ltd',
            'partner_name'     => 'Rajesh Kumar',
            'partner_code'     => 'TGP-2026-DEMO',
            'commission_rate'  => '10.00%',
            'commission_type'  => 'Percentage of Verified Payment',
        ];

        $renderedContent = $agreementTemplate->render($sampleData);

        return view('admin.settings.agreement-templates.show', [
            'template'        => $agreementTemplate,
            'renderedContent' => $renderedContent,
            'sampleData'      => $sampleData,
        ]);
    }

    /**
     * Edit draft template.
     */
    public function edit(AgreementTemplate $agreementTemplate): View
    {
        return view('admin.settings.agreement-templates.edit', [
            'template' => $agreementTemplate,
        ]);
    }

    /**
     * Update agreement template.
     */
    public function update(Request $request, AgreementTemplate $agreementTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'content'         => ['required', 'string'],
            'status'          => ['required', 'in:draft,active,archived'],
            'is_active'       => ['nullable', 'boolean'],
            'effective_from'  => ['required', 'date'],
            'effective_until' => ['nullable', 'date'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $isActive = (bool) ($validated['is_active'] ?? false);
        if ($isActive && !$agreementTemplate->is_active) {
            AgreementTemplate::where('id', '!=', $agreementTemplate->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $oldAttributes = $agreementTemplate->getAttributes();

        $agreementTemplate->update([
            'title'           => $validated['title'],
            'content'         => $validated['content'],
            'status'          => $validated['status'],
            'is_active'       => $isActive,
            'effective_from'  => $validated['effective_from'],
            'effective_until' => $validated['effective_until'] ?? null,
            'notes'           => $validated['notes'] ?? null,
        ]);

        app(\App\Services\AuditLogService::class)->recordModelUpdate(
            model: $agreementTemplate,
            oldAttributes: $oldAttributes,
            action: \App\Enums\AuditLogAction::AgreementTemplateUpdated,
            module: \App\Enums\AuditLogModule::Agreement,
            description: "Agreement Template #{$agreementTemplate->template_code} updated by Admin.",
            actor: $request->user()
        );

        return redirect()->route('admin.settings.agreement-templates.show', $agreementTemplate)
            ->with('success', "Agreement Template #{$agreementTemplate->template_code} updated successfully.");
    }

    /**
     * Activate agreement template.
     */
    public function activate(AgreementTemplate $agreementTemplate): RedirectResponse
    {
        AgreementTemplate::where('id', '!=', $agreementTemplate->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $agreementTemplate->update([
            'is_active' => true,
            'status'    => 'active',
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::AgreementTemplateActivated,
            module: \App\Enums\AuditLogModule::Agreement,
            description: "Agreement Template #{$agreementTemplate->template_code} (v{$agreementTemplate->version}) marked as active default agreement template.",
            subject: $agreementTemplate,
            actor: auth()->user()
        );

        return back()->with('success', "Template #{$agreementTemplate->template_code} (v{$agreementTemplate->version}) is now the active Partner Agreement template.");
    }
}
