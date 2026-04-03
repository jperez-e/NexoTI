async function refrescarVistaTickets() {
    await Promise.all([cargarTickets(), cargarNotificaciones()]);
}

async function cargarDatosTicketExpandido(idTicket) {
    if (!idTicket) {
        todosComentarios = [];
        todosAdjuntos = [];
        todosParticipantes = [];
        return;
    }

    await Promise.all([
        cargarComentarios([idTicket]),
        cargarAdjuntos([idTicket]),
        cargarParticipantes([idTicket]),
    ]);
}

async function subirAdjuntosTicket(idTicket, archivos, idComentario) {
    if (!archivos || archivos.length === 0) {
        return { status: true, data: { count: 0 } };
    }

    const datosFormulario = new FormData();
    datosFormulario.set('ticket_id', String(idTicket));
    if (idComentario) {
        datosFormulario.set('comentario_id', String(idComentario));
    }
    datosFormulario.set('_token', obtenerTokenCsrf());
    Array.from(archivos).forEach(function (archivo) {
        datosFormulario.append('adjuntos[]', archivo);
    });

    const respuesta = await fetch('api.php?c=ticket&m=subirAdjuntos', {
        method: 'POST',
        body: datosFormulario,
    });
    return respuesta.json();
}

async function enviarRespuestaEnLinea(ticket, areaTexto, selectEstado, entradaAdjuntos, nodoMensaje, contenedorRespuesta, botonAlternar, botonEnviar) {
    const comentario = areaTexto.value.trim();
    const idEstadoSeleccionado = selectEstado ? String(selectEstado.value) : '';
    const idEstadoActual = ticket.estado_id ? String(ticket.estado_id) : '';
    const puedeActualizarEstado = esUsuarioTecnico() || esUsuarioAdmin();
    const debeActualizarEstado = puedeActualizarEstado && idEstadoSeleccionado !== '' && idEstadoSeleccionado !== idEstadoActual;
    const archivos = entradaAdjuntos ? Array.from(entradaAdjuntos.files || []) : [];

    if (comentario === '' && !debeActualizarEstado && archivos.length === 0) {
        establecerMensajeEnLinea(nodoMensaje, 'Escribe una respuesta, selecciona un nuevo estado o agrega evidencia.', 'error');
        return;
    }
    establecerBotonCargando(botonEnviar, true, 'Enviando...');
    try {
        let respuestaEstado = { status: true };
        if (debeActualizarEstado) {
            respuestaEstado = await enviarJson('api.php?c=ticket&m=actualizarEstado', { ticket_id: ticket.id, estado_id: idEstadoSeleccionado });
            if (!respuestaEstado.status) {
                establecerMensajeEnLinea(nodoMensaje, respuestaEstado.message ? respuestaEstado.message : 'No se pudo actualizar el estado.', 'error');
                return;
            }
        }
        let respuestaComentario = { status: true };
        let idComentario = null;
        if (comentario !== '') {
            respuestaComentario = await enviarJson('api.php?c=comentario&m=crear', { ticket_id: ticket.id, comentario: comentario });
            if (!respuestaComentario.status) {
                establecerMensajeEnLinea(nodoMensaje, respuestaComentario.message ? respuestaComentario.message : 'No se pudo enviar la respuesta.', 'error');
                return;
            }
            idComentario = respuestaComentario.data && respuestaComentario.data.id ? Number(respuestaComentario.data.id) : null;
        }
        let respuestaAdjuntos = { status: true, data: { count: 0 } };
        if (archivos.length > 0) {
            respuestaAdjuntos = await subirAdjuntosTicket(ticket.id, archivos, idComentario);
            if (!respuestaAdjuntos.status) {
                establecerMensajeEnLinea(nodoMensaje, respuestaAdjuntos.message ? respuestaAdjuntos.message : 'No se pudieron cargar los adjuntos.', 'error');
                return;
            }
        }

        const cantidadAdjuntos = Number((respuestaAdjuntos.data && respuestaAdjuntos.data.count) || 0);
        const partesExito = [];
        if (comentario !== '') {
            partesExito.push('respuesta enviada');
        }
        if (debeActualizarEstado) {
            partesExito.push('estado actualizado');
        }
        if (cantidadAdjuntos > 0) {
            partesExito.push(cantidadAdjuntos === 1 ? '1 evidencia cargada' : cantidadAdjuntos + ' evidencias cargadas');
        }
        let mensajeExito = 'Proceso completado.';
        if (partesExito.length === 1) {
            mensajeExito = partesExito[0].charAt(0).toUpperCase() + partesExito[0].slice(1) + '.';
        } else if (partesExito.length === 2) {
            mensajeExito = partesExito[0].charAt(0).toUpperCase() + partesExito[0].slice(1) + ' y ' + partesExito[1] + '.';
        } else if (partesExito.length >= 3) {
            mensajeExito = partesExito[0].charAt(0).toUpperCase() + partesExito[0].slice(1) + ', ' + partesExito[1] + ' y ' + partesExito[2] + '.';
        }
        establecerMensajeEnLinea(nodoMensaje, mensajeExito, 'success');
        areaTexto.value = '';
        if (entradaAdjuntos) {
            entradaAdjuntos.value = '';
        }
        idTicketRespuestaAbierta = String(ticket.id);
        await preservarPosicionTicket(ticket.id, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(botonEnviar, false);
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

async function cargarComentarios(idsTicket) {
    if (!idsTicket || idsTicket.length === 0) {
        todosComentarios = [];
        return;
    }
    const params = new URLSearchParams({ ticket_ids: idsTicket.join(',') });
    todosComentarios = await obtenerJson('api.php?c=comentario&m=listar&' + params.toString());
}

async function cargarAdjuntos(idsTicket) {
    if (!idsTicket || idsTicket.length === 0) {
        todosAdjuntos = [];
        return;
    }
    const params = new URLSearchParams({ ticket_ids: idsTicket.join(',') });
    todosAdjuntos = await obtenerJson('api.php?c=ticket&m=listarAdjuntos&' + params.toString());
}

async function cargarParticipantes(idsTicket) {
    if (!idsTicket || idsTicket.length === 0) {
        todosParticipantes = [];
        return;
    }
    const params = new URLSearchParams({ ticket_ids: idsTicket.join(',') });
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
        const opcionTodos = document.createElement('option');
        opcionTodos.value = 'todos';
        opcionTodos.textContent = 'Todos los estados';
        domElementos.statusFilterSelect.appendChild(opcionTodos);
        estados.forEach(function (estado) {
            const opcion = document.createElement('option');
            opcion.value = normalizarNombreEstado(estado.nombre);
            opcion.textContent = estado.nombre;
            domElementos.statusFilterSelect.appendChild(opcion);
        });
        domElementos.statusFilterSelect.value = filtroEstadoActivo;
    }
}

async function cargarTecnicos() {
    const tecnicos = await obtenerJson('api.php?c=usuario&m=listarTecnicos');
    tecnicosDisponibles = Array.isArray(tecnicos) ? tecnicos.slice() : [];
}

async function guardarAsignacionEnLinea(ticket, selectTecnico, selectEstado, obtenerIdsParticipantes, nodoMensaje, botonAsignar, alGuardarExitoso) {
    const idTicket = Number(ticket.id || 0);
    const idEstado = Number(selectEstado ? selectEstado.value : (ticket && ticket.estado_id ? ticket.estado_id : 0));
    const tecnicoRaw = selectTecnico ? selectTecnico.value : '';
    const idTecnico = tecnicoRaw === '' ? '' : Number(tecnicoRaw);

    if (idTicket <= 0 || idEstado <= 0) {
        establecerMensajeEnLinea(nodoMensaje, 'Selecciona un estado válido para continuar.', 'error');
        return;
    }

    establecerBotonCargando(botonAsignar, true, 'Guardando...');
    try {
        const datos = await enviarJson('api.php?c=ticket&m=asignar', {
            ticket_id: idTicket,
            tecnico_id: idTecnico,
            estado_id: idEstado,
        });
        if (!datos.status) {
            establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'No se pudo actualizar la asignación.', 'error');
            return;
        }

        const idsObjetivoRaw = typeof obtenerIdsParticipantes === 'function' ? obtenerIdsParticipantes() : [];
        const idsObjetivo = Array.from(new Set((Array.isArray(idsObjetivoRaw) ? idsObjetivoRaw : [])
            .map(function (value) { return Number(value || 0); })
            .filter(function (value) { return value > 0; })));
        const idSolicitante = Number(ticket && ticket.usuario_id ? ticket.usuario_id : 0);
        const idResponsable = tecnicoRaw === '' ? 0 : (idTecnico > 0 ? idTecnico : 0);
        const objetivoFiltrado = idsObjetivo.filter(function (idUsuario) {
            return idUsuario !== idSolicitante && idUsuario !== idResponsable;
        });

        const idsActuales = Array.from(new Set(participantesPorTicket(ticket.id)
            .map(function (participant) { return Number(participant.usuario_id || 0); })
            .filter(function (idUsuario) { return idUsuario > 0 && idUsuario !== idSolicitante && idUsuario !== idResponsable; })));

        for (const idUsuario of idsActuales) {
            if (objetivoFiltrado.indexOf(idUsuario) !== -1) {
                continue;
            }
            const respuestaQuitar = await enviarJson('api.php?c=ticket&m=quitarParticipante', {
                ticket_id: idTicket,
                usuario_id: idUsuario,
            });
            if (!respuestaQuitar.status) {
                establecerMensajeEnLinea(nodoMensaje, respuestaQuitar.message ? respuestaQuitar.message : 'No se pudo actualizar participantes.', 'error');
                return;
            }
        }

        for (const idUsuario of objetivoFiltrado) {
            if (idsActuales.indexOf(idUsuario) !== -1) {
                continue;
            }
            const respuestaAgregar = await enviarJson('api.php?c=ticket&m=agregarParticipante', {
                ticket_id: idTicket,
                usuario_id: idUsuario,
            });
            if (!respuestaAgregar.status) {
                establecerMensajeEnLinea(nodoMensaje, respuestaAgregar.message ? respuestaAgregar.message : 'No se pudo actualizar participantes.', 'error');
                return;
            }
        }

        establecerMensajeEnLinea(nodoMensaje, '', '');
        mostrarToast('Asignación', datos.message ? datos.message : 'Asignación actualizada.', 'success');
        if (typeof alGuardarExitoso === 'function') {
            alGuardarExitoso();
        }
        await preservarPosicionTicket(idTicket, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(botonAsignar, false);
    }
}

async function cerrarTicketEnLinea(idTicket, nodoMensaje, botonCerrar) {
    const id = Number(idTicket || 0);
    if (id <= 0) {
        establecerMensajeEnLinea(nodoMensaje, 'Selecciona un ticket válido.', 'error');
        return;
    }

    establecerBotonCargando(botonCerrar, true, 'Cerrando...');
    try {
        const datos = await enviarJson('api.php?c=ticket&m=cerrarTicket', { ticket_id: id });
        if (!datos.status) {
            establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'No se pudo cerrar el ticket.', 'error');
            return;
        }

        establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'Ticket cerrado.', 'success');
        await preservarPosicionTicket(id, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(botonCerrar, false);
    }
}

async function eliminarTicketEnLinea(idTicket, nodoMensaje, botonEliminar) {
    const id = Number(idTicket || 0);
    if (id <= 0) {
        establecerMensajeEnLinea(nodoMensaje, 'Selecciona un ticket válido.', 'error');
        return;
    }

    const confirmado = window.confirm('Esta acción eliminará el ticket y su historial asociado. ¿Deseas continuar?');
    if (!confirmado) {
        return;
    }

    establecerBotonCargando(botonEliminar, true, 'Eliminando...');
    try {
        const datos = await enviarJson('api.php?c=ticket&m=eliminar', { ticket_id: id });
        if (!datos.status) {
            establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'No se pudo eliminar el ticket.', 'error');
            return;
        }

        if (String(idTicketExpandido || '') === String(id)) {
            idTicketExpandido = null;
        }
        if (String(idTicketRespuestaAbierta || '') === String(id)) {
            idTicketRespuestaAbierta = null;
        }
        establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'Ticket eliminado.', 'success');
        await preservarPosicionElemento(domElementos.ticketsList, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(botonEliminar, false);
    }
}

async function agregarParticipanteEnLinea(ticket, select, nodoMensaje, boton) {
    const idUsuario = Number(select ? select.value : 0);
    if (idUsuario <= 0) {
        establecerMensajeEnLinea(nodoMensaje, 'Selecciona un usuario para agregar.', 'error');
        return;
    }
    const idSolicitante = Number(ticket && ticket.usuario_id ? ticket.usuario_id : 0);
    const idTecnicoAsignado = Number(ticket && ticket.tecnico_id ? ticket.tecnico_id : 0);
    if (idUsuario === idSolicitante || (idTecnicoAsignado > 0 && idUsuario === idTecnicoAsignado)) {
        establecerMensajeEnLinea(nodoMensaje, 'Ese usuario ya participa en el ticket.', 'error');
        return;
    }

    establecerBotonCargando(boton, true, 'Agregando...');
    try {
        const datos = await enviarJson('api.php?c=ticket&m=agregarParticipante', {
            ticket_id: Number(ticket.id),
            usuario_id: idUsuario,
        });
        if (!datos.status) {
            establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'No se pudo agregar el participante.', 'error');
            return;
        }
        establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'Participante agregado.', 'success');
        await preservarPosicionTicket(ticket.id, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(boton, false);
    }
}

async function quitarParticipanteEnLinea(ticket, idUsuario, nodoMensaje, boton) {
    const id = Number(idUsuario || 0);
    if (id <= 0) {
        establecerMensajeEnLinea(nodoMensaje, 'Participante inválido.', 'error');
        return;
    }

    establecerBotonCargando(boton, true, 'Quitando...');
    try {
        const datos = await enviarJson('api.php?c=ticket&m=quitarParticipante', {
            ticket_id: Number(ticket.id),
            usuario_id: id,
        });
        if (!datos.status) {
            establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'No se pudo quitar el participante.', 'error');
            return;
        }
        establecerMensajeEnLinea(nodoMensaje, datos.message ? datos.message : 'Participante removido.', 'success');
        await preservarPosicionTicket(ticket.id, async function () {
            await refrescarVistaTickets();
        });
    } finally {
        establecerBotonCargando(boton, false);
    }
}
