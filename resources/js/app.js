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

function updateNotificationCount() {
    const badge = document.getElementById('student-notification-count');

    if (!badge) {
        return;
    }

    const current = Number.parseInt(badge.textContent, 10) || 0;
    const next = current + 1;
    badge.textContent = next > 99 ? '99+' : String(next);
    badge.classList.remove('d-none');
}

function showStudentNotification(payload) {
    updateNotificationCount();
    showEvaluationToast({
        title: payload.title ?? 'Thông báo mới',
        message: payload.message ?? '',
        url: payload.url ?? '',
        timestamp: payload.created_at ?? new Date().toISOString(),
    });
}

function formatActivityDuration(milliseconds) {
    const seconds = Math.max(0, Math.floor(milliseconds / 1000));
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    return `${days} ngày ${hours} giờ ${minutes} phút ${remainingSeconds} giây`;
}

function updateActivityCard(card) {
    const now = Date.now();
    const openAt = Date.parse(card.dataset.openAt);
    const closeAt = Date.parse(card.dataset.closeAt);
    const endAt = Date.parse(card.dataset.endAt);
    const registered = card.dataset.registered === '1';
    const capacity = Number.parseInt(card.dataset.capacity, 10);
    const registeredCount = Number.parseInt(card.querySelector('[data-registered-count]')?.textContent ?? '0', 10);
    let status = card.dataset.status;
    let message = '';

    if (Number.isFinite(endAt) && now >= endAt) {
        status = 'completed';
        message = 'Hoạt động đã kết thúc';
    } else if (Number.isFinite(closeAt) && now >= closeAt) {
        status = 'registration_closed';
        message = 'Hoạt động đã đóng đăng ký';
    } else if (Number.isFinite(openAt) && now >= openAt) {
        status = 'open';
        message = `Thời gian đăng ký còn lại: ${formatActivityDuration(closeAt - now)}`;
    } else {
        status = 'scheduled';
        message = `Hoạt động sẽ mở đăng ký sau: ${formatActivityDuration(openAt - now)}`;
    }

    card.dataset.status = status;
    const labels = { scheduled: 'Chưa mở đăng ký', open: 'Đang mở đăng ký', registration_closed: 'Đã đóng đăng ký', completed: 'Đã kết thúc' };
    const badge = card.querySelector('[data-activity-status]');
    if (badge) {
        badge.textContent = labels[status] ?? status;
        badge.className = `badge text-bg-${status === 'open' ? 'success' : status === 'scheduled' ? 'warning' : status === 'completed' ? 'dark' : 'secondary'}`;
    }

    const countdown = card.querySelector('[data-activity-countdown]');
    if (countdown) countdown.textContent = message;

    const button = card.querySelector('[data-register-button]');
    if (button && !registered) {
        button.disabled = status !== 'open' || (Number.isFinite(capacity) && registeredCount >= capacity);
    }
}

function applyActivityCount(card, registeredCount, remainingSlots) {
    const count = card.querySelector('[data-registered-count]');
    const remaining = card.querySelector('[data-remaining-slots]');
    if (count) count.textContent = String(registeredCount);
    if (remaining && remainingSlots !== null) remaining.textContent = String(remainingSlots);
    updateActivityCard(card);
}

function initializeActivityCards() {
    const cards = [...document.querySelectorAll('[data-activity-card]')];
    if (!cards.length) return;

    cards.forEach(updateActivityCard);
    window.setInterval(() => cards.forEach(updateActivityCard), 1000);

    cards.forEach((card) => {
        const form = card.querySelector('[data-activity-registration-form]');
        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('[data-register-button]');
            const errorBox = card.querySelector('[data-registration-error]');
            button.disabled = true;
            if (errorBox) errorBox.classList.add('d-none');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(Object.values(payload.errors ?? {}).flat()[0] || payload.message || 'Không thể đăng ký hoạt động.');

                card.dataset.registered = '1';
                button.textContent = 'Đã đăng ký';
                button.className = 'btn btn-outline-success w-100';
                applyActivityCount(card, payload.registered_count, payload.remaining_slots);
                showEvaluationToast({ title: 'Đăng ký thành công', message: payload.message, timestamp: new Date().toISOString() });
            } catch (error) {
                button.disabled = false;
                if (errorBox) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('d-none');
                }
            }
        });

        if (window.Echo) {
            window.Echo.private(`activities.${card.dataset.activityId}`)
                .listen('.activity.registration-count.changed', (payload) => applyActivityCount(card, payload.registered_count, payload.remaining_slots));
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeActivityCards();
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

    if (!userId || !window.Echo) {
        return;
    }

    window.Echo.private(`users.${userId}`)
        .listen('.evaluation.status.changed', showEvaluationToast)
        .listen('.student.notification.created', showStudentNotification);
});
