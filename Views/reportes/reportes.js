// Este archivo JavaScript define la lógica de interfaz del módulo reportes.
// Maneja eventos, consumo de API y renderizado dinámico del DOM para mejorar la experiencia de usuario.
let filasReporte = [];
let paginaReporteActual = 1;
const filasPorPaginaReporte = 5;

function porId(id) {
    return document.getElementById(id);
}

function rolActual() {
    return Number(document.body.dataset.rolId || 0);
}

function valorFiltro(id) {
    const nodo = porId(id);
    return nodo ? String(nodo.value || '').trim() : '';
}

function parametrosFiltro() {
    const parametros = new URLSearchParams();
    const desde = valorFiltro('filtro-desde');
    const hasta = valorFiltro('filtro-hasta');
    if (desde !== '') {
        parametros.set('desde', desde);
    }
    if (hasta !== '') {
        parametros.set('hasta', hasta);
    }
    return parametros;
}

function construirUrlApi(metodo) {
    const parametros = parametrosFiltro();
    parametros.set('c', 'reporte');
    parametros.set('m', metodo);
    return '/NexoTI/api.php?' + parametros.toString();
}

function actualizarEnlacesExportacion() {
    const parametros = parametrosFiltro();
    const csv = porId('export-csv');
    const excel = porId('export-excel');
    const pdf = porId('export-pdf');
    if (csv) {
        const p = new URLSearchParams(parametros.toString());
        p.set('c', 'reporte');
        p.set('m', 'ticketsCsv');
        csv.href = '/NexoTI/api.php?' + p.toString();
    }
    if (excel) {
        const p = new URLSearchParams(parametros.toString());
        p.set('c', 'reporte');
        p.set('m', 'ticketsExcel');
        excel.href = '/NexoTI/api.php?' + p.toString();
    }
    if (pdf) {
        const p = new URLSearchParams(parametros.toString());
        p.set('c', 'reporte');
        p.set('m', 'ticketsPdf');
        pdf.href = '/NexoTI/api.php?' + p.toString();
    }
}

async function obtenerJson(url) {
    const respuesta = await fetch(url);
    const carga = await respuesta.json();
    if (!carga || carga.status === false) {
        throw new Error(carga && carga.message ? carga.message : 'No se pudo cargar la información.');
    }
    return carga.data || [];
}

function establecerTexto(id, valor, fallback) {
    const nodo = porId(id);
    if (!nodo) {
        return;
    }
    if (valor === null || valor === undefined || valor === '') {
        nodo.textContent = fallback;
        return;
    }
    nodo.textContent = String(valor);
}

function formatearNumero(valor) {
    const numero = Number(valor || 0);
    return Number.isFinite(numero) ? numero.toLocaleString('es-DO') : '0';
}

function formatearHoraDecimal(valor) {
    const numero = Number(valor || 0);
    if (!Number.isFinite(numero)) {
        return '0.00';
    }
    return numero.toFixed(2);
}

function formatearFecha(valor) {
    if (!valor) {
        return 'Sin fecha';
    }
    const fecha = new Date(String(valor).replace(' ', 'T'));
    return Number.isNaN(fecha.getTime()) ? String(valor) : fecha.toLocaleString('es-DO');
}

function normalizarClaseEstadoReporte(valor) {
    const nombre = String(valor || '').toLowerCase();
    if (nombre.includes('progreso') || nombre.includes('proceso')) {
        return 'progreso';
    }
    if (nombre.includes('resuelto')) {
        return 'resuelto';
    }
    if (nombre.includes('cerrado')) {
        return 'cerrado';
    }
    return 'abierto';
}

function renderizarFilasClaveValor(idCuerpo, filas, campoClave, etiquetaVacia) {
    const cuerpo = porId(idCuerpo);
    if (!cuerpo) {
        return;
    }
    cuerpo.innerHTML = '';

    if (!Array.isArray(filas) || filas.length === 0) {
        const fila = document.createElement('tr');
        const celda = document.createElement('td');
        celda.colSpan = 2;
        celda.textContent = etiquetaVacia;
        fila.appendChild(celda);
        cuerpo.appendChild(fila);
        return;
    }

    filas.forEach(function (item) {
        const fila = document.createElement('tr');
        const clave = document.createElement('td');
        const total = document.createElement('td');
        clave.textContent = item[campoClave] ? String(item[campoClave]) : '-';
        total.textContent = formatearNumero(item.total);
        fila.appendChild(clave);
        fila.appendChild(total);
        cuerpo.appendChild(fila);
    });
}

function renderizarTendencia(filas) {
    const cuerpo = porId('report-tendencia');
    if (!cuerpo) {
        return;
    }
    cuerpo.innerHTML = '';

    if (!Array.isArray(filas) || filas.length === 0) {
        const fila = document.createElement('tr');
        const celda = document.createElement('td');
        celda.colSpan = 2;
        celda.textContent = 'No hay datos de tendencia para el rango seleccionado.';
        fila.appendChild(celda);
        cuerpo.appendChild(fila);
        return;
    }

    filas.slice().reverse().forEach(function (item) {
        const fila = document.createElement('tr');
        const periodo = document.createElement('td');
        const total = document.createElement('td');
        periodo.textContent = String(item.periodo || '-');
        total.textContent = formatearNumero(item.total);
        fila.appendChild(periodo);
        fila.appendChild(total);
        cuerpo.appendChild(fila);
    });
}

function renderizarTecnicos(filas) {
    const seccion = porId('tecnicos-section');
    const cuerpo = porId('report-tecnicos');
    if (!seccion || !cuerpo) {
        return;
    }

    const mostrar = rolActual() === 1;
    seccion.classList.toggle('hidden', !mostrar);
    if (!mostrar) {
        return;
    }

    cuerpo.innerHTML = '';
    if (!Array.isArray(filas) || filas.length === 0) {
        const fila = document.createElement('tr');
        const celda = document.createElement('td');
        celda.colSpan = 4;
        celda.textContent = 'No hay técnicos con actividad en el rango seleccionado.';
        fila.appendChild(celda);
        cuerpo.appendChild(fila);
        return;
    }

    filas.forEach(function (item) {
        const fila = document.createElement('tr');
        const nombre = document.createElement('td');
        const asignados = document.createElement('td');
        const cerrados = document.createElement('td');
        const promedio = document.createElement('td');
        nombre.textContent = String(item.nombre || 'Técnico');
        asignados.textContent = formatearNumero(item.asignados);
        cerrados.textContent = formatearNumero(item.cerrados);
        promedio.textContent = formatearHoraDecimal(item.promedio_resolucion_horas);
        fila.appendChild(nombre);
        fila.appendChild(asignados);
        fila.appendChild(cerrados);
        fila.appendChild(promedio);
        cuerpo.appendChild(fila);
    });
}

function renderizarResumen(resumen) {
    establecerTexto('stat-total', formatearNumero(resumen.total), '0');
    establecerTexto('stat-abiertos', formatearNumero(resumen.abiertos), '0');
    establecerTexto('stat-progreso', formatearNumero(resumen.en_progreso), '0');
    establecerTexto('stat-cerrados', formatearNumero(resumen.cerrados), '0');
    establecerTexto('stat-resueltos', formatearNumero(resumen.resueltos), '0');
    establecerTexto('stat-prom-resolucion', formatearHoraDecimal(resumen.promedio_resolucion_horas), '0.00');
    establecerTexto('stat-prom-primera', formatearHoraDecimal(resumen.promedio_primera_respuesta_horas), '0.00');

    const backlog = (resumen.backlog_prioridad || []).reduce(function (acum, item) {
        return acum + Number(item.total || 0);
    }, 0);
    establecerTexto('stat-backlog', formatearNumero(backlog), '0');

    renderizarFilasClaveValor('report-estados', resumen.estados || [], 'nombre', 'Sin estados para mostrar.');
    renderizarFilasClaveValor('report-prioridades', resumen.prioridades || [], 'nombre', 'Sin prioridades para mostrar.');
    renderizarFilasClaveValor('report-categorias', resumen.categorias || [], 'nombre', 'Sin categorías para mostrar.');
    renderizarFilasClaveValor('report-backlog', resumen.backlog_prioridad || [], 'nombre', 'No hay backlog pendiente.');
    renderizarTendencia(resumen.tendencia_mensual || []);
    renderizarTecnicos(resumen.rendimiento_tecnicos || []);
}

function agregarCeldaTexto(fila, texto) {
    const celda = document.createElement('td');
    celda.textContent = texto;
    fila.appendChild(celda);
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
    if (!texto) {
        celda.className = 'report-user-muted';
    }
    fila.appendChild(celda);
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
    const cuerpo = porId('report-preview');
    if (!cuerpo) {
        return;
    }
    cuerpo.innerHTML = '';

    if (!filasReporte.length) {
        const fila = document.createElement('tr');
        const celda = document.createElement('td');
        celda.colSpan = 6;
        celda.className = 'empty';
        celda.textContent = 'No hay tickets para mostrar.';
        fila.appendChild(celda);
        cuerpo.appendChild(fila);
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
        agregarCeldaTexto(fila, item.titulo || 'Sin título');
        agregarCeldaUsuario(fila, item.usuario, 'Sin usuario');
        agregarCeldaUsuario(fila, item.tecnico, 'Sin asignar');
        agregarCeldaEstado(fila, item.estado);
        agregarCeldaTexto(fila, formatearFecha(item.fecha_creacion));
        cuerpo.appendChild(fila);
    });

    actualizarPaginadorReporte(filasReporte.length, totalPaginas);
}

async function cargarResumen() {
    const datos = await obtenerJson(construirUrlApi('resumen'));
    renderizarResumen(datos);
}

async function cargarVistaPrevia() {
    filasReporte = await obtenerJson(construirUrlApi('vistaPrevia'));
    paginaReporteActual = 1;
    renderizarPaginaPrevia();
}

async function recargarTodo() {
    actualizarEnlacesExportacion();
    await Promise.all([cargarResumen(), cargarVistaPrevia()]);
}

function conectarFiltros() {
    const aplicar = porId('aplicar-filtro');
    const limpiar = porId('limpiar-filtro');
    if (aplicar) {
        aplicar.addEventListener('click', function () {
            recargarTodo();
        });
    }
    if (limpiar) {
        limpiar.addEventListener('click', function () {
            const desde = porId('filtro-desde');
            const hasta = porId('filtro-hasta');
            if (desde) {
                desde.value = '';
            }
            if (hasta) {
                hasta.value = '';
            }
            recargarTodo();
        });
    }
}

function conectarPaginador() {
    const anterior = porId('reportes-prev');
    if (anterior) {
        anterior.addEventListener('click', function () {
            if (paginaReporteActual <= 1) {
                return;
            }
            preservarPosicionElemento(porId('reportes-pager'), function () {
                paginaReporteActual -= 1;
                renderizarPaginaPrevia();
            });
        });
    }

    const siguiente = porId('reportes-next');
    if (siguiente) {
        siguiente.addEventListener('click', function () {
            const totalPaginas = Math.max(1, Math.ceil(filasReporte.length / filasPorPaginaReporte));
            if (paginaReporteActual >= totalPaginas) {
                return;
            }
            preservarPosicionElemento(porId('reportes-pager'), function () {
                paginaReporteActual += 1;
                renderizarPaginaPrevia();
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    conectarFiltros();
    conectarPaginador();

    const recargar = porId('reload-reportes');
    if (recargar) {
        recargar.addEventListener('click', function () {
            recargarTodo();
        });
    }

    recargarTodo().catch(function () {
        const preview = porId('report-preview');
        if (preview) {
            preview.innerHTML = '<tr><td colspan="6">No se pudieron cargar los reportes.</td></tr>';
        }
    });
});
