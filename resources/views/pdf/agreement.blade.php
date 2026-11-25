<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Referral Partner Agreement - {{ $agreement->agreement_number }}</title>
    <style>
        @page {
            margin: 44mm 16mm 32mm 16mm;
            size: a4 portrait;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9pt;
            line-height: 1.45;
            color: #252525;
            margin: 0;
            padding: 0;
        }

        /* Fixed Running Letterhead Header across all pages */
        .running-header {
            position: fixed;
            top: -44mm;
            left: -16mm;
            right: -16mm;
            height: 40mm;
        }

        .header-wave-img {
            width: 100%;
            height: 13.5mm;
            display: block;
            margin: 0;
            padding: 0;
        }

        .header-content-wrap {
            padding: 2.5mm 16mm 0 16mm;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0;
        }

        .logo-img {
            max-height: 38px;
            max-width: 165px;
            display: block;
        }

        .header-contact-box {
            border-left: 2px solid #0072b8;
            padding-left: 7px;
            font-size: 7.5pt;
            line-height: 1.35;
            color: #252525;
        }

        .header-contact-box .contact-row {
            margin-bottom: 1.5px;
        }

        .header-contact-box strong {
            color: #0072b8;
            font-weight: bold;
        }

        /* Thin Branded Accent Bar below header */
        .brand-accent-bar {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3.5mm;
        }

        .brand-accent-bar td {
            height: 2px;
            padding: 0;
        }

        /* Fixed Running Letterhead Footer across all pages */
        .running-footer {
            position: fixed;
            bottom: -32mm;
            left: -16mm;
            right: -16mm;
            height: 30mm;
        }

        .footer-content-wrap {
            padding: 0 16mm;
        }

        .footer-divider-line {
            width: 100%;
            height: 1.5px;
            background-color: #6B3D95;
            margin-bottom: 2mm;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td {
            vertical-align: middle;
            padding: 0;
        }

        .footer-item-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-icon-cell {
            width: 18px;
            vertical-align: middle;
            text-align: left;
            padding: 0;
        }

        .footer-icon {
            width: 16px;
            height: 16px;
            display: block;
        }

        .footer-text-cell {
            padding-left: 9px;
            vertical-align: middle;
            text-align: left;
        }

        .footer-text {
            font-size: 7.5pt;
            color: #1e293b;
            font-weight: 500;
            white-space: nowrap;
        }

        .footer-text-loc {
            font-size: 7pt;
            color: #1e293b;
            line-height: 1.3;
            display: block;
        }

        .footer-wave-img {
            width: 100%;
            height: 11mm;
            display: block;
            margin-top: 2.2mm;
            padding: 0;
        }

        /* Document Title Section */
        .doc-title-box {
            text-align: center;
            margin: 0 0 4mm 0;
            padding-bottom: 2.5mm;
            border-bottom: 1.5px solid #e2e8f0;
        }

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1a202c;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .doc-subtitle {
            font-size: 8pt;
            color: #6B3D95;
            font-weight: bold;
            margin-top: 2px;
            letter-spacing: 0.4px;
        }

        /* Metadata Specification Table */
        .metadata-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4mm;
            background-color: #faf8fc;
            border: 1px solid #e9d8fd;
            border-radius: 4px;
        }

        .metadata-table td {
            padding: 4px 8px;
            font-size: 8pt;
            border-bottom: 1px solid #f3e8ff;
            vertical-align: top;
        }

        .meta-label {
            font-weight: bold;
            color: #6B3D95;
            width: 22%;
        }

        .meta-value {
            font-weight: 600;
            color: #2d3748;
            width: 28%;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 7pt;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .badge-accepted {
            background-color: #def7ec;
            color: #03543f;
            border: 1px solid #31c48d;
        }

        .badge-pending {
            background-color: #fef08a;
            color: #713f12;
            border: 1px solid #eab308;
        }

        .badge-revoked {
            background-color: #fde8e8;
            color: #9b1c1c;
            border: 1px solid #f98080;
        }

        .badge-superseded {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        /* Main Agreement Body */
        .agreement-body {
            width: 100%;
            margin-bottom: 4mm;
        }

        .agreement-body h1 {
            display: none; /* Already rendered in .doc-title-box */
        }

        .agreement-body h2 {
            font-size: 9.5pt;
            color: #6B3D95;
            margin-top: 3.5mm;
            margin-bottom: 1.5mm;
            border-bottom: 1px solid #e9d8fd;
            padding-bottom: 1.5px;
            font-weight: bold;
        }

        .agreement-body h3 {
            font-size: 9pt;
            color: #6B3D95;
            margin-top: 3mm;
            margin-bottom: 1.5mm;
            font-weight: bold;
        }

        .agreement-body p {
            margin: 0 0 2mm 0;
            text-align: justify;
            line-height: 1.45;
            font-size: 8.5pt;
        }

        .agreement-body ul, .agreement-body ol {
            margin: 1.5mm 0 2.5mm 4mm;
            padding: 0;
        }

        .agreement-body li {
            margin-bottom: 1.2mm;
            font-size: 8.5pt;
            line-height: 1.4;
        }

        .agreement-body hr {
            border: 0;
            height: 1px;
            background-color: #e2e8f0;
            margin: 3.5mm 0;
        }

        /* Commercial Schedule Box */
        .schedule-box {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 4px;
            padding: 7px 10px;
            margin: 3.5mm 0;
            page-break-inside: avoid;
        }

        .schedule-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #166534;
            margin: 0 0 3px 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .schedule-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        /* Signatures Section */
        .signatures-wrap {
            margin-top: 4mm;
            page-break-inside: avoid;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-col {
            width: 48%;
            border: 1px solid #cbd5e1;
            background-color: #fafbfc;
            padding: 8px;
            vertical-align: top;
            border-radius: 4px;
        }

        .sig-heading {
            font-size: 8.5pt;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 1.5px solid #6B3D95;
            padding-bottom: 3px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .sig-row {
            font-size: 7.8pt;
            color: #334155;
            margin-bottom: 2px;
        }

        .sig-stamp-box {
            margin-top: 6px;
            padding: 5px 7px;
            border-radius: 3px;
            font-size: 7.2pt;
            line-height: 1.35;
        }

        .sig-accepted {
            background-color: #ecfdf5;
            border: 1px dashed #10b981;
            color: #065f46;
        }

        .sig-pending {
            background-color: #fffbeb;
            border: 1px dashed #f59e0b;
            color: #92400e;
        }

        .sig-revoked {
            background-color: #fef2f2;
            border: 1px dashed #ef4444;
            color: #991b1b;
        }

        .watermark {
            position: fixed;
            top: 40%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 48pt;
            font-weight: bold;
            color: rgba(220, 38, 38, 0.08);
            transform: rotate(-30deg);
            z-index: -1000;
        }

        .watermark-superseded {
            position: fixed;
            top: 40%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 48pt;
            font-weight: bold;
            color: rgba(100, 116, 139, 0.08);
            transform: rotate(-30deg);
            z-index: -1000;
        }
    </style>
</head>
<body>

@if($agreement->status->value === 'revoked')
    <div class="watermark">REVOKED</div>
@elseif($agreement->status->value === 'superseded')
    <div class="watermark-superseded">SUPERSEDED</div>
@endif

<!-- Fixed Running Header on all pages -->
<div class="running-header">
    @if(!empty($headerWaveBase64))
        <img src="{{ $headerWaveBase64 }}" class="header-wave-img" alt="Header Wave">
    @else
        <div style="height: 13.5mm; background: linear-gradient(to right, #6B3D95, #EE3A81);"></div>
    @endif
    <div class="header-content-wrap">
        <table class="header-table">
            <tr>
                <td style="width: 44%; text-align: left;">
                    @if(!empty($logoBase64))
                        <img src="{{ $logoBase64 }}" class="logo-img" alt="Tech Googly">
                    @else
                        <div style="font-size: 14pt; font-weight: bold; color: #6B3D95;">TECH GOOGLY</div>
                        <div style="font-size: 7.5pt; color: #EE3A81; font-weight: bold;">Innovation For You</div>
                    @endif
                </td>
                <td style="width: 56%; text-align: left;">
                    <div class="header-contact-box">
                        <div class="contact-row"><strong>Phone:</strong> {{ $companySettings['company_phone'] ?? '+91 6295710238' }}</div>
                        <div class="contact-row"><strong>Web:</strong> {{ $companySettings['company_website'] ?? 'www.techgoogly.com' }}</div>
                        <div class="contact-row"><strong>ADD:</strong> {{ $companySettings['company_address'] ?? '2 No. Park Street Road, Near Surendra Cinema Hall, Ranaghat, Nadia, West Bengal. - 741201' }}</div>
                    </div>
                </td>
            </tr>
        </table>
        <table class="brand-accent-bar">
            <tr>
                <td style="width: 60%; background-color: #6B3D95;"></td>
                <td style="width: 40%; background-color: #EE3A81;"></td>
            </tr>
        </table>
    </div>
</div>

<!-- Fixed Running Footer on all pages -->
<div class="running-footer">
    <div class="footer-content-wrap">
        <div class="footer-divider-line"></div>
        <table class="footer-table">
            <tr>
                <!-- Column 1: Phone (~27%) -->
                <td style="width: 27%; vertical-align: middle; padding: 0 8px 0 0;">
                    <table class="footer-item-table">
                        <tr>
                            <td class="footer-icon-cell">
                                @if(!empty($phoneIconBase64))
                                    <img src="{{ $phoneIconBase64 }}" class="footer-icon" alt="Phone">
                                @else
                                    <span style="color: #0072b8; font-weight: bold; font-size: 8pt;">[P]</span>
                                @endif
                            </td>
                            <td class="footer-text-cell">
                                <span class="footer-text">{{ $companySettings['company_phone'] ?? '+91 6295710238' }}</span>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Column 2: Email (~27%) -->
                <td style="width: 27%; vertical-align: middle; padding: 0 8px 0 0;">
                    <table class="footer-item-table">
                        <tr>
                            <td class="footer-icon-cell">
                                @if(!empty($emailIconBase64))
                                    <img src="{{ $emailIconBase64 }}" class="footer-icon" alt="Email">
                                @else
                                    <span style="color: #0072b8; font-weight: bold; font-size: 8pt;">[@]</span>
                                @endif
                            </td>
                            <td class="footer-text-cell">
                                <span class="footer-text">{{ $companySettings['company_email'] ?? 'info@techgoogly.com' }}</span>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Column 3: Address (~46%) -->
                <td style="width: 46%; vertical-align: middle; padding: 0;">
                    <table class="footer-item-table">
                        <tr>
                            <td class="footer-icon-cell">
                                @if(!empty($locIconBase64))
                                    <img src="{{ $locIconBase64 }}" class="footer-icon" alt="Location">
                                @else
                                    <span style="color: #0072b8; font-weight: bold; font-size: 8pt;">[*]</span>
                                @endif
                            </td>
                            <td class="footer-text-cell">
                                <span class="footer-text-loc">2 No. Park Street Road, Near Surendra Cinema Hall, Ranaghat, Nadia, West Bengal - 741201</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    @if(!empty($footerWaveBase64))
        <img src="{{ $footerWaveBase64 }}" class="footer-wave-img" alt="Footer Wave">
    @endif
</div>

<!-- Document Header Title -->
<div class="doc-title-box">
    <h1 class="doc-title">REFERRAL PARTNER AGREEMENT</h1>
    <div class="doc-subtitle">OFFICIAL REFERRAL CHANNEL PARTNER COVENANT</div>
</div>

<!-- Metadata Specification Box -->
<table class="metadata-table">
    <tr>
        <td class="meta-label">Agreement Number:</td>
        <td class="meta-value">{{ $agreement->agreement_number }}</td>
        <td class="meta-label">Agreement Status:</td>
        <td class="meta-value">
            <span class="badge badge-{{ $agreement->status->value === 'pending_acceptance' ? 'pending' : ($agreement->status->value === 'accepted' ? 'accepted' : ($agreement->status->value === 'revoked' ? 'revoked' : 'superseded')) }}">
                {{ $agreement->status->label() }}
            </span>
        </td>
    </tr>
    <tr>
        <td class="meta-label">Partner Code:</td>
        <td class="meta-value">{{ $partner->partner_code }}</td>
        <td class="meta-label">Template Version:</td>
        <td class="meta-value">v{{ $agreement->template_version ?? '2.0' }} (Rev {{ $agreement->version }})</td>
    </tr>
    <tr>
        <td class="meta-label">Partner Full Name:</td>
        <td class="meta-value">{{ $partner->user?->name ?? 'Authorized Partner' }}</td>
        <td class="meta-label">Issue Date (IST):</td>
        <td class="meta-value">{{ $agreement->issue_date->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="meta-label">Partner Entity:</td>
        <td class="meta-value">{{ $partner->company_name ?: ($partner->user?->name ?? 'Authorized Partner') }}</td>
        <td class="meta-label">Effective Date (IST):</td>
        <td class="meta-value">{{ $agreement->effective_date->format('d M Y') }}</td>
    </tr>
</table>

<!-- Agreement Content Clauses (Part B) -->
<div class="agreement-body">
    {!! $renderedContent !!}
</div>

<!-- Commercial Schedule Box (Part C Dynamic Commission Snapshot) -->
<div class="schedule-box">
    <div class="schedule-title">Commercial Schedule &amp; Commission Remuneration</div>
    <table class="schedule-table">
        <tr>
            <td style="width: 25%; font-weight: bold; color: #166534;">
                @if(strtolower((string)$agreement->commission_type_snapshot) === 'fixed')
                    Fixed Commission Amount:
                @else
                    Standard Commission Rate:
                @endif
            </td>
            <td style="width: 25%; font-weight: bold; color: #1e293b;">
                @if(strtolower((string)$agreement->commission_type_snapshot) === 'fixed')
                    ₹{{ number_format($agreement->commission_rate_snapshot, 2) }}
                @else
                    {{ number_format($agreement->commission_rate_snapshot, 2) }}%
                @endif
            </td>
            <td style="width: 25%; font-weight: bold; color: #166534;">Commission Type:</td>
            <td style="width: 25%; font-weight: bold; color: #1e293b;">{{ ucfirst($agreement->commission_type_snapshot) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #166534;">Commission Policy:</td>
            <td style="color: #334155;">{{ $policyName ?? 'Standard Partner Policy' }} (v{{ $agreement->commission_policy_version ?? '1.0' }})</td>
            <td style="font-weight: bold; color: #166534;">Effective Date (IST):</td>
            <td style="color: #334155;">{{ $agreement->effective_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td colspan="4" style="padding-top: 3px; font-size: 7.2pt; color: #166534; line-height: 1.3;">
                <em>* Note: Standard commission applies unless a different project-specific commission rate or fixed remuneration is specifically configured for a project in the Partner Portal. Commission is payable solely on cleared, verified customer payments actually received by Tech Googly.</em>
            </td>
        </tr>
    </table>
</div>

<!-- Acceptance & Signature Section -->
<div class="signatures-wrap">
    <table class="signatures-table">
        <tr>
            <td class="sig-col">
                <div class="sig-heading">For Tech Googly</div>
                <div class="sig-row"><strong>Signatory:</strong> Authorized Representative</div>
                <div class="sig-row"><strong>Entity:</strong> Tech Googly</div>
                <div class="sig-row"><strong>Office:</strong> 2 No. Park Street Road, Ranaghat, Nadia, West Bengal - 741201</div>
                <div class="sig-stamp-box" style="background-color: #f5f3ff; border: 1px solid #ddd6fe; color: #5b21b6;">
                    <strong>TECH GOOGLY AUTHORIZED ISSUANCE</strong><br>
                    Verification Key: TG-AUTH-{{ substr(md5($agreement->agreement_number), 0, 10) }}<br>
                    Issuing Authority: Channel Partner Program Administration
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td class="sig-col">
                <div class="sig-heading">For Referral Partner</div>
                <div class="sig-row"><strong>Partner Name:</strong> {{ $partner->user?->name ?? 'Authorized Partner' }}</div>
                <div class="sig-row"><strong>Partner Code:</strong> {{ $partner->partner_code }}</div>
                <div class="sig-row"><strong>Agreement Number:</strong> {{ $agreement->agreement_number }}</div>

                @if($agreement->status->value === 'accepted' && $agreement->accepted_at)
                    <div class="sig-stamp-box sig-accepted">
                        <strong>[ DIGITALLY ACCEPTED &amp; EXECUTED ]</strong><br>
                        Accepted Date (IST): {{ $agreement->accepted_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}<br>
                        Acceptance IP: {{ $agreement->accepted_ip ?? 'Recorded at Portal' }}<br>
                        Audit Reference: {{ substr(hash('sha256', $agreement->agreement_number . $agreement->accepted_at->toIso8601String()), 0, 16) }}<br>
                        Status: Verified Electronic Acceptance
                    </div>
                @elseif($agreement->status->value === 'revoked')
                    <div class="sig-stamp-box sig-revoked">
                        <strong>[ AGREEMENT REVOKED ]</strong><br>
                        Revoked Date (IST): {{ $agreement->revoked_at ? $agreement->revoked_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') : 'N/A' }}<br>
                        Reason: {{ $agreement->revocation_reason ?? 'Administrative Action' }}
                    </div>
                @else
                    <div class="sig-stamp-box sig-pending">
                        <strong>[ PENDING ELECTRONIC ACCEPTANCE ]</strong><br>
                        Awaiting partner electronic signature &amp; acceptance via partner.techgoogly.com.
                    </div>
                @endif
            </td>
        </tr>
    </table>
</div>

</body>
</html>
