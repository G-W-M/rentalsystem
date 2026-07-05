<footer class="footer mt-auto py-3 bg-white border-top">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <span class="text-muted small">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </span>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="text-muted small">
                    Version {{ config('app.version', '1.0.0') }}
                    <span class="mx-2">|</span>
                    <i class="fas fa-heart text-danger"></i>
                </span>
            </div>
        </div>
    </div>
</footer>
