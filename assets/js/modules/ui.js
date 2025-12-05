let notifications = [];

async function ensureNotificationsLoaded() {
    if (notifications.length) return notifications;

    try {
        const { data } = await fetchJSON(`${API_BASE}?resource=notifications`);
        notifications = data || [];
    } catch (err) {
        notifications = [];
    }

    return notifications;
}


function renderNotifications(containerId, filterType) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const list = notifications.filter(n => !filterType || n.type === filterType);
    if (!list.length) {
        container.innerHTML = '<div class="loading">Naujienų nėra.</div>';
        return;
    }
    container.innerHTML = list.map(item => `
        <div class="notice">
            <div>
                <p class="notice-title">${item.message}</p>
                <small class="muted">${item.created_at}</small>
            </div>
            <span class="badge pill">${item.type}</span>
        </div>
    `).join('');
}

function sanitizePayload(payload = {}) {
    const cleaned = {};
    Object.entries(payload).forEach(([key, value]) => {
        if (value !== undefined && value !== null && String(value).trim() !== '') {
            cleaned[key] = value;
        }
    });
    return cleaned;
}

export {
    ensureNotificationsLoaded,
    renderNotifications,
    sanitizePayload
}
