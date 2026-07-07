{{-- resources/views/tenant/receipt.blade.php --}}
{{-- Rendered by PaymentController@downloadReceipt via dompdf --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $payment->receipt_url ?? $payment->id }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 13px; color: #17202a; background: #fff; }

        .page { padding: 40px 48px; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
        .brand { }
        .brand-name { font-size: 22px; font-weight: 700; color: #055236; }
        .brand-sub  { font-size: 11px; color: #6c757d; margin-top: 2px; }
        .receipt-label { text-align: right; }
        .receipt-label h2 { font-size: 20px; font-weight: 700; color: #055236; text-transform: uppercase; letter-spacing: 1px; }
        .receipt-label p  { font-size: 11px; color: #6c757d; margin-top: 4px; }

        /* Divider */
        .divider { border: none; border-top: 2px solid #055236; margin: 0 0 24px; }
        .divider-light { border: none; border-top: 1px solid #e9ecef; margin: 16px 0; }

        /* Status badge */
        .status-paid { display: inline-block; background: #d4edda; color: #155724; padding: 4px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }

        /* Info grid */
        .info-grid { display: table; width: 100%; margin-bottom: 24px; }
        .info-col   { display: table-cell; width: 50%; vertical-align: top; }
        .info-col h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; margin-bottom: 8px; }
        .info-col p  { font-size: 13px; line-height: 1.7; }

        /* Amount box */
        .amount-box { background: #f0f7f4; border: 1px solid #c3ddd4; border-radius: 8px; padding: 20px 24px; margin-bottom: 24px; display: table; width: 100%; }
        .amount-left  { display: table-cell; vertical-align: middle; }
        .amount-left p { font-size: 12px; color: #6c757d; }
        .amount-left h3 { font-size: 28px; font-weight: 700; color: #055236; margin-top: 4px; }
        .amount-right { display: table-cell; vertical-align: middle; text-align: right; }

        /* Details table */
        .details { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .details th { background: #055236; color: #fff; padding: 9px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
        .details td { padding: 9px 12px; border-bottom: 1px solid #e9ecef; font-size: 13px; }
        .details tr:last-child td { border-bottom: none; }
        .details td:last-child { text-align: right; font-weight: 600; }

        /* Footer */
        .footer { border-top: 1px solid #e9ecef; padding-top: 16px; text-align: center; font-size: 11px; color: #6c757d; line-height: 1.6; }
        .footer strong { color: #055236; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="brand">
            <div class="brand-name">{{ config('app.name', 'Rental System') }}</div>
            <div class="brand-sub">Property Management</div>
            @if ($property)
                <div class="brand-sub" style="margin-top:6px;">
                    {{ $property->name }}<br>
                    {{ $property->address }}
                </div>
            @endif
        </div>
        <div class="receipt-label">
            <h2>Payment Receipt</h2>
            <p>{{ $payment->receipt_url ?? ('RCP-' . $payment->id) }}</p>
            <p style="margin-top:6px;">
                {{ $payment->verified_at ? \Carbon\Carbon::parse($payment->verified_at)->format('d M Y, H:i') : \Carbon\Carbon::parse($payment->updated_at)->format('d M Y') }}
            </p>
            <div style="margin-top:8px;"><span class="status-paid">✓ Paid</span></div>
        </div>
    </div>

    <hr class="divider">

    {{-- Tenant & Unit info --}}
    <div class="info-grid">
        <div class="info-col">
            <h4>Tenant</h4>
            <p>
                <strong>{{ optional(optional($payment->tenant)->user)->full_name ?? 'Tenant' }}</strong><br>
                {{ optional(optional($payment->tenant)->user)->email ?? '' }}<br>
                {{ optional(optional($payment->tenant)->user)->phone ?? '' }}
            </p>
        </div>
        <div class="info-col">
            <h4>Unit</h4>
            <p>
                <strong>Unit {{ $unit->unit_number }}</strong><br>
                {{ $property->name ?? '' }}<br>
                {{ $property->address ?? '' }}
            </p>
        </div>
    </div>

    {{-- Amount --}}
    <div class="amount-box">
        <div class="amount-left">
            <p>Amount Paid</p>
            <h3>KES {{ number_format($payment->amount, 2) }}</h3>
        </div>
        <div class="amount-right">
            <p style="font-size:11px;color:#6c757d;">Billing Period</p>
            <p style="font-size:15px;font-weight:700;color:#055236;">
                {{ \Carbon\Carbon::parse($payment->due_date)->format('F Y') }}
            </p>
        </div>
    </div>

    {{-- Details table --}}
    <table class="details">
        <thead>
            <tr>
                <th>Detail</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Receipt Number</td>
                <td>{{ $payment->receipt_url ?? ('RCP-' . $payment->id) }}</td>
            </tr>
            <tr>
                <td>Payment Date</td>
                <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '—' }}</td>
            </tr>
            <tr>
                <td>Due Date</td>
                <td>{{ \Carbon\Carbon::parse($payment->due_date)->format('d M Y') }}</td>
            </tr>
            <tr>
                <td>Payment Method</td>
                <td class="text-capitalize">{{ ucfirst($payment->payment_method ?? '—') }}</td>
            </tr>
            <tr>
                <td>Transaction Reference</td>
                <td>{{ $payment->transaction_id ?? '—' }}</td>
            </tr>
            <tr>
                <td>Verified By</td>
                <td>
                    @if ($payment->verified_at)
                        {{ \Carbon\Carbon::parse($payment->verified_at)->format('d M Y, H:i') }}
                    @else
                        —
                    @endif
                </td>
            </tr>
            @if ($payment->notes)
            <tr>
                <td>Notes</td>
                <td>{{ $payment->notes }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <p>This is an official payment receipt issued by <strong>{{ config('app.name', 'Rental System') }}</strong>.</p>
        <p>Please retain this document for your records. For queries, contact your landlord or property manager.</p>
        <p style="margin-top:8px;">Generated on {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; {{ config('app.url') }}</p>
    </div>

</div>
</body>
</html>