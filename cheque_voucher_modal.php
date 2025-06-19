<div class="modal fade" id="chequeVoucherModal" tabindex="-1" aria-labelledby="chequeVoucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chequeVoucherModalLabel">
                    <i class="fas fa-receipt me-2"></i>Enter Payment Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please enter the cheque number and voucher number for this cash advance.
                </div>
                <form id="chequeVoucherForm" novalidate>
                    <div class="mb-3">
                        <label for="cheque_number" class="form-label">
                            <i class="fas fa-money-check me-2"></i>Cheque Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="cheque_number" name="cheque_number" 
                               placeholder="Enter cheque number" required>
                        <div class="form-text">Enter the cheque number issued for this cash advance.</div>
                    </div>
                    <div class="mb-3">
                        <label for="voucher_number" class="form-label">
                            <i class="fas fa-file-invoice me-2"></i>Voucher Number <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="voucher_number" name="voucher_number" 
                               placeholder="Enter voucher number" required>
                        <div class="form-text">Enter the voucher number associated with this cash advance.</div>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Please verify these numbers carefully before submitting. 
                        They will be recorded in the system and cannot be changed later.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmGrantBtn">
                    <i class="fas fa-check me-2"></i>Grant Cash Advance
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showChequeVoucherModal() {
    // Remove any existing modal with the same ID
    const oldModal = document.getElementById('chequeVoucherModal');
    if (oldModal) oldModal.remove();

    fetch('cheque_voucher_modal.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalContainer').innerHTML = html;

            // Wait for DOM to update
            setTimeout(() => {
                const modalElement = document.getElementById('chequeVoucherModal');
                // Fix z-index and pointer events
                modalElement.style.zIndex = '2000';
                modalElement.style.pointerEvents = 'auto';

                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();

                // Focus the first input
                modalElement.addEventListener('shown.bs.modal', function() {
                    document.getElementById('cheque_number').focus();
                }, { once: true });

                document.getElementById('confirmGrantBtn').addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const chequeNumber = document.getElementById('cheque_number').value.trim();
                    const voucherNumber = document.getElementById('voucher_number').value.trim();
                    if (!chequeNumber || !voucherNumber) {
                        alert('Please enter both cheque number and voucher number.');
                        return;
                    }
                    submitForm(chequeNumber, voucherNumber);
                    modal.hide();
                });
            }, 10);
        });
}
</script>

<style>
    .modal-content {
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(16,185,129,0.18);
        border: none;
        overflow: hidden;
    }
    .modal-header {
        background: linear-gradient(90deg, #10b981 0%, #2563eb 100%);
        color: #fff;
        border-bottom: none;
        border-radius: 20px 20px 0 0;
        padding: 1.5rem 2rem 1.2rem 2rem;
        box-shadow: 0 2px 8px rgba(16,185,129,0.08);
    }
    .modal-title {
        font-size: 1.35rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 0.7rem;
    }
    .modal-header .btn-close {
        background-color: #fff;
        opacity: 0.8;
        border-radius: 50%;
        margin-left: 1rem;
        transition: opacity 0.2s;
    }
    .modal-header .btn-close:hover {
        opacity: 1;
    }
    .modal-body {
        background: #f8fafc;
        padding: 2rem 2rem 1.5rem 2rem;
    }
    .modal-footer {
        background: linear-gradient(90deg, #e0f2fe 0%, #f8fafc 100%);
        border-top: none;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 -2px 8px rgba(16,185,129,0.04);
        padding: 1.2rem 2rem;
    }
    .modal .form-control {
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        padding: 0.9rem 1.1rem;
        font-size: 1.05rem;
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .modal .form-control:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 0.15rem rgba(16,185,129,0.13);
    }
    .modal .btn {
        border-radius: 18px;
        font-weight: 700;
        font-size: 1.05rem;
        padding: 0.6rem 1.6rem;
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 8px rgba(16,185,129,0.08);
    }
    .modal .btn-success {
        background: linear-gradient(90deg, #10b981 0%, #2563eb 100%);
        border: none;
        color: #fff;
    }
    .modal .btn-success:hover {
        background: linear-gradient(90deg, #2563eb 0%, #10b981 100%);
        color: #fff;
    }
    .modal .btn-secondary {
        background: #e0e7ef;
        color: #2563eb;
        border: none;
    }
    .modal .btn-secondary:hover {
        background: #2563eb;
        color: #fff;
    }
    .modal .alert {
        border-radius: 12px;
        font-size: 1.01rem;
        margin-bottom: 1.2rem;
    }
    @media (max-width: 600px) {
        .modal-content, .modal-header, .modal-footer, .modal-body {
            padding-left: 0.7rem !important;
            padding-right: 0.7rem !important;
        }
        .modal-header, .modal-footer {
            padding-top: 1rem !important;
            padding-bottom: 1rem !important;
        }
    }
</style> 