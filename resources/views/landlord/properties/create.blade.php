@extends('layouts.landlord')

@section('title', 'Add Property')

@section('content')
    <div class="p-4">
        <h1 class="text-2xl font-bold text-primary mb-4">Add Property</h1>
        <div class="card shadow-sm rounded-lg border-0" style="max-width: 560px;">
            <div class="card-body">
                <div id="create-result"></div>
                <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control"
                        id="name"></div>
                <div class="mb-3"><label class="form-label">Address</label>
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
                <div class="mb-3"><label class="form-label">Description</label>
                    <textarea class="form-control" id="description" rows="2"></textarea>
                </div>
                <button class="btn btn-primary" id="create-btn">Create Property</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import {
            api,
            renderError
        } from '/resources/js/api.js';

        document.getElementById('create-btn').addEventListener('click', async () => {
            const out = document.getElementById('create-result');
            out.innerHTML = '';
            try {
                await api('/api/landlord/properties', {
                    method: 'POST',
                    body: JSON.stringify({
                        name: document.getElementById('name').value,
                        address: document.getElementById('address').value,
                        property_type: document.getElementById('property_type').value,
                        description: document.getElementById('description').value,
                    }),
                });
                out.innerHTML =
                    '<div class="alert alert-success">Property created. <a href="/landlord/properties">View all</a></div>';
            } catch (e) {
                renderError(out, e);
            }
        });
    </script>
@endpush
