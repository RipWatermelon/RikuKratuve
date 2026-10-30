(() => {
    const requestsGrid = document.getElementById('requests-grid');
    if (!requestsGrid) return;
    const ratingsGrid = document.getElementById('ratings-grid');
    const borrowedGrid = document.getElementById('borrowed-grid');
    const modal = document.getElementById('request-modal');
    const modalBody = document.getElementById('request-modal-body');
    let requests = [];
    let pendingRatings = [];
    let borrowed = [];

    function stars(rating) {
        const rounded = Math.max(0, Math.min(5, Math.round(Number(rating))));
        return `${'★'.repeat(rounded)}${'☆'.repeat(5 - rounded)}`;
    }

    function showModal(content, title) {
        document.getElementById('request-modal-title').textContent = title;
        modalBody.innerHTML = content;
        modal.classList.add('show', 'opacity-100', 'pointer-events-auto');
        modal.classList.remove('opacity-0', 'pointer-events-none');
    }

    function closeModal() {
        modal.classList.remove('show', 'opacity-100', 'pointer-events-auto');
        modal.classList.add('opacity-0', 'pointer-events-none');
    }

    function renderTiles() {
        requestsGrid.innerHTML = requests.length ? requests.map((request) => `
            <article class="item-card request-card" data-request-id="${request.id}" data-kind="request" role="button" tabindex="0">
                <div class="item-category-icon-box">${categoryIcon(request.category)}</div>
                <div class="item-card-content">
                    <h3>${escapeHtml(shortenTitle(request.item_name))}</h3>
                    <p class="item-status ${request.status === 'pending' ? 'is-available' : 'is-taken'}">${escapeHtml(statusLabel(request.status))}</p>
                    <p class="item-meta">${escapeHtml(request.borrower_name)}</p>
                </div>
            </article>
        `).join('') : '<p class="request-empty">Nav saņemtu pieprasījumu.</p>';
        ratingsGrid.innerHTML = pendingRatings.length ? pendingRatings.map((request) => `
            <article class="item-card request-card" data-request-id="${request.id}" data-kind="rating" role="button" tabindex="0">
                <div class="item-category-icon-box">${categoryIcon(request.category)}</div>
                <div class="item-card-content">
                    <h3>${escapeHtml(shortenTitle(request.item_name))}</h3>
                    <p class="item-status is-available">Gaida vērtējumu</p>
                    <p class="item-meta">${escapeHtml(request.borrower_name)}</p>
                </div>
            </article>
        `).join('') : '<p class="request-empty">Nav neviena pieprasījuma, kas gaidītu vērtējumu.</p>';
        borrowedGrid.innerHTML = borrowed.length ? borrowed.map((request) => `
            <article class="item-card request-card" data-request-id="${request.id}" data-kind="borrowed" role="button" tabindex="0">
                <div class="item-category-icon-box">${categoryIcon(request.category)}</div>
                <div class="item-card-content">
                    <h3>${escapeHtml(shortenTitle(request.item_name))}</h3>
                    <p class="item-status is-taken">Aizņemta</p>
                    <p class="item-meta">${escapeHtml(request.location || 'Nav norādīta atrašanās vieta')}</p>
                </div>
            </article>
        `).join('') : '<p class="request-empty">Jums nav aizņemtu mantu.</p>';
        document.querySelectorAll('.request-card').forEach(bindCardGlow);
    }

    async function loadRequests() {
        try {
            const payload = await apiFetch('../api/requests.php?action=list');
            requests = payload.requests || [];
            pendingRatings = payload.pending_ratings || [];
            borrowed = payload.borrowed || [];
            renderTiles();
        } catch (error) {
            showNotice('#requests-notice', error.message, 'error');
        }
    }

    function openRequest(request) {
        showModal(`
            <p class="item-meta"><strong>Manta:</strong> ${escapeHtml(request.item_name)}</p>
            <p class="item-meta"><strong>Lietotājs:</strong> ${escapeHtml(request.borrower_name)}</p>
            <p class="item-meta"><strong>Pašreizējais reitings:</strong> <span class="rating-stars">${stars(request.borrower_rating)}</span> (${Number(request.borrower_rating).toFixed(1)})</p>
            <p class="item-meta"><strong>Iemesls:</strong> ${escapeHtml(request.reason)}</p>
            <p class="item-meta"><strong>Ilgums:</strong> ${request.duration_days} dienas</p>
            ${request.status === 'pending' ? `<div class="request-actions">
                <button type="button" class="request-accept" data-decision="approved">Apstiprināt</button>
                <button type="button" class="request-deny" data-decision="rejected">Noraidīt</button>
            </div>` : ''}
        `, 'Pieprasījuma detaļas');
        modalBody.querySelectorAll('[data-decision]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await apiFetch('../api/requests.php?action=decision', { method: 'POST', body: JSON.stringify({ request_id: request.id, decision: button.dataset.decision }) });
                    closeModal();
                    await loadRequests();
                } catch (error) {
                    showNotice('#requests-notice', error.message, 'error');
                }
            });
        });
    }

    function openRating(request) {
        showModal(`
            <p class="item-meta"><strong>Manta:</strong> ${escapeHtml(request.item_name)}</p>
            <p class="item-meta"><strong>Lietotājs:</strong> ${escapeHtml(request.borrower_name)}</p>
            <form id="rating-form" class="form-grid">
                <div>
                    <p class="item-meta mb-2"><strong>Vērtējums</strong></p>
                    <div class="rating-choice-buttons">
                        <button type="button" class="rating-choice rating-good" data-score="5" aria-pressed="false">Labs</button>
                        <button type="button" class="rating-choice rating-bad" data-score="1" aria-pressed="false">Slikts</button>
                    </div>
                    <input type="hidden" name="score">
                </div>
                <label for="rating-comment">Komentārs</label>
                <textarea id="rating-comment" name="comment" class="borrow-field" required></textarea>
                <button type="submit" class="borrow-submit-button">Saglabāt vērtējumu</button>
            </form>
        `, 'Gaida vērtējumu');
        const ratingForm = document.getElementById('rating-form');
        ratingForm.querySelectorAll('[data-score]').forEach((button) => {
            button.addEventListener('click', () => {
                ratingForm.querySelector('input[name="score"]').value = button.dataset.score;
                ratingForm.querySelectorAll('[data-score]').forEach((option) => {
                    option.classList.toggle('is-selected', option === button);
                    option.setAttribute('aria-pressed', option === button ? 'true' : 'false');
                });
            });
        });
        ratingForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = new FormData(event.currentTarget);
            if (!form.get('score')) {
                showNotice('#requests-notice', 'Izvēlieties labu vai sliktu vērtējumu', 'error');
                return;
            }
            try {
                await apiFetch('../api/requests.php?action=rate', { method: 'POST', body: JSON.stringify({ request_id: request.id, score: Number(form.get('score')), comment: form.get('comment') }) });
                closeModal();
                await loadRequests();
            } catch (error) {
                showNotice('#requests-notice', error.message, 'error');
            }
        });
    }

    function openBorrowed(request) {
        showModal(`
            <p class="item-meta"><strong>Manta:</strong> ${escapeHtml(request.item_name)}</p>
            <p class="item-meta"><strong>Atrašanās vieta:</strong> ${escapeHtml(request.location || 'Nav norādīta atrašanās vieta')}</p>
            <p class="item-meta"><strong>Ilgums:</strong> ${request.duration_days} dienas</p>
            <p class="item-meta"><strong>Iemesls:</strong> ${escapeHtml(request.reason)}</p>
            <button type="button" class="request-return" id="return-item-button">Atgriezt mantu</button>
        `, 'Aizņemtās mantas');
        document.getElementById('return-item-button').addEventListener('click', async () => {
            try {
                await apiFetch('../api/requests.php?action=return', {
                    method: 'POST',
                    body: JSON.stringify({ request_id: request.id }),
                });
                closeModal();
                await loadRequests();
            } catch (error) {
                showNotice('#requests-notice', error.message, 'error');
            }
        });
    }

    document.addEventListener('click', (event) => {
        if (event.target === modal || event.target.closest('#close-request-modal')) closeModal();
        const card = event.target.closest('.request-card');
        if (!card) return;
        const collection = card.dataset.kind === 'rating'
            ? pendingRatings
            : card.dataset.kind === 'borrowed' ? borrowed : requests;
        const request = collection.find((entry) => String(entry.id) === card.dataset.requestId);
        if (request) {
            if (card.dataset.kind === 'rating') openRating(request);
            else if (card.dataset.kind === 'borrowed') openBorrowed(request);
            else openRequest(request);
        }
    });
    document.addEventListener('keydown', (event) => {
        const card = event.target.closest('.request-card');
        if (card && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            card.click();
        }
    });
    loadRequests();
})();
