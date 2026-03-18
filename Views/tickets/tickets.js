let allTickets = [];
let allComments = [];

async function fetchJSON(url) {
    const res = await fetch(url);
    const payload = await res.json();
    if (!payload) { return []; }
    if (payload.status === false) { return []; }
    if (!payload.data) { return []; }
    return payload.data;
}

function fillSelect(select, rows, labelKey, valueKey) {
    if (!select) { return; }
    select.innerHTML = '';
    rows.forEach(function (row) {
        const opt = document.createElement('option');
        opt.value = row[valueKey];
        opt.textContent = typeof labelKey === 'function' ? labelKey(row) : row[labelKey];
        select.appendChild(opt);
    });
}

function setMessage(id, text, type) {
    const node = document.getElementById(id);
    if (!node) { return; }
    node.textContent = text;
    node.className = type ? 'message ' + type : 'message';
}

function buildMeta(text) {
    const meta = document.createElement('p');
    meta.className = 'ticket-meta';
    meta.textContent = text;
    return meta;
}

function ensureCommentSection() {
    if (document.getElementById('comment-form')) { return; }
    const listNode = document.getElementById('tickets-list');
    if (!listNode) { return; }
    const listCard = listNode.closest('.card');
    if (!listCard) { return; }
    const parent = listCard.parentNode;
    if (!parent) { return; }
    const section = document.createElement('section');
    section.className = 'card';
    const title = document.createElement('h2');
    title.textContent = 'Seguimiento del ticket';
    const form = document.createElement('form');
    form.id = 'comment-form';
    const grid = document.createElement('div');
    grid.className = 'grid';
    const ticketLabel = document.createElement('label');
    ticketLabel.textContent = 'Ticket';
    const ticketSelect = document.createElement('select');
    ticketSelect.name = 'ticket_id';
    ticketSelect.id = 'comment_ticket_id';
    ticketSelect.required = true;
    ticketLabel.appendChild(ticketSelect);
    const textLabel = document.createElement('label');
    textLabel.textContent = 'Comentario';
    const textarea = document.createElement('textarea');
    textarea.name = 'comentario';
    textarea.id = 'comentario';
    textarea.rows = 3;
    textarea.placeholder = 'Escribe una actualizacion del ticket';
    textarea.required = true;
    textLabel.appendChild(textarea);
    grid.appendChild(ticketLabel);
    grid.appendChild(textLabel);
    const actions = document.createElement('div');
    actions.className = 'actions';
    const button = document.createElement('button');
    button.type = 'submit';
    button.className = 'btn primary';
    button.textContent = 'Publicar comentario';
    const message = document.createElement('span');
    message.id = 'comment-message';
    message.className = 'message';
    actions.appendChild(button);
    actions.appendChild(message);
    const list = document.createElement('div');
    list.id = 'comments-list';
    list.className = 'list';
    form.appendChild(grid);
    form.appendChild(actions);
    section.appendChild(title);
    section.appendChild(form);
    section.appendChild(list);
    parent.insertBefore(section, listCard.nextSibling);
}

function renderTickets(container, tickets) {
    if (!container) { return; }
    container.innerHTML = '';
    if (tickets.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty';
        empty.textContent = 'No hay tickets registrados.';
        container.appendChild(empty);
        return;
    }
    const list = document.createElement('div');
    list.className = 'ticket-list';
    tickets.forEach(function (ticket) {
        const item = document.createElement('article');
        item.className = 'ticket-item';
        const head = document.createElement('div');
        head.className = 'ticket-head';
        const code = document.createElement('strong');
        code.textContent = ticket.codigo;
        const status = document.createElement('span');
        status.className = 'status';
        status.textContent = ticket.estado_nombre ? ticket.estado_nombre : 'Sin estado';
        head.appendChild(code);
        head.appendChild(status);
        const title = document.createElement('h3');
        title.textContent = ticket.titulo;
        const metaGrid = document.createElement('div');
        metaGrid.className = 'meta-grid';
        metaGrid.appendChild(buildMeta('Usuario: ' + (ticket.usuario_nombre ? ticket.usuario_nombre : 'Sin usuario')));
        metaGrid.appendChild(buildMeta('Tecnico: ' + (ticket.tecnico_nombre ? ticket.tecnico_nombre : 'Sin asignar')));
        metaGrid.appendChild(buildMeta('Categoria: ' + (ticket.categoria_nombre ? ticket.categoria_nombre : 'N/A')));
        metaGrid.appendChild(buildMeta('Prioridad: ' + (ticket.prioridad_nombre ? ticket.prioridad_nombre : 'N/A')));
        const description = document.createElement('p');
        description.className = 'ticket-description';
        description.textContent = ticket.descripcion;
        item.appendChild(head);
        item.appendChild(title);
        item.appendChild(metaGrid);
        item.appendChild(description);

        list.appendChild(item);
    });
    container.appendChild(list);
}
function fillCommentSelect(tickets) {
    const select = document.getElementById('comment_ticket_id');
    if (!select) { return; }
    fillSelect(select, tickets, function (row) { return row.codigo + ' - ' + row.titulo; }, 'id');
}

function renderComments() {
    const container = document.getElementById('comments-list');
    const select = document.getElementById('comment_ticket_id');
    if (!container) { return; }
    container.innerHTML = '';
    if (!select) { return; }
    if (!select.value) {
        const empty = document.createElement('p');
        empty.className = 'empty';
        empty.textContent = 'Selecciona un ticket para ver el seguimiento.';
        container.appendChild(empty);
        return;
    }
    const rows = allComments.filter(function (row) { return String(row.ticket_id) === String(select.value); });
    if (rows.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty';
        empty.textContent = 'Aun no hay comentarios para este ticket.';
        container.appendChild(empty);
        return;
    }
    rows.forEach(function (row) {
        const item = document.createElement('article');
        item.className = 'ticket-item';
        const head = document.createElement('div');
        head.className = 'ticket-head';
        const author = document.createElement('strong');
        author.textContent = row.usuario_nombre ? row.usuario_nombre : 'Usuario';
        const date = document.createElement('span');
        date.className = 'status';
        date.textContent = row.fecha;
        head.appendChild(author);
        head.appendChild(date);
        const meta = document.createElement('p');
        meta.className = 'ticket-meta';
        meta.textContent = (row.ticket_codigo ? row.ticket_codigo : '') + ' - ' + (row.ticket_titulo ? row.ticket_titulo : '');
        const text = document.createElement('p');
        text.className = 'ticket-description';
        text.textContent = row.comentario;
        item.appendChild(head);
        item.appendChild(meta);
        item.appendChild(text);
        container.appendChild(item);
    });
}

function refreshTicketSelects(tickets) {
    const assignSelect = document.getElementById('assign_ticket_id');
    const statusSelect = document.getElementById('status_ticket_id');
    const closeSelect = document.getElementById('close_ticket_id');
    if (assignSelect) { fillSelect(assignSelect, tickets, function (row) { return row.codigo + ' - ' + row.titulo; }, 'id'); }
    if (statusSelect) { fillSelect(statusSelect, tickets, function (row) { return row.codigo + ' - ' + row.titulo; }, 'id'); }
    if (closeSelect) {
        const openTickets = tickets.filter(function (ticket) { return String(ticket.estado_nombre).toLowerCase() !== 'cerrado'; });
        fillSelect(closeSelect, openTickets, function (row) { return row.codigo + ' - ' + row.titulo; }, 'id');
    }
    fillCommentSelect(tickets);
    renderComments();
}

function applySearch() {
    const input = document.getElementById('ticket-search');
    let rows = allTickets.slice();
    if (input) {
        const query = input.value.trim().toLowerCase();
        if (query !== '') {
            rows = allTickets.filter(function (ticket) {
                const source = [ticket.codigo, ticket.titulo, ticket.descripcion, ticket.usuario_nombre, ticket.tecnico_nombre, ticket.estado_nombre, ticket.categoria_nombre, ticket.prioridad_nombre].join(' ').toLowerCase();
                return source.indexOf(query) !== -1;
            });
        }
    }
    renderTickets(document.getElementById('tickets-list'), rows);
    refreshTicketSelects(rows);
}


async function loadTickets() {
    allTickets = await fetchJSON('api.php?c=ticket&m=list');
    applySearch();
}

async function loadComments() {
    allComments = await fetchJSON('api.php?c=comentario&m=list');
    renderComments();
}

async function loadCombos() {
    const categorias = await fetchJSON('api.php?c=categoria&m=list');
    const prioridades = await fetchJSON('api.php?c=prioridad&m=list');
    const estados = await fetchJSON('api.php?c=estado&m=list');
    fillSelect(document.getElementById('categoria_id'), categorias, 'nombre', 'id');
    fillSelect(document.getElementById('prioridad_id'), prioridades, 'nombre', 'id');
    fillSelect(document.getElementById('estado_id'), estados, 'nombre', 'id');
    fillSelect(document.getElementById('assign_estado_id'), estados, 'nombre', 'id');
    fillSelect(document.getElementById('status_estado_id'), estados, 'nombre', 'id');
}

async function loadTecnicos() {
    const select = document.getElementById('assign_tecnico_id');
    if (!select) { return; }
    const tecnicos = await fetchJSON('api.php?c=usuario&m=tecnicos');
    select.innerHTML = '';
    const emptyOpt = document.createElement('option');
    emptyOpt.value = '';
    emptyOpt.textContent = 'Sin asignar';
    select.appendChild(emptyOpt);
    tecnicos.forEach(function (row) {
        const opt = document.createElement('option');
        opt.value = row.id;
        opt.textContent = row.nombre + ' (' + row.email + ')';
        select.appendChild(opt);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    ensureCommentSection();
    loadCombos();
    loadTecnicos();
    loadTickets();
    loadComments();
    const search = document.getElementById('ticket-search');
    if (search) { search.addEventListener('input', applySearch); }
    const refresh = document.getElementById('refresh-btn');
    if (refresh) { refresh.addEventListener('click', function () { loadTickets(); loadComments(); }); }
    const avatarBtn = document.getElementById('avatar-btn');
    const avatarMenu = document.getElementById('avatar-menu');
    if (avatarBtn) {
        avatarBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            if (avatarMenu) { avatarMenu.classList.toggle('is-open'); }
        });
    }
    document.addEventListener('click', function (event) {
        if (!avatarMenu) { return; }
        if (avatarMenu.contains(event.target)) { return; }
        if (avatarBtn) { if (avatarBtn.contains(event.target)) { return; } }
        avatarMenu.classList.remove('is-open');
    });
    const closeForm = document.getElementById('close-form');
    const stateSelect = document.getElementById('estado_id');
    if (closeForm) {
        if (stateSelect) {
            stateSelect.required = false;
            stateSelect.disabled = true;
            const stateLabel = stateSelect.closest('label');
            if (stateLabel) { stateLabel.style.display = 'none'; }
        }
    }
    const commentSelect = document.getElementById('comment_ticket_id');
    if (commentSelect) { commentSelect.addEventListener('change', renderComments); }
    const ticketForm = document.getElementById('ticket-form');
    if (ticketForm) {
        ticketForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const formData = new FormData(ticketForm);
            if (!formData.get('codigo')) { formData.set('codigo', 'TCK-' + Date.now()); }
            const res = await fetch('api.php?c=ticket&m=create', { method: 'POST', body: formData });
            const data = await res.json();
            setMessage('form-message', data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
            if (data.status) { ticketForm.reset(); loadTickets(); loadComments(); }
        });
    }
    const assignForm = document.getElementById('assign-form');
    if (assignForm) {
        assignForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const payload = { ticket_id: document.getElementById('assign_ticket_id').value, tecnico_id: document.getElementById('assign_tecnico_id').value, estado_id: document.getElementById('assign_estado_id').value };
            const res = await fetch('api.php?c=ticket&m=assign', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const data = await res.json();
            setMessage('assign-message', data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
            if (data.status) { loadTickets(); loadComments(); }
        });
    }
    const statusForm = document.getElementById('status-form');
    if (statusForm) {
        statusForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const payload = { ticket_id: document.getElementById('status_ticket_id').value, estado_id: document.getElementById('status_estado_id').value };
            const res = await fetch('api.php?c=ticket&m=updateStatus', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const data = await res.json();
            setMessage('status-message', data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
            if (data.status) { loadTickets(); loadComments(); }
        });
    }
    if (closeForm) {
        closeForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const payload = { ticket_id: document.getElementById('close_ticket_id').value };
            const res = await fetch('api.php?c=ticket&m=closeTicket', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const data = await res.json();
            setMessage('close-message', data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
            if (data.status) { loadTickets(); loadComments(); }
        });
    }
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const payload = { ticket_id: document.getElementById('comment_ticket_id').value, comentario: document.getElementById('comentario').value };
            const res = await fetch('api.php?c=comentario&m=create', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const data = await res.json();
            setMessage('comment-message', data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
            if (data.status) {
                commentForm.reset();
                fillCommentSelect(allTickets);
                loadComments();
            }
        });
    }
});
