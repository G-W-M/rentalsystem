{{-- resources/views/exports/units-pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Units Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .meta { color: #6b7280; font-size: 9px; margin-bottom: 12px; }
        .filters { background: #f3f4f6; padding: 6px 8px; margin-bottom: 12px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #055236; color: #fff; text-align: left; padding: 6px; font-size: 9px; }
        td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .num { text-align: right; }
        .st-occupied    { color: #2563eb; font-weight: bold; }
        .st-available   { color: #16a34a; font-weight: bold; }
        .st-maintenance { color: #d97706; font-weight: bold; }
        .empty { text-align: center; color: #9ca3af; padding: 20px; }
        .totals { margin-top: 12px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>

<h1>System Units Report</h1>
<div class="meta">
    Generated {{ $generatedAt->format('d M Y, H:i') }} by {{ $generatedBy }} &nbsp;|&nbsp;
    Total units: {{ $units->count() }}
</div>

@if(!empty($filters))
    <div class="filters">
        <strong>Filters applied:</strong>
        @foreach($filters as $label => $value)
            {{ $label }}: {{ $value }}@if(!$loop->last) &nbsp;|&nbsp; @endif
        @endforeach
    </div>
@endif

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Unit No.</th>
            <th>Property</th>
            <th>Landlord</th>
            <th class="num">Rent (KES)</th>
            <th>Status</th>
            <th>Current Tenant</th>
        </tr>
    </thead>
    <tbody>
        @forelse($units as $u)
            @php
                $occupancy  = $u->activeOccupancy ?? null;
                $tenantName = $occupancy && $occupancy->tenant && $occupancy->tenant->user
                    ? $occupancy->tenant->user->full_name
                    : '-';
            @endphp
            <tr>
                <td>{{ $u->id }}</td>
                <td>{{ $u->unit_number }}</td>
                <td>{{ $u->property ? $u->property->name : '-' }}</td>
                <td>
                    {{ $u->property && $u->property->landlord && $u->property->landlord->user
                        ? $u->property->landlord->user->full_name
                        : '-' }}
                </td>
                <td class="num">{{ number_format((float) $u->rent_amount, 2) }}</td>
                <td class="st-{{ $u->status }}">{{ ucfirst($u->status) }}</td>
                <td>{{ $tenantName }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No units match the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>

@if($units->count() > 0)
    @php
        $occupied    = $units->where('status', 'occupied')->count();
        $available   = $units->where('status', 'available')->count();
        $maintenance = $units->where('status', 'maintenance')->count();
        $potential   = $units->sum('rent_amount');
        $actual      = $units->where('status', 'occupied')->sum('rent_amount');
    @endphp
    <div class="totals">
        Occupied: {{ $occupied }} &nbsp;|&nbsp;
        Available: {{ $available }} &nbsp;|&nbsp;
        Under maintenance: {{ $maintenance }}<br>
        Potential monthly rent: KES {{ number_format((float) $potential, 2) }} &nbsp;|&nbsp;
        Actual (occupied only): KES {{ number_format((float) $actual, 2) }}
    </div>
@endif

</body>
</html>
