async function refrescarVistaTickets() {
    await Promise.all([cargarTickets(), cargarNotificaciones()]);
}

async function cargarDatosTicketExpandido(ticketId) {
    if (!ticketId) {
        todosComentarios = [];
        todosAdjuntos = [];
        todosParticipantes = [];
        return;
    }

    await Promise.all([
        cargarComentarios([ticketId]),
        cargarAdjuntos([ticketId]),
        cargarParticipantes([ticketId]),
    ]);
}

async function subirAdjuntosTicket(ticketId, files, commentId) {
    if (!files || files.length === 0) {
        return { status: true, data: { count: 0 } };
    }

    const formData = new FormData();
    formData.set('ticket_id', String(ticketId));
    if (commentId) {
        formData.set('comentario_id', String(commentId));
    }
    formData.set('_token', obtenerTokenCsrf());
    Array.from(files).forEach(function (file) {
        formData.append('adjuntos[]', file);
    });

    const response = await fetch('api.php?c=ticket&m=subirAdjuntos', {
        method: 'POST',
        body: formData,
    });
    return response.json();
}

async function enviarRespuestaEnLinea(ticket, textarea, stateSelect, attachmentInput, messageNode, replyBox, toggleButton, sendButton) {
    const comentario = textarea.value.trim();
    const selectedStateId = stateSelect ? String(stateSelect.value) : '';
    const currentStateId = ticket.estado_id ? String(ticket.estado_id) : '';
    const mustUpdateState = esUsuarioTecnico() && selectedStateId !== '' && selectedStateId !== currentStateId;
    const files = attachmentInput ? Array.from(attachmentInput.files || []) : [];

    if (comentario === '' && !mustUpdateState && files.length === 0) {
        establecerMensajeEnLinea(messageNode, 'Escribe una respuesta, selecciona un nuevo estado o agrega evidencia.', 'error');
        return;
    }
    establecerBotonCargando(sendButton, true, 'Enviando...');
    try {
        let statusResponse = { status: true };
        if (mustUpdateState) {
            statusResponse = await enviarJson('api.php?c=ticket&m=actualizarEstado', { ticket_id: ticket.id, estado_id: selectedStateId });
            if (!statusResponse.status) {
                establecerMensajeEnLinea(messageNode, statusResponse.message ? statusResponse.message : 'No se pudo actualizar el estado.', 'error');
                return;
            }
        }
        let commentResponse = { status: true };
        let commentId = null;
        if (comentario !== '') {
            commentResponse = await enviarJson('api.php?c=comentario&m=crear', { ticket_id: ticket.id, comentario: comentario });
            if (!commentResponse.status) {
                establecerMensajeEnLinea(messageNode, commentResponse.message ? commentResponse.message : 'No se pudo enviar la respuesta.', 'error');
                return;
            }
            commentId = commentResponse.data && commentResponse.data.id ? Number(commentResponse.data.id) : null;
        }
        let attachmentResponse = { status: true, data: { count: 0 } };
        if (files.length > 0) {
            attachmentResponse = await subirAdjuntosTicket(ticket.id, files, commentId);
            if (!attachmentResponse.status) {
                establecerMensajeEnLinea(messageNode, attachmentResponse.message ? attachmentResponse.message : 'No se pudieron cargar los adjuntos.', 'error');
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
        establecerMensajeEnLinea(messageNode, successMessage, 'success');
        textarea.value = '';
        if (attachmentInput) {
            attachmentInput.value = '';
        }
        idTicketRespuestaAbierta = String(ticket.id);
        await preservarPosicionTicket(ticket.id, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(sendButton, false);
    }
}

async function cargarTickets() {
    const params = new URLSearchParams({
        page: String(paginaTicketActual),
        per_page: String(ticketsPorPagina),
        query: consultaBusquedaNormalizada(),
        estado: filtroEstadoActivo,
        asignacion: filtroAsignacionActivo,
    });
    const payload = await obtenerCargaUtil('api.php?c=ticket&m=listar&' + params.toString());
    const data = payload.data || {};
    ticketsPagina = Array.isArray(data.items) ? data.items : [];
    ticketsAsignables = Array.isArray(data.assignable) ? data.assignable : [];
    ticketsCerrables = Array.isArray(data.closable) ? data.closable : [];
    metaTicketActual = Object.assign({
        page: paginaTicketActual,
        per_page: ticketsPorPagina,
        total: 0,
        total_pages: 1,
        query: consultaBusquedaNormalizada(),
        estado: filtroEstadoActivo === 'todos' ? null : filtroEstadoActivo,
        asignacion: filtroAsignacionActivo === 'todos' ? null : filtroAsignacionActivo,
    }, data.meta || {});
    paginaTicketActual = Number(metaTicketActual.page || 1);

    if (idTicketExpandido !== null && !ticketsPagina.some(function (ticket) { return String(ticket.id) === String(idTicketExpandido); })) {
        idTicketExpandido = null;
    }
    if (idTicketRespuestaAbierta !== null && !ticketsPagina.some(function (ticket) { return String(ticket.id) === String(idTicketRespuestaAbierta); })) {
        idTicketRespuestaAbierta = null;
    }
    await cargarDatosTicketExpandido(idTicketExpandido);
    renderizarTickets(ticketsPagina);
    refrescarSelectsTicket();
}

async function cargarNotificaciones() {
    const payload = await obtenerCargaUtil('api.php?c=notificacion&m=listar');
    const data = payload.data || {};
    itemsNotificaciones = Array.isArray(data.items) ? data.items : [];
    totalNotificaciones = Number(data.count || 0);
    renderizarPanelNotificaciones();
}

async function cargarComentarios(ticketIds) {
    if (!ticketIds || ticketIds.length === 0) {
        todosComentarios = [];
        return;
    }
    const params = new URLSearchParams({ ticket_ids: ticketIds.join(',') });
    todosComentarios = await obtenerJson('api.php?c=comentario&m=listar&' + params.toString());
}

async function cargarAdjuntos(ticketIds) {
    if (!ticketIds || ticketIds.length === 0) {
        todosAdjuntos = [];
        return;
    }
    const params = new URLSearchParams({ ticket_ids: ticketIds.join(',') });
    todosAdjuntos = await obtenerJson('api.php?c=ticket&m=listarAdjuntos&' + params.toString());
}

async function cargarParticipantes(ticketIds) {
    if (!ticketIds || ticketIds.length === 0) {
        todosParticipantes = [];
        return;
    }
    const params = new URLSearchParams({ ticket_ids: ticketIds.join(',') });
    todosParticipantes = await obtenerJson('api.php?c=ticket&m=listarParticipantes&' + params.toString());
}

async function cargarCandidatosParticipantes() {
    if (!esUsuarioAdmin() && !esUsuarioTecnico()) {
        candidatosParticipantes = [];
        return;
    }
    candidatosParticipantes = await obtenerJson('api.php?c=ticket&m=listarCandidatosParticipantes');
}

async function cargarCombos() {
    const [categorias, prioridades, estados] = await Promise.all([
        obtenerJson('api.php?c=categoria&m=listar'),
        obtenerJson('api.php?c=prioridad&m=listar'),
        obtenerJson('api.php?c=estado&m=listar'),
    ]);
    estadosDisponibles = estados.slice();
    llenarSelect(domElementos.categoria, categorias, 'nombre', 'id');
    llenarSelect(domElementos.prioridad, prioridades, 'nombre', 'id');
    llenarSelect(domElementos.estado, estados, 'nombre', 'id');
    if (domElementos.statusFilterSelect) {
        domElementos.statusFilterSelect.innerHTML = '';
        const allOption = document.createElement('option');
        allOption.value = 'todos';
        allOption.textContent = 'Todos los estados';
        domElementos.statusFilterSelect.appendChild(allOption);
        estados.forEach(function (status) {
            const option = document.createElement('option');
            option.value = normalizarNombreEstado(status.nombre);
            option.textContent = status.nombre;
            domElementos.statusFilterSelect.appendChild(option);
        });
        domElementos.statusFilterSelect.value = filtroEstadoActivo;
    }
}

async function cargarTecnicos() {
    const tecnicos = await obtenerJson('api.php?c=usuario&m=listarTecnicos');
    tecnicosDisponibles = Array.isArray(tecnicos) ? tecnicos.slice() : [];
}

async function guardarAsignacionEnLinea(ticket, techSelect, stateSelect, messageNode, assignButton) {
    const ticketId = Number(ticket.id || 0);
    const estadoId = Number(stateSelect ? stateSelect.value : 0);
    const tecnicoRaw = techSelect ? techSelect.value : '';
    const tecnicoId = tecnicoRaw === '' ? '' : Number(tecnicoRaw);

    if (ticketId <= 0 || estadoId <= 0) {
        establecerMensajeEnLinea(messageNode, 'Selecciona un estado válido para continuar.', 'error');
        return;
    }

    establecerBotonCargando(assignButton, true, 'Guardando...');
    try {
        const data = await enviarJson('api.php?c=ticket&m=asignar', {
            ticket_id: ticketId,
            tecnico_id: tecnicoId,
            estado_id: estadoId,
        });
        if (!data.status) {
            establecerMensajeEnLinea(messageNode, data.message ? data.message : 'No se pudo actualizar la asignación.', 'error');
            return;
        }
        establecerMensajeEnLinea(messageNode, data.message ? data.message : 'Asignación actualizada.', 'success');
        await preservarPosicionTicket(ticketId, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(assignButton, false);
    }
}

async function cerrarTicketEnLinea(ticketId, messageNode, closeButton) {
    const id = Number(ticketId || 0);
    if (id <= 0) {
        establecerMensajeEnLinea(messageNode, 'Selecciona un ticket válido.', 'error');
        return;
    }

    establecerBotonCargando(closeButton, true, 'Cerrando...');
    try {
        const data = await enviarJson('api.php?c=ticket&m=cerrarTicket', { ticket_id: id });
        if (!data.status) {
            establecerMensajeEnLinea(messageNode, data.message ? data.message : 'No se pudo cerrar el ticket.', 'error');
            return;
        }

        establecerMensajeEnLinea(messageNode, data.message ? data.message : 'Ticket cerrado.', 'success');
        await preservarPosicionTicket(id, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(closeButton, false);
    }
}

async function agregarParticipanteEnLinea(ticket, select, messageNode, button) {
    const userId = Number(select ? select.value : 0);
    if (userId <= 0) {
        establecerMensajeEnLinea(messageNode, 'Selecciona un usuario para agregar.', 'error');
        return;
    }

    establecerBotonCargando(button, true, 'Agregando...');
    try {
        const data = await enviarJson('api.php?c=ticket&m=agregarParticipante', {
            ticket_id: Number(ticket.id),
            usuario_id: userId,
        });
        if (!data.status) {
            establecerMensajeEnLinea(messageNode, data.message ? data.message : 'No se pudo agregar el participante.', 'error');
            return;
        }
        establecerMensajeEnLinea(messageNode, data.message ? data.message : 'Participante agregado.', 'success');
        await preservarPosicionTicket(ticket.id, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(button, false);
    }
}

async function quitarParticipanteEnLinea(ticket, userId, messageNode, button) {
    const id = Number(userId || 0);
    if (id <= 0) {
        establecerMensajeEnLinea(messageNode, 'Participante inválido.', 'error');
        return;
    }

    establecerBotonCargando(button, true, 'Quitando...');
    try {
        const data = await enviarJson('api.php?c=ticket&m=quitarParticipante', {
            ticket_id: Number(ticket.id),
            usuario_id: id,
        });
        if (!data.status) {
            establecerMensajeEnLinea(messageNode, data.message ? data.message : 'No se pudo quitar el participante.', 'error');
            return;
        }
        establecerMensajeEnLinea(messageNode, data.message ? data.message : 'Participante removido.', 'success');
        await preservarPosicionTicket(ticket.id, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(button, false);
    }
}
