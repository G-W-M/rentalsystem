@props([
    'id' => 'confirmModal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure you want to proceed?',
    'detail' => null,
    'confirmText' => 'Confirm',
    'confirmVariant' => 'danger',
    'cancelText' => 'Cancel',
    'icon' => 'fa-exclamation-triangle',
    'iconColor' => 'text-warning',
    'onConfirm' => null,
    'onCancel' => null,
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="text-center mb-3">
                    <i class="fas {{ $icon }} {{ $iconColor }} fs-1"></i>
                </div>
                <p class="text-center mb-0">{{ $message }}</p>
                @if ($detail)
                    <p class="text-center text-muted small mt-2">{{ $detail }}</p>
                @endif
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    onclick="{{ $onCancel ?? '' }}">
                    <i class="fas fa-times me-1"></i> {{ $cancelText }}
                </button>
                <button type="button" class="btn btn-{{ $confirmVariant }} btn-confirm"
                    onclick="{{ $onConfirm ?? '' }}">
                    <i class="fas fa-check me-1"></i> {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        window.confirmAction = function(message, options = {}) {
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                if (!modal) {
                    resolve(false);
                    return;
                }

                const body = modal.querySelector('.modal-body p');
                if (body) body.textContent = message;

                const confirmBtn = modal.querySelector('.btn-confirm');
                const cancelBtn = modal.querySelector('[data-bs-dismiss="modal"]');

                let resolved = false;
                const cleanup = () => {
                    resolved = true;
                };
                const onConfirm = () => {
                    cleanup();
                    resolve(true);
                };
                const onCancel = () => {
                    cleanup();
                    resolve(false);
                };
                const onHidden = () => {
                    if (!resolved) resolve(false);
                };

                confirmBtn.onclick = onConfirm;
                cancelBtn.onclick = onCancel;
                modal.addEventListener('hidden.bs.modal', onHidden);

                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            });
        };
    </script>
@endpush
