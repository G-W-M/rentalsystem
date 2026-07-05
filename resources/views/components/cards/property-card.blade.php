@props([
    'property' => null,
    'image' => null,
    'name' => null,
    'title' => null,
    'address' => null,
    'units' => null,
    'occupied' => null,
    'vacant' => null,
    'tenants' => null,
    'occupancy' => null,
    'landlord' => null,
    'caretaker' => null,
    'status' => 'active',
    'url' => null,
    'badge' => null,
])

@php
    $resolvedName = $name ?? ($title ?? ($property['name'] ?? null));
    $resolvedAddress = $address ?? ($property['address'] ?? null);

    $resolvedUnits = $units ?? ($property['units'] ?? 0);
    $resolvedOccupied = $occupied ?? ($property['occupied'] ?? null);
    $resolvedVacant = $vacant ?? ($property['vacant'] ?? null);

    // Backward compatibility with older views that passed `tenants` as occupied count.
    if ($resolvedOccupied === null && $tenants !== null) {
        $resolvedOccupied = $tenants;
    }

    // Backward compatibility with older views that passed occupancy percentage only.
    if ($resolvedOccupied === null && is_numeric($occupancy) && is_numeric($resolvedUnits)) {
        $resolvedOccupied = (int) round(((float) $occupancy / 100) * (float) $resolvedUnits);
    }

    if ($resolvedOccupied === null) {
        $resolvedOccupied = 0;
    }

    if ($resolvedVacant === null && is_numeric($resolvedUnits) && is_numeric($resolvedOccupied)) {
        $resolvedVacant = max((int) $resolvedUnits - (int) $resolvedOccupied, 0);
    }

    if ($resolvedVacant === null) {
        $resolvedVacant = 0;
    }
@endphp

<div class="card property-card h-100 shadow-sm hover-lift transition-all">
    @if ($image)
        <div class="card-img-top position-relative" style="height: 180px; overflow: hidden;">
            <img src="{{ $image }}" alt="{{ $resolvedName }}" class="w-100 h-100" style="object-fit: cover;"
                loading="lazy">
            <div class="position-absolute top-0 end-0 m-2">
                <span class="badge bg-{{ $status === 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($status) }}
                </span>
            </div>
        </div>
    @else
        <div class="card-img-top property-image-placeholder d-flex align-items-center justify-content-center"
            style="height: 180px; background: var(--gray-200);">
            <i class="fas fa-building text-muted opacity-50" style="font-size: 48px;"></i>
        </div>
    @endif

    <div class="card-body">
        <h5 class="card-title fw-semibold">
            @if ($url)
                <a href="{{ $url }}" class="text-decoration-none text-primary">{{ $resolvedName }}</a>
            @else
                {{ $resolvedName }}
            @endif
        </h5>

        @if ($resolvedAddress)
            <p class="card-text text-muted small mb-2">
                <i class="fas fa-map-marker-alt me-1"></i> {{ $resolvedAddress }}
            </p>
        @endif

        @if (is_array($badge) && ($badge['text'] ?? false))
            <div class="mb-2">
                <span class="badge bg-{{ $badge['color'] ?? 'primary' }}">{{ $badge['text'] }}</span>
            </div>
        @endif

        <div class="row g-0 text-center mt-3">
            <div class="col-4 border-end">
                <div class="fw-bold">{{ $resolvedUnits }}</div>
                <div class="text-muted small">Units</div>
            </div>
            <div class="col-4 border-end">
                <div class="fw-bold text-success">{{ $resolvedOccupied }}</div>
                <div class="text-muted small">Occupied</div>
            </div>
            <div class="col-4">
                <div class="fw-bold text-danger">{{ $resolvedVacant }}</div>
                <div class="text-muted small">Vacant</div>
            </div>
        </div>

        @if ($landlord || $caretaker)
            <div class="mt-3 pt-2 border-top">
                @if ($landlord)
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Landlord</span>
                        <span class="fw-medium">{{ $landlord }}</span>
                    </div>
                @endif
                @if ($caretaker)
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Caretaker</span>
                        <span class="fw-medium">{{ $caretaker }}</span>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
