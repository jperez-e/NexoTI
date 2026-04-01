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
        list.appendChild(buildParticipant(ticket.tecnico_nombre || 'Técnico', ticket.tecnico_rol_nombre || 'Técnico', resolvePhoto(ticket.tecnico_foto), 'Responsable actual'));
    } else {
        list.appendChild(buildParticipant('Sin asignar', 'Técnico', '', 'Pendiente de asignación'));
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
    strong.textContent = attachment.usuario_nombre ? 'Evidencia de ' + attachment.usuario_nombre : 'Evidencia adjunta';
    author.appendChild(strong);
    if (attachment.rol_nombre) {
        author.appendChild(buildRoleBadge(attachment.rol_nombre));
    }

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
    if (attachment.comentario_id) {
        caption.textContent += ' · adjunta a una respuesta';
    }

    wrap.appendChild(link);
    wrap.appendChild(caption);
    head.appendChild(author);
    head.appendChild(meta);
    body.appendChild(head);
    body.appendChild(wrap);
    item.appendChild(createAvatar(attachment.usuario_nombre || 'Evidencia', resolvePhoto(attachment.usuario_foto), 'thread-avatar attachment-avatar'));
    item.appendChild(body);
    return item;
}

function buildTicketThread(ticket) {
    const wrapper = document.createElement('div');
    wrapper.className = 'ticket-thread';
    const attachments = attachmentsForTicket(ticket.id);
    const comments = allComments
        .filter(function (comment) { return String(comment.ticket_id) === String(ticket.id); })
        .slice()
        .reverse();

    if (attachments.length === 0 && comments.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty thread-empty';
        empty.textContent = 'No hay respuestas ni evidencias en este ticket todavía.';
        wrapper.appendChild(empty);
        return wrapper;
    }

    attachments.forEach(function (attachment) {
        wrapper.appendChild(buildAttachmentEntry(attachment));
    });
    comments.forEach(function (comment) {
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
    return 'Pendiente de confirmación';
}

function renderNotificationsPanel() {
    if (!dom.noticePanel || !dom.noticeCount) { return; }
    dom.noticeCount.textContent = String(notificationTotal);
    dom.noticeCount.classList.toggle('hidden', notificationTotal === 0);
    dom.noticeCount.setAttribute('aria-hidden', notificationTotal === 0 ? 'true' : 'false');
    dom.noticePanel.innerHTML = '';
    const header = document.createElement('div');
    header.className = 'notice-header';
    const heading = document.createElement('strong');
    heading.textContent = 'Notificaciones';
    header.appendChild(heading);
    if (notificationTotal > 0) {
        const markAllButton = document.createElement('button');
        markAllButton.type = 'button';
        markAllButton.className = 'notice-action';
        markAllButton.textContent = 'Marcar todas';
        markAllButton.addEventListener('click', async function () {
            const data = await postJSON('api.php?c=notificacion&m=readAll', {});
            if (data.status) {
                await loadNotifications();
            }
        });
        header.appendChild(markAllButton);
    }
    dom.noticePanel.appendChild(header);
    if (notificationItems.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'notice-empty';
        empty.textContent = 'No hay notificaciones pendientes.';
        dom.noticePanel.appendChild(empty);
        return;
    }
    notificationItems.forEach(function (notification) {
        const item = document.createElement('div');
        item.className = 'notice-item';
        item.classList.toggle('is-read', Number(notification.leida || 0) === 1);
        const title = document.createElement('strong');
        title.textContent = notification.titulo || 'Notificación';
        const text = document.createElement('span');
        text.textContent = notification.mensaje || '';
        const meta = document.createElement('small');
        const code = notification.ticket_codigo ? String(notification.ticket_codigo) + ' · ' : '';
        meta.textContent = code + formatTicketDate(notification.creada_en);
        item.appendChild(title);
        item.appendChild(text);
        item.appendChild(meta);
        if (Number(notification.leida || 0) === 0) {
            const action = document.createElement('button');
            action.type = 'button';
            action.className = 'notice-action';
            action.textContent = 'Marcar como leída';
            action.addEventListener('click', async function () {
                const data = await postJSON('api.php?c=notificacion&m=read', { id: notification.id });
                if (data.status) {
                    await loadNotifications();
                }
            });
            item.appendChild(action);
        }
        dom.noticePanel.appendChild(item);
    });
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
    textarea.placeholder = 'Escribe una respuesta aquí...';
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

function buildInlineAssignment(ticket) {
    const panel = document.createElement('div');
    panel.className = 'inline-assign';

    const title = document.createElement('p');
    title.className = 'inline-assign-title';
    title.textContent = 'Asignación del ticket';

    const grid = document.createElement('div');
    grid.className = 'inline-assign-grid';

    const techLabel = document.createElement('label');
    techLabel.className = 'inline-assign-field';
    techLabel.textContent = 'Técnico';
    const techSelect = document.createElement('select');
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = 'Sin asignar';
    techSelect.appendChild(emptyOption);
    availableTechnicians.forEach(function (tecnico) {
        const option = document.createElement('option');
        option.value = tecnico.id;
        option.textContent = tecnico.nombre + ' (' + tecnico.email + ')';
        if (String(tecnico.id) === String(ticket.tecnico_id || '')) {
            option.selected = true;
        }
        techSelect.appendChild(option);
    });
    techLabel.appendChild(techSelect);

    const stateLabel = document.createElement('label');
    stateLabel.className = 'inline-assign-field';
    stateLabel.textContent = 'Estado';
    const stateSelect = document.createElement('select');
    availableStatuses.forEach(function (status) {
        const option = document.createElement('option');
        option.value = status.id;
        option.textContent = status.nombre;
        if (String(status.id) === String(ticket.estado_id || '')) {
            option.selected = true;
        }
        stateSelect.appendChild(option);
    });
    stateLabel.appendChild(stateSelect);

    grid.appendChild(techLabel);
    grid.appendChild(stateLabel);

    const footer = document.createElement('div');
    footer.className = 'inline-assign-actions';
    const messageNode = document.createElement('span');
    messageNode.className = 'message inline-message';
    messageNode.setAttribute('role', 'status');
    messageNode.setAttribute('aria-live', 'polite');
    messageNode.setAttribute('aria-hidden', 'true');
    const assignButton = document.createElement('button');
    assignButton.type = 'button';
    assignButton.className = 'btn primary';
    setButtonContent(assignButton, 'refresh', 'Guardar asignación');
    assignButton.addEventListener('click', function () {
        submitInlineAssignment(ticket, techSelect, stateSelect, messageNode, assignButton);
    });
    footer.appendChild(assignButton);
    footer.appendChild(messageNode);

    panel.appendChild(title);
    panel.appendChild(grid);
    panel.appendChild(footer);
    return panel;
}

function buildInlineActions(ticket) {
    const wrapper = document.createElement('div');
    wrapper.className = 'inline-tools';
    if (isAdminUser()) {
        wrapper.appendChild(buildInlineAssignment(ticket));
    }
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
    status.classList.add('status-' + statusClassSuffix(ticket.estado_nombre || ''));
    appendHighlightedText(status, ticket.estado_nombre ? ticket.estado_nombre : 'Sin estado', query);
    head.appendChild(info);
    head.appendChild(status);

    const excerpt = buildHighlightedTextElement('p', 'ticket-excerpt', String(ticket.descripcion || '').trim() || 'Sin descripción', query);

    const metaGrid = document.createElement('div');
    metaGrid.className = 'meta-grid';
    metaGrid.appendChild(buildHighlightedTextElement('p', 'ticket-meta', 'Usuario: ' + (ticket.usuario_nombre || 'N/A'), query));
    metaGrid.appendChild(buildHighlightedTextElement('p', 'ticket-meta', 'Técnico: ' + (ticket.tecnico_nombre || 'Sin asignar'), query));
    metaGrid.appendChild(buildHighlightedTextElement('p', 'ticket-meta', 'Categoría: ' + (ticket.categoria_nombre || 'N/A'), query));
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
            // Al re-renderizar el listado, restauramos la posición del ticket activo para evitar saltos molestos.
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
        empty.textContent = normalizedSearchQuery() === '' ? 'No hay tickets registrados.' : 'No se encontraron tickets con ese criterio de búsqueda.';
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

function refreshTicketSelects() {
    if (dom.closeTicket) {
        fillSelect(dom.closeTicket, closableTickets, function (ticket) {
            return ticket.codigo + ' - ' + ticket.titulo;
        }, 'id');
    }
}

function updateResultsInfo() {
    if (!dom.resultsInfo) { return; }
    const query = normalizedSearchQuery();
    const statusLabel = dom.statusFilterSelect && dom.statusFilterSelect.selectedOptions[0]
        ? dom.statusFilterSelect.selectedOptions[0].textContent
        : (activeStatusFilter === 'todos' ? 'Todos los estados' : activeStatusFilter);
    const assignmentLabel = dom.assignmentFilterSelect && dom.assignmentFilterSelect.selectedOptions[0]
        ? dom.assignmentFilterSelect.selectedOptions[0].textContent
        : (activeAssignmentFilter === 'todos' ? 'Todos' : activeAssignmentFilter);
    if (query === '') {
        dom.resultsInfo.textContent = 'Mostrando ' + currentTicketMeta.total + ' ticket(s) · Estado: ' + statusLabel + ' · Asignación: ' + assignmentLabel + '.';
        return;
    }
    dom.resultsInfo.textContent = 'Resultados para "' + query + '" · Estado: ' + statusLabel + ' · Asignación: ' + assignmentLabel + ' · ' + currentTicketMeta.total + ' ticket(s).';
}

function updateTicketsPager() {
    if (!dom.ticketsPager || !dom.ticketsPrev || !dom.ticketsNext || !dom.ticketsPageInfo) { return; }
    const hasItems = currentTicketMeta.total > 0;
    dom.ticketsPager.classList.toggle('hidden', !hasItems);
    if (!hasItems) {
        dom.ticketsPageInfo.textContent = '';
        return;
    }
    dom.ticketsPageInfo.textContent = 'Página ' + currentTicketMeta.page + ' de ' + currentTicketMeta.total_pages + ' - ' + currentTicketMeta.total + ' ticket(s)';
    dom.ticketsPrev.disabled = currentTicketMeta.page <= 1;
    dom.ticketsNext.disabled = currentTicketMeta.page >= currentTicketMeta.total_pages;
}
