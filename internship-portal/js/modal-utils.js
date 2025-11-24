/**
 * Modal Utilities - Replace alert() with themed Bootstrap modals
 */

// Create a reusable alert modal
function showAlertModal(title, message, type = 'info', callback = null) {
    // Remove existing alert modal if any
    const existingModal = document.getElementById('appAlertModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Create modal element
    const modal = document.createElement('div');
    modal.id = 'appAlertModal';
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('data-bs-backdrop', 'static');
    modal.setAttribute('data-bs-keyboard', 'false');

    // Determine icon and color based on type
    let iconClass = 'ph-info';
    let iconColor = 'text-primary';
    if (type === 'success') {
        iconClass = 'ph-check-circle';
        iconColor = 'text-success';
    } else if (type === 'error' || type === 'danger') {
        iconClass = 'ph-x-circle';
        iconColor = 'text-danger';
    } else if (type === 'warning') {
        iconClass = 'ph-warning';
        iconColor = 'text-warning';
    }

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <div class="mb-3">
                        <i class="${iconClass} ${iconColor}" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-2">${title}</h5>
                    <p class="text-muted mb-0">${message}</p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const modalInstance = new bootstrap.Modal(modal);
    
    // Handle callback after modal is hidden
    if (callback) {
        modal.addEventListener('hidden.bs.modal', () => {
            callback();
            modal.remove();
        }, { once: true });
    } else {
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        }, { once: true });
    }

    modalInstance.show();
}

// Create a confirmation modal
function showConfirmModal(title, message, confirmText = 'Confirm', cancelText = 'Cancel', callback = null) {
    // Remove existing confirm modal if any
    const existingModal = document.getElementById('appConfirmModal');
    if (existingModal) {
        existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'appConfirmModal';
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('data-bs-backdrop', 'static');
    modal.setAttribute('data-bs-keyboard', 'false');

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">${message}</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">${cancelText}</button>
                    <button type="button" class="btn btn-primary" id="confirmBtn">${confirmText}</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const modalInstance = new bootstrap.Modal(modal);
    const confirmBtn = modal.querySelector('#confirmBtn');

    confirmBtn.addEventListener('click', () => {
        modalInstance.hide();
        if (callback) {
            callback(true);
        }
        modal.remove();
    });

    modal.addEventListener('hidden.bs.modal', () => {
        if (callback) {
            callback(false);
        }
        modal.remove();
    }, { once: true });

    modalInstance.show();
}

// Replace window.alert and window.confirm globally
window.alert = function(message) {
    showAlertModal('Notification', message, 'info');
};

window.confirm = function(message) {
    return new Promise((resolve) => {
        showConfirmModal('Confirm', message, 'OK', 'Cancel', (result) => {
            resolve(result);
        });
    });
};

