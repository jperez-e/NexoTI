let allReportRows = [];
let currentReportPage = 1;
const reportRowsPerPage = 5;

async function fetchJSON(url) {
    const res = await fetch(url);
    const payload = await res.json();
    if (!payload || payload.status === false) { return []; }
    return payload.data || [];
}

function byId(id) {
    return document.getElementById(id);
}

function appendCell(row, text) {
    const td = document.createElement('td');
    td.textContent = text;
    row.appendChild(td);
}

function formatDate(value) {
    if (!value) {
        return 'Sin fecha';
    }

    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('es-DO');
}

function updateReportPager(totalItems, totalPages) {
    const pager = byId('reportes-pager');
    const prev = byId('reportes-prev');
    const next = byId('reportes-next');
    const info = byId('reportes-page-info');

    if (!pager || !prev || !next || !info) {
        return;
    }

    const hasItems = totalItems > 0;
    pager.classList.toggle('hidden', !hasItems);

    if (!hasItems) {
        info.textContent = '';
        return;
    }

    info.textContent = 'Página ' + currentReportPage + ' de ' + totalPages + ' - ' + totalItems + ' ticket(s)';
    prev.disabled = currentReportPage <= 1;
    next.disabled = currentReportPage >= totalPages;
}

function restoreElementPosition(element, previousTop) {
    if (!element || previousTop === null) {
        return;
    }

    window.requestAnimationFrame(function () {
        const nextTop = element.getBoundingClientRect().top;
        window.scrollBy(0, nextTop - previousTop);
    });
}

function preserveElementPosition(element, work) {
    const previousTop = element ? element.getBoundingClientRect().top : null;
    const result = typeof work === 'function' ? work() : null;

    if (result && typeof result.then === 'function') {
        return result.finally(function () {
            restoreElementPosition(element, previousTop);
        });
    }

    restoreElementPosition(element, previousTop);
    return Promise.resolve();
}

function renderPreviewPage() {
    const tbody = byId('report-preview');
    if (!tbody) { return; }

    tbody.innerHTML = '';

    if (!allReportRows.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 6;
        td.className = 'empty';
        td.textContent = 'No hay tickets para mostrar.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        updateReportPager(0, 1);
        return;
    }

    const totalPages = Math.max(1, Math.ceil(allReportRows.length / reportRowsPerPage));
    if (currentReportPage > totalPages) {
        currentReportPage = totalPages;
    }

    const start = (currentReportPage - 1) * reportRowsPerPage;
    const pageRows = allReportRows.slice(start, start + reportRowsPerPage);

    pageRows.forEach(function (item) {
        const tr = document.createElement('tr');
        appendCell(tr, item.codigo);
        appendCell(tr, item.titulo);
        appendCell(tr, item.usuario);
        appendCell(tr, item.tecnico || 'Sin asignar');
        appendCell(tr, item.estado);
        appendCell(tr, formatDate(item.fecha_creacion));
        tbody.appendChild(tr);
    });

    updateReportPager(allReportRows.length, totalPages);
}

async function loadResumen() {
    const data = await fetchJSON('/NexoTI/api.php?c=reporte&m=resumen');
    byId('stat-total').textContent = data.total || 0;
    byId('stat-abiertos').textContent = data.abiertos || 0;
    byId('stat-progreso').textContent = data.en_progreso || 0;
    byId('stat-cerrados').textContent = data.cerrados || 0;
}

async function loadPreview() {
    allReportRows = await fetchJSON('/NexoTI/api.php?c=reporte&m=preview');
    currentReportPage = 1;
    renderPreviewPage();
}

document.addEventListener('DOMContentLoaded', function () {
    loadResumen();
    loadPreview();

    const btn = byId('reload-reportes');
    if (btn) {
        btn.addEventListener('click', function () {
            loadResumen();
            loadPreview();
        });
    }

    const prev = byId('reportes-prev');
    if (prev) {
        prev.addEventListener('click', function () {
            if (currentReportPage <= 1) { return; }
            preserveElementPosition(byId('reportes-pager'), function () {
                currentReportPage -= 1;
                renderPreviewPage();
            });
        });
    }

    const next = byId('reportes-next');
    if (next) {
        next.addEventListener('click', function () {
            const totalPages = Math.max(1, Math.ceil(allReportRows.length / reportRowsPerPage));
            if (currentReportPage >= totalPages) { return; }
            preserveElementPosition(byId('reportes-pager'), function () {
                currentReportPage += 1;
                renderPreviewPage();
            });
        });
    }
});
