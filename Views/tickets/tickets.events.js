

async function aplicarBusqueda() {
    paginaTicketActual = 1;
    await cargarTickets();
}

const INTERVALO_NOTIFICACION_VISIBLE_MS = 10000;
const INTERVALO_NOTIFICACION_OCULTO_MS = 30000;
let temporizadorConsultaNotificaciones = null;
let consultaNotificacionesEnCurso = false;

async function aplicarFiltroEstado(filterValue) {
    filtroEstadoActivo = filterValue || 'todos';
    paginaTicketActual = 1;
    await cargarTickets();
}

async function aplicarFiltroAsignacion(filterValue) {
    filtroAsignacionActivo = filterValue || 'todos';
    paginaTicketActual = 1;
    await cargarTickets();
}

function alternarPanelNotificaciones(forceState) {
    if (!domElementos.noticePanel) { return; }
    const wasOpen = domElementos.noticePanel.classList.contains('is-open');
    const nextState = typeof forceState === 'boolean' ? forceState : !domElementos.noticePanel.classList.contains('is-open');
    if (nextState === wasOpen) {
        domElementos.noticePanel.setAttribute('aria-hidden', nextState ? 'false' : 'true');
        if (domElementos.noticeButton) {
            domElementos.noticeButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        }
        return;
    }
    domElementos.noticePanel.classList.toggle('is-open', nextState);
    domElementos.noticePanel.setAttribute('aria-hidden', nextState ? 'false' : 'true');
    if (domElementos.noticeButton) {
        domElementos.noticeButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        if (!nextState && wasOpen) {
            domElementos.noticeButton.focus();
        }
    }
    if (nextState) {
        domElementos.noticePanel.focus();
    }
}

function alternarMenuAvatar(forceState) {
    if (!domElementos.avatarMenu) { return; }
    const wasOpen = domElementos.avatarMenu.classList.contains('is-open');
    const nextState = typeof forceState === 'boolean' ? forceState : !domElementos.avatarMenu.classList.contains('is-open');
    if (nextState === wasOpen) {
        domElementos.avatarMenu.setAttribute('aria-hidden', nextState ? 'false' : 'true');
        if (domElementos.avatarButton) {
            domElementos.avatarButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        }
        return;
    }
    domElementos.avatarMenu.classList.toggle('is-open', nextState);
    domElementos.avatarMenu.setAttribute('aria-hidden', nextState ? 'false' : 'true');
    if (domElementos.avatarButton) {
        domElementos.avatarButton.setAttribute('aria-expanded', nextState ? 'true' : 'false');
        if (!nextState && wasOpen) {
            domElementos.avatarButton.focus();
        }
    }
    if (nextState) {
        const firstItem = domElementos.avatarMenu.querySelector('a');
        if (firstItem) { firstItem.focus(); }
    }
}

function configurarMenuAvatar() {
    if (domElementos.avatarButton && domElementos.avatarMenu) {
        domElementos.avatarButton.addEventListener('click', function (event) {
            event.stopPropagation();
            alternarMenuAvatar();
        });
        domElementos.avatarButton.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                alternarMenuAvatar(true);
            }
        });
    }
    if (domElementos.noticeButton) {
        domElementos.noticeButton.addEventListener('click', function (event) {
            event.stopPropagation();
            alternarPanelNotificaciones();
        });
    }
    if (domElementos.avatarMenu) {
        domElementos.avatarMenu.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                alternarMenuAvatar(false);
            }
        });
    }
    if (domElementos.noticePanel) {
        domElementos.noticePanel.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                alternarPanelNotificaciones(false);
            }
        });
    }
    document.addEventListener('click', function (event) {
        if (domElementos.avatarMenu && domElementos.avatarButton && !domElementos.avatarMenu.contains(event.target) && !domElementos.avatarButton.contains(event.target)) {
            alternarMenuAvatar(false);
        }
        if (domElementos.noticePanel && domElementos.noticeButton && !domElementos.noticePanel.contains(event.target) && !domElementos.noticeButton.contains(event.target)) {
            alternarPanelNotificaciones(false);
        }
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        if (domElementos.avatarMenu && domElementos.avatarMenu.classList.contains('is-open')) {
            alternarMenuAvatar(false);
        }
        if (domElementos.noticePanel && domElementos.noticePanel.classList.contains('is-open')) {
            alternarPanelNotificaciones(false);
        }
    });
}

function establecerOcurrenciaPorDefecto() {
    if (!domElementos.occurrenceInput) { return; }
    const now = new Date();
    const offset = now.getTimezoneOffset();
    domElementos.occurrenceInput.value = new Date(now.getTime() - offset * 60000).toISOString().slice(0, 16);
}

function configurarFormularioCrear() {
    if (!domElementos.ticketForm) { return; }
    establecerOcurrenciaPorDefecto();
    domElementos.ticketForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const formData = new FormData(domElementos.ticketForm);
        const submitButton = domElementos.ticketForm.querySelector('button[type="submit"]');
        formData.set('_token', obtenerTokenCsrf());
        establecerBotonCargando(submitButton, true, 'Creando...');
        try {
            const response = await fetch('api.php?c=ticket&m=create', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status) {
                establecerMensaje(domElementos.formMessage, '', '');
                mostrarToast('Creación de ticket', data.message ? data.message : 'Proceso completado.', 'success');
            } else {
                establecerMensaje(domElementos.formMessage, data.message ? data.message : 'Proceso completado.', 'error', 'Creación de ticket');
            }
            if (data.status) {
                domElementos.ticketForm.reset();
                establecerOcurrenciaPorDefecto();
                if (idTicketExpandido !== null) {
                    await preservarPosicionTicket(idTicketExpandido, async function () {
                        await refrescarVistaTickets();
                    });
                } else {
                    await refrescarVistaTickets();
                }
            }
        } finally {
            establecerBotonCargando(submitButton, false);
        }
    });
}

function configurarFormularioCerrar() {
    if (!domElementos.closeForm) { return; }
    const closeCard = domElementos.closeForm.closest('.card');
    if (closeCard) { closeCard.style.display = 'none'; }
}

function configurarFiltrosListado() {
    if (domElementos.statusFilterSelect) {
        domElementos.statusFilterSelect.value = filtroEstadoActivo;
        domElementos.statusFilterSelect.addEventListener('change', async function () {
            await aplicarFiltroEstado(domElementos.statusFilterSelect.value || 'todos');
        });
    }
    if (domElementos.assignmentFilterSelect) {
        domElementos.assignmentFilterSelect.value = filtroAsignacionActivo;
        domElementos.assignmentFilterSelect.addEventListener('change', async function () {
            await aplicarFiltroAsignacion(domElementos.assignmentFilterSelect.value || 'todos');
        });
    }
}

async function consultarNotificaciones() {
    if (consultaNotificacionesEnCurso) { return; }
    consultaNotificacionesEnCurso = true;
    try {
        await cargarNotificaciones();
    } finally {
        consultaNotificacionesEnCurso = false;
    }
}

function reiniciarConsultaNotificaciones() {
    if (temporizadorConsultaNotificaciones) {
        clearInterval(temporizadorConsultaNotificaciones);
        temporizadorConsultaNotificaciones = null;
    }

    const interval = document.visibilityState === 'visible'
        ? INTERVALO_NOTIFICACION_VISIBLE_MS
        : INTERVALO_NOTIFICACION_OCULTO_MS;

    temporizadorConsultaNotificaciones = window.setInterval(function () {
        consultarNotificaciones();
    }, interval);
}

function configurarConsultaNotificaciones() {
    reiniciarConsultaNotificaciones();
    document.addEventListener('visibilitychange', function () {
        reiniciarConsultaNotificaciones();
        if (document.visibilityState === 'visible') {
            consultarNotificaciones();
        }
    });
    window.addEventListener('beforeunload', function () {
        if (temporizadorConsultaNotificaciones) {
            clearInterval(temporizadorConsultaNotificaciones);
            temporizadorConsultaNotificaciones = null;
        }
    });
}

function cachearDom() {
    domElementos.search = porId('ticket-search');
    domElementos.refreshButton = porId('refresh-btn');
    domElementos.noticeButton = porId('notice-btn');
    domElementos.noticePanel = porId('notice-panel');
    domElementos.noticeCount = porId('notice-count');
    domElementos.avatarButton = porId('avatar-btn');
    domElementos.avatarMenu = porId('avatar-menu');
    domElementos.ticketsList = porId('tickets-list');
    domElementos.resultsInfo = porId('results-info');
    domElementos.statusFilterSelect = porId('status-filter-select');
    domElementos.assignmentFilterSelect = porId('assignment-filter-select');
    domElementos.ticketsPager = porId('tickets-pager');
    domElementos.ticketsPrev = porId('tickets-prev');
    domElementos.ticketsNext = porId('tickets-next');
    domElementos.ticketsPageInfo = porId('tickets-page-info');
    domElementos.ticketForm = porId('ticket-form');
    domElementos.formMessage = porId('form-message');
    domElementos.categoria = porId('categoria_id');
    domElementos.prioridad = porId('prioridad_id');
    domElementos.estado = porId('estado_id');
    domElementos.occurrenceInput = domElementos.ticketForm ? domElementos.ticketForm.querySelector('input[name="fecha_ocurrencia"]') : null;
    domElementos.closeForm = porId('close-form');
    domElementos.closeTicket = porId('close_ticket_id');
    domElementos.closeMessage = porId('close-message');
}

document.addEventListener('DOMContentLoaded', async function () {
    cachearDom();
    configurarMenuAvatar();
    configurarFormularioCrear();
    configurarFormularioCerrar();
    configurarFiltrosListado();
    configurarConsultaNotificaciones();
    if (domElementos.search) {
        domElementos.search.addEventListener('input', function () {
            if (temporizadorBusqueda) {
                clearTimeout(temporizadorBusqueda);
            }
            temporizadorBusqueda = window.setTimeout(function () {
                aplicarBusqueda();
            }, 250);
        });
    }
    if (domElementos.ticketsPrev) {
        domElementos.ticketsPrev.addEventListener('click', async function () {
            if (metaTicketActual.page <= 1) { return; }
            await preservarPosicionElemento(domElementos.ticketsPager, async function () {
                paginaTicketActual -= 1;
                await cargarTickets();
            });
        });
    }
    if (domElementos.ticketsNext) {
        domElementos.ticketsNext.addEventListener('click', async function () {
            if (metaTicketActual.page >= metaTicketActual.total_pages) { return; }
            await preservarPosicionElemento(domElementos.ticketsPager, async function () {
                paginaTicketActual += 1;
                await cargarTickets();
            });
        });
    }
    if (domElementos.refreshButton) {
        domElementos.refreshButton.addEventListener('click', async function () {
            const button = this;
            establecerBotonCargando(button, true, 'Actualizando...');
            try {
                if (idTicketExpandido !== null) {
                    await preservarPosicionTicket(idTicketExpandido, async function () {
                        await refrescarVistaTickets();
                    });
                } else {
                    await refrescarVistaTickets();
                }
            } finally { establecerBotonCargando(button, false); }
        });
    }
    await cargarCombos();
    await cargarTecnicos();
    await cargarCandidatosParticipantes();
    await Promise.all([cargarTickets(), cargarNotificaciones()]);
});
