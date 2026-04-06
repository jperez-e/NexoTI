// El código JavaScript en este archivo se encarga de gestionar el sistema de notificaciones en la página de inicio de la aplicación.


// Se definen constantes para los intervalos de tiempo en los que se consultarán las notificaciones, 
// diferenciando entre cuando la página está visible y cuando está oculta para optimizar el uso de recursos.
const INTERVALO_NOTIFICACION_VISIBLE_MS = 10000;  // 10 segundos
const INTERVALO_NOTIFICACION_OCULTO_MS = 30000;  // 30 segundos

// El objeto `domInicio` actúa como un contenedor para almacenar referencias a los elementos DOM relacionados con las notificaciones 
// y el menú de avatar.
const domInicio = {
    noticeButton: null,
    noticePanel: null,
    noticeCount: null,
    avatarButton: null,
    avatarMenu: null,
};

// Las variables de estado — la memoria del sistema de notificaciones 
// — se declaran en el ámbito global del módulo para facilitar su acceso y manipulación desde 
// cualquier función relacionada con las notificaciones.
let itemsNotificacionesInicio = []; // la lista actual de notificaciones obtenida del servidor
let totalNotificacionesInicio = 0; // el numero del badge rojo.
let temporizadorNotificacionesInicio = null; // el reloj interno.
let consultaNotificacionesInicioEnCurso = false; // estamos consultando al servidor en este momento?

function porIdInicio(id) {
    return document.getElementById(id);
}

function obtenerTokenCsrfInicio() {
    const tokenNode = porIdInicio('csrf-token');
    return tokenNode ? tokenNode.value : '';
}

async function obtenerCargaUtilInicio(url) {
    const respuesta = await fetch(url);
    return respuesta.json();
}

async function enviarJsonInicio(url, datos) {
    const token = obtenerTokenCsrfInicio();
    const payload = Object.assign({}, datos || {});
    const respuesta = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': token,
        },
        body: JSON.stringify(payload),
    });
    return respuesta.json();
}

function formatearFechaInicio(valor) {
    if (!valor) {
        return 'Sin fecha';
    }
    const fecha = new Date(String(valor).replace(' ', 'T'));
    if (Number.isNaN(fecha.getTime())) {
        return String(valor);
    }
    return fecha.toLocaleString('es-DO', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function alternarPanelNotificacionesInicio(forceState) {
    if (!domInicio.noticePanel) {
        return;
    }
    const abierto = domInicio.noticePanel.classList.contains('is-open');
    const siguiente = typeof forceState === 'boolean' ? forceState : !abierto;
    domInicio.noticePanel.classList.toggle('is-open', siguiente);
    domInicio.noticePanel.setAttribute('aria-hidden', siguiente ? 'false' : 'true');
    if (domInicio.noticeButton) {
        domInicio.noticeButton.setAttribute('aria-expanded', siguiente ? 'true' : 'false');
    }
    if (siguiente) {
        domInicio.noticePanel.focus();
    }
}

function alternarMenuAvatarInicio(forceState) {
    if (!domInicio.avatarMenu) {
        return;
    }
    const abierto = domInicio.avatarMenu.classList.contains('is-open');
    const siguiente = typeof forceState === 'boolean' ? forceState : !abierto;
    domInicio.avatarMenu.classList.toggle('is-open', siguiente);
    domInicio.avatarMenu.setAttribute('aria-hidden', siguiente ? 'false' : 'true');
    if (domInicio.avatarButton) {
        domInicio.avatarButton.setAttribute('aria-expanded', siguiente ? 'true' : 'false');
    }
}

// La función `renderizarPanelNotificacionesInicio` es responsable de actualizar el contenido del panel de notificaciones cada vez que se obtiene nueva información del servidor.
function renderizarPanelNotificacionesInicio() {
    if (!domInicio.noticePanel || !domInicio.noticeCount) {
        return;
    }
    domInicio.noticeCount.textContent = String(totalNotificacionesInicio);
    domInicio.noticeCount.classList.toggle('hidden', totalNotificacionesInicio === 0);
    domInicio.noticeCount.setAttribute('aria-hidden', totalNotificacionesInicio === 0 ? 'true' : 'false');

    domInicio.noticePanel.innerHTML = '';
    const header = document.createElement('div');
    header.className = 'notice-header';
    const heading = document.createElement('strong');
    heading.textContent = 'Notificaciones';
    header.appendChild(heading);

    if (totalNotificacionesInicio > 0) {
        const markAllButton = document.createElement('button');
        markAllButton.type = 'button';
        markAllButton.className = 'notice-action';
        markAllButton.textContent = 'Marcar todas';
        markAllButton.addEventListener('click', async function () {
            const data = await enviarJsonInicio('api.php?c=notificacion&m=marcarTodasLeidas', {});
            if (data.status) {
                await cargarNotificacionesInicio();
            }
        });
        header.appendChild(markAllButton);
    }

    domInicio.noticePanel.appendChild(header);
    if (itemsNotificacionesInicio.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'notice-empty';
        empty.textContent = 'No hay notificaciones pendientes.';
        domInicio.noticePanel.appendChild(empty);
        return;
    }

    itemsNotificacionesInicio.forEach(function (notification) {
        const item = document.createElement('div');
        item.className = 'notice-item';
        item.classList.toggle('is-read', Number(notification.leida || 0) === 1);

        const title = document.createElement('strong');
        title.textContent = notification.titulo || 'Notificación';

        const text = document.createElement('span');
        text.textContent = notification.mensaje || '';

        const meta = document.createElement('small');
        const code = notification.ticket_codigo ? String(notification.ticket_codigo) + ' · ' : '';
        meta.textContent = code + formatearFechaInicio(notification.creada_en);

        item.appendChild(title);
        item.appendChild(text);
        item.appendChild(meta);

        if (Number(notification.leida || 0) === 0) {
            const action = document.createElement('button');
            action.type = 'button';
            action.className = 'notice-action';
            action.textContent = 'Marcar como leída';
            action.addEventListener('click', async function () {
                const data = await enviarJsonInicio('api.php?c=notificacion&m=marcarLeida', { id: notification.id });
                if (data.status) {
                    await cargarNotificacionesInicio();
                }
            });
            item.appendChild(action);
        }

        domInicio.noticePanel.appendChild(item);
    });
}

async function cargarNotificacionesInicio() {
    const payload = await obtenerCargaUtilInicio('api.php?c=notificacion&m=listar');
    const data = payload.data || {};
    itemsNotificacionesInicio = Array.isArray(data.items) ? data.items : [];
    totalNotificacionesInicio = Number(data.count || 0);
    renderizarPanelNotificacionesInicio();
}


// La función `consultarNotificacionesInicio` se encarga de gestionar la consulta al servidor para 
// obtener las notificaciones, asegurándose de que no se realicen múltiples consultas simultáneas 
// y actualizando el estado de las notificaciones una vez que se recibe la respuesta.
async function consultarNotificacionesInicio() {
    if (consultaNotificacionesInicioEnCurso) {
        return;
    }
    consultaNotificacionesInicioEnCurso = true;
    try {
        await cargarNotificacionesInicio();
    } finally {
        consultaNotificacionesInicioEnCurso = false;
    }
}

function reiniciarConsultaNotificacionesInicio() {
    if (temporizadorNotificacionesInicio) {
        clearInterval(temporizadorNotificacionesInicio);
    }
    const intervalo = document.visibilityState === 'visible'
        ? INTERVALO_NOTIFICACION_VISIBLE_MS
        : INTERVALO_NOTIFICACION_OCULTO_MS;
    temporizadorNotificacionesInicio = window.setInterval(function () {
        consultarNotificacionesInicio();
    }, intervalo);
}

// La función `configurarInteraccionesInicio` se encarga de establecer los event listeners necesarios para manejar las interacciones del usuario con el panel de notificaciones y el menú de avatar, así como para cerrar estos elementos cuando el usuario haga clic fuera de ellos o presione la tecla Escape.
function configurarInteraccionesInicio() {
    if (domInicio.noticeButton) {
        domInicio.noticeButton.addEventListener('click', function (event) {
            event.stopPropagation();
            alternarPanelNotificacionesInicio();
        });
    }

    if (domInicio.avatarButton && domInicio.avatarMenu) {
        domInicio.avatarButton.addEventListener('click', function (event) {
            event.stopPropagation();
            alternarMenuAvatarInicio();
        });
    }

    document.addEventListener('click', function (event) {
        if (domInicio.avatarMenu && domInicio.avatarButton && !domInicio.avatarMenu.contains(event.target) && !domInicio.avatarButton.contains(event.target)) {
            alternarMenuAvatarInicio(false);
        }
        if (domInicio.noticePanel && domInicio.noticeButton && !domInicio.noticePanel.contains(event.target) && !domInicio.noticeButton.contains(event.target)) {
            alternarPanelNotificacionesInicio(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }
        alternarMenuAvatarInicio(false);
        alternarPanelNotificacionesInicio(false);
    });
}

function cachearDomInicio() {
    domInicio.noticeButton = porIdInicio('notice-btn');
    domInicio.noticePanel = porIdInicio('notice-panel');
    domInicio.noticeCount = porIdInicio('notice-count');
    domInicio.avatarButton = porIdInicio('avatar-btn');
    domInicio.avatarMenu = porIdInicio('avatar-menu');
}

document.addEventListener('DOMContentLoaded', async function () {
    cachearDomInicio();
    configurarInteraccionesInicio();
    await consultarNotificacionesInicio();
    reiniciarConsultaNotificacionesInicio();

    document.addEventListener('visibilitychange', function () {
        reiniciarConsultaNotificacionesInicio();
        if (document.visibilityState === 'visible') {
            consultarNotificacionesInicio();
        }
    });
    window.addEventListener('beforeunload', function () {
        if (temporizadorNotificacionesInicio) {
            clearInterval(temporizadorNotificacionesInicio);
            temporizadorNotificacionesInicio = null;
        }
    });
});
