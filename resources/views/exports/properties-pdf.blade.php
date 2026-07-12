{{-- resources/views/exports/properties-pdf.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Properties Report</title>
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
        .empty { text-align: center; color: #9ca3af; padding: 20px; }
        .totals { margin-top: 12px; font-size: 10px; font-weight: bold; }
    </style>
</head>
<body>

<h1>System Properties Report</h1>
<div class="meta">
    Generated {{ $generatedAt->format('d M Y, H:i') }} by {{ $generatedBy }} &nbsp;|&nbsp;
    Total properties: {{ $properties->count() }}
</div>

@if(!empty($filters))
    <div class="filters">
        <strong>Filters applied:</strong>
        @foreach($filters as $label => $value)
            {{ $label }}: {{ $value }}@if(!$loop->last) &nbsp;|&nbsp; @endif
        @endforeach
    </div>
@endif

@php
    $totalUnits    = 0;
    $totalOccupied = 0;
@endphp

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Property Name</th>
            <th>Landlord</th>
            <th>Address</th>
            <th>Type</th>
            <th class="num">Units</th>
            <th class="num">Occupied</th>
            <th class="num">Available</th>
            <th>Caretaker</th>
        </tr>
    </thead>
    <tbody>
        @forelse($properties as $p)
            @php
                $units     = $p->units ?? collect();
                $occupied  = $units->where('status', 'occupied')->count();
                $available = $units->where('status', 'available')->count();
                $totalUnits    += $units->count();
                $totalOccupied += $occupied;
            @endphp
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->landlord && $p->landlord->user ? $p->landlord->user->full_name : '-' }}</td>
                <td>{{ $p->address ?? '-' }}</td>
                <td>{{ $p->type ?? '-' }}</td>
                <td class="num">{{ $units->count() }}</td>
                <td class="num">{{ $occupied }}</td>
                <td class="num">{{ $available }}</td>
                <td>{{ $p->caretaker && $p->caretaker->user ? $p->caretaker->user->full_name : 'Unassigned' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="empty">No properties match the selected filters.</td></tr>
        @endforelse
    </tbody>
</table>

@if($properties->count() > 0)
    <div class="totals">
        Total units across all properties: {{ $totalUnits }} &nbsp;|&nbsp;
        Occupied: {{ $totalOccupied }} &nbsp;|&nbsp;
        Occupancy rate: {{ $totalUnits > 0 ? number_format(($totalOccupied / $totalUnits) * 100, 1) : '0.0' }}%
    </div>
@endif

</body>
</html>
