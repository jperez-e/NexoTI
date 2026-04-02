let filasReporte = [];
let paginaReporteActual = 1;
const filasPorPaginaReporte = 5;

async function obtenerJson(url) {
    const res = await fetch(url);
    const payload = await res.json();
    if (!payload || payload.status === false) { return []; }
    return payload.data || [];
}

function porId(id) {
    return document.getElementById(id);
}

function agregarCelda(row, text) { 
    const td = document.createElement('td'); 
    td.textContent = text; 
    row.appendChild(td); 
} 
function normalizarClaseEstadoReporte(value) { 
    const raw = String(value || '').toLowerCase(); 
    if (raw.includes('progreso') || raw.includes('proceso')) { return 'progreso'; } 
    if (raw.includes('resuelto')) { return 'resuelto'; } 
    if (raw.includes('cerrado')) { return 'cerrado'; } 
    return 'abierto'; 
} 
function agregarCeldaCodigo(row, text) { 
    const td = document.createElement('td'); 
    const badge = document.createElement('span'); 
    badge.className = 'report-code'; 
    badge.textContent = text || 'Sin código'; 
    td.appendChild(badge); 
    row.appendChild(td); 
} 
function agregarCeldaEstado(row, text) { 
    const td = document.createElement('td'); 
    const badge = document.createElement('span'); 
    badge.className = 'report-status report-status-' + normalizarClaseEstadoReporte(text); 
    badge.textContent = text || 'Sin estado'; 
    td.appendChild(badge); 
    row.appendChild(td); 
} 
function agregarCeldaUsuario(row, text, fallback) { 
    const td = document.createElement('td'); 
    td.textContent = text || fallback; 
    if (!text) { td.className = 'report-user-muted'; } 
    row.appendChild(td); 
}

function formatearFecha(value) {
    if (!value) {
        return 'Sin fecha';
    }

    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('es-DO');
}

function actualizarPaginadorReporte(totalItems, totalPages) {
    const pager = porId('reportes-pager');
    const prev = porId('reportes-prev');
    const next = porId('reportes-next');
    const info = porId('reportes-page-info');

    if (!pager || !prev || !next || !info) {
        return;
    }

    const hasItems = totalItems > 0;
    pager.classList.toggle('hidden', !hasItems);

    if (!hasItems) {
        info.textContent = '';
        return;
    }

    info.textContent = 'Página ' + paginaReporteActual + ' de ' + totalPages + ' - ' + totalItems + ' ticket(s)';
    prev.disabled = paginaReporteActual <= 1;
    next.disabled = paginaReporteActual >= totalPages;
}

function restaurarPosicionElemento(element, previousTop) {
    if (!element || previousTop === null) {
        return;
    }

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

function renderizarPaginaPrevia() {
    const tbody = porId('report-preview');
    if (!tbody) { return; }

    tbody.innerHTML = '';

    if (!filasReporte.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.className = 'empty';
        td.textContent = 'No hay tickets para mostrar.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        actualizarPaginadorReporte(0, 1);
        return;
    }

    const totalPages = Math.max(1, Math.ceil(filasReporte.length / filasPorPaginaReporte));
    if (paginaReporteActual > totalPages) {
        paginaReporteActual = totalPages;
    }

    const start = (paginaReporteActual - 1) * filasPorPaginaReporte;
    const pageRows = filasReporte.slice(start, start + filasPorPaginaReporte);

    pageRows.forEach(function (item) {
        const tr = document.createElement('tr');
        agregarCeldaCodigo(tr, item.codigo);
        agregarCelda(tr, item.titulo);
        agregarCeldaUsuario(tr, item.usuario, 'Sin usuario');
        agregarCeldaUsuario(tr, item.tecnico, 'Sin asignar');
        agregarCeldaEstado(tr, item.estado);
        agregarCelda(tr, formatearFecha(item.fecha_creacion));
        tbody.appendChild(tr);
    });

    actualizarPaginadorReporte(filasReporte.length, totalPages);
}

async function cargarResumen() {
    const data = await obtenerJson('/NexoTI/api.php?c=reporte&m=resumen');
    porId('stat-total').textContent = data.total || 0;
    porId('stat-abiertos').textContent = data.abiertos || 0;
    porId('stat-progreso').textContent = data.en_progreso || 0;
    porId('stat-cerrados').textContent = data.cerrados || 0;
}

async function cargarVistaPrevia() {
    filasReporte = await obtenerJson('/NexoTI/api.php?c=reporte&m=vistaPrevia');
    paginaReporteActual = 1;
    renderizarPaginaPrevia();
}

document.addEventListener('DOMContentLoaded', function () {
    cargarResumen();
    cargarVistaPrevia();

    const btn = porId('reload-reportes');
    if (btn) {
        btn.addEventListener('click', function () {
            cargarResumen();
            cargarVistaPrevia();
        });
    }

    const prev = porId('reportes-prev');
    if (prev) {
        prev.addEventListener('click', function () {
            if (paginaReporteActual <= 1) { return; }
            preservarPosicionElemento(porId('reportes-pager'), function () {
                paginaReporteActual -= 1;
                renderizarPaginaPrevia();
            });
        });
    }

    const next = porId('reportes-next');
    if (next) {
        next.addEventListener('click', function () {
            const totalPages = Math.max(1, Math.ceil(filasReporte.length / filasPorPaginaReporte));
            if (paginaReporteActual >= totalPages) { return; }
            preservarPosicionElemento(porId('reportes-pager'), function () {
                paginaReporteActual += 1;
                renderizarPaginaPrevia();
            });
        });
    }
});
