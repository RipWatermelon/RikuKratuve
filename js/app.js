const state = {
    items: [],
    currentUser: null,
    selectedItem: null,
    inventoryPage: 1,
};

const INVENTORY_PAGE_SIZE = 15;

async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
        ...options,
    });

    const contentType = response.headers.get('content-type') || '';
    const isJson = contentType.includes('application/json');
    const data = isJson ? await response.json() : await response.text();

    if (!response.ok) {
        throw new Error(data.error || 'Pieprasījums neizdevās');
    }

    return data;
}

function showNotice(selector, message, type = 'success') {
    const notice = document.querySelector(selector);
    if (!notice) return;
    notice.textContent = message;
    notice.className = 'notice show ' + type;
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function shortenTitle(title, maxLength = 25) {
    const value = String(title || '');
    return value.length > maxLength ? `${value.slice(0, maxLength - 3)}...` : value;
}

function categoryIcon(category) {
    const normalizedCategory = String(category || '').toLowerCase();
    const icons = {
        'grāmatas': '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5v-15Z"/><path d="M4 20.5A2.5 2.5 0 0 1 6.5 18H20M8 7h8M8 10h8"/>',
        'instrumenti': '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'klēpjdatori': '<path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55a1 1 0 0 1-.9 1.45H3.62a1 1 0 0 1-.9-1.45L4 16"/>',
        'piederumi': '<path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/>',
        'laboratorija': '<path d="M9 3h6M10 3v6l-5.2 8.2A2.5 2.5 0 0 0 6.9 21h10.2a2.5 2.5 0 0 0 2.1-3.8L14 9V3"/><path d="M7.5 16h9"/>',
        'inventars': '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
        'pieprasijumi': '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/>',
        'profils': '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    };
    const paths = icons[normalizedCategory] || '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="m8 12 2.5 2.5L16 9"/>';

    return `<svg class="item-category-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;
}

function statusLabel(status) {
    const labels = {
        available: 'Pieejama',
        borrowed: 'Aizņemta',
        requested: 'Pieprasīta',
        pending: 'Gaida apstiprinājumu',
        approved: 'Apstiprināts',
        active: 'Aktīva',
        returned: 'Atgriezta',
        rejected: 'Noraidīts',
    };
    return labels[String(status).toLowerCase()] || status;
}

function canDeleteItem(item) {
    const user = state.currentUser;
    if (!user) return false;

    return user.role === 'admin'
        || Number(item.owner_user_id) === Number(user.id)
        || Number(item.created_by) === Number(user.id);
}

function isCurrentUserItem(item) {
    return Boolean(state.currentUser)
        && Number(item.created_by) === Number(state.currentUser.id);
}

async function loadCurrentUser() {
    try {
        const user = await apiFetch('../api/auth.php?action=me');
        state.currentUser = user.user;
        if (document.querySelector('[data-user-name]')) {
            document.querySelectorAll('[data-user-name]').forEach((el) => {
                el.textContent = user.user.username || user.user.email;
            });
        }
        if (document.querySelector('[data-user-role]')) {
            document.querySelector('[data-user-role]').textContent = user.user.role;
        }
    } catch (error) {
        state.currentUser = null;
    }
}

function bindAuthForms() {
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(loginForm);
            try {
                const result = await apiFetch('../api/auth.php?action=login', {
                    method: 'POST',
                    body: JSON.stringify({
                        username: formData.get('username'),
                        password: formData.get('password'),
                    }),
                });
                showNotice('#login-notice', result.message || 'Pieteikšanās veiksmīga', 'success');
                window.location.href = '../pages/inventory.php';
            } catch (error) {
                showNotice('#login-notice', error.message, 'error');
            }
        });
    }

    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(registerForm);
            try {
                const payload = Object.fromEntries(formData.entries());
                const result = await apiFetch('../api/auth.php?action=register', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                showNotice('#register-notice', result.message || 'Reģistrācija veiksmīga', 'success');
                registerForm.reset();
            } catch (error) {
                showNotice('#register-notice', error.message, 'error');
            }
        });
    }

    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(passwordForm);
            const oldPassword = formData.get('old_password');
            const newPassword = formData.get('new_password');
            try {
                const result = await apiFetch('../api/profile.php?action=change-password', {
                    method: 'POST',
                    body: JSON.stringify({ old_password: oldPassword, new_password: newPassword }),
                });
                showNotice('#profile-notice', result.message || 'Parole atjaunināta', 'success');
                passwordForm.reset();
            } catch (error) {
                showNotice('#profile-notice', error.message, 'error');
            }
        });
    }

    const logoutButton = document.getElementById('logout-btn');
    if (logoutButton) {
        logoutButton.addEventListener('click', async () => {
            try {
                await apiFetch('../api/auth.php?action=logout', { method: 'POST' });
                window.location.href = '../pages/register.php';
            } catch (e) {
                window.location.href = '../pages/register.php';
            }
        });
    }
}

async function loadInventory() {
    const grid = document.getElementById('inventory-grid');
    if (!grid) return;

    const search = document.getElementById('search-input')?.value || '';
    const category = document.getElementById('category-filter')?.value || 'all';
    const includeUnavailable = document.getElementById('include-unavailable')?.checked ? '1' : '0';

    try {
        const payload = await apiFetch(`../api/items.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&include_unavailable=${includeUnavailable}`);
        state.items = payload.items || [];
        state.inventoryPage = 1;
        renderInventoryPage(grid);
    } catch (error) {
        showNotice('#inventory-notice', error.message, 'error');
    }
}

function renderInventoryPage(grid) {
    grid.innerHTML = '';
    const pagination = document.getElementById('inventory-pagination');
    const pageNumber = document.getElementById('inventory-page-number');
    const previousButton = document.getElementById('inventory-prev');
    const nextButton = document.getElementById('inventory-next');

    if (!state.items.length) {
        grid.innerHTML = '<div class="item-card"><h3>Nekas netika atrasts</h3><p>Izmēģiniet citu meklēšanas vaicājumu.</p></div>';
        if (pagination) pagination.hidden = true;
        return;
    }

    const totalPages = Math.ceil(state.items.length / INVENTORY_PAGE_SIZE);
    state.inventoryPage = Math.min(state.inventoryPage, totalPages);
    const start = (state.inventoryPage - 1) * INVENTORY_PAGE_SIZE;
    const pageItems = state.items.slice(start, start + INVENTORY_PAGE_SIZE);

    pageItems.forEach((item) => {
            const card = document.createElement('article');
            card.className = 'item-card';
            card.dataset.itemId = String(item.id);
            card.setAttribute('role', 'button');
            card.tabIndex = 0;
            const isAvailable = String(item.status).toLowerCase() === 'available';
            const isOwnItem = isCurrentUserItem(item);
            const locationValue = String(item.location || '').trim();
            const location = locationValue === 'Nav norādīta atrašanās vieta' ? '' : locationValue;
            card.innerHTML = `
                <div class="item-category-icon-box">${categoryIcon(item.category)}</div>
                <div class="item-card-content">
                    <h3 title="${escapeHtml(item.name)}">
                        ${escapeHtml(shortenTitle(item.name))}
                        ${isOwnItem ? '<span class="item-owner-tag">JŪSU</span>' : ''}
                    </h3>
                    <p class="item-status ${isAvailable ? 'is-available' : 'is-taken'}">
                        ${isAvailable ? 'Pieejams' : 'Aizņemts'}
                    </p>
                    ${location ? `<p class="item-location">
                        <svg class="item-location-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path>
                            <circle cx="12" cy="10" r="2.5"></circle>
                        </svg>
                        <span>${escapeHtml(location)}</span>
                    </p>` : ''}
                </div>
            `;

            bindCardGlow(card);
            grid.appendChild(card);
    });

    if (pagination) pagination.hidden = totalPages <= 1;
    if (pageNumber) pageNumber.textContent = String(state.inventoryPage);
    if (previousButton) previousButton.disabled = state.inventoryPage === 1;
    if (nextButton) nextButton.disabled = state.inventoryPage === totalPages;
}

function bindInventoryPage() {
    const grid = document.getElementById('inventory-grid');
    if (!grid) return;

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', loadInventory);
    }

    const categoryFilter = document.getElementById('category-filter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', loadInventory);
    }

    const includeUnavailable = document.getElementById('include-unavailable');
    if (includeUnavailable) {
        includeUnavailable.addEventListener('change', loadInventory);
    }

    const previousButton = document.getElementById('inventory-prev');
    const nextButton = document.getElementById('inventory-next');
    if (previousButton) {
        previousButton.addEventListener('click', () => {
            if (state.inventoryPage > 1) {
                state.inventoryPage -= 1;
                renderInventoryPage(grid);
            }
        });
    }
    if (nextButton) {
        nextButton.addEventListener('click', () => {
            if (state.inventoryPage < Math.ceil(state.items.length / INVENTORY_PAGE_SIZE)) {
                state.inventoryPage += 1;
                renderInventoryPage(grid);
            }
        });
    }

    grid.addEventListener('click', (event) => {
        const card = event.target.closest('.item-card[data-item-id]');
        if (!card || !grid.contains(card)) return;

        const item = state.items.find((entry) => String(entry.id) === card.dataset.itemId);
        if (item) openItemModal(item);
    });

    grid.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;

        const card = event.target.closest('.item-card[data-item-id]');
        if (!card || !grid.contains(card)) return;

        event.preventDefault();
        const item = state.items.find((entry) => String(entry.id) === card.dataset.itemId);
        if (item) openItemModal(item);
    });

    const itemForm = document.getElementById('add-item-form');
    if (itemForm) {
        itemForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(itemForm);
            try {
                const result = await apiFetch('../api/items.php?action=create', {
                    method: 'POST',
                    body: JSON.stringify({
                        name: formData.get('name'),
                        category: formData.get('category'),
                        description: formData.get('description'),
                        location: formData.get('location'),
                        owner_type: formData.get('owner_type') || 'user',
                    }),
                });
                showNotice('#inventory-notice', result.message || 'Rīks pievienots katalogam', 'success');
                itemForm.reset();
                await loadInventory();
            } catch (error) {
                showNotice('#inventory-notice', error.message, 'error');
            }
        });
    }

    loadInventory();
}

function openItemModal(item) {
    state.selectedItem = item;
    const modal = document.getElementById('item-modal');
    if (!modal) return;
    const modalBody = document.getElementById('item-modal-body');
    const isOwnItem = isCurrentUserItem(item);
    const hasRequested = Boolean(item.has_requested);
    modalBody.innerHTML = `
        <div class="badge">${escapeHtml(item.category)}</div>
        <h2 class="text-2xl">${escapeHtml(item.name)}</h2>
        <p class="item-meta"><strong>Statuss:</strong> ${escapeHtml(statusLabel(item.status))}</p>
        <p class="item-meta"><strong>Apraksts:</strong> ${escapeHtml(item.description || 'Nav apraksta')}</p>
        <p class="item-meta"><strong>Atrašanās vieta:</strong> ${escapeHtml(item.location || 'Nav norādīta atrašanās vieta')}</p>
        <p class="item-meta"><strong>Īpašnieks:</strong> ${escapeHtml(item.owner_type === 'school' ? 'Skola / administrācija' : 'Lietotājs')}</p>
        ${isOwnItem ? '<p class="item-owner-notice">Šī ir jūsu manta.</p>' : hasRequested ? '<p class="item-request-notice">Jūs jau esat nosūtījis pieprasījumu šai mantai.</p>' : `<form id="borrow-form" class="form-grid">
            <div>
                <label for="borrow-duration">Izmantošanas ilgums (dienās)</label>
                <input id="borrow-duration" class="borrow-field borrow-duration-field" name="duration_days" type="number" min="1" max="90" required>
            </div>
            <div>
                <label for="borrow-reason">Aizņemšanas iemesls</label>
                <textarea id="borrow-reason" class="borrow-field borrow-reason-field" name="reason" required>Izmantoju mācību nolūkos.</textarea>
            </div>
            <button type="submit" class="borrow-submit-button">Nosūtīt pieprasījumu</button>
        </form>`}
        ${canDeleteItem(item) ? '<button id="delete-item-button" type="button" class="delete-item-button">Dzēst mantu</button>' : ''}
    `;

    const borrowForm = document.getElementById('borrow-form');
    if (borrowForm) borrowForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(borrowForm);
        try {
            const result = await apiFetch('../api/borrow.php?action=request', {
                method: 'POST',
                body: JSON.stringify({
                    item_id: item.id,
                    duration_days: Number(formData.get('duration_days')),
                    reason: formData.get('reason'),
                }),
            });
            showNotice('#inventory-notice', result.message || 'Pieprasījums nosūtīts', 'success');
            closeItemModal(modal);
            await loadInventory();
        } catch (error) {
            showNotice('#inventory-notice', error.message, 'error');
        }
    });

    const deleteButton = document.getElementById('delete-item-button');
    if (deleteButton) {
        deleteButton.addEventListener('click', async () => {
            if (!window.confirm(`Vai tiešām dzēst “${item.name}”?`)) return;

            try {
                const result = await apiFetch('../api/items.php?action=delete', {
                    method: 'POST',
                    body: JSON.stringify({ id: item.id }),
                });
                closeItemModal(modal);
                showNotice('#inventory-notice', result.message || 'Manta veiksmīgi dzēsta', 'success');
                await loadInventory();
            } catch (error) {
                showNotice('#inventory-notice', error.message, 'error');
            }
        });
    }

    modal.classList.add('show', 'opacity-100', 'pointer-events-auto');
    modal.classList.remove('opacity-0', 'pointer-events-none');
}

function closeItemModal(modal) {
    modal.classList.remove('show', 'opacity-100', 'pointer-events-auto');
    modal.classList.add('opacity-0', 'pointer-events-none');
}

function bindModalClose() {
    const modal = document.getElementById('item-modal');
    if (!modal) return;
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeItemModal(modal);
        }
    });
    const closeBtn = document.getElementById('close-item-modal');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => closeItemModal(modal));
    }
}

async function loadProfile() {
    const profilePanel = document.getElementById('profile-panel');
    if (!profilePanel) return;

    try {
        const payload = await apiFetch('../api/profile.php');
        const profile = payload.profile;
        profilePanel.innerHTML = `
            <div class="user-card panel">
                <label style="font-size: 1.5rem /* 24px */;
    line-height: 2rem /* 32px */;" for="profile-settings">Profils</label>
                <p><strong>Vārds:</strong> ${escapeHtml(profile.username)}</p>
                <p><strong>Loma:</strong> ${escapeHtml(profile.role)}</p>
                <p><strong>E-pasts:</strong> ${escapeHtml(profile.email)}</p>
                <p><strong>Grupa / kods:</strong> ${escapeHtml(profile.group_name || profile.employee_code || '—')}</p>
                <p><strong>Reitings:</strong> <span class="rating-stars">${'★'.repeat(Math.max(1, Math.round(profile.rating)))}${'☆'.repeat(5 - Math.max(1, Math.round(profile.rating)))}</span> (${Number(profile.rating).toFixed(1)})</p>
            </div>
            <hr class="my-1 border-t invisible">
            <div class="section-card panel">
                <label style="font-size: 1.5rem /* 24px */;
    line-height: 2rem /* 32px */;" for="profile-history">Vēsture un saņemtie vērtējumi</label>
                <ul class="list">
                    ${(profile.history || []).map((entry) => `
                        <li>
                            <div><strong>${escapeHtml(entry.name)}</strong></div>
                            <div class="small">Statuss: ${escapeHtml(statusLabel(entry.status))} · ${escapeHtml(entry.requested_at)}</div>
                        </li>
                    `).join('')}
                    ${(profile.received_ratings || []).map((rating) => `
                        <li>
                            <div><strong>${rating.score === 5 ? 'Labs' : 'Slikts'} vērtējums</strong> no ${escapeHtml(rating.rater_name)}</div>
                            <div class="small">${escapeHtml(rating.comment)} · ${escapeHtml(rating.created_at)}</div>
                        </li>
                    `).join('')}
                    ${!(profile.history || []).length && !(profile.received_ratings || []).length ? '<li>Vēl nav reģistrētu aktivitāšu.</li>' : ''}
                </ul>
            </div>
            <hr class="my-1 border-t invisible">
            <div class="section-card panel">
                <label style="font-size: 1.5rem /* 24px */;
    line-height: 2rem /* 32px */;" for="profile-notifications">Paziņojumi</label>
                <ul class="list">
                    ${(profile.notifications || []).map((n) => `
                        <li>
                            <div><strong>${escapeHtml(n.title)}</strong></div>
                            <div class="small">${escapeHtml(n.message)}</div>
                        </li>
                    `).join('') || '<li>Nav paziņojumu.</li>'}
                </ul>
            </div>
        `;
    } catch (error) {
        showNotice('#profile-notice', error.message, 'error');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    (async () => {
        bindAuthForms();
        bindModalClose();
        await loadCurrentUser();
        bindInventoryPage();
        loadProfile();
    })();
});

function bindCardGlow(card) {
    card.addEventListener('pointermove', (event) => {
        const bounds = card.getBoundingClientRect();
        const x = ((event.clientX - bounds.left) / bounds.width) * 100;
        const y = ((event.clientY - bounds.top) / bounds.height) * 100;

        card.style.setProperty('--glow-x', `${x}%`);
        card.style.setProperty('--glow-y', `${y}%`);
    });

    card.addEventListener('pointerenter', () => {
        card.style.setProperty('--glow-x', '50%');
        card.style.setProperty('--glow-y', '50%');
    });

}