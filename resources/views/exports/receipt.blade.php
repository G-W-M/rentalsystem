<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    @include('exports._pdf_styles')
    <style>
        .receipt-box {
            border: 2px solid #9BE866;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
            background: #f9fefb;
        }

        .receipt-row {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-row td {
            border: none;
            padding: 6px 4px;
            border-bottom: 1px solid #e9ecef;
            font-size: 11px;
            background: transparent;
        }

        .receipt-row tr:last-child td {
            border-bottom: none;
        }

        .receipt-row td:first-child {
            color: #6c757d;
            width: 42%;
        }

        .receipt-row td:last-child {
            font-weight: bold;
            color: #17202a;
            text-align: right;
        }

        .amount-block {
            text-align: center;
            background: #FEFDD6;
            border: 1px solid #9BE866;
            border-radius: 6px;
            padding: 18px;
            margin: 16px 0;
        }

        .amount-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6c757d;
            margin-bottom: 4px;
        }

        .amount-value {
            font-size: 30px;
            font-weight: bold;
            color: #504E76;
            line-height: 1.2;
        }

        .verified-badge {
            display: inline-block;
            background: #9BE866;
            color: #1a1a1a;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 3px 10px;
            border-radius: 12px;
        }
    </style>
</head>

<body>

    {{-- ===== Header ===== --}}
    <div class="pdf-header">
        <table>
            <tr>
                <td class="logo-cell">
                    <div class="logo-circle">
                        <img src="{{ public_path('images/logo.jpg') }}" alt="Logo">
                    </div>
                </td>
                <td class="brand-cell">
                    <div class="brand-name">Rental System</div>
                    <div class="report-title">Payment Receipt</div>
                </td>
                <td class="meta-cell">
                    Receipt Ref: <strong>{{ $receiptRef }}</strong><br>
                    Generated: {{ now()->format('d M Y, H:i') }}<br>
                    <span class="verified-badge">&#10003; Verified</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ===== Amount Block ===== --}}
    <div class="amount-block">
        <div class="amount-label">Amount Paid</div>
        <div class="amount-value">KES {{ number_format((float) $payment->amount, 2) }}</div>
    </div>

    {{-- ===== Receipt Details ===== --}}
    <div class="receipt-box">
        <table class="receipt-row">
            <tr>
                <td>Tenant Name</td>
                <td>{{ $payment->tenant?->user?->full_name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tenant Email</td>
                <td>{{ $payment->tenant?->user?->email ?? '-' }}</td>
            </tr>
            <tr>
                <td>Property</td>
                <td>{{ $property?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Unit</td>
                <td>{{ $unit?->unit_number ?? '-' }}</td>
            </tr>
            <tr>
                <td>Payment Method</td>
                <td>{{ ucfirst($payment->payment_method ?? '-') }}</td>
            </tr>
            <tr>
                <td>Transaction Reference</td>
                <td>{{ $payment->transaction_id ?? '-' }}</td>
            </tr>
            <tr>
                <td>Payment Date</td>
                <td>{{ optional($payment->payment_date)->format('d M Y') ?? '-' }}</td>
            </tr>
            <tr>
                <td>Due Date</td>
                <td>{{ optional($payment->due_date)->format('d M Y') ?? '-' }}</td>
            </tr>
            <tr>
                <td>Verified On</td>
                <td>{{ optional($payment->verified_at)->format('d M Y, H:i') ?? '-' }}</td>
            </tr>
            <tr>
                <td>Notes</td>
                <td>{{ $payment->notes ?? '-' }}</td>
            </tr>
            <tr>
                <td>Receipt Reference</td>
                <td>{{ $receiptRef }}</td>
            </tr>
        </table>
    </div>

    {{-- ===== Footer ===== --}}
    <div class="pdf-footer">
        This receipt confirms a verified rent payment recorded in the Rental System.<br>
        For queries contact your landlord or property manager.<br><br>
        Rental System &nbsp;|&nbsp; Confidential &nbsp;|&nbsp; Generated {{ now()->format('d M Y, H:i') }}
    </div>

</body>

</html>
