@extends('layouts.landlord')

@section('title', 'Add Property')

@section('content')
<div class="p-4">
    <h1 class="text-2xl font-bold text-primary mb-4">Add Property</h1>
    <div class="card shadow-sm rounded-lg border-0" style="max-width: 560px;">
        <div class="card-body">
            <div id="create-result"></div>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" id="name">
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea class="form-control" id="address" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select class="form-select" id="property_type">
                    <option value="apartment">Apartment</option>
                    <option value="house">House</option>
                    <option value="commercial">Commercial</option>
                    <option value="office">Office</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="description" rows="2"></textarea>
            </div>
            <button class="btn btn-primary" id="create-btn">Create Property</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function xsrf() { return decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || ''); }

    document.getElementById('create-btn').addEventListener('click', async () => {
        const out = document.getElementById('create-result');
        out.innerHTML = '';
        try {
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });

            const res = await fetch('/api/landlord/properties', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrf(),
                },
                body: JSON.stringify({
                    name: document.getElementById('name').value,
                    address: document.getElementById('address').value,
                    property_type: document.getElementById('property_type').value,
                    description: document.getElementById('description').value,
                }),
            });

            const body = await res.json();

            if (!res.ok) {
                out.innerHTML = '<div class="alert alert-danger">' + (body.message || 'Failed to create property.') + '</div>';
                return;
            }

            out.innerHTML = '<div class="alert alert-success">Property created. <a href="/landlord/properties">View all</a></div>';
            document.getElementById('name').value = '';
            document.getElementById('address').value = '';
            document.getElementById('description').value = '';
        } catch (e) {
            out.innerHTML = '<div class="alert alert-danger">Network error. Try again.</div>';
        }
    });
</script>
@endpush
