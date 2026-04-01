async function refreshTicketsView() {
    await Promise.all([loadTickets(), loadNotifications()]);
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

async function uploadTicketAttachments(ticketId, files, commentId) {
    if (!files || files.length === 0) {
        return { status: true, data: { count: 0 } };
    }

    const formData = new FormData();
    formData.set('ticket_id', String(ticketId));
    if (commentId) {
        formData.set('comentario_id', String(commentId));
    }
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
        let commentId = null;
        if (comentario !== '') {
            commentResponse = await postJSON('api.php?c=comentario&m=create', { ticket_id: ticket.id, comentario: comentario });
            if (!commentResponse.status) {
                setInlineMessage(messageNode, commentResponse.message ? commentResponse.message : 'No se pudo enviar la respuesta.', 'error');
                return;
            }
            commentId = commentResponse.data && commentResponse.data.id ? Number(commentResponse.data.id) : null;
        }
        let attachmentResponse = { status: true, data: { count: 0 } };
        if (files.length > 0) {
            attachmentResponse = await uploadTicketAttachments(ticket.id, files, commentId);
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

async function loadTickets() {
    const params = new URLSearchParams({
        page: String(currentTicketPage),
        per_page: String(ticketsPerPage),
        query: normalizedSearchQuery(),
        estado: activeStatusFilter,
        asignacion: activeAssignmentFilter,
    });
    const payload = await fetchPayload('api.php?c=ticket&m=list&' + params.toString());
    const data = payload.data || {};
    pageTickets = Array.isArray(data.items) ? data.items : [];
    assignableTickets = Array.isArray(data.assignable) ? data.assignable : [];
    closableTickets = Array.isArray(data.closable) ? data.closable : [];
    currentTicketMeta = Object.assign({
        page: currentTicketPage,
        per_page: ticketsPerPage,
        total: 0,
        total_pages: 1,
        query: normalizedSearchQuery(),
        estado: activeStatusFilter === 'todos' ? null : activeStatusFilter,
        asignacion: activeAssignmentFilter === 'todos' ? null : activeAssignmentFilter,
    }, data.meta || {});
    currentTicketPage = Number(currentTicketMeta.page || 1);

    if (expandedTicketId !== null && !pageTickets.some(function (ticket) { return String(ticket.id) === String(expandedTicketId); })) {
        expandedTicketId = null;
    }
    await loadExpandedTicketData(expandedTicketId);
    renderTickets(pageTickets);
    refreshTicketSelects();
}

async function loadNotifications() {
    const payload = await fetchPayload('api.php?c=notificacion&m=list');
    const data = payload.data || {};
    notificationItems = Array.isArray(data.items) ? data.items : [];
    notificationTotal = Number(data.count || 0);
    renderNotificationsPanel();
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
    if (dom.statusFilterSelect) {
        dom.statusFilterSelect.innerHTML = '';
        const allOption = document.createElement('option');
        allOption.value = 'todos';
        allOption.textContent = 'Todos los estados';
        dom.statusFilterSelect.appendChild(allOption);
        estados.forEach(function (status) {
            const option = document.createElement('option');
            option.value = normalizeStatusName(status.nombre);
            option.textContent = status.nombre;
            dom.statusFilterSelect.appendChild(option);
        });
        dom.statusFilterSelect.value = activeStatusFilter;
    }
}

async function loadTecnicos() {
    const tecnicos = await fetchJSON('api.php?c=usuario&m=tecnicos');
    availableTechnicians = Array.isArray(tecnicos) ? tecnicos.slice() : [];
}

async function submitInlineAssignment(ticket, techSelect, stateSelect, messageNode, assignButton) {
    const ticketId = Number(ticket.id || 0);
    const estadoId = Number(stateSelect ? stateSelect.value : 0);
    const tecnicoRaw = techSelect ? techSelect.value : '';
    const tecnicoId = tecnicoRaw === '' ? '' : Number(tecnicoRaw);

    if (ticketId <= 0 || estadoId <= 0) {
        setInlineMessage(messageNode, 'Selecciona un estado válido para continuar.', 'error');
        return;
    }

    setButtonLoading(assignButton, true, 'Guardando...');
    try {
        const data = await postJSON('api.php?c=ticket&m=assign', {
            ticket_id: ticketId,
            tecnico_id: tecnicoId,
            estado_id: estadoId,
        });
        if (!data.status) {
            setInlineMessage(messageNode, data.message ? data.message : 'No se pudo actualizar la asignación.', 'error');
            return;
        }
        setInlineMessage(messageNode, data.message ? data.message : 'Asignación actualizada.', 'success');
        await preserveTicketPosition(ticketId, async function () {
            await refreshTicketsView();
        });
    } finally {
        setButtonLoading(assignButton, false);
    }
}
