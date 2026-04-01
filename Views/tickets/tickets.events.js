async function applySearch() {
    currentTicketPage = 1;
    await loadTickets();
}

async function applyStatusFilter(filterValue) {
    activeStatusFilter = filterValue;
    currentTicketPage = 1;
    if (dom.statusFilters) {
        dom.statusFilters.forEach(function (button) {
            const isActive = button.dataset.statusFilter === filterValue;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }
    await loadTickets();
}

function toggleNoticePanel(forceState) {
    if (!dom.noticePanel) { return; }
    const wasOpen = dom.noticePanel.classList.contains('is-open');
    const nextState = typeof forceState === 'boolean' ? forceState : !dom.noticePanel.classList.contains('is-open');
    if (nextState === wasOpen) {
        dom.noticePanel.setAttribute('aria-hidden', nextState ? 'false' : 'true');
        if (dom.noticeButton) {
            dom.noticeButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        }
        return;
    }
    dom.noticePanel.classList.toggle('is-open', nextState);
    dom.noticePanel.setAttribute('aria-hidden', nextState ? 'false' : 'true');
    if (dom.noticeButton) {
        dom.noticeButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        if (!nextState && wasOpen) {
            dom.noticeButton.focus();
        }
    }
    if (nextState) {
        dom.noticePanel.focus();
    }
}

function toggleAvatarMenu(forceState) {
    if (!dom.avatarMenu) { return; }
    const wasOpen = dom.avatarMenu.classList.contains('is-open');
    const nextState = typeof forceState === 'boolean' ? forceState : !dom.avatarMenu.classList.contains('is-open');
    if (nextState === wasOpen) {
        dom.avatarMenu.setAttribute('aria-hidden', nextState ? 'false' : 'true');
        if (dom.avatarButton) {
            dom.avatarButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        }
        return;
    }
    dom.avatarMenu.classList.toggle('is-open', nextState);
    dom.avatarMenu.setAttribute('aria-hidden', nextState ? 'false' : 'true');
    if (dom.avatarButton) {
        dom.avatarButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        if (!nextState && wasOpen) {
            dom.avatarButton.focus();
        }
    }
    if (nextState) {
        const firstItem = dom.avatarMenu.querySelector('a');
        if (firstItem) { firstItem.focus(); }
    }
}

function setupAvatarMenu() {
    if (dom.avatarButton && dom.avatarMenu) {
        dom.avatarButton.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleAvatarMenu();
        });
        dom.avatarButton.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                toggleAvatarMenu(true);
            }
        });
    }
    if (dom.noticeButton) {
        dom.noticeButton.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleNoticePanel();
        });
    }
    if (dom.avatarMenu) {
        dom.avatarMenu.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                toggleAvatarMenu(false);
            }
        });
    }
    if (dom.noticePanel) {
        dom.noticePanel.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                toggleNoticePanel(false);
            }
        });
    }
    document.addEventListener('click', function (event) {
        if (dom.avatarMenu && dom.avatarButton && !dom.avatarMenu.contains(event.target) && !dom.avatarButton.contains(event.target)) {
            toggleAvatarMenu(false);
        }
        if (dom.noticePanel && dom.noticeButton && !dom.noticePanel.contains(event.target) && !dom.noticeButton.contains(event.target)) {
            toggleNoticePanel(false);
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        if (dom.avatarMenu && dom.avatarMenu.classList.contains('is-open')) {
            toggleAvatarMenu(false);
        }
        if (dom.noticePanel && dom.noticePanel.classList.contains('is-open')) {
            toggleNoticePanel(false);
        }
    });
}

function setDefaultOccurrence() {
    if (!dom.occurrenceInput) { return; }
    const now = new Date();
    const offset = now.getTimezoneOffset();
    dom.occurrenceInput.value = new Date(now.getTime() - offset * 60000).toISOString().slice(0, 16);
}

function setupCreateForm() {
    if (!dom.ticketForm) { return; }
    setDefaultOccurrence();
    dom.ticketForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const formData = new FormData(dom.ticketForm);
        const submitButton = dom.ticketForm.querySelector('button[type="submit"]');
        formData.set('_token', getCsrfToken());
        setButtonLoading(submitButton, true, 'Creando...');
        try {
            const response = await fetch('api.php?c=ticket&m=create', { method: 'POST', body: formData });
            const data = await response.json();
            setMessage(dom.formMessage, data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error', 'Creacion de ticket');
            if (data.status) {
                dom.ticketForm.reset();
                setDefaultOccurrence();
                if (expandedTicketId !== null) {
                    await preserveTicketPosition(expandedTicketId, async function () {
                        await refreshTicketsView();
                    });
                } else {
                    await refreshTicketsView();
                }
            }
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}

function setupAssignForm() {
    if (!dom.assignForm) { return; }
    if (dom.assignState) {
        dom.assignState.required = false;
        const label = dom.assignState.closest('label');
        if (label) { label.style.display = 'none'; }
    }
    if (dom.assignTicket) { dom.assignTicket.addEventListener('change', syncAdminAssignState); }
    dom.assignForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const submitButton = dom.assignForm.querySelector('button[type="submit"]');
        setButtonLoading(submitButton, true, 'Asignando...');
        try {
            const data = await postJSON('api.php?c=ticket&m=assign', {
                ticket_id: dom.assignTicket.value,
                tecnico_id: dom.assignTech.value,
                estado_id: dom.assignState.value,
            });
            setMessage(dom.assignMessage, data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error', 'Asignacion');
            if (data.status) {
                const anchorId = expandedTicketId !== null ? expandedTicketId : dom.assignTicket.value;
                await preserveTicketPosition(anchorId, async function () {
                    await refreshTicketsView();
                });
            }
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}

function setupCloseForm() {
    if (!dom.closeForm) { return; }
    const closeCard = dom.closeForm.closest('.card');
    if (closeCard) { closeCard.style.display = 'none'; }
}

function cacheDom() {
    dom.search = byId('ticket-search');
    dom.refreshButton = byId('refresh-btn');
    dom.noticeButton = byId('notice-btn');
    dom.noticePanel = byId('notice-panel');
    dom.noticeCount = byId('notice-count');
    dom.avatarButton = byId('avatar-btn');
    dom.avatarMenu = byId('avatar-menu');
    dom.ticketsList = byId('tickets-list');
    dom.resultsInfo = byId('results-info');
    dom.statusFilters = Array.from(document.querySelectorAll('[data-status-filter]'));
    dom.ticketsPager = byId('tickets-pager');
    dom.ticketsPrev = byId('tickets-prev');
    dom.ticketsNext = byId('tickets-next');
    dom.ticketsPageInfo = byId('tickets-page-info');
    dom.ticketForm = byId('ticket-form');
    dom.formMessage = byId('form-message');
    dom.categoria = byId('categoria_id');
    dom.prioridad = byId('prioridad_id');
    dom.estado = byId('estado_id');
    dom.occurrenceInput = dom.ticketForm ? dom.ticketForm.querySelector('input[name="fecha_ocurrencia"]') : null;
    dom.assignForm = byId('assign-form');
    dom.assignTicket = byId('assign_ticket_id');
    dom.assignTech = byId('assign_tecnico_id');
    dom.assignState = byId('assign_estado_id');
    dom.assignMessage = byId('assign-message');
    dom.closeForm = byId('close-form');
    dom.closeTicket = byId('close_ticket_id');
    dom.closeMessage = byId('close-message');
}

document.addEventListener('DOMContentLoaded', async function () {
    cacheDom();
    setupAvatarMenu();
    setupCreateForm();
    setupAssignForm();
    setupCloseForm();
    if (dom.search) {
        dom.search.addEventListener('input', function () {
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            searchTimer = window.setTimeout(function () {
                applySearch();
            }, 250);
        });
    }
    if (dom.statusFilters) {
        dom.statusFilters.forEach(function (button) {
            button.addEventListener('click', async function () {
                await applyStatusFilter(button.dataset.statusFilter || 'todos');
            });
        });
    }
    if (dom.ticketsPrev) {
        dom.ticketsPrev.addEventListener('click', async function () {
            if (currentTicketMeta.page <= 1) { return; }
            await preserveElementPosition(dom.ticketsPager, async function () {
                currentTicketPage -= 1;
                await loadTickets();
            });
        });
    }
    if (dom.ticketsNext) {
        dom.ticketsNext.addEventListener('click', async function () {
            if (currentTicketMeta.page >= currentTicketMeta.total_pages) { return; }
            await preserveElementPosition(dom.ticketsPager, async function () {
                currentTicketPage += 1;
                await loadTickets();
            });
        });
    }
    if (dom.refreshButton) {
        dom.refreshButton.addEventListener('click', async function () {
            const button = this;
            setButtonLoading(button, true, 'Actualizando...');
            try {
                if (expandedTicketId !== null) {
                    await preserveTicketPosition(expandedTicketId, async function () {
                        await refreshTicketsView();
                    });
                } else {
                    await refreshTicketsView();
                }
            } finally { setButtonLoading(button, false); }
        });
    }
    await loadCombos();
    await loadTecnicos();
    await Promise.all([loadTickets(), loadNotifications()]);
});
