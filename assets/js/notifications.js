(() => {
    const menu = document.getElementById('notificationMenu');
    const toggle = document.getElementById('notificationToggle');
    const panel = document.getElementById('notificationPanel');
    const list = document.getElementById('notificationList');
    const empty = document.getElementById('notificationEmpty');
    const badge = document.getElementById('notificationBadge');
    const markAll = document.getElementById('markAllNotificationsRead');
    const feedback = document.getElementById('notificationFeedback');
    if (!menu || !toggle || !panel || !list || !badge) return;

    const endpoint = '/vehicle-document-manager/notifications/api.php';
    const csrfToken = menu.dataset.csrf || '';
    let loaded = false;
    let loading = false;

    const setCount = (count) => {
        const value = Math.max(0, Number(count) || 0);
        badge.textContent = value > 99 ? '99+' : String(value);
        badge.hidden = value === 0;
        toggle.setAttribute('aria-label', value ? `Notifications, ${value} unread` : 'Notifications');
    };
    const setFeedback = (message = '') => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.hidden = !message;
    };
    const request = async (method = 'GET', body = null) => {
        const options = { method, credentials: 'same-origin', headers: { Accept: 'application/json' } };
        if (body) {
            options.headers['Content-Type'] = 'application/json';
            options.headers['X-CSRF-Token'] = csrfToken;
            options.body = JSON.stringify(body);
        }
        const response = await fetch(endpoint, options);
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'Notifications are temporarily unavailable.');
        return result;
    };
    const render = (items) => {
        list.replaceChildren();
        if (empty) empty.hidden = items.length !== 0;
        if (markAll) markAll.disabled = !items.some((item) => !item.is_read);
        for (const item of items) {
            const link = document.createElement('a');
            link.className = `notification-item${item.is_read ? '' : ' is-unread'}`;
            link.href = item.url;
            link.dataset.notificationId = String(item.id);
            link.dataset.read = item.is_read ? 'true' : 'false';
            const icon = document.createElement('span');
            icon.className = `notification-item-icon ${item.milestone === 'post_expiry' ? 'is-expired' : item.milestone === 'final_week' || item.milestone === 'expiry_day' ? 'is-soon' : 'is-upcoming'}`;
            const iconElement = document.createElement('i');
            iconElement.className = item.milestone === 'post_expiry' ? 'bi bi-exclamation-circle' : item.milestone === 'expiry_day' ? 'bi bi-calendar-event' : 'bi bi-calendar2-check';
            iconElement.setAttribute('aria-hidden', 'true');
            icon.append(iconElement);
            const copy = document.createElement('span');
            copy.className = 'notification-item-copy';
            const status = document.createElement('span');
            status.className = 'notification-item-status';
            status.textContent = item.milestone === 'post_expiry' ? 'Expired' : item.milestone === 'final_week' ? 'Expiring soon' : item.milestone === 'expiry_day' ? 'Expires today' : 'Upcoming expiry';
            const message = document.createElement('span');
            message.className = 'notification-item-message';
            message.textContent = item.message;
            const meta = document.createElement('span');
            meta.className = 'notification-item-meta';
            const expiry = new Date(`${item.expiry_date}T00:00:00`);
            const dateLabel = Number.isNaN(expiry.getTime()) ? item.expiry_date : expiry.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
            const dayOffset = Number(item.days_offset) || 0;
            const timing = dayOffset < 0 ? `${Math.abs(dayOffset)} days overdue` : dayOffset === 0 ? 'Expires today' : `${dayOffset} days remaining`;
            meta.textContent = `${dateLabel} · ${timing}`;
            copy.append(status, message, meta);
            link.append(icon, copy);
            list.append(link);
        }
    };
    const load = async () => {
        if (loading) return;
        loading = true;
        setFeedback('');
        try {
            const result = await request();
            setCount(result.unread_count);
            render(result.notifications || []);
            loaded = true;
        } catch (error) {
            setFeedback(error.message || 'Notifications are temporarily unavailable.');
        } finally {
            loading = false;
        }
    };
    const close = (returnFocus = false) => {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        if (returnFocus) toggle.focus();
    };

    toggle.addEventListener('click', () => {
        const opening = panel.hidden;
        panel.hidden = !opening;
        toggle.setAttribute('aria-expanded', String(opening));
        if (opening && !loaded) load();
    });
    document.addEventListener('click', (event) => {
        if (!menu.contains(event.target) && !panel.hidden) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) close(true);
    });
    list.addEventListener('click', async (event) => {
        const link = event.target.closest('a[data-notification-id]');
        if (!link || link.dataset.read === 'true') return;
        event.preventDefault();
        try {
            const result = await request('POST', { action: 'mark_read', notification_id: Number(link.dataset.notificationId) });
            setCount(result.unread_count);
            link.dataset.read = 'true';
            link.classList.remove('is-unread');
            if (markAll && !list.querySelector('.notification-item.is-unread')) markAll.disabled = true;
        } catch (error) {
            setFeedback(error.message || 'Could not update notification.');
        }
        window.location.assign(link.href);
    });
    if (markAll) {
        markAll.addEventListener('click', async () => {
            markAll.disabled = true;
            try {
                const result = await request('POST', { action: 'mark_all_read' });
                setCount(result.unread_count);
                list.querySelectorAll('.notification-item').forEach((item) => {
                    item.classList.remove('is-unread');
                    item.dataset.read = 'true';
                });
                setFeedback('All notifications marked as read.');
            } catch (error) {
                markAll.disabled = false;
                setFeedback(error.message || 'Could not update notifications.');
            }
        });
    }
    load();
})();
