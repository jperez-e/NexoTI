let pageTickets = [];
let assignableTickets = [];
let closableTickets = [];
let allComments = [];
let allAttachments = [];
let availableStatuses = [];
let notificationItems = [];
let notificationTotal = 0;
let expandedTicketId = null;
let activeStatusFilter = 'todos';
let currentTicketPage = 1;
let searchTimer = null;
let currentTicketMeta = {
    page: 1,
    per_page: 5,
    total: 0,
    total_pages: 1,
    query: '',
    estado: null,
};
const ticketsPerPage = 5;
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

async function fetchPayload(url) {
    const response = await fetch(url);
    const payload = await response.json();
    return payload && payload.status !== false ? payload : { status: false, message: 'No se pudo cargar la informacion.', data: {} };
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

function normalizeStatusName(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function normalizedSearchQuery() {
    return dom.search ? dom.search.value.trim() : '';
}

function appendHighlightedText(node, text, query) {
    const source = String(text || '');
    const term = String(query || '').trim();
    if (term === '') {
        node.textContent = source;
        return;
    }

    const sourceLower = source.toLowerCase();
    const termLower = term.toLowerCase();
    let startIndex = 0;

    while (startIndex < source.length) {
        const matchIndex = sourceLower.indexOf(termLower, startIndex);
        if (matchIndex === -1) {
            node.appendChild(document.createTextNode(source.slice(startIndex)));
            break;
        }
        if (matchIndex > startIndex) {
            node.appendChild(document.createTextNode(source.slice(startIndex, matchIndex)));
        }
        const mark = document.createElement('mark');
        mark.className = 'search-hit';
        mark.textContent = source.slice(matchIndex, matchIndex + term.length);
        node.appendChild(mark);
        startIndex = matchIndex + term.length;
    }
}

function buildHighlightedTextElement(tagName, className, text, query) {
    const element = document.createElement(tagName);
    if (className) {
        element.className = className;
    }
    appendHighlightedText(element, text, query);
    return element;
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

function resolveAttachment(path) {
    return path ? '/NexoTI/' + String(path).replace(/^\/+/, '') : '';
}

function attachmentsForTicket(ticketId) {
    return allAttachments.filter(function (attachment) {
        return String(attachment.ticket_id) === String(ticketId);
    });
}

function isImageAttachment(name) {
    return /\.(png|jpe?g|gif|webp)$/i.test(String(name || ''));
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

function buildAttachmentEntry(attachment) {
    const item = document.createElement('div');
    item.className = 'thread-entry is-attachment';
    const body = document.createElement('div');
    body.className = 'thread-body';
    const head = document.createElement('div');
    head.className = 'thread-head';
    const author = document.createElement('div');
    author.className = 'thread-author';
    const strong = document.createElement('strong');
    strong.textContent = 'Evidencia adjunta';
    author.appendChild(strong);

    const meta = document.createElement('div');
    meta.className = 'thread-meta';
    const date = document.createElement('span');
    date.className = 'thread-date';
    date.textContent = formatTicketDate(attachment.creado_en);
    const tag = document.createElement('span');
    tag.className = 'thread-tag';
    tag.textContent = 'Evidencia';
    meta.appendChild(date);
    meta.appendChild(tag);

    const wrap = document.createElement('div');
    wrap.className = 'attachment-wrap';
    const link = document.createElement('a');
    link.className = 'attachment-link';
    link.href = resolveAttachment(attachment.archivo);
    link.target = '_blank';
    link.rel = 'noopener noreferrer';

    if (isImageAttachment(attachment.nombre_original)) {
        const image = document.createElement('img');
        image.className = 'attachment-image';
        image.src = resolveAttachment(attachment.archivo);
        image.alt = attachment.nombre_original || 'Adjunto del ticket';
        link.appendChild(image);
    } else {
        const fileName = document.createElement('span');
        fileName.className = 'attachment-file';
        fileName.textContent = attachment.nombre_original || 'Archivo adjunto';
        link.appendChild(fileName);
    }

    const caption = document.createElement('small');
    caption.className = 'attachment-caption';
    caption.textContent = attachment.nombre_original || 'Archivo adjunto';

    wrap.appendChild(link);
    wrap.appendChild(caption);
    head.appendChild(author);
    head.appendChild(meta);
    body.appendChild(head);
    body.appendChild(wrap);
    item.appendChild(createAvatar('Evidencia', '', 'thread-avatar attachment-avatar'));
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
    attachmentsForTicket(ticket.id).forEach(function (attachment) {
        wrapper.appendChild(buildAttachmentEntry(attachment));
    });
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

function buildNotificationTitle(ticket) {
    const state = normalizeStatusName(ticket.estado_nombre);
    if (isAdminUser() && state === 'abierto') {
        return 'Ticket abierto pendiente';
    }
    if (isTechUser()) {
        return 'Seguimiento asignado';
    }
    return 'Pendiente de confirmacion';
}

function renderNotificationsPanel() {
    if (!dom.noticePanel || !dom.noticeCount) { return; }
    dom.noticeCount.textContent = String(notificationTotal);
    dom.noticeCount.classList.toggle('hidden', notificationTotal === 0);
    dom.noticeCount.setAttribute('aria-hidden', notificationTotal === 0 ? 'true' : 'false');
    dom.noticePanel.innerHTML = '';
    if (notificationItems.length === 0) {
        dom.noticePanel.innerHTML = '<p class="notice-empty">No hay notificaciones pendientes.</p>';
        return;
    }
    notificationItems.forEach(function (ticket) {
        const item = document.createElement('div');
        item.className = 'notice-item';
        const title = document.createElement('strong');
        title.textContent = buildNotificationTitle(ticket);
        const text = document.createElement('span');
        text.textContent = ticket.codigo + ' - ' + ticket.titulo;
        item.appendChild(title);
        item.appendChild(text);
        dom.noticePanel.appendChild(item);
    });
}

async function refreshTicketsView() {
    await loadTickets();
}

async function loadExpandedTicketData(ticketId) {
    if (!ticketId) {
        allComments = [];
        allAttachments = [];
        return;
    }

    await Promise.all([
        loadComments([ticketId]),
        loadAttachments([ticketId]),
    ]);
}

async function uploadTicketAttachments(ticketId, files) {
    if (!files || files.length === 0) {
        return { status: true, data: { count: 0 } };
    }

    const formData = new FormData();
    formData.set('ticket_id', String(ticketId));
    formData.set('_token', getCsrfToken());
    Array.from(files).forEach(function (file) {
        formData.append('adjuntos[]', file);
    });

    const response = await fetch('api.php?c=ticket&m=uploadAdjuntos', {
        method: 'POST',
        body: formData,
    });
    return response.json();
}

async function submitInlineReply(ticket, textarea, stateSelect, attachmentInput, messageNode, replyBox, toggleButton, sendButton) {
    const comentario = textarea.value.trim();
    const selectedStateId = stateSelect ? String(stateSelect.value) : '';
    const currentStateId = ticket.estado_id ? String(ticket.estado_id) : '';
    const mustUpdateState = isTechUser() && selectedStateId !== '' && selectedStateId !== currentStateId;
    const files = attachmentInput ? Array.from(attachmentInput.files || []) : [];

    if (comentario === '' && !mustUpdateState && files.length === 0) {
        setInlineMessage(messageNode, 'Escribe una respuesta, selecciona un nuevo estado o agrega evidencia.', 'error');
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
        let attachmentResponse = { status: true, data: { count: 0 } };
        if (files.length > 0) {
            attachmentResponse = await uploadTicketAttachments(ticket.id, files);
            if (!attachmentResponse.status) {
                setInlineMessage(messageNode, attachmentResponse.message ? attachmentResponse.message : 'No se pudieron cargar los adjuntos.', 'error');
                return;
            }
        }

        const uploadedCount = Number((attachmentResponse.data && attachmentResponse.data.count) || 0);
        const successParts = [];
        if (comentario !== '') {
            successParts.push('respuesta enviada');
        }
        if (mustUpdateState) {
            successParts.push('estado actualizado');
        }
        if (uploadedCount > 0) {
            successParts.push(uploadedCount === 1 ? '1 evidencia cargada' : uploadedCount + ' evidencias cargadas');
        }
        let successMessage = 'Proceso completado.';
        if (successParts.length === 1) {
            successMessage = successParts[0].charAt(0).toUpperCase() + successParts[0].slice(1) + '.';
        } else if (successParts.length === 2) {
            successMessage = successParts[0].charAt(0).toUpperCase() + successParts[0].slice(1) + ' y ' + successParts[1] + '.';
        } else if (successParts.length >= 3) {
            successMessage = successParts[0].charAt(0).toUpperCase() + successParts[0].slice(1) + ', ' + successParts[1] + ' y ' + successParts[2] + '.';
        }
        setInlineMessage(messageNode, successMessage, 'success');
        textarea.value = '';
        if (attachmentInput) {
            attachmentInput.value = '';
        }
        replyBox.classList.remove('is-open');
        toggleButton.setAttribute('aria-expanded', 'false');
        setButtonContent(toggleButton, 'reply', 'Responder');
        await preserveTicketPosition(ticket.id, async function () {
            await refreshTicketsView();
        });
    } finally {
        setButtonLoading(sendButton, false);
    }
}

async function closeInlineTicket(ticketId, messageNode, closeButton) {
    setButtonLoading(closeButton, true, 'Cerrando...');
    try {
        const data = await postJSON('api.php?c=ticket&m=closeTicket', { ticket_id: ticketId });
        setInlineMessage(messageNode, data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
        if (data.status) {
            await preserveTicketPosition(ticketId, async function () {
                await refreshTicketsView();
            });
        }
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
    const attachmentLabel = document.createElement('label');
    attachmentLabel.className = 'reply-attachments';
    attachmentLabel.textContent = 'Agregar evidencias';
    const attachmentInput = document.createElement('input');
    attachmentInput.type = 'file';
    attachmentInput.accept = 'image/*,.pdf,.doc,.docx';
    attachmentInput.multiple = true;
    attachmentLabel.appendChild(attachmentInput);
    body.appendChild(attachmentLabel);
    const actions = document.createElement('div');
    actions.className = 'inline-reply-actions';
    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'btn ghost';
    setButtonContent(cancelButton, 'close', 'Cancelar');
    cancelButton.addEventListener('click', function () {
        preserveTicketPosition(ticket.id, function () {
            textarea.value = '';
            attachmentInput.value = '';
            box.classList.remove('is-open');
            toggleButton.setAttribute('aria-expanded', 'false');
            setButtonContent(toggleButton, 'reply', 'Responder');
            setInlineMessage(messageNode, '', '');
        });
    });
    const sendButton = document.createElement('button');
    sendButton.type = 'button';
    sendButton.className = 'btn primary';
    setButtonContent(sendButton, 'reply', 'Responder');
    sendButton.addEventListener('click', function () {
        submitInlineReply(ticket, textarea, stateSelect, attachmentInput, messageNode, box, toggleButton, sendButton);
    });
    actions.appendChild(cancelButton);
    actions.appendChild(sendButton);
    body.appendChild(actions);
    body.appendChild(messageNode);
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
        const scrollTop = window.scrollY;
        const isOpen = reply.box.classList.toggle('is-open');
        setButtonContent(replyButton, 'reply', isOpen ? 'Ocultar respuesta' : 'Responder');
        replyButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        window.requestAnimationFrame(function () {
            window.scrollTo(0, scrollTop);
            window.requestAnimationFrame(function () {
                try {
                    replyButton.focus({ preventScroll: true });
                } catch (error) {
                    replyButton.focus();
                    window.scrollTo(0, scrollTop);
                }
                window.scrollTo(0, scrollTop);
            });
        });
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
    return wrapper;
}

function buildTicketSummary(ticket, isExpanded) {
    const query = normalizedSearchQuery();
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
    const code = buildHighlightedTextElement('strong', '', ticket.codigo, query);
    const title = buildHighlightedTextElement('h3', '', ticket.titulo, query);
    info.appendChild(code);
    info.appendChild(title);
    const status = document.createElement('span');
    status.className = 'status';
    appendHighlightedText(status, ticket.estado_nombre ? ticket.estado_nombre : 'Sin estado', query);
    head.appendChild(info);
    head.appendChild(status);

    const excerpt = buildHighlightedTextElement('p', 'ticket-excerpt', String(ticket.descripcion || '').trim() || 'Sin descripcion', query);

    const metaGrid = document.createElement('div');
    metaGrid.className = 'meta-grid';
    metaGrid.appendChild(buildHighlightedTextElement('p', 'ticket-meta', 'Usuario: ' + (ticket.usuario_nombre || 'N/A'), query));
    metaGrid.appendChild(buildHighlightedTextElement('p', 'ticket-meta', 'Tecnico: ' + (ticket.tecnico_nombre || 'Sin asignar'), query));
    metaGrid.appendChild(buildHighlightedTextElement('p', 'ticket-meta', 'Categoria: ' + (ticket.categoria_nombre || 'N/A'), query));
    metaGrid.appendChild(buildHighlightedTextElement('p', 'ticket-meta', 'Prioridad: ' + (ticket.prioridad_nombre || 'N/A'), query));

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

function getTicketNode(ticketId) {
    return document.querySelector('[data-ticket-id="' + ticketId + '"]');
}

function restoreTicketPosition(ticketId, previousTop) {
    if (previousTop === null) { return; }
    window.requestAnimationFrame(function () {
        const nextNode = getTicketNode(ticketId);
        if (!nextNode) { return; }
        const nextTop = nextNode.getBoundingClientRect().top;
        window.scrollBy(0, nextTop - previousTop);
    });
}

function restoreElementPosition(element, previousTop) {
    if (!element || previousTop === null) { return; }
    window.requestAnimationFrame(function () {
        const nextTop = element.getBoundingClientRect().top;
        window.scrollBy(0, nextTop - previousTop);
    });
}

function preserveElementPosition(element, work) {
    const previousTop = element ? element.getBoundingClientRect().top : null;
    const result = typeof work === 'function' ? work() : null;

    if (result && typeof result.then === 'function') {
        return result.finally(function () {
            restoreElementPosition(element, previousTop);
        });
    }

    restoreElementPosition(element, previousTop);
    return Promise.resolve();
}

function preserveTicketPosition(ticketId, work) {
    const currentNode = getTicketNode(ticketId);
    const previousTop = currentNode ? currentNode.getBoundingClientRect().top : null;
    const result = typeof work === 'function' ? work() : null;

    if (result && typeof result.then === 'function') {
        return result.finally(function () {
            // Al re-renderizar el listado, restauramos la posicion del ticket activo para evitar saltos molestos.
            restoreTicketPosition(ticketId, previousTop);
        });
    }

    restoreTicketPosition(ticketId, previousTop);
    return Promise.resolve();
}

function renderTickets(tickets) {
    if (!dom.ticketsList) { return; }
    dom.ticketsList.innerHTML = '';
    updateResultsInfo();
    updateTicketsPager();
    if (tickets.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty';
        empty.textContent = normalizedSearchQuery() === '' ? 'No hay tickets registrados.' : 'No se encontraron tickets con ese criterio de busqueda.';
        dom.ticketsList.appendChild(empty);
        return;
    }
    if (expandedTicketId !== null && !pageTickets.some(function (ticket) { return String(ticket.id) === String(expandedTicketId); })) {
        expandedTicketId = null;
    }
    const list = document.createElement('div');
    list.className = 'ticket-list';
    // El listado se dibuja de nuevo en cada filtro o actualizacion para mantener resumen y detalle sincronizados.
    pageTickets.forEach(function (ticket) {
        const item = document.createElement('article');
        item.className = 'ticket-item';
        item.dataset.ticketId = String(ticket.id);
        const isExpanded = String(ticket.id) === String(expandedTicketId);
        item.classList.toggle('is-open', isExpanded);
        const summary = buildTicketSummary(ticket, isExpanded);
        summary.addEventListener('click', function () {
            preserveTicketPosition(ticket.id, async function () {
                expandedTicketId = isExpanded ? null : ticket.id;
                await loadExpandedTicketData(expandedTicketId);
                renderTickets(pageTickets);
            });
        });
        item.appendChild(summary);
        if (isExpanded) {
            item.appendChild(buildTicketDetails(ticket));
        }
        list.appendChild(item);
    });
    dom.ticketsList.appendChild(list);
}

function syncAdminAssignState() {
    if (!dom.assignTicket || !dom.assignState) { return; }
    const selectedTicket = assignableTickets.find(function (ticket) {
        return String(ticket.id) === String(dom.assignTicket.value);
    });
    if (selectedTicket && selectedTicket.estado_id) {
        dom.assignState.value = String(selectedTicket.estado_id);
    }
}

function refreshTicketSelects() {
    if (dom.assignTicket) {
        fillSelect(dom.assignTicket, assignableTickets, function (ticket) {
            return ticket.codigo + ' - ' + ticket.titulo;
        }, 'id');
        syncAdminAssignState();
    }
    if (dom.closeTicket) {
        fillSelect(dom.closeTicket, closableTickets, function (ticket) {
            return ticket.codigo + ' - ' + ticket.titulo;
        }, 'id');
    }
}

function updateResultsInfo() {
    if (!dom.resultsInfo) { return; }
    const query = normalizedSearchQuery();
    const filterLabel = activeStatusFilter === 'todos' ? 'todos los estados' : activeStatusFilter;
    if (query === '') {
        dom.resultsInfo.textContent = 'Mostrando ' + currentTicketMeta.total + ' ticket(s) en ' + filterLabel + '.';
        return;
    }
    dom.resultsInfo.textContent = 'Resultados para "' + query + '" en ' + filterLabel + ': ' + currentTicketMeta.total + ' ticket(s).';
}

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

function updateTicketsPager() {
    if (!dom.ticketsPager || !dom.ticketsPrev || !dom.ticketsNext || !dom.ticketsPageInfo) { return; }
    const hasItems = currentTicketMeta.total > 0;
    dom.ticketsPager.classList.toggle('hidden', !hasItems);
    if (!hasItems) {
        dom.ticketsPageInfo.textContent = '';
        return;
    }
    dom.ticketsPageInfo.textContent = 'Pagina ' + currentTicketMeta.page + ' de ' + currentTicketMeta.total_pages + ' - ' + currentTicketMeta.total + ' ticket(s)';
    dom.ticketsPrev.disabled = currentTicketMeta.page <= 1;
    dom.ticketsNext.disabled = currentTicketMeta.page >= currentTicketMeta.total_pages;
}

async function loadTickets() {
    const params = new URLSearchParams({
        page: String(currentTicketPage),
        per_page: String(ticketsPerPage),
        query: normalizedSearchQuery(),
        estado: activeStatusFilter,
    });
    const payload = await fetchPayload('api.php?c=ticket&m=list&' + params.toString());
    const data = payload.data || {};
    pageTickets = Array.isArray(data.items) ? data.items : [];
    assignableTickets = Array.isArray(data.assignable) ? data.assignable : [];
    closableTickets = Array.isArray(data.closable) ? data.closable : [];
    notificationItems = data.notifications && Array.isArray(data.notifications.items) ? data.notifications.items : [];
    notificationTotal = data.notifications ? Number(data.notifications.count || 0) : 0;
    currentTicketMeta = Object.assign({
        page: currentTicketPage,
        per_page: ticketsPerPage,
        total: 0,
        total_pages: 1,
        query: normalizedSearchQuery(),
        estado: activeStatusFilter === 'todos' ? null : activeStatusFilter,
    }, data.meta || {});
    currentTicketPage = Number(currentTicketMeta.page || 1);

    if (expandedTicketId !== null && !pageTickets.some(function (ticket) { return String(ticket.id) === String(expandedTicketId); })) {
        expandedTicketId = null;
    }
    await loadExpandedTicketData(expandedTicketId);

    renderNotificationsPanel();
    renderTickets(pageTickets);
    refreshTicketSelects();
}

async function loadComments(ticketIds) {
    if (!ticketIds || ticketIds.length === 0) {
        allComments = [];
        return;
    }
    const params = new URLSearchParams({ ticket_ids: ticketIds.join(',') });
    allComments = await fetchJSON('api.php?c=comentario&m=list&' + params.toString());
}

async function loadAttachments(ticketIds) {
    if (!ticketIds || ticketIds.length === 0) {
        allAttachments = [];
        return;
    }
    const params = new URLSearchParams({ ticket_ids: ticketIds.join(',') });
    allAttachments = await fetchJSON('api.php?c=ticket&m=listAdjuntos&' + params.toString());
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
    await loadTickets();
});
