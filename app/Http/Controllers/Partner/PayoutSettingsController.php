<?php

namespace App\Http\Controllers\Partner;

use App\Enums\BankVerificationStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayoutSettingsController extends Controller
{
    /**
     * Show partner payout & bank configuration form.
     */
    public function edit(Request $request): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $isLocked = (bool) ($partner->kyc && $partner->kyc->isLocked());

        return view('partner.payout-settings.edit', compact('partner', 'isLocked'));
    }

    /**
     * Update partner payout & bank details securely.
     */
    public function update(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        if ($partner->kyc && $partner->kyc->isLocked()) {
            return redirect()
                ->route('partner.payout-settings.edit')
                ->with('error', 'Bank and payout details cannot be modified directly while your KYC application is under review or verified.')
                ->withErrors(['bank' => 'Bank details cannot be modified directly while your KYC application is under review or verified.']);
        }

        $validated = $request->validate([
            'payout_method'                 => ['required', 'in:bank_transfer,upi'],
            'bank_name'                     => ['required_if:payout_method,bank_transfer', 'nullable', 'string', 'max:150'],
            'bank_account_name'             => ['required_if:payout_method,bank_transfer', 'nullable', 'string', 'max:150'],
            'bank_account_number'           => ['required_if:payout_method,bank_transfer', 'nullable', 'string', 'min:9', 'max:24', 'confirmed'],
            'bank_routing_code'             => [
                'required_if:payout_method,bank_transfer',
                'nullable',
                'string',
                'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/',
            ],
            'upi_id'                        => [
                'required_if:payout_method,upi',
                'nullable',
                'string',
                'regex:/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/',
            ],
        ], [
            'bank_routing_code.regex'       => 'Please enter a valid 11-character Indian IFSC code (e.g. HDFC0001234).',
            'upi_id.regex'                  => 'Please enter a valid UPI ID (e.g. yourname@okhdfcbank).',
            'bank_account_number.confirmed' => 'The bank account number confirmation does not match.',
        ]);

        $updateData = [
            'payout_method' => $validated['payout_method'],
        ];

        if ($validated['payout_method'] === 'bank_transfer') {
            $updateData['bank_name']         = $validated['bank_name'];
            $updateData['bank_account_name'] = $validated['bank_account_name'];
            if (! empty($validated['bank_account_number'])) {
                $updateData['bank_account_number'] = $validated['bank_account_number'];
            }
            if (! empty($validated['bank_routing_code'])) {
                $updateData['bank_routing_code'] = strtoupper(trim($validated['bank_routing_code']));
            }
        } elseif ($validated['payout_method'] === 'upi') {
            $updateData['payout_details'] = trim($validated['upi_id']);
        }

        // If user submitted both, preserve both for partner convenience
        if (! empty($validated['upi_id'])) {
            $updateData['payout_details'] = trim($validated['upi_id']);
        }

        $partner->update($updateData);

        // Financial Integrity: If bank details were modified, invalidate previous bank verification
        if ($partner->bank_verification_status === BankVerificationStatus::Verified) {
            $partner->update([
                'bank_verification_status' => BankVerificationStatus::Pending,
            ]);

            if ($partner->kyc) {
                $partner->kyc->update([
                    'bank_verification_status' => BankVerificationStatus::Pending,
                ]);
            }

            Log::info('Partner bank details modified, bank verification invalidated and reset to Pending', [
                'partner_id' => $partner->id,
                'user_id'    => $request->user()->id,
                'ip'         => $request->ip(),
            ]);
        }

        return redirect()
            ->route('partner.payout-settings.edit')
            ->with('success', 'Your payout destination details have been updated and encrypted securely.');
    }
}

