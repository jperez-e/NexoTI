let allTickets = [];
let allComments = [];
let availableStatuses = [];
let expandedTicketId = null;
const dom = {};

function byId(id) { return document.getElementById(id); }
function bodyData(name) { return document.body.dataset[name] ? String(document.body.dataset[name]) : ''; }
function getCsrfToken() { const node = byId('csrf-token'); return node ? node.value : ''; }
function currentRoleId() { return Number(document.body.dataset.roleId || 0); }
function isAdminUser() { return currentRoleId() === 1; }
function isTechUser() { return currentRoleId() === 2; }
function isEndUser() { return currentRoleId() === 3; }

async function fetchJSON(url) {
    const response = await fetch(url);
    const payload = await response.json();
    return payload && payload.status !== false && payload.data ? payload.data : [];
}

async function postJSON(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken(),
        },
        body: JSON.stringify(payload),
    });
    return response.json();
}

function setButtonLoading(button, loading, loadingText) {
    if (!button) { return; }
    if (loading) {
        button.dataset.labelHtml = button.innerHTML;
        button.textContent = loadingText;
        button.disabled = true;
        button.classList.add('is-loading');
        return;
    }
    button.innerHTML = button.dataset.labelHtml ? button.dataset.labelHtml : button.innerHTML;
    button.disabled = false;
    button.classList.remove('is-loading');
}

function ensureToastStack() {
    let stack = document.querySelector('.toast-stack');
    if (stack) { return stack; }
    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
    return stack;
}

function showToast(title, text, type) {
    const stack = ensureToastStack();
    const toast = document.createElement('div');
    toast.className = 'toast' + (type ? ' ' + type : '');
    toast.innerHTML = '<strong>' + title + '</strong><span>' + text + '</span>';
    stack.appendChild(toast);
    window.setTimeout(function () { toast.remove(); }, 3600);
}

function clearMessageLater(node, baseClass, delay) {
    if (!node) { return; }
    if (node._messageTimer) { clearTimeout(node._messageTimer); }
    node._messageTimer = window.setTimeout(function () {
        node.textContent = '';
        node.className = baseClass;
    }, delay);
}

function setMessage(node, text, type, title) {
    if (!node) { return; }
    node.textContent = text;
    node.className = type ? 'message ' + type : 'message';
    node.setAttribute('aria-hidden', text === '' ? 'true' : 'false');
    if (text !== '' && type) {
        clearMessageLater(node, 'message', 4000);
        showToast(title || (type === 'success' ? 'Operacion completada' : 'Atencion'), text, type);
    }
}

function setInlineMessage(node, text, type) {
    if (!node) { return; }
    node.textContent = text;
    node.className = type ? 'message inline-message ' + type : 'message inline-message';
    node.setAttribute('aria-hidden', text === '' ? 'true' : 'false');
    if (text !== '' && type) {
        clearMessageLater(node, 'message inline-message', 4000);
        showToast(type === 'success' ? 'Actualizacion del ticket' : 'Atencion', text, type);
    }
}

function fillSelect(select, rows, labelResolver, valueKey) {
    if (!select) { return; }
    select.innerHTML = '';
    rows.forEach(function (row) {
        const option = document.createElement('option');
        option.value = row[valueKey];
        option.textContent = typeof labelResolver === 'function' ? labelResolver(row) : row[labelResolver];
        select.appendChild(option);
    });
}

function initialsFromName(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) { return 'NT'; }
    return (parts[0][0] || 'N').toUpperCase() + ((parts[1] && parts[1][0]) ? parts[1][0].toUpperCase() : '');
}

function normalizeRoleLabel(roleName) {
    const value = String(roleName || '').toLowerCase();
    if (value.includes('admin')) { return 'Admin'; }
    if (value.includes('tecn')) { return 'Tecnico'; }
    return 'Usuario';
}

function roleClassName(roleName) {
    return normalizeRoleLabel(roleName).toLowerCase();
}

function formatTicketDate(value) {
    if (!value) { return 'Sin fecha'; }
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('es-DO');
}

function buildMeta(text) {
    const meta = document.createElement('p');
    meta.className = 'ticket-meta';
    meta.textContent = text;
    return meta;
}

function countCommentsForTicket(ticketId) {
    return allComments.filter(function (comment) {
        return String(comment.ticket_id) === String(ticketId);
    }).length;
}

function buildRoleBadge(roleName) {
    const badge = document.createElement('span');
    badge.className = 'role-badge role-' + roleClassName(roleName);
    badge.textContent = normalizeRoleLabel(roleName);
    return badge;
}

function createAvatar(name, photoUrl, className) {
    const avatar = document.createElement('div');
    avatar.className = className;
    if (photoUrl) {
        const image = document.createElement('img');
        image.src = photoUrl;
        image.alt = name;
        avatar.appendChild(image);
    } else {
        const fallback = document.createElement('span');
        fallback.textContent = initialsFromName(name);
        avatar.appendChild(fallback);
    }
    return avatar;
}

function setButtonContent(button, iconName, label) {
    if (!button) {
        return;
    }
    button.innerHTML = window.UiIcons ? window.UiIcons.buttonContent(iconName, label) : label;
}

function resolvePhoto(photoPath) {
    return photoPath ? '/NexoTI/' + String(photoPath).replace(/^\/+/, '') : '';
}

function buildParticipant(name, roleName, photoUrl, helperText) {
    const item = document.createElement('div');
    item.className = 'participant-card';
    const content = document.createElement('div');
    content.className = 'participant-content';
    const title = document.createElement('strong');
    title.textContent = name;
    const role = document.createElement('span');
    role.className = 'participant-role';
    role.textContent = normalizeRoleLabel(roleName);
    const helper = document.createElement('small');
    helper.textContent = helperText;
    content.appendChild(title);
    content.appendChild(role);
    content.appendChild(helper);
    item.appendChild(createAvatar(name, photoUrl, 'participant-avatar'));
    item.appendChild(content);
    return item;
}

function buildParticipantsPanel(ticket) {
    const panel = document.createElement('section');
    panel.className = 'participants-panel';
    const head = document.createElement('div');
    head.className = 'participants-head';
    head.innerHTML = '<strong>Participantes</strong><span>' + (ticket.tecnico_id ? '2' : '1') + '</span>';
    const list = document.createElement('div');
    list.className = 'participants-list';
    list.appendChild(buildParticipant(ticket.usuario_nombre || 'Usuario', ticket.usuario_rol_nombre || 'Usuario', resolvePhoto(ticket.usuario_foto), 'Solicitante'));
    if (ticket.tecnico_id) {
        list.appendChild(buildParticipant(ticket.tecnico_nombre || 'Tecnico', ticket.tecnico_rol_nombre || 'Tecnico', resolvePhoto(ticket.tecnico_foto), 'Responsable actual'));
    } else {
        list.appendChild(buildParticipant('Sin asignar', 'Tecnico', '', 'Pendiente de asignacion'));
    }
    panel.appendChild(head);
    panel.appendChild(list);
    return panel;
}

function buildThreadEntry(entry) {
    const item = document.createElement('div');
    item.className = 'thread-entry role-' + roleClassName(entry.roleName) + (entry.variant ? ' ' + entry.variant : '');
    const body = document.createElement('div');
    body.className = 'thread-body';
    const head = document.createElement('div');
    head.className = 'thread-head';
    const author = document.createElement('div');
    author.className = 'thread-author';
    const strong = document.createElement('strong');
    strong.textContent = entry.name;
    author.appendChild(strong);
    author.appendChild(buildRoleBadge(entry.roleName));
    const meta = document.createElement('div');
    meta.className = 'thread-meta';
    const date = document.createElement('span');
    date.className = 'thread-date';
    date.textContent = formatTicketDate(entry.dateText);
    const tag = document.createElement('span');
    tag.className = 'thread-tag';
    tag.textContent = entry.tag;
    meta.appendChild(date);
    meta.appendChild(tag);
    const text = document.createElement('p');
    text.className = 'thread-text';
    text.textContent = entry.bodyText;
    head.appendChild(author);
    head.appendChild(meta);
    body.appendChild(head);
    body.appendChild(text);
    item.appendChild(createAvatar(entry.name, entry.photoUrl, 'thread-avatar'));
    item.appendChild(body);
    return item;
}

function buildTicketThread(ticket) {
    const wrapper = document.createElement('div');
    wrapper.className = 'ticket-thread';
    wrapper.appendChild(buildThreadEntry({
        name: ticket.usuario_nombre || 'Usuario',
        roleName: ticket.usuario_rol_nombre || 'Usuario',
        photoUrl: resolvePhoto(ticket.usuario_foto),
        dateText: ticket.fecha_creacion,
        bodyText: ticket.descripcion,
        tag: 'Descripcion',
        variant: 'is-initial',
    }));
    allComments
        .filter(function (comment) { return String(comment.ticket_id) === String(ticket.id); })
        .slice()
        .reverse()
        .forEach(function (comment) {
            wrapper.appendChild(buildThreadEntry({
                name: comment.usuario_nombre || 'Usuario',
                roleName: comment.rol_nombre || 'Usuario',
                photoUrl: resolvePhoto(comment.usuario_foto),
                dateText: comment.fecha,
                bodyText: comment.comentario,
                tag: normalizeRoleLabel(comment.rol_nombre),
                variant: 'is-comment',
            }));
        });
    return wrapper;
}

function buildNotificationItems() {
    if (isAdminUser()) {
        return allTickets.filter(function (ticket) {
            return String(ticket.estado_nombre).toLowerCase() === 'abierto';
        }).map(function (ticket) {
            return { title: 'Ticket abierto pendiente', text: ticket.codigo + ' - ' + ticket.titulo };
        });
    }
    if (isTechUser()) {
        return allTickets.filter(function (ticket) {
            const state = String(ticket.estado_nombre).toLowerCase();
            return state === 'abierto' || state === 'en proceso' || state === 'resuelto';
        }).map(function (ticket) {
            return { title: 'Seguimiento asignado', text: ticket.codigo + ' - ' + ticket.titulo };
        });
    }
    return allTickets.filter(function (ticket) {
        return String(ticket.estado_nombre).toLowerCase() === 'resuelto';
    }).map(function (ticket) {
        return { title: 'Pendiente de confirmacion', text: ticket.codigo + ' - ' + ticket.titulo };
    });
}

function renderNotificationsPanel() {
    if (!dom.noticePanel || !dom.noticeCount) { return; }
    const rows = buildNotificationItems();
    dom.noticeCount.textContent = String(rows.length);
    dom.noticePanel.innerHTML = '';
    if (rows.length === 0) {
        dom.noticePanel.innerHTML = '<p class="notice-empty">No hay notificaciones pendientes.</p>';
        return;
    }
    rows.slice(0, 6).forEach(function (row) {
        const item = document.createElement('div');
        item.className = 'notice-item';
        const title = document.createElement('strong');
        title.textContent = row.title;
        const text = document.createElement('span');
        text.textContent = row.text;
        item.appendChild(title);
        item.appendChild(text);
        dom.noticePanel.appendChild(item);
    });
}

async function refreshTicketsView() {
    await Promise.all([loadComments(), loadTickets()]);
}

async function submitInlineReply(ticket, textarea, stateSelect, messageNode, replyBox, toggleButton, sendButton) {
    const comentario = textarea.value.trim();
    const selectedStateId = stateSelect ? String(stateSelect.value) : '';
    const currentStateId = ticket.estado_id ? String(ticket.estado_id) : '';
    const mustUpdateState = isTechUser() && selectedStateId !== '' && selectedStateId !== currentStateId;
    if (comentario === '' && !mustUpdateState) {
        setInlineMessage(messageNode, 'Escribe una respuesta o selecciona un nuevo estado.', 'error');
        return;
    }
    setButtonLoading(sendButton, true, 'Enviando...');
    try {
        let statusResponse = { status: true };
        if (mustUpdateState) {
            statusResponse = await postJSON('api.php?c=ticket&m=updateStatus', { ticket_id: ticket.id, estado_id: selectedStateId });
            if (!statusResponse.status) {
                setInlineMessage(messageNode, statusResponse.message ? statusResponse.message : 'No se pudo actualizar el estado.', 'error');
                return;
            }
        }
        let commentResponse = { status: true };
        if (comentario !== '') {
            commentResponse = await postJSON('api.php?c=comentario&m=create', { ticket_id: ticket.id, comentario: comentario });
            if (!commentResponse.status) {
                setInlineMessage(messageNode, commentResponse.message ? commentResponse.message : 'No se pudo enviar la respuesta.', 'error');
                return;
            }
        }
        const successMessage = mustUpdateState && comentario !== '' ? 'Respuesta enviada y estado actualizado.' :
            mustUpdateState ? (statusResponse.message ? statusResponse.message : 'Estado actualizado.') :
            (commentResponse.message ? commentResponse.message : 'Respuesta enviada.');
        setInlineMessage(messageNode, successMessage, 'success');
        textarea.value = '';
        replyBox.classList.remove('is-open');
        setButtonContent(toggleButton, 'reply', 'Responder');
        await refreshTicketsView();
    } finally {
        setButtonLoading(sendButton, false);
    }
}

async function closeInlineTicket(ticketId, messageNode, closeButton) {
    setButtonLoading(closeButton, true, 'Cerrando...');
    try {
        const data = await postJSON('api.php?c=ticket&m=closeTicket', { ticket_id: ticketId });
        setInlineMessage(messageNode, data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
        if (data.status) { await refreshTicketsView(); }
    } finally {
        setButtonLoading(closeButton, false);
    }
}

function buildInlineReply(ticket, messageNode, toggleButton) {
    const box = document.createElement('div');
    box.className = 'inline-reply';
    box.id = 'ticket-reply-' + ticket.id;
    const composer = document.createElement('div');
    composer.className = 'reply-composer';
    composer.appendChild(createAvatar(bodyData('userName'), bodyData('userPhoto'), 'reply-avatar'));
    const body = document.createElement('div');
    body.className = 'reply-body';
    const identity = document.createElement('div');
    identity.className = 'reply-identity';
    const strong = document.createElement('strong');
    strong.textContent = bodyData('userName');
    identity.appendChild(strong);
    identity.appendChild(buildRoleBadge(bodyData('roleName')));
    body.appendChild(identity);
    let stateSelect = null;
    if (isTechUser()) {
        const stateLabel = document.createElement('label');
        stateLabel.className = 'reply-state';
        stateLabel.textContent = 'Estado';
        stateSelect = document.createElement('select');
        availableStatuses.filter(function (status) {
            return String(status.nombre).toLowerCase() !== 'cerrado';
        }).forEach(function (status) {
            const option = document.createElement('option');
            option.value = status.id;
            option.textContent = status.nombre;
            if (String(status.id) === String(ticket.estado_id)) { option.selected = true; }
            stateSelect.appendChild(option);
        });
        stateLabel.appendChild(stateSelect);
        body.appendChild(stateLabel);
    }
    const textarea = document.createElement('textarea');
    textarea.rows = 4;
    textarea.placeholder = 'Escribe una respuesta aqui...';
    body.appendChild(textarea);
    const actions = document.createElement('div');
    actions.className = 'inline-reply-actions';
    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'btn ghost';
    setButtonContent(cancelButton, 'close', 'Cancelar');
    cancelButton.addEventListener('click', function () {
        textarea.value = '';
        box.classList.remove('is-open');
        setButtonContent(toggleButton, 'reply', 'Responder');
        setInlineMessage(messageNode, '', '');
    });
    const sendButton = document.createElement('button');
    sendButton.type = 'button';
    sendButton.className = 'btn primary';
    setButtonContent(sendButton, 'reply', 'Responder');
    sendButton.addEventListener('click', function () {
        submitInlineReply(ticket, textarea, stateSelect, messageNode, box, toggleButton, sendButton);
    });
    actions.appendChild(cancelButton);
    actions.appendChild(sendButton);
    body.appendChild(actions);
    composer.appendChild(body);
    box.appendChild(composer);
    return { box: box, textarea: textarea };
}

function buildInlineActions(ticket) {
    const wrapper = document.createElement('div');
    wrapper.className = 'inline-tools';
    const actions = document.createElement('div');
    actions.className = 'inline-actions';
    const messageNode = document.createElement('span');
    messageNode.className = 'message inline-message';
    messageNode.setAttribute('role', 'status');
    messageNode.setAttribute('aria-live', 'polite');
    messageNode.setAttribute('aria-hidden', 'true');
    const replyButton = document.createElement('button');
    replyButton.type = 'button';
    replyButton.className = 'btn ghost';
    replyButton.setAttribute('aria-expanded', 'false');
    setButtonContent(replyButton, 'reply', 'Responder');
    const reply = buildInlineReply(ticket, messageNode, replyButton);
    replyButton.setAttribute('aria-controls', reply.box.id);
    replyButton.addEventListener('click', function () {
        const isOpen = reply.box.classList.toggle('is-open');
        setButtonContent(replyButton, 'reply', isOpen ? 'Ocultar respuesta' : 'Responder');
        replyButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (isOpen) { reply.textarea.focus(); }
    });
    actions.appendChild(replyButton);
    if (isEndUser() && String(ticket.estado_nombre).toLowerCase() === 'resuelto') {
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn primary';
        setButtonContent(closeButton, 'close', 'Confirmar cierre');
        closeButton.addEventListener('click', function () {
            closeInlineTicket(ticket.id, messageNode, closeButton);
        });
        actions.appendChild(closeButton);
    }
    wrapper.appendChild(actions);
    wrapper.appendChild(reply.box);
    wrapper.appendChild(messageNode);
    return wrapper;
}

function buildTicketSummary(ticket, isExpanded) {
    const summary = document.createElement('button');
    summary.type = 'button';
    summary.className = 'ticket-summary';
    summary.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    summary.setAttribute('aria-controls', 'ticket-details-' + ticket.id);
    summary.setAttribute('aria-label', (isExpanded ? 'Ocultar detalle del ticket ' : 'Ver detalle del ticket ') + ticket.codigo + ' ' + ticket.titulo);

    const main = document.createElement('div');
    main.className = 'ticket-summary-main';

    const head = document.createElement('div');
    head.className = 'ticket-head';
    const info = document.createElement('div');
    info.className = 'ticket-head-main';
    const code = document.createElement('strong');
    code.textContent = ticket.codigo;
    const title = document.createElement('h3');
    title.textContent = ticket.titulo;
    info.appendChild(code);
    info.appendChild(title);
    const status = document.createElement('span');
    status.className = 'status';
    status.textContent = ticket.estado_nombre ? ticket.estado_nombre : 'Sin estado';
    head.appendChild(info);
    head.appendChild(status);

    const excerpt = document.createElement('p');
    excerpt.className = 'ticket-excerpt';
    excerpt.textContent = String(ticket.descripcion || '').trim() || 'Sin descripcion';

    const metaGrid = document.createElement('div');
    metaGrid.className = 'meta-grid';
    metaGrid.appendChild(buildMeta('Usuario: ' + (ticket.usuario_nombre || 'N/A')));
    metaGrid.appendChild(buildMeta('Tecnico: ' + (ticket.tecnico_nombre || 'Sin asignar')));
    metaGrid.appendChild(buildMeta('Categoria: ' + (ticket.categoria_nombre || 'N/A')));
    metaGrid.appendChild(buildMeta('Prioridad: ' + (ticket.prioridad_nombre || 'N/A')));

    const footer = document.createElement('div');
    footer.className = 'ticket-summary-footer';
    footer.appendChild(buildMeta('Fecha: ' + formatTicketDate(ticket.fecha_creacion)));
    footer.appendChild(buildMeta('Mensajes: ' + countCommentsForTicket(ticket.id)));

    const toggle = document.createElement('span');
    toggle.className = 'ticket-toggle';
    toggle.textContent = isExpanded ? 'Ocultar detalle' : 'Ver detalle';
    footer.appendChild(toggle);

    main.appendChild(head);
    main.appendChild(excerpt);
    main.appendChild(metaGrid);
    main.appendChild(footer);
    summary.appendChild(main);

    return summary;
}

function buildTicketDetails(ticket) {
    const details = document.createElement('div');
    details.className = 'ticket-details';
    details.id = 'ticket-details-' + ticket.id;
    details.appendChild(buildParticipantsPanel(ticket));
    details.appendChild(buildTicketThread(ticket));
    details.appendChild(buildInlineActions(ticket));
    return details;
}

function keepTicketPosition(ticketId, previousTop) {
    window.requestAnimationFrame(function () {
        const nextNode = document.querySelector('[data-ticket-id="' + ticketId + '"]');
        if (!nextNode) { return; }
        const nextTop = nextNode.getBoundingClientRect().top;
        window.scrollBy(0, nextTop - previousTop);
    });
}

function renderTickets(tickets) {
    if (!dom.ticketsList) { return; }
    dom.ticketsList.innerHTML = '';
    if (tickets.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty';
        empty.textContent = 'No hay tickets registrados.';
        dom.ticketsList.appendChild(empty);
        return;
    }
    if (expandedTicketId !== null && !tickets.some(function (ticket) { return String(ticket.id) === String(expandedTicketId); })) {
        expandedTicketId = null;
    }
    const list = document.createElement('div');
    list.className = 'ticket-list';
    tickets.forEach(function (ticket) {
        const item = document.createElement('article');
        item.className = 'ticket-item';
        item.dataset.ticketId = String(ticket.id);
        const isExpanded = String(ticket.id) === String(expandedTicketId);
        item.classList.toggle('is-open', isExpanded);
        const summary = buildTicketSummary(ticket, isExpanded);
        summary.addEventListener('click', function () {
            const previousTop = item.getBoundingClientRect().top;
            expandedTicketId = isExpanded ? null : ticket.id;
            renderTickets(filterTicketsBySearch());
            keepTicketPosition(ticket.id, previousTop);
        });
        item.appendChild(summary);
        if (isExpanded) {
            item.appendChild(buildTicketDetails(ticket));
        }
        list.appendChild(item);
    });
    dom.ticketsList.appendChild(list);
}

function filterTicketsBySearch() {
    const query = dom.search ? dom.search.value.trim().toLowerCase() : '';
    if (query === '') { return allTickets.slice(); }
    return allTickets.filter(function (ticket) {
        const source = [
            ticket.codigo, ticket.titulo, ticket.descripcion, ticket.usuario_nombre, ticket.tecnico_nombre,
            ticket.estado_nombre, ticket.categoria_nombre, ticket.prioridad_nombre, ticket.usuario_rol_nombre, ticket.tecnico_rol_nombre,
        ].join(' ').toLowerCase();
        return source.includes(query);
    });
}

function syncAdminAssignState() {
    if (!dom.assignTicket || !dom.assignState) { return; }
    const selectedTicket = allTickets.find(function (ticket) {
        return String(ticket.id) === String(dom.assignTicket.value);
    });
    if (selectedTicket && selectedTicket.estado_id) {
        dom.assignState.value = String(selectedTicket.estado_id);
    }
}

function refreshTicketSelects(tickets) {
    if (dom.assignTicket) {
        fillSelect(dom.assignTicket, tickets.filter(function (ticket) {
            return String(ticket.estado_nombre).toLowerCase() === 'abierto';
        }), function (ticket) {
            return ticket.codigo + ' - ' + ticket.titulo;
        }, 'id');
        syncAdminAssignState();
    }
    if (dom.closeTicket) {
        fillSelect(dom.closeTicket, tickets.filter(function (ticket) {
            return String(ticket.estado_nombre).toLowerCase() === 'resuelto';
        }), function (ticket) {
            return ticket.codigo + ' - ' + ticket.titulo;
        }, 'id');
    }
}

function applySearch() {
    const filteredTickets = filterTicketsBySearch();
    renderTickets(filteredTickets);
    refreshTicketSelects(filteredTickets);
}

async function loadTickets() {
    allTickets = await fetchJSON('api.php?c=ticket&m=list');
    renderNotificationsPanel();
    applySearch();
}

async function loadComments() {
    allComments = await fetchJSON('api.php?c=comentario&m=list');
}

async function loadCombos() {
    const [categorias, prioridades, estados] = await Promise.all([
        fetchJSON('api.php?c=categoria&m=list'),
        fetchJSON('api.php?c=prioridad&m=list'),
        fetchJSON('api.php?c=estado&m=list'),
    ]);
    availableStatuses = estados.slice();
    fillSelect(dom.categoria, categorias, 'nombre', 'id');
    fillSelect(dom.prioridad, prioridades, 'nombre', 'id');
    fillSelect(dom.estado, estados, 'nombre', 'id');
    fillSelect(dom.assignState, estados, 'nombre', 'id');
}

async function loadTecnicos() {
    if (!dom.assignTech) { return; }
    const tecnicos = await fetchJSON('api.php?c=usuario&m=tecnicos');
    dom.assignTech.innerHTML = '';
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = 'Sin asignar';
    dom.assignTech.appendChild(emptyOption);
    tecnicos.forEach(function (tecnico) {
        const option = document.createElement('option');
        option.value = tecnico.id;
        option.textContent = tecnico.nombre + ' (' + tecnico.email + ')';
        dom.assignTech.appendChild(option);
    });
}

function toggleNoticePanel(forceState) {
    if (!dom.noticePanel) { return; }
    const nextState = typeof forceState === 'boolean' ? forceState : !dom.noticePanel.classList.contains('is-open');
    dom.noticePanel.classList.toggle('is-open', nextState);
    dom.noticePanel.setAttribute('aria-hidden', nextState ? 'false' : 'true');
    if (dom.noticeButton) {
        dom.noticeButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        if (!nextState) {
            dom.noticeButton.focus();
        }
    }
    if (nextState) {
        dom.noticePanel.focus();
    }
}

function toggleAvatarMenu(forceState) {
    if (!dom.avatarMenu) { return; }
    const nextState = typeof forceState === 'boolean' ? forceState : !dom.avatarMenu.classList.contains('is-open');
    dom.avatarMenu.classList.toggle('is-open', nextState);
    dom.avatarMenu.setAttribute('aria-hidden', nextState ? 'false' : 'true');
    if (dom.avatarButton) {
        dom.avatarButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        if (!nextState) {
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
        if (!formData.get('codigo')) { formData.set('codigo', 'TCK-' + Date.now()); }
        setButtonLoading(submitButton, true, 'Creando...');
        try {
            const response = await fetch('api.php?c=ticket&m=create', { method: 'POST', body: formData });
            const data = await response.json();
            setMessage(dom.formMessage, data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error', 'Creacion de ticket');
            if (data.status) {
                dom.ticketForm.reset();
                setDefaultOccurrence();
                await refreshTicketsView();
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
            if (data.status) { await refreshTicketsView(); }
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
    if (dom.search) { dom.search.addEventListener('input', applySearch); }
    if (dom.refreshButton) {
        dom.refreshButton.addEventListener('click', async function () {
            const button = this;
            setButtonLoading(button, true, 'Actualizando...');
            try { await refreshTicketsView(); } finally { setButtonLoading(button, false); }
        });
    }
    await loadCombos();
    await loadTecnicos();
    await loadComments();
    await loadTickets();
});
