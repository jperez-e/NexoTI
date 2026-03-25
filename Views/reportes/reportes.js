async function fetchJSON(url) {
    const res = await fetch(url);
    const payload = await res.json();
    if (!payload || payload.status === false) { return []; }
    return payload.data || [];
}

async function loadResumen() {
    const data = await fetchJSON('/NexoTI/api.php?c=reporte&m=resumen');
    document.getElementById('stat-total').textContent = data.total || 0;
    document.getElementById('stat-abiertos').textContent = data.abiertos || 0;
    document.getElementById('stat-progreso').textContent = data.en_progreso || 0;
    document.getElementById('stat-cerrados').textContent = data.cerrados || 0;
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

async function loadPreview() {
    const rows = await fetchJSON('/NexoTI/api.php?c=reporte&m=preview');
    const tbody = document.getElementById('report-preview');
    if (!tbody) { return; }
    tbody.innerHTML = '';
    if (!rows.length) {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 5;
        td.className = 'empty';
        td.textContent = 'No hay tickets para mostrar.';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
    }
    rows.forEach(function (item) {
        const tr = document.createElement('tr');
        appendCell(tr, item.codigo);
        appendCell(tr, item.titulo);
        appendCell(tr, item.usuario);
        appendCell(tr, item.tecnico || 'Sin asignar');
        appendCell(tr, item.estado);
        appendCell(tr, formatDate(item.fecha_creacion));
        tbody.appendChild(tr);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    loadResumen();
    loadPreview();
    const btn = document.getElementById('reload-reportes');
    if (btn) {
        btn.addEventListener('click', function () {
            loadResumen();
            loadPreview();
        });
    }
});
