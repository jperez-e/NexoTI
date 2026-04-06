// Este archivo JavaScript define la lógica de interfaz del módulo tickets.render.
// Maneja eventos, consumo de API y renderizado dinámico del DOM para mejorar la experiencia de usuario.
function construirParticipante(name, roleName, photoUrl, helperText, options) {
    const config = options || {};
    const item = document.createElement('div');
    item.className = 'participant-card';
    const content = document.createElement('div');
    content.className = 'participant-content';
    const title = document.createElement('strong');
    title.textContent = name;
    const helper = document.createElement('small');
    helper.textContent = helperText;
    content.appendChild(title);
    content.appendChild(helper);
    item.appendChild(crearAvatar(name, photoUrl, 'participant-avatar'));
    item.appendChild(content);
    if (config.removable) {
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn ghost participant-remove';
        establecerContenidoBoton(removeButton, 'close', 'Quitar');
        removeButton.addEventListener('click', function () {
            if (typeof config.onRemove === 'function') {
                config.onRemove(removeButton);
            }
        });
        item.appendChild(removeButton);
    }
    return item;
}

function construirPanelParticipantes(ticket) {
    const baseUserId = Number(ticket.usuario_id || 0);
    const baseTechId = Number(ticket.tecnico_id || 0);
    const canManageParticipants = esUsuarioAdmin() || (esUsuarioTecnico() && baseTechId === idUsuarioActual());
    const extras = participantesPorTicket(ticket.id).filter(function (participant) {
        const id = Number(participant.usuario_id || 0);
        return id > 0 && id !== baseUserId && id !== baseTechId;
    });
    const extrasOrdenados = extras.slice().sort(function (a, b) {
        const aEsTecnico = normalizarEtiquetaRol(a.rol_nombre || '') === 'Técnico' ? 1 : 0;
        const bEsTecnico = normalizarEtiquetaRol(b.rol_nombre || '') === 'Técnico' ? 1 : 0;
        if (aEsTecnico !== bEsTecnico) {
            return bEsTecnico - aEsTecnico;
        }
        return String(a.usuario_nombre || '').localeCompare(String(b.usuario_nombre || ''), 'es', { sensitivity: 'base' });
    });
    const totalParticipants = 1 + (baseTechId > 0 ? 1 : 0) + extrasOrdenados.length;

    const panel = document.createElement('section');
    panel.className = 'participants-panel';
    const head = document.createElement('div');
    head.className = 'participants-head';
    head.innerHTML = '<strong>Participantes</strong><span>' + String(totalParticipants) + '</span>';
    const list = document.createElement('div');
    list.className = 'participants-list';
    list.appendChild(construirParticipante(ticket.usuario_nombre || 'Usuario', ticket.usuario_rol_nombre || 'Usuario', resolverFoto(ticket.usuario_foto), 'Solicitante'));
    if (ticket.tecnico_id) {
        list.appendChild(construirParticipante(ticket.tecnico_nombre || 'Técnico', ticket.tecnico_rol_nombre || 'Técnico', resolverFoto(ticket.tecnico_foto), 'Responsable actual'));
    } else if (extrasOrdenados.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'participants-empty';
        empty.textContent = 'Sin responsable asignado aún.';
        list.appendChild(empty);
    }
    const participantMessage = document.createElement('span');
    participantMessage.className = 'message inline-message';
    participantMessage.setAttribute('role', 'status');
    participantMessage.setAttribute('aria-live', 'polite');
    participantMessage.setAttribute('aria-hidden', 'true');

    extrasOrdenados.forEach(function (participant) {
        list.appendChild(construirParticipante(
            participant.usuario_nombre || 'Participante',
            participant.rol_nombre || 'Usuario',
            resolverFoto(participant.usuario_foto),
            'Participante adicional',
            canManageParticipants ? {
                removable: true,
                onRemove: function (button) {
                    quitarParticipanteEnLinea(ticket, Number(participant.usuario_id || 0), participantMessage, button);
                },
            } : null
        ));
    });

    panel.appendChild(head);
    panel.appendChild(list);
    if (puedeGestionarAsignacionEnLinea(ticket)) {
        panel.appendChild(construirAsignacionEnLinea(ticket, extrasOrdenados));
    }
    panel.appendChild(participantMessage);
    return panel;
}

function construirSelectorParticipantesAsignacion(ticket, extrasOrdenados, selectResponsable) {
    const label = document.createElement('label');
    label.className = 'inline-assign-field inline-assign-field-participants';
    label.textContent = 'Participantes';

    const details = document.createElement('details');
    details.className = 'assign-multi';
    const summary = document.createElement('summary');
    summary.className = 'assign-multi-toggle';
    const summaryLabel = document.createElement('span');
    const summaryMeta = document.createElement('small');
    summary.appendChild(summaryLabel);
    summary.appendChild(summaryMeta);

    const menu = document.createElement('div');
    menu.className = 'assign-multi-menu';
    const empty = document.createElement('p');
    empty.className = 'assign-multi-empty';
    empty.textContent = 'Sin candidatos disponibles.';

    const baseUserId = Number(ticket.usuario_id || 0);
    const seleccionados = new Set(extrasOrdenados
        .map(function (participant) { return Number(participant.usuario_id || 0); })
        .filter(function (idUsuario) { return idUsuario > 0; }));
    const opciones = [];

    candidatosParticipantes.forEach(function (candidate) {
        const idUsuario = Number(candidate.id || 0);
        if (idUsuario <= 0 || idUsuario === baseUserId || !rolParticipantePermitido(candidate.rol_id)) {
            return;
        }

        const option = document.createElement('label');
        option.className = 'assign-option';
        const check = document.createElement('input');
        check.type = 'checkbox';
        check.className = 'assign-option-check';
        check.value = String(idUsuario);
        check.checked = seleccionados.has(idUsuario);
        const text = document.createElement('span');
        text.className = 'assign-option-text';
        text.textContent = candidate.nombre + ' · ' + normalizarEtiquetaRol(candidate.rol_nombre || candidate.rol_id || 'Usuario');
        option.appendChild(check);
        option.appendChild(text);
        menu.appendChild(option);
        opciones.push({ id: idUsuario, check: check, option: option });
    });

    if (opciones.length === 0) {
        menu.appendChild(empty);
    }

    function actualizarResumen() {
        const totalSeleccionados = opciones.reduce(function (total, item) {
            return total + (item.check.checked ? 1 : 0);
        }, 0);
        summaryLabel.textContent = totalSeleccionados > 0 ? String(totalSeleccionados) + ' participante(s) seleccionado(s)' : 'Seleccionar participantes';
        summaryMeta.textContent = 'Opcional';
    }

    function bloquearResponsableSeleccionado() {
        const idResponsable = Number(selectResponsable ? selectResponsable.value : 0);
        opciones.forEach(function (item) {
            const bloqueado = idResponsable > 0 && item.id === idResponsable;
            item.check.disabled = bloqueado;
            item.option.classList.toggle('is-disabled', bloqueado);
            if (bloqueado && item.check.checked) {
                item.check.checked = false;
            }
        });
        actualizarResumen();
    }

    opciones.forEach(function (item) {
        item.check.addEventListener('change', function () {
            actualizarResumen();
        });
    });
    if (selectResponsable) {
        selectResponsable.addEventListener('change', bloquearResponsableSeleccionado);
    }
    bloquearResponsableSeleccionado();

    details.appendChild(summary);
    details.appendChild(menu);
    label.appendChild(details);

    return {
        node: label,
        obtenerSeleccionados: function () {
            return opciones
                .filter(function (item) { return item.check.checked && !item.check.disabled; })
                .map(function (item) { return item.id; });
        },
    };
}

function construirEntradaHilo(entry) {
    const item = document.createElement('div');
    item.className = 'thread-entry role-' + nombreClaseRol(entry.roleName) + (entry.variant ? ' ' + entry.variant : '');
    const body = document.createElement('div');
    body.className = 'thread-body';
    const head = document.createElement('div');
    head.className = 'thread-head';
    const author = document.createElement('div');
    author.className = 'thread-author';
    const strong = document.createElement('strong');
    strong.textContent = entry.name;
    author.appendChild(strong);
    author.appendChild(construirInsigniaRol(entry.roleName));
    const meta = document.createElement('div');
    meta.className = 'thread-meta';
    const date = document.createElement('span');
    date.className = 'thread-date';
    date.textContent = formatearFechaTicket(entry.dateText);
    meta.appendChild(date);
    if (entry.tag) {
        const tag = document.createElement('span');
        tag.className = 'thread-tag';
        tag.textContent = entry.tag;
        meta.appendChild(tag);
    }
    const text = document.createElement('p');
    text.className = 'thread-text';
    text.textContent = entry.bodyText;
    head.appendChild(author);
    head.appendChild(meta);
    body.appendChild(head);
    body.appendChild(text);
    item.appendChild(crearAvatar(entry.name, entry.photoUrl, 'thread-avatar'));
    item.appendChild(body);
    return item;
}

function construirEntradaAdjunto(attachment) {
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
        author.appendChild(construirInsigniaRol(attachment.rol_nombre));
    }

    const meta = document.createElement('div');
    meta.className = 'thread-meta';
    const date = document.createElement('span');
    date.className = 'thread-date';
    date.textContent = formatearFechaTicket(attachment.creado_en);
    const tag = document.createElement('span');
    tag.className = 'thread-tag';
    tag.textContent = 'Evidencia';
    meta.appendChild(date);
    meta.appendChild(tag);

    const wrap = document.createElement('div');
    wrap.className = 'attachment-wrap';
    const link = document.createElement('a');
    link.className = 'attachment-link';
    link.href = resolverAdjunto(attachment.archivo);
    link.target = '_blank';
    link.rel = 'noopener noreferrer';

    if (esAdjuntoImagen(attachment.nombre_original)) {
        const image = document.createElement('img');
        image.className = 'attachment-image';
        image.src = resolverAdjunto(attachment.archivo);
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
    item.appendChild(crearAvatar(attachment.usuario_nombre || 'Evidencia', resolverFoto(attachment.usuario_foto), 'thread-avatar attachment-avatar'));
    item.appendChild(body);
    return item;
}

function construirHiloTicket(ticket) {
    const wrapper = document.createElement('div');
    wrapper.className = 'ticket-thread';
    const attachments = adjuntosPorTicket(ticket.id);
    const comments = todosComentarios
        .filter(function (comment) { return String(comment.ticket_id) === String(ticket.id); })
        .slice()
        .reverse();
    wrapper.appendChild(construirEntradaHilo({
        name: ticket.usuario_nombre || 'Usuario',
        roleName: ticket.usuario_rol_id || ticket.usuario_rol_nombre || 'Usuario',
        photoUrl: resolverFoto(ticket.usuario_foto),
        dateText: ticket.fecha_creacion,
        bodyText: String(ticket.descripcion || '').trim() || 'Sin descripción.',
        tag: 'Descripción',
        variant: 'is-initial',
    }));

    attachments.forEach(function (attachment) {
        wrapper.appendChild(construirEntradaAdjunto(attachment));
    });
    comments.forEach(function (comment) {
        const commentText = String(comment.comentario || '').trim();
        const normalizedCommentText = normalizarNombreEstado(commentText);
        const isClosureAcceptedByUser = normalizedCommentText.includes('acepto la solucion')
            && normalizedCommentText.includes('confirmo el cierre');
        const isClosureConfirmedByTech = normalizedCommentText.includes('tecnico confirmo el cierre del ticket');
        const isClosureConfirmedByAdmin = normalizedCommentText.includes('administrador confirmo el cierre del ticket');
        const isClosureAccepted = isClosureAcceptedByUser || isClosureConfirmedByTech || isClosureConfirmedByAdmin;
        wrapper.appendChild(construirEntradaHilo({
            name: comment.usuario_nombre || 'Usuario',
            roleName: comment.rol_nombre || 'Usuario',
            photoUrl: resolverFoto(comment.usuario_foto),
            dateText: comment.fecha,
            bodyText: commentText,
            variant: isClosureAccepted ? 'is-closure-accepted' : 'is-comment',
        }));
    });
    return wrapper;
}

function construirTituloNotificacion(ticket) {
    const state = normalizarNombreEstado(ticket.estado_nombre);
    if (esUsuarioAdmin() && state === 'abierto') {
        return 'Ticket abierto pendiente';
    }
    if (esUsuarioTecnico()) {
        return 'Seguimiento asignado';
    }
    return 'Pendiente de confirmación';
}

function renderizarPanelNotificaciones() {
    if (!domElementos.noticePanel || !domElementos.noticeCount) { return; }
    domElementos.noticeCount.textContent = String(totalNotificaciones);
    domElementos.noticeCount.classList.toggle('hidden', totalNotificaciones === 0);
    domElementos.noticeCount.setAttribute('aria-hidden', totalNotificaciones === 0 ? 'true' : 'false');
    domElementos.noticePanel.innerHTML = '';
    const header = document.createElement('div');
    header.className = 'notice-header';
    const heading = document.createElement('strong');
    heading.textContent = 'Notificaciones';
    header.appendChild(heading);
    if (totalNotificaciones > 0) {
        const markAllButton = document.createElement('button');
        markAllButton.type = 'button';
        markAllButton.className = 'notice-action';
        markAllButton.textContent = 'Marcar todas';
        markAllButton.addEventListener('click', async function () {
            const data = await enviarJson('api.php?c=notificacion&m=readAll', {});
            if (data.status) {
                await cargarNotificaciones();
            }
        });
        header.appendChild(markAllButton);
    }
    domElementos.noticePanel.appendChild(header);
    if (itemsNotificaciones.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'notice-empty';
        empty.textContent = 'No hay notificaciones pendientes.';
        domElementos.noticePanel.appendChild(empty);
        return;
    }
    itemsNotificaciones.forEach(function (notification) {
        const item = document.createElement('div');
        item.className = 'notice-item';
        item.classList.toggle('is-read', Number(notification.leida || 0) === 1);
        const title = document.createElement('strong');
        title.textContent = notification.titulo || 'Notificación';
        const text = document.createElement('span');
        text.textContent = notification.mensaje || '';
        const meta = document.createElement('small');
        const code = notification.ticket_codigo ? String(notification.ticket_codigo) + ' · ' : '';
        meta.textContent = code + formatearFechaTicket(notification.creada_en);
        item.appendChild(title);
        item.appendChild(text);
        item.appendChild(meta);
        if (Number(notification.leida || 0) === 0) {
            const action = document.createElement('button');
            action.type = 'button';
            action.className = 'notice-action';
            action.textContent = 'Marcar como leída';
            action.addEventListener('click', async function () {
                const data = await enviarJson('api.php?c=notificacion&m=read', { id: notification.id });
                if (data.status) {
                    await cargarNotificaciones();
                }
            });
            item.appendChild(action);
        }
        domElementos.noticePanel.appendChild(item);
    });
}

function construirRespuestaEnLinea(ticket, messageNode, toggleButton) {
    const box = document.createElement('div');
    box.className = 'inline-reply';
    box.id = 'ticket-reply-' + ticket.id;
    const composer = document.createElement('div');
    composer.className = 'reply-composer';
    composer.appendChild(crearAvatar(datoBody('userName'), datoBody('userPhoto'), 'reply-avatar'));
    const body = document.createElement('div');
    body.className = 'reply-body';
    const identity = document.createElement('div');
    identity.className = 'reply-identity';
    const strong = document.createElement('strong');
    strong.textContent = datoBody('userName');
    identity.appendChild(strong);
    identity.appendChild(construirInsigniaRol(datoBody('roleName')));
    body.appendChild(identity);
    let stateSelect = null;
    const tecnicoAsignadoActual = Number(ticket.tecnico_id || 0) === idUsuarioActual();
    const puedeCambiarEstadoRespuesta = esUsuarioAdmin() || (esUsuarioTecnico() && tecnicoAsignadoActual);
    if (puedeCambiarEstadoRespuesta) {
        const stateLabel = document.createElement('details');
        stateLabel.className = 'reply-state reply-state-collapsible';
        const stateToggle = document.createElement('summary');
        stateToggle.className = 'reply-state-toggle';
        const stateToggleText = document.createElement('span');
        stateToggleText.className = 'reply-state-toggle-text';
        const stateToggleCaret = document.createElement('span');
        stateToggleCaret.className = 'reply-state-toggle-caret';
        stateToggleCaret.setAttribute('aria-hidden', 'true');
        stateToggle.appendChild(stateToggleText);
        stateToggle.appendChild(stateToggleCaret);
        stateSelect = document.createElement('select');
        estadosDisponibles.filter(function (status) {
            return String(status.nombre).toLowerCase() !== 'cerrado';
        }).forEach(function (status) {
            const option = document.createElement('option');
            option.value = status.id;
            option.textContent = status.nombre;
            if (String(status.id) === String(ticket.estado_id)) { option.selected = true; }
            stateSelect.appendChild(option);
        });
        const stateBody = document.createElement('div');
        stateBody.className = 'reply-state-body';
        stateBody.appendChild(stateSelect);
        const actualizarEstadoSeleccionado = function () {
            const selected = stateSelect && stateSelect.selectedOptions && stateSelect.selectedOptions[0]
                ? stateSelect.selectedOptions[0].textContent
                : 'Sin estado';
            stateToggleText.textContent = 'Estado: ' + selected;
        };
        stateSelect.addEventListener('change', function () {
            actualizarEstadoSeleccionado();
            stateLabel.open = false;
        });
        actualizarEstadoSeleccionado();
        stateLabel.appendChild(stateToggle);
        stateLabel.appendChild(stateBody);
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
    establecerContenidoBoton(cancelButton, 'close', 'Cancelar');
    cancelButton.addEventListener('click', function () {
        preservarPosicionTicket(ticket.id, function () {
            idTicketRespuestaAbierta = null;
            textarea.value = '';
            attachmentInput.value = '';
            box.classList.remove('is-open');
            toggleButton.setAttribute('aria-expanded', 'false');
            establecerContenidoBoton(toggleButton, 'reply', 'Responder');
            establecerMensajeEnLinea(messageNode, '', '');
        });
    });
    const sendButton = document.createElement('button');
    sendButton.type = 'button';
    sendButton.className = 'btn primary';
    establecerContenidoBoton(sendButton, 'reply', 'Responder');
    sendButton.addEventListener('click', function () {
        enviarRespuestaEnLinea(ticket, textarea, stateSelect, attachmentInput, messageNode, box, toggleButton, sendButton);
    });
    actions.appendChild(cancelButton);
    actions.appendChild(sendButton);
    body.appendChild(actions);
    body.appendChild(messageNode);
    composer.appendChild(body);
    box.appendChild(composer);
    return { box: box, textarea: textarea };
}

function construirAsignacionEnLinea(ticket, extrasOrdenados) {
    const panel = document.createElement('div');
    panel.className = 'inline-assign';
    if (!esUsuarioAdmin()) {
        panel.classList.add('inline-assign-compact');
    }
    const estaColapsado = Number(ticket.tecnico_id || 0) > 0;
    panel.classList.toggle('is-collapsed', estaColapsado);

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'inline-assign-title inline-assign-toggle';
    toggle.setAttribute('aria-expanded', estaColapsado ? 'false' : 'true');
    toggle.innerHTML = '<span>Asignación del ticket</span><span class="inline-assign-caret" aria-hidden="true"></span>';

    const body = document.createElement('div');
    body.className = 'inline-assign-body';

    const grid = document.createElement('div');
    grid.className = 'inline-assign-grid';

    const techLabel = document.createElement('label');
    techLabel.className = 'inline-assign-field';
    techLabel.textContent = 'Responsable';
    const techSelect = document.createElement('select');
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = 'Sin responsable';
    techSelect.appendChild(emptyOption);
    const idActual = idUsuarioActual();
    const idSolicitante = Number(ticket.usuario_id || 0);
    tecnicosDisponibles.forEach(function (tecnico) {
        const idTecnico = Number(tecnico.id || 0);
        const ocultarTecnicoActual = esUsuarioTecnico() && idTecnico === idActual;
        const ocultarSolicitante = esUsuarioTecnico() && idSolicitante > 0 && idTecnico === idSolicitante;
        if (ocultarTecnicoActual || ocultarSolicitante) {
            return;
        }
        const option = document.createElement('option');
        option.value = tecnico.id;
        option.textContent = tecnico.nombre + ' (' + tecnico.email + ')';
        if (String(tecnico.id) === String(ticket.tecnico_id || '')) {
            option.selected = true;
        }
        techSelect.appendChild(option);
    });
    techLabel.appendChild(techSelect);

    grid.appendChild(techLabel);
    const selectorParticipantes = construirSelectorParticipantesAsignacion(ticket, Array.isArray(extrasOrdenados) ? extrasOrdenados : [], techSelect);
    grid.appendChild(selectorParticipantes.node);

    let stateSelect = null;
    if (esUsuarioAdmin()) {
        const stateLabel = document.createElement('label');
        stateLabel.className = 'inline-assign-field';
        stateLabel.textContent = 'Estado';
        stateSelect = document.createElement('select');
        estadosDisponibles.forEach(function (status) {
            const option = document.createElement('option');
            option.value = status.id;
            option.textContent = status.nombre;
            if (String(status.id) === String(ticket.estado_id || '')) {
                option.selected = true;
            }
            stateSelect.appendChild(option);
        });
        stateLabel.appendChild(stateSelect);
        grid.appendChild(stateLabel);
    }

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
    establecerContenidoBoton(assignButton, 'refresh', 'Guardar asignación');
    assignButton.addEventListener('click', function () {
        guardarAsignacionEnLinea(
            ticket,
            techSelect,
            stateSelect,
            selectorParticipantes.obtenerSeleccionados,
            messageNode,
            assignButton,
            function () {
                panel.classList.add('is-collapsed');
                toggle.setAttribute('aria-expanded', 'false');
            }
        );
    });
    footer.appendChild(assignButton);
    footer.appendChild(messageNode);

    toggle.addEventListener('click', function () {
        const collapsed = panel.classList.toggle('is-collapsed');
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });

    body.appendChild(grid);
    body.appendChild(footer);
    panel.appendChild(toggle);
    panel.appendChild(body);
    return panel;
}

function puedeGestionarAsignacionEnLinea(ticket) {
    if (esUsuarioAdmin()) {
        return true;
    }
    if (!esUsuarioTecnico()) {
        return false;
    }

    const esCreadorTecnico = normalizarEtiquetaRol(ticket.usuario_rol_id || ticket.usuario_rol_nombre || '') === 'Técnico';
    if (!esCreadorTecnico) {
        return false;
    }

    const idActual = idUsuarioActual();
    return Number(ticket.usuario_id || 0) === idActual || Number(ticket.tecnico_id || 0) === idActual;
}

function construirAccionesEnLinea(ticket) {
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
    establecerContenidoBoton(replyButton, 'reply', 'Responder');
    const reply = construirRespuestaEnLinea(ticket, messageNode, replyButton);
    if (String(idTicketRespuestaAbierta || '') === String(ticket.id)) {
        reply.box.classList.add('is-open');
        replyButton.setAttribute('aria-expanded', 'true');
        establecerContenidoBoton(replyButton, 'reply', 'Ocultar respuesta');
    }
    replyButton.setAttribute('aria-controls', reply.box.id);
    replyButton.addEventListener('click', function () {
        const scrollTop = window.scrollY;
        const isOpen = reply.box.classList.toggle('is-open');
        idTicketRespuestaAbierta = isOpen ? String(ticket.id) : null;
        establecerContenidoBoton(replyButton, 'reply', isOpen ? 'Ocultar respuesta' : 'Responder');
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
    if (esUsuarioAdmin()) {
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn danger';
        establecerContenidoBoton(deleteButton, 'delete', 'Eliminar ticket');
        deleteButton.addEventListener('click', function () {
            eliminarTicketEnLinea(ticket.id, messageNode, deleteButton);
        });
        actions.appendChild(deleteButton);
    }
    if ((esUsuarioFinal() || esUsuarioTecnico() || esUsuarioAdmin()) && normalizarNombreEstado(ticket.estado_nombre || '') === 'resuelto') {
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn primary';
        establecerContenidoBoton(closeButton, 'close', 'Confirmar cierre');
        closeButton.addEventListener('click', function () {
            cerrarTicketEnLinea(ticket.id, messageNode, closeButton);
        });
        actions.appendChild(closeButton);
    }
    wrapper.appendChild(actions);
    wrapper.appendChild(reply.box);
    return wrapper;
}

function construirResumenTicket(ticket, isExpanded) {
    const query = consultaBusquedaNormalizada();
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
    const code = construirElementoTextoResaltado('strong', '', ticket.codigo, query);
    const title = construirElementoTextoResaltado('h3', '', ticket.titulo, query);
    info.appendChild(code);
    info.appendChild(title);
    const status = document.createElement('span');
    status.className = 'status';
    status.classList.add('status-' + sufijoClaseEstado(ticket.estado_nombre || ''));
    agregarTextoResaltado(status, ticket.estado_nombre ? ticket.estado_nombre : 'Sin estado', query);
    head.appendChild(info);
    head.appendChild(status);

    const metaGrid = document.createElement('div');
    metaGrid.className = 'meta-grid';
    metaGrid.appendChild(construirElementoTextoResaltado('p', 'ticket-meta ticket-meta-user', 'Usuario: ' + (ticket.usuario_nombre || 'N/A'), query));
    metaGrid.appendChild(construirElementoTextoResaltado('p', 'ticket-meta ticket-meta-tech', 'Técnico: ' + (ticket.tecnico_nombre || 'Sin asignar'), query));
    metaGrid.appendChild(construirElementoTextoResaltado('p', 'ticket-meta ticket-meta-category', 'Categoría: ' + (ticket.categoria_nombre || 'N/A'), query));
    const priorityMeta = construirElementoTextoResaltado('p', 'ticket-meta ticket-meta-priority', 'Prioridad: ' + (ticket.prioridad_nombre || 'N/A'), query);
    priorityMeta.classList.add('ticket-meta-priority-' + normalizarNombreEstado(ticket.prioridad_nombre || ''));
    metaGrid.appendChild(priorityMeta);

    const footer = document.createElement('div');
    footer.className = 'ticket-summary-footer';

    const footerMeta = document.createElement('div');
    footerMeta.className = 'ticket-summary-meta';
    const createdMeta = construirMeta('Fecha: ' + formatearFechaTicket(ticket.fecha_creacion));
    createdMeta.classList.add('ticket-meta-inline');
    const messagesMeta = construirMeta('Mensajes: ' + contarComentariosPorTicket(ticket.id));
    messagesMeta.classList.add('ticket-meta-inline');
    footerMeta.appendChild(createdMeta);
    footerMeta.appendChild(messagesMeta);
    footer.appendChild(footerMeta);

    const toggle = document.createElement('span');
    toggle.className = 'ticket-toggle';
    toggle.textContent = isExpanded ? 'Ocultar detalle' : 'Ver detalle';
    footer.appendChild(toggle);

    main.appendChild(head);
    main.appendChild(metaGrid);
    main.appendChild(footer);
    summary.appendChild(main);

    return summary;
}

function construirDetalleTicket(ticket) {
    const details = document.createElement('div');
    details.className = 'ticket-details';
    details.id = 'ticket-details-' + ticket.id;
    details.appendChild(construirPanelParticipantes(ticket));
    details.appendChild(construirHiloTicket(ticket));
    details.appendChild(construirAccionesEnLinea(ticket));
    return details;
}

function obtenerNodoTicket(ticketId) {
    return document.querySelector('[data-ticket-id="' + ticketId + '"]');
}

function restaurarPosicionTicket(ticketId, previousTop) {
    if (previousTop === null) { return; }
    window.requestAnimationFrame(function () {
        const nextNode = obtenerNodoTicket(ticketId);
        if (!nextNode) { return; }
        const nextTop = nextNode.getBoundingClientRect().top;
        window.scrollBy(0, nextTop - previousTop);
    });
}

function restaurarPosicionElemento(element, previousTop) {
    if (!element || previousTop === null) { return; }
    window.requestAnimationFrame(function () {
        const nextTop = element.getBoundingClientRect().top;
        window.scrollBy(0, nextTop - previousTop);
    });
}

function preservarPosicionElemento(element, work) {
    const previousTop = element ? element.getBoundingClientRect().top : null;
    const result = typeof work === 'function' ? work() : null;

    if (result && typeof result.then === 'function') {
        return result.finally(function () {
            restaurarPosicionElemento(element, previousTop);
        });
    }

    restaurarPosicionElemento(element, previousTop);
    return Promise.resolve();
}

function preservarPosicionTicket(ticketId, work) {
    const currentNode = obtenerNodoTicket(ticketId);
    const previousTop = currentNode ? currentNode.getBoundingClientRect().top : null;
    const result = typeof work === 'function' ? work() : null;

    if (result && typeof result.then === 'function') {
        return result.finally(function () {
            // Al re-renderizar el listado, restauramos la posición del ticket activo para evitar saltos molestos.
            restaurarPosicionTicket(ticketId, previousTop);
        });
    }

    restaurarPosicionTicket(ticketId, previousTop);
    return Promise.resolve();
}

function renderizarTickets(tickets) {
    if (!domElementos.ticketsList) { return; }
    domElementos.ticketsList.innerHTML = '';
    actualizarInfoResultados();
    actualizarPaginadorTickets();
    if (tickets.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty';
        empty.textContent = consultaBusquedaNormalizada() === '' ? 'No hay tickets registrados.' : 'No se encontraron tickets con ese criterio de búsqueda.';
        domElementos.ticketsList.appendChild(empty);
        return;
    }
    if (idTicketExpandido !== null && !ticketsPagina.some(function (ticket) { return String(ticket.id) === String(idTicketExpandido); })) {
        idTicketExpandido = null;
    }
    const list = document.createElement('div');
    list.className = 'ticket-list';
    // El listado se dibuja de nuevo en cada filtro o actualizacion para mantener resumen y detalle sincronizados.
    ticketsPagina.forEach(function (ticket) {
        const item = document.createElement('article');
        item.className = 'ticket-item';
        item.dataset.ticketId = String(ticket.id);
        const isExpanded = String(ticket.id) === String(idTicketExpandido);
        item.classList.toggle('is-open', isExpanded);
        const summary = construirResumenTicket(ticket, isExpanded);
        summary.addEventListener('click', function () {
            preservarPosicionTicket(ticket.id, async function () {
                if (isExpanded) {
                    idTicketRespuestaAbierta = null;
                }
                idTicketExpandido = isExpanded ? null : ticket.id;
                await cargarDatosTicketExpandido(idTicketExpandido);
                renderizarTickets(ticketsPagina);
            });
        });
        item.appendChild(summary);
        if (isExpanded) {
            item.appendChild(construirDetalleTicket(ticket));
        }
        list.appendChild(item);
    });
    domElementos.ticketsList.appendChild(list);
}

function refrescarSelectsTicket() {
    if (domElementos.closeTicket) {
        llenarSelect(domElementos.closeTicket, ticketsCerrables, function (ticket) {
            return ticket.codigo + ' - ' + ticket.titulo;
        }, 'id');
    }
}

function actualizarInfoResultados() {
    if (!domElementos.resultsInfo) { return; }
    const query = consultaBusquedaNormalizada();
    const statusLabel = domElementos.statusFilterSelect && domElementos.statusFilterSelect.selectedOptions[0]
        ? domElementos.statusFilterSelect.selectedOptions[0].textContent
        : (filtroEstadoActivo === 'todos' ? 'Todos los estados' : filtroEstadoActivo);
    const assignmentLabel = (domElementos.assignmentFilterSelect && domElementos.assignmentFilterSelect.selectedOptions[0]
        ? domElementos.assignmentFilterSelect.selectedOptions[0].textContent
        : (filtroAsignacionActivo === 'todos' ? 'Todos' : filtroAsignacionActivo));
    if (query === '') {
        domElementos.resultsInfo.textContent = 'Mostrando ' + metaTicketActual.total + ' ticket(s) · Estado: ' + statusLabel + ' · Asignación: ' + assignmentLabel + '.';
        return;
    }
    domElementos.resultsInfo.textContent = 'Resultados para "' + query + '" · Estado: ' + statusLabel + ' · Asignación: ' + assignmentLabel + ' · ' + metaTicketActual.total + ' ticket(s).';
}

function actualizarPaginadorTickets() {
    if (!domElementos.ticketsPager || !domElementos.ticketsPrev || !domElementos.ticketsNext || !domElementos.ticketsPageInfo) { return; }
    const hasItems = metaTicketActual.total > 0;
    domElementos.ticketsPager.classList.toggle('hidden', !hasItems);
    if (!hasItems) {
        domElementos.ticketsPageInfo.textContent = '';
        return;
    }
    domElementos.ticketsPageInfo.textContent = 'Página ' + metaTicketActual.page + ' de ' + metaTicketActual.total_pages + ' - ' + metaTicketActual.total + ' ticket(s)';
    domElementos.ticketsPrev.disabled = metaTicketActual.page <= 1;
    domElementos.ticketsNext.disabled = metaTicketActual.page >= metaTicketActual.total_pages;
}
 
/* Mejora visual del listado: indicadores dinamicos y bloque visual. */ 
function asegurarPanelVivoTickets() { 
    if (!domElementos.ticketsList) { return null; } 
    var panel = porId('ticket-highlights'); 
    if (panel) { return panel; } 
    var card = domElementos.ticketsList.closest('.card'); 
    if (!card) { return null; } 
    panel = document.createElement('div'); 
    panel.className = 'ticket-highlights'; 
    panel.id = 'ticket-highlights'; 
    panel.setAttribute('aria-live', 'polite'); 
    var defs = [ 
        { id: 'total', clase: 'highlight-total', titulo: 'Total encontrados', detalle: 'Pagina actual: 0' }, 
        { id: 'open', clase: 'highlight-open', titulo: 'Abiertos', detalle: 'Requieren seguimiento' }, 
        { id: 'progress', clase: 'highlight-progress', titulo: 'En proceso', detalle: 'Atencion tecnica activa' }, 
        { id: 'closed', clase: 'highlight-closed', titulo: 'Cerrados o resueltos', detalle: 'Sin asignar: 0' } 
    ];
    defs.forEach(function (item) { 
        var article = document.createElement('article'); 
        article.className = 'highlight-card ' + item.clase; 
        var title = document.createElement('p'); 
        title.textContent = item.titulo; 
        var value = document.createElement('strong'); 
        value.id = 'stat-' + item.id; 
        value.textContent = '0'; 
        var detail = document.createElement('small'); 
        if (item.id === 'closed') { 
            detail.id = 'stat-unassigned'; 
        } else if (item.id === 'total') { 
            detail.id = 'stat-page'; 
        } 
        detail.textContent = item.detalle; 
        article.appendChild(title); 
        article.appendChild(value); 
        article.appendChild(detail); 
        panel.appendChild(article); 
    }); 
    var filters = card.querySelector('.list-filters'); 
    if (!filters) { 
        card.insertBefore(panel, domElementos.ticketsList); 
        return panel; 
    } 
    if (!filters.parentNode) { 
        card.insertBefore(panel, domElementos.ticketsList); 
        return panel; 
    } 
    if (filters.nextSibling) { 
        filters.parentNode.insertBefore(panel, filters.nextSibling); 
    } else { 
        filters.parentNode.appendChild(panel); 
    } 
    return panel; 
}
 
function obtenerEstadoNormalizadoTicket(ticket) { 
    if (!ticket) { return ''; } 
    var estado = ticket.estado_nombre ? ticket.estado_nombre : ''; 
    return normalizarNombreEstado(estado); 
} 
 
function contarEstadoTicketVivo(lista, primerEstado, segundoEstado) { 
    return lista.filter(function (ticket) { 
        var estado = obtenerEstadoNormalizadoTicket(ticket); 
        if (estado === primerEstado) { return true; } 
        if (segundoEstado !== '' && estado === segundoEstado) { return true; } 
        return false; 
    }).length; 
} 
 
function actualizarIndicadoresVivosTickets(tickets) { 
    var panel = asegurarPanelVivoTickets(); 
    if (!panel) { return; } 
    var lista = Array.isArray(tickets) ? tickets : []; 
    var total = Number(metaTicketActual.total); 
    if (!Number.isFinite(total)) { 
        total = lista.length; 
    } 
    if (total <= 0) { 
        total = lista.length; 
    } 
    var cantidadPagina = lista.length; 
    var abiertos = contarEstadoTicketVivo(lista, 'abierto', ''); 
    var enProceso = contarEstadoTicketVivo(lista, 'en proceso', 'en progreso'); 
    var cerrados = contarEstadoTicketVivo(lista, 'cerrado', 'resuelto'); 
    var sinAsignar = lista.filter(function (ticket) { 
        return Number(ticket && ticket.tecnico_id ? ticket.tecnico_id : 0) <= 0; 
    }).length; 
    var totalNode = porId('stat-total'); 
    var openNode = porId('stat-open'); 
    var progressNode = porId('stat-progress'); 
    var closedNode = porId('stat-closed'); 
    var pageNode = porId('stat-page'); 
    var unassignedNode = porId('stat-unassigned'); 
    if (totalNode) { totalNode.textContent = String(total); } 
    if (openNode) { openNode.textContent = String(abiertos); } 
    if (progressNode) { progressNode.textContent = String(enProceso); } 
    if (closedNode) { closedNode.textContent = String(cerrados); } 
    if (pageNode) { pageNode.textContent = 'Pagina actual: ' + cantidadPagina; } 
    if (unassignedNode) { unassignedNode.textContent = 'Sin asignar: ' + sinAsignar; } 
} 
 
if (typeof window.__ticketsVivosHookeado === 'undefined') { 
    window.__ticketsVivosHookeado = true; 
    var renderizarTicketsBase = renderizarTickets; 
    renderizarTickets = function (tickets) { 
        renderizarTicketsBase(tickets); 
        actualizarIndicadoresVivosTickets(tickets); 
    }; 
}
 
function decorarEstadoVacioTickets() { 
    if (!domElementos.ticketsList) { return; } 
    var lista = domElementos.ticketsList.querySelector('.ticket-list'); 
    if (lista) { return; } 
    var empty = domElementos.ticketsList.querySelector('.empty'); 
    if (!empty) { return; } 
    var box = document.createElement('div'); 
    box.className = 'list-empty'; 
    var title = document.createElement('strong'); 
    title.textContent = 'No hay tickets para mostrar'; 
    var text = document.createElement('p'); 
    text.textContent = empty.textContent ? empty.textContent : 'Ajusta filtros o crea un nuevo ticket.'; 
    box.appendChild(title); 
    box.appendChild(text); 
    empty.replaceWith(box); 
} 
 
if (typeof window.__ticketsVivosDecorador === 'undefined') { 
    window.__ticketsVivosDecorador = true; 
    var renderizarTicketsDecorado = renderizarTickets; 
    renderizarTickets = function (tickets) { 
        renderizarTicketsDecorado(tickets); 
        decorarEstadoVacioTickets(); 
    }; 
}
