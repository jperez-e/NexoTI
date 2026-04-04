let ticketsPagina = [];
let ticketsAsignables = [];
let ticketsCerrables = [];
let todosComentarios = [];
let todosAdjuntos = [];
let todosParticipantes = [];
let estadosDisponibles = [];
let tecnicosDisponibles = [];
let candidatosParticipantes = [];
let itemsNotificaciones = [];
let totalNotificaciones = 0;
let idTicketExpandido = null;
let idTicketRespuestaAbierta = null;
let filtroEstadoActivo = 'todos';
let filtroAsignacionActivo = 'todos';
let paginaTicketActual = 1;
let temporizadorBusqueda = null;
let metaTicketActual = {
    page: 1,
    per_page: 5,
    total: 0,
    total_pages: 1,
    query: '',
    estado: null,
    asignacion: null,
};
const ticketsPorPagina = 5;
const domElementos = {};

function porId(id) { return document.getElementById(id); }
function datoBody(name) { return document.body.dataset[name] ? String(document.body.dataset[name]) : ''; }
function obtenerUiCore() { return window.NexoUI ? window.NexoUI : null; }
function obtenerTokenCsrf() {
    const ui = obtenerUiCore();
    if (ui) { return ui.obtenerTokenCsrf(); }
    const node = porId('csrf-token');
    return node ? node.value : '';
}
function idUsuarioActual() { return Number(document.body.dataset.userId || 0); }
function idRolActual() { return Number(document.body.dataset.roleId || 0); }
function esUsuarioAdmin() { return idRolActual() === 1; }
function esUsuarioTecnico() { return idRolActual() === 2; }
function esUsuarioFinal() { return idRolActual() === 3; }

async function obtenerJson(url) {
    const response = await fetch(url);
    const payload = await response.json();
    return payload && payload.status !== false && payload.data ? payload.data : [];
}

async function obtenerCargaUtil(url) {
    const response = await fetch(url);
    const payload = await response.json();
    return payload && payload.status !== false ? payload : { status: false, message: 'No se pudo cargar la información.', data: {} };
}

async function enviarJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': obtenerTokenCsrf(),
        },
        body: JSON.stringify(payload),
    });
    return response.json();
}

function establecerBotonCargando(button, loading, loadingText) {
    const ui = obtenerUiCore();
    if (ui) {
        ui.establecerBotonCargando(button, loading, loadingText);
        return;
    }
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

function asegurarPilaToasts() {
    const ui = obtenerUiCore();
    if (ui) {
        let stack = document.querySelector('.toast-stack');
        if (stack) { return stack; }
        stack = document.createElement('div');
        stack.className = 'toast-stack';
        document.body.appendChild(stack);
        return stack;
    }
    let stack = document.querySelector('.toast-stack');
    if (stack) { return stack; }
    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
    return stack;
}

function mostrarToast(title, text, type) {
    const ui = obtenerUiCore();
    if (ui) {
        ui.mostrarToast(title, text, type);
        return;
    }
    const stack = asegurarPilaToasts();
    const toast = document.createElement('div');
    toast.className = 'toast' + (type ? ' ' + type : '');
    toast.innerHTML = '<strong>' + title + '</strong><span>' + text + '</span>';
    stack.appendChild(toast);
    window.setTimeout(function () { toast.remove(); }, 3600);
}

function limpiarMensajeLuego(node, baseClass, delay) {
    const ui = obtenerUiCore();
    if (ui) {
        ui.limpiarMensajeLuego(node, delay, baseClass);
        return;
    }
    if (!node) { return; }
    if (node._messageTimer) { clearTimeout(node._messageTimer); }
    node._messageTimer = window.setTimeout(function () {
        node.textContent = '';
        node.className = baseClass;
    }, delay);
}

function establecerMensaje(node, text, type, title) {
    if (!node) { return; }
    node.textContent = text;
    node.className = type ? 'message ' + type : 'message';
    node.setAttribute('aria-hidden', text === '' ? 'true' : 'false');
    if (text !== '' && type) {
        limpiarMensajeLuego(node, 'message', 4000);
        mostrarToast(title || (type === 'success' ? 'Operación completada' : 'Atención'), text, type);
    }
}

function establecerMensajeEnLinea(node, text, type) {
    if (!node) { return; }
    node.textContent = text;
    node.className = type ? 'message inline-message ' + type : 'message inline-message';
    node.setAttribute('aria-hidden', text === '' ? 'true' : 'false');
    if (text !== '' && type) {
        limpiarMensajeLuego(node, 'message inline-message', 4000);
    }
}

function llenarSelect(select, rows, labelResolver, valueKey) {
    if (!select) { return; }
    select.innerHTML = '';
    rows.forEach(function (row) {
        const option = document.createElement('option');
        option.value = row[valueKey];
        option.textContent = typeof labelResolver === 'function' ? labelResolver(row) : row[labelResolver];
        select.appendChild(option);
    });
}

function inicialesDesdeNombre(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) { return 'NT'; }
    return (parts[0][0] || 'N').toUpperCase() + ((parts[1] && parts[1][0]) ? parts[1][0].toUpperCase() : '');
}

function normalizarEtiquetaRol(roleName) {
    const value = String(roleName || '').toLowerCase().trim();
    if (value === '1' || value.includes('admin')) { return 'Admin'; }
    if (value === '2' || value.includes('tecn') || value.includes('agent') || value.includes('soporte')) { return 'Técnico'; }
    if (value === '3' || value.includes('usuario') || value.includes('cliente')) { return 'Usuario'; }
    return 'Usuario';
}

function nombreClaseRol(roleName) {
    return normalizarNombreEstado(normalizarEtiquetaRol(roleName));
}

function formatearFechaTicket(value) {
    if (!value) { return 'Sin fecha'; }
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('es-DO');
}

function normalizarNombreEstado(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function sufijoClaseEstado(value) {
    return normalizarNombreEstado(value).replace(/\s+/g, '-');
}

function consultaBusquedaNormalizada() {
    return domElementos.search ? domElementos.search.value.trim() : '';
}

function agregarTextoResaltado(node, text, query) {
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

function construirElementoTextoResaltado(tagName, className, text, query) {
    const element = document.createElement(tagName);
    if (className) {
        element.className = className;
    }
    agregarTextoResaltado(element, text, query);
    return element;
}

function construirMeta(text) {
    const meta = document.createElement('p');
    meta.className = 'ticket-meta';
    meta.textContent = text;
    return meta;
}

function contarComentariosPorTicket(ticketId) {
    return todosComentarios.filter(function (comment) {
        return String(comment.ticket_id) === String(ticketId);
    }).length;
}

function construirInsigniaRol(roleName) {
    const badge = document.createElement('span');
    badge.className = 'role-badge role-' + nombreClaseRol(roleName);
    badge.textContent = normalizarEtiquetaRol(roleName);
    return badge;
}

function crearAvatar(name, photoUrl, className) {
    const avatar = document.createElement('div');
    avatar.className = className;
    if (photoUrl) {
        const image = document.createElement('img');
        image.src = photoUrl;
        image.alt = name;
        avatar.appendChild(image);
    } else {
        const fallback = document.createElement('span');
        fallback.textContent = inicialesDesdeNombre(name);
        avatar.appendChild(fallback);
    }
    return avatar;
}

function establecerContenidoBoton(button, iconName, label) {
    if (!button) {
        return;
    }
    button.innerHTML = window.UiIcons ? window.UiIcons.buttonContent(iconName, label) : label;
}

function resolverFoto(photoPath) {
    return photoPath ? '/NexoTI/' + String(photoPath).replace(/^\/+/, '') : '';
}

function resolverAdjunto(path) {
    return path ? '/NexoTI/' + String(path).replace(/^\/+/, '') : '';
}

function adjuntosPorTicket(ticketId) {
    return todosAdjuntos.filter(function (attachment) {
        return String(attachment.ticket_id) === String(ticketId);
    });
}

function participantesPorTicket(ticketId) {
    return todosParticipantes.filter(function (participant) {
        return String(participant.ticket_id) === String(ticketId);
    });
}

function rolParticipantePermitido(roleId) {
    const id = Number(roleId || 0);
    if (esUsuarioAdmin()) {
        return id > 0;
    }
    return id === 2 || id === 3;
}

function esAdjuntoImagen(name) {
    return /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(String(name || ''));
}
