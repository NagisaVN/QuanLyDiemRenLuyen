import './echo';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

function toastContainer() {
    let container = document.getElementById('realtime-toast-container');

    if (!container) {
        container = document.createElement('div');
        container.id = 'realtime-toast-container';
        container.className = 'position-fixed';
        container.style.top = '72px';
        container.style.right = '16px';
        container.style.zIndex = '1080';
        container.style.maxWidth = '360px';
        document.body.appendChild(container);
    }

    return container;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function showEvaluationToast(payload) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.setAttribute('data-delay', '7000');

    const link = payload.url
        ? `<a class="btn btn-sm btn-outline-primary mt-2" href="${escapeHtml(payload.url)}">Xem chi tiết</a>`
        : '';

    toast.innerHTML = `
        <div class="toast-header">
            <strong class="mr-auto">${escapeHtml(payload.title ?? 'Cập nhật phiếu')}</strong>
            <small>${payload.timestamp ? new Date(payload.timestamp).toLocaleTimeString('vi-VN') : ''}</small>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Đóng">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body">
            <div>${escapeHtml(payload.message ?? '')}</div>
            ${link}
        </div>
    `;

    toastContainer().appendChild(toast);

    if (window.jQuery?.fn?.toast) {
        window.jQuery(toast).toast('show').on('hidden.bs.toast', () => toast.remove());
    } else {
        setTimeout(() => toast.remove(), 7000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

    if (!userId || !window.Echo) {
        return;
    }

    window.Echo.private(`users.${userId}`)
        .listen('.evaluation.status.changed', showEvaluationToast);
});
