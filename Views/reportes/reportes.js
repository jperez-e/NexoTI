let filasReporte = [];
let paginaReporteActual = 1;
const filasPorPaginaReporte = 5;

async function obtenerJson(url) {
    const respuesta = await fetch(url);
    const datosRespuesta = await respuesta.json();
    if (!datosRespuesta || datosRespuesta.status === false) { return []; }
    return datosRespuesta.data || [];
}

function porId(id) {
    return document.getElementById(id);
}

function agregarCelda(fila, texto) { 
    const celda = document.createElement('td'); 
    celda.textContent = texto; 
    fila.appendChild(celda); 
} 
function normalizarClaseEstadoReporte(valor) { 
    const normalizado = String(valor || '').toLowerCase(); 
    if (normalizado.includes('progreso') || normalizado.includes('proceso')) { return 'progreso'; } 
    if (normalizado.includes('resuelto')) { return 'resuelto'; } 
    if (normalizado.includes('cerrado')) { return 'cerrado'; } 
    return 'abierto'; 
} 
function agregarCeldaCodigo(fila, texto) { 
    const celda = document.createElement('td'); 
    const insignia = document.createElement('span'); 
    insignia.className = 'report-code'; 
    insignia.textContent = texto || 'Sin código'; 
    celda.appendChild(insignia); 
    fila.appendChild(celda); 
} 
function agregarCeldaEstado(fila, texto) { 
    const celda = document.createElement('td'); 
    const insignia = document.createElement('span'); 
    insignia.className = 'report-status report-status-' + normalizarClaseEstadoReporte(texto); 
    insignia.textContent = texto || 'Sin estado'; 
    celda.appendChild(insignia); 
    fila.appendChild(celda); 
} 
function agregarCeldaUsuario(fila, texto, respaldo) { 
    const celda = document.createElement('td'); 
    celda.textContent = texto || respaldo; 
    if (!texto) { celda.className = 'report-user-muted'; } 
    fila.appendChild(celda); 
}

function formatearFecha(valor) {
    if (!valor) {
        return 'Sin fecha';
    }

    const fecha = new Date(String(valor).replace(' ', 'T'));
    return Number.isNaN(fecha.getTime()) ? String(valor) : fecha.toLocaleString('es-DO');
}

function actualizarPaginadorReporte(totalElementos, totalPaginas) {
    const paginador = porId('reportes-pager');
    const anterior = porId('reportes-prev');
    const siguiente = porId('reportes-next');
    const info = porId('reportes-page-info');

    if (!paginador || !anterior || !siguiente || !info) {
        return;
    }

    const hayElementos = totalElementos > 0;
    paginador.classList.toggle('hidden', !hayElementos);

    if (!hayElementos) {
        info.textContent = '';
        return;
    }

    info.textContent = 'Página ' + paginaReporteActual + ' de ' + totalPaginas + ' - ' + totalElementos + ' ticket(s)';
    anterior.disabled = paginaReporteActual <= 1;
    siguiente.disabled = paginaReporteActual >= totalPaginas;
}

function restaurarPosicionElemento(elemento, topeAnterior) {
    if (!elemento || topeAnterior === null) {
        return;
    }

    window.requestAnimationFrame(function () {
        const siguienteTope = elemento.getBoundingClientRect().top;
        window.scrollBy(0, siguienteTope - topeAnterior);
    });
}

function preservarPosicionElemento(elemento, trabajo) {
    const topeAnterior = elemento ? elemento.getBoundingClientRect().top : null;
    const resultado = typeof trabajo === 'function' ? trabajo() : null;

    if (resultado && typeof resultado.then === 'function') {
        return resultado.finally(function () {
            restaurarPosicionElemento(elemento, topeAnterior);
        });
    }

    restaurarPosicionElemento(elemento, topeAnterior);
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

    const totalPaginas = Math.max(1, Math.ceil(filasReporte.length / filasPorPaginaReporte));
    if (paginaReporteActual > totalPaginas) {
        paginaReporteActual = totalPaginas;
    }

    const inicio = (paginaReporteActual - 1) * filasPorPaginaReporte;
    const filasPagina = filasReporte.slice(inicio, inicio + filasPorPaginaReporte);

    filasPagina.forEach(function (item) {
        const fila = document.createElement('tr');
        agregarCeldaCodigo(fila, item.codigo);
        agregarCelda(fila, item.titulo);
        agregarCeldaUsuario(fila, item.usuario, 'Sin usuario');
        agregarCeldaUsuario(fila, item.tecnico, 'Sin asignar');
        agregarCeldaEstado(fila, item.estado);
        agregarCelda(fila, formatearFecha(item.fecha_creacion));
        tbody.appendChild(fila);
    });

    actualizarPaginadorReporte(filasReporte.length, totalPaginas);
}

async function cargarResumen() {
    const datos = await obtenerJson('/NexoTI/api.php?c=reporte&m=resumen');
    porId('stat-total').textContent = datos.total || 0;
    porId('stat-abiertos').textContent = datos.abiertos || 0;
    porId('stat-progreso').textContent = datos.en_progreso || 0;
    porId('stat-cerrados').textContent = datos.cerrados || 0;
}

async function cargarVistaPrevia() {
    filasReporte = await obtenerJson('/NexoTI/api.php?c=reporte&m=vistaPrevia');
    paginaReporteActual = 1;
    renderizarPaginaPrevia();
}

document.addEventListener('DOMContentLoaded', function () {
    cargarResumen();
    cargarVistaPrevia();

    const botonRecargar = porId('reload-reportes');
    if (botonRecargar) {
        botonRecargar.addEventListener('click', function () {
            cargarResumen();
            cargarVistaPrevia();
        });
    }

    const botonAnterior = porId('reportes-prev');
    if (botonAnterior) {
        botonAnterior.addEventListener('click', function () {
            if (paginaReporteActual <= 1) { return; }
            preservarPosicionElemento(porId('reportes-pager'), function () {
                paginaReporteActual -= 1;
                renderizarPaginaPrevia();
            });
        });
    }

    const botonSiguiente = porId('reportes-next');
    if (botonSiguiente) {
        botonSiguiente.addEventListener('click', function () {
            const totalPaginas = Math.max(1, Math.ceil(filasReporte.length / filasPorPaginaReporte));
            if (paginaReporteActual >= totalPaginas) { return; }
            preservarPosicionElemento(porId('reportes-pager'), function () {
                paginaReporteActual += 1;
                renderizarPaginaPrevia();
            });
        });
    }
});
