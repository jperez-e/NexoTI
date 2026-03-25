let allTickets = [];
let allComments = [];
let availableStatuses = [];

const dom = {};

function byId(id) {
    return document.getElementById(id);
}

function getCsrfToken() {
    const tokenNode = byId('csrf-token');
    return tokenNode ? tokenNode.value : '';
}

function currentRoleId() {
    return Number(document.body.dataset.roleId || 0);
}

function isTechUser() {
    return currentRoleId() === 2;
}

function isEndUser() {
    return currentRoleId() === 3;
}

async function fetchJSON(url) {
    const response = await fetch(url);
    const payload = await response.json();
    return payload && payload.status !== false && payload.data ? payload.data : [];
}

async function postJSON(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken(),
        },
        body: JSON.stringify(payload),
    });
    return response.json();
}

function setButtonLoading(button, loading, loadingText) {
    if (!button) {
        return;
    }

    if (loading) {
        button.dataset.label = button.textContent;
        button.textContent = loadingText;
        button.disabled = true;
        button.classList.add('is-loading');
        return;
    }

    button.textContent = button.dataset.label ? button.dataset.label : button.textContent;
    button.disabled = false;
    button.classList.remove('is-loading');
}

function fillSelect(select, rows, labelResolver, valueKey) {
    if (!select) {
        return;
    }

    select.innerHTML = '';
    rows.forEach(function (row) {
        const option = document.createElement('option');
        option.value = row[valueKey];
        option.textContent = typeof labelResolver === 'function' ? labelResolver(row) : row[labelResolver];
        select.appendChild(option);
    });
}

function setMessage(node, text, type) {
    if (!node) {
        return;
    }

    node.textContent = text;
    node.className = type ? 'message ' + type : 'message';
}

function buildMeta(text) {
    const meta = document.createElement('p');
    meta.className = 'ticket-meta';
    meta.textContent = text;
    return meta;
}

function formatTicketDate(value) {
    if (!value) {
        return 'Sin fecha';
    }

    const safeValue = String(value).replace(' ', 'T');
    const date = new Date(safeValue);
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('es-DO');
}

function buildThreadEntry(title, dateText, bodyText, extraClass) {
    const item = document.createElement('div');
    item.className = 'thread-entry' + (extraClass ? ' ' + extraClass : '');

    const head = document.createElement('div');
    head.className = 'thread-head';

    const strong = document.createElement('strong');
    strong.textContent = title;

    const date = document.createElement('span');
    date.className = 'thread-date';
    date.textContent = dateText;

    const text = document.createElement('p');
    text.className = 'thread-text';
    text.textContent = bodyText;

    head.appendChild(strong);
    head.appendChild(date);
    item.appendChild(head);
    item.appendChild(text);

    return item;
}

function buildTicketThread(ticket) {
    const wrapper = document.createElement('div');
    wrapper.className = 'ticket-thread';

    wrapper.appendChild(
        buildThreadEntry(
            'Solicitud inicial',
            formatTicketDate(ticket.fecha_creacion),
            ticket.descripcion,
            'is-initial'
        )
    );

    allComments
        .filter(function (comment) {
            return String(comment.ticket_id) === String(ticket.id);
        })
        .slice()
        .reverse()
        .forEach(function (comment) {
            wrapper.appendChild(
                buildThreadEntry(
                    comment.usuario_nombre ? comment.usuario_nombre : 'Usuario',
                    formatTicketDate(comment.fecha),
                    comment.comentario,
                    'is-comment'
                )
            );
        });

    return wrapper;
}

function setInlineMessage(node, text, type) {
    if (!node) {
        return;
    }

    node.textContent = text;
    node.className = type ? 'message inline-message ' + type : 'message inline-message';
}

async function refreshTicketsView() {
    await Promise.all([loadComments(), loadTickets()]);
}

async function submitInlineReply(ticket, textarea, stateSelect, messageNode, replyBox, toggleButton, sendButton) {
    const comentario = textarea.value.trim();
    const selectedStateId = stateSelect ? String(stateSelect.value) : '';
    const currentStateId = ticket.estado_id ? String(ticket.estado_id) : '';
    const mustUpdateState = isTechUser() && selectedStateId !== '' && selectedStateId !== currentStateId;

    if (comentario === '' && !mustUpdateState) {
        setInlineMessage(messageNode, 'Escribe una respuesta o selecciona un nuevo estado.', 'error');
        return;
    }

    setButtonLoading(sendButton, true, 'Enviando...');
    try {
        let statusResponse = { status: true };
        if (mustUpdateState) {
            statusResponse = await postJSON('api.php?c=ticket&m=updateStatus', {
                ticket_id: ticket.id,
                estado_id: selectedStateId,
            });

            if (!statusResponse.status) {
                setInlineMessage(
                    messageNode,
                    statusResponse.message ? statusResponse.message : 'No se pudo actualizar el estado.',
                    'error'
                );
                return;
            }
        }

        let commentResponse = { status: true };
        if (comentario !== '') {
            commentResponse = await postJSON('api.php?c=comentario&m=create', {
                ticket_id: ticket.id,
                comentario: comentario,
            });

            if (!commentResponse.status) {
                setInlineMessage(
                    messageNode,
                    commentResponse.message ? commentResponse.message : 'No se pudo enviar la respuesta.',
                    'error'
                );
                return;
            }
        }

        let successMessage = 'Proceso completado.';
        if (mustUpdateState && comentario !== '') {
            successMessage = 'Respuesta enviada y estado actualizado.';
        } else if (mustUpdateState) {
            successMessage = statusResponse.message ? statusResponse.message : 'Estado actualizado.';
        } else if (comentario !== '') {
            successMessage = commentResponse.message ? commentResponse.message : 'Respuesta enviada.';
        }

        setInlineMessage(messageNode, successMessage, 'success');

        textarea.value = '';
        replyBox.classList.remove('is-open');
        toggleButton.textContent = 'Responder';
        await refreshTicketsView();
    } finally {
        setButtonLoading(sendButton, false);
    }
}

async function closeInlineTicket(ticketId, messageNode, closeButton) {
    setButtonLoading(closeButton, true, 'Cerrando...');
    try {
        const data = await postJSON('api.php?c=ticket&m=closeTicket', { ticket_id: ticketId });

        setInlineMessage(
            messageNode,
            data.message ? data.message : 'Proceso completado.',
            data.status ? 'success' : 'error'
        );

        if (data.status) {
            await refreshTicketsView();
        }
    } finally {
        setButtonLoading(closeButton, false);
    }
}

function buildInlineReply(ticket, messageNode, toggleButton) {
    const box = document.createElement('div');
    box.className = 'inline-reply';

    let stateSelect = null;
    if (isTechUser()) {
        const stateLabel = document.createElement('label');
        stateLabel.textContent = 'Actualizar estado';

        stateSelect = document.createElement('select');
        availableStatuses
            .filter(function (status) {
                return String(status.nombre).toLowerCase() !== 'cerrado';
            })
            .forEach(function (status) {
                const option = document.createElement('option');
                option.value = status.id;
                option.textContent = status.nombre;
                if (String(status.id) === String(ticket.estado_id)) {
                    option.selected = true;
                }
                stateSelect.appendChild(option);
            });

        stateLabel.appendChild(stateSelect);
        box.appendChild(stateLabel);
    }

    const textarea = document.createElement('textarea');
    textarea.rows = 3;
    textarea.placeholder = 'Escribe tu respuesta o seguimiento del ticket';

    const actions = document.createElement('div');
    actions.className = 'inline-reply-actions';

    const sendButton = document.createElement('button');
    sendButton.type = 'button';
    sendButton.className = 'btn primary';
    sendButton.textContent = 'Enviar respuesta';
    sendButton.addEventListener('click', function () {
        submitInlineReply(ticket, textarea, stateSelect, messageNode, box, toggleButton, sendButton);
    });

    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'btn ghost';
    cancelButton.textContent = 'Cancelar';
    cancelButton.addEventListener('click', function () {
        textarea.value = '';
        box.classList.remove('is-open');
        toggleButton.textContent = 'Responder';
        setInlineMessage(messageNode, '', '');
    });

    actions.appendChild(sendButton);
    actions.appendChild(cancelButton);
    box.appendChild(textarea);
    box.appendChild(actions);

    return { box: box, textarea: textarea };
}

function buildInlineActions(ticket) {
    const wrapper = document.createElement('div');
    wrapper.className = 'inline-tools';

    const actions = document.createElement('div');
    actions.className = 'inline-actions';

    const messageNode = document.createElement('span');
    messageNode.className = 'message inline-message';

    const replyButton = document.createElement('button');
    replyButton.type = 'button';
    replyButton.className = 'btn ghost';
    replyButton.textContent = 'Responder';

    const reply = buildInlineReply(ticket, messageNode, replyButton);
    replyButton.addEventListener('click', function () {
        const isOpen = reply.box.classList.toggle('is-open');
        replyButton.textContent = isOpen ? 'Ocultar respuesta' : 'Responder';
        if (isOpen) {
            reply.textarea.focus();
        }
    });

    actions.appendChild(replyButton);

    if (isEndUser() && String(ticket.estado_nombre).toLowerCase() === 'resuelto') {
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn primary';
        closeButton.textContent = 'Cerrar ticket';
        closeButton.addEventListener('click', function () {
            closeInlineTicket(ticket.id, messageNode, closeButton);
        });
        actions.appendChild(closeButton);
    }

    wrapper.appendChild(actions);
    wrapper.appendChild(reply.box);
    wrapper.appendChild(messageNode);

    return wrapper;
}

function renderTickets(tickets) {
    if (!dom.ticketsList) {
        return;
    }

    dom.ticketsList.innerHTML = '';
    if (tickets.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'empty';
        empty.textContent = 'No hay tickets registrados.';
        dom.ticketsList.appendChild(empty);
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
        metaGrid.appendChild(buildMeta('Creado: ' + formatTicketDate(ticket.fecha_creacion)));

        item.appendChild(head);
        item.appendChild(title);
        item.appendChild(metaGrid);
        item.appendChild(buildTicketThread(ticket));
        item.appendChild(buildInlineActions(ticket));

        list.appendChild(item);
    });

    dom.ticketsList.appendChild(list);
}

function filterTicketsBySearch() {
    const query = dom.search ? dom.search.value.trim().toLowerCase() : '';
    if (query === '') {
        return allTickets.slice();
    }

    return allTickets.filter(function (ticket) {
        const source = [
            ticket.codigo,
            ticket.titulo,
            ticket.descripcion,
            ticket.usuario_nombre,
            ticket.tecnico_nombre,
            ticket.estado_nombre,
            ticket.categoria_nombre,
            ticket.prioridad_nombre,
        ]
            .join(' ')
            .toLowerCase();

        return source.includes(query);
    });
}

function syncAdminAssignState() {
    if (!dom.assignTicket || !dom.assignState) {
        return;
    }

    const selectedTicket = allTickets.find(function (ticket) {
        return String(ticket.id) === String(dom.assignTicket.value);
    });

    if (selectedTicket && selectedTicket.estado_id) {
        dom.assignState.value = String(selectedTicket.estado_id);
    }
}

function refreshTicketSelects(tickets) {
    if (dom.assignTicket) {
        fillSelect(
            dom.assignTicket,
            tickets.filter(function (ticket) {
                return String(ticket.estado_nombre).toLowerCase() === 'abierto';
            }),
            function (ticket) {
                return ticket.codigo + ' - ' + ticket.titulo;
            },
            'id'
        );
        syncAdminAssignState();
    }

    if (dom.statusTicket) {
        fillSelect(dom.statusTicket, tickets, function (ticket) {
            return ticket.codigo + ' - ' + ticket.titulo;
        }, 'id');
    }

    if (dom.closeTicket) {
        fillSelect(
            dom.closeTicket,
            tickets.filter(function (ticket) {
                return String(ticket.estado_nombre).toLowerCase() === 'resuelto';
            }),
            function (ticket) {
                return ticket.codigo + ' - ' + ticket.titulo;
            },
            'id'
        );
    }
}

function applySearch() {
    const filteredTickets = filterTicketsBySearch();
    renderTickets(filteredTickets);
    refreshTicketSelects(filteredTickets);
}

async function loadTickets() {
    allTickets = await fetchJSON('api.php?c=ticket&m=list');
    applySearch();
}

async function loadComments() {
    allComments = await fetchJSON('api.php?c=comentario&m=list');
}

async function loadCombos() {
    const [categorias, prioridades, estados] = await Promise.all([
        fetchJSON('api.php?c=categoria&m=list'),
        fetchJSON('api.php?c=prioridad&m=list'),
        fetchJSON('api.php?c=estado&m=list'),
    ]);

    availableStatuses = estados.slice();

    fillSelect(dom.categoria, categorias, 'nombre', 'id');
    fillSelect(dom.prioridad, prioridades, 'nombre', 'id');
    fillSelect(dom.estado, estados, 'nombre', 'id');
    fillSelect(dom.assignState, estados, 'nombre', 'id');
    fillSelect(dom.statusState, estados, 'nombre', 'id');

    if (dom.statusState) {
        Array.from(dom.statusState.options).forEach(function (option) {
            if (String(option.textContent).toLowerCase() === 'cerrado') {
                option.remove();
            }
        });
    }
}

async function loadTecnicos() {
    if (!dom.assignTech) {
        return;
    }

    const tecnicos = await fetchJSON('api.php?c=usuario&m=tecnicos');
    dom.assignTech.innerHTML = '';

    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = 'Sin asignar';
    dom.assignTech.appendChild(emptyOption);

    tecnicos.forEach(function (tecnico) {
        const option = document.createElement('option');
        option.value = tecnico.id;
        option.textContent = tecnico.nombre + ' (' + tecnico.email + ')';
        dom.assignTech.appendChild(option);
    });
}

function setupAvatarMenu() {
    if (!dom.avatarButton || !dom.avatarMenu) {
        return;
    }

    dom.avatarButton.addEventListener('click', function (event) {
        event.stopPropagation();
        dom.avatarMenu.classList.toggle('is-open');
    });

    document.addEventListener('click', function (event) {
        if (dom.avatarMenu.contains(event.target) || dom.avatarButton.contains(event.target)) {
            return;
        }

        dom.avatarMenu.classList.remove('is-open');
    });
}

function setupCreateForm() {
    if (!dom.ticketForm) {
        return;
    }

    dom.ticketForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = new FormData(dom.ticketForm);
        const submitButton = dom.ticketForm.querySelector('button[type="submit"]');
        formData.set('_token', getCsrfToken());
        if (!formData.get('codigo')) {
            formData.set('codigo', 'TCK-' + Date.now());
        }

        setButtonLoading(submitButton, true, 'Creando...');
        try {
            const response = await fetch('api.php?c=ticket&m=create', {
                method: 'POST',
                body: formData,
            });
            const data = await response.json();

            setMessage(dom.formMessage, data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
            if (data.status) {
                dom.ticketForm.reset();
                await refreshTicketsView();
            }
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}

function setupAssignForm() {
    if (!dom.assignForm) {
        return;
    }

    if (dom.assignState) {
        dom.assignState.required = false;
        const label = dom.assignState.closest('label');
        if (label) {
            label.style.display = 'none';
        }
    }

    if (dom.assignTicket) {
        dom.assignTicket.addEventListener('change', syncAdminAssignState);
    }

    dom.assignForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const submitButton = dom.assignForm.querySelector('button[type="submit"]');

        setButtonLoading(submitButton, true, 'Asignando...');
        try {
            const data = await postJSON('api.php?c=ticket&m=assign', {
                ticket_id: dom.assignTicket.value,
                tecnico_id: dom.assignTech.value,
                estado_id: dom.assignState.value,
            });

            setMessage(dom.assignMessage, data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
            if (data.status) {
                await refreshTicketsView();
            }
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
}

function setupStatusForm() {
    if (!dom.statusForm) {
        return;
    }

    const statusCard = dom.statusForm.closest('.card');
    if (statusCard) {
        statusCard.style.display = 'none';
    }
}

function setupCloseForm() {
    if (!dom.closeForm) {
        return;
    }

    if (dom.estado) {
        dom.estado.required = false;
        dom.estado.disabled = true;
        const label = dom.estado.closest('label');
        if (label) {
            label.style.display = 'none';
        }
    }

    const closeCard = dom.closeForm.closest('.card');
    if (closeCard) {
        closeCard.style.display = 'none';
    }

    dom.closeForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const data = await postJSON('api.php?c=ticket&m=closeTicket', {
            ticket_id: dom.closeTicket.value,
        });

        setMessage(dom.closeMessage, data.message ? data.message : 'Proceso completado.', data.status ? 'success' : 'error');
        if (data.status) {
            await refreshTicketsView();
        }
    });
}

function cacheDom() {
    dom.search = byId('ticket-search');
    dom.refreshButton = byId('refresh-btn');
    dom.avatarButton = byId('avatar-btn');
    dom.avatarMenu = byId('avatar-menu');
    dom.ticketsList = byId('tickets-list');

    dom.ticketForm = byId('ticket-form');
    dom.formMessage = byId('form-message');
    dom.categoria = byId('categoria_id');
    dom.prioridad = byId('prioridad_id');
    dom.estado = byId('estado_id');

    dom.assignForm = byId('assign-form');
    dom.assignTicket = byId('assign_ticket_id');
    dom.assignTech = byId('assign_tecnico_id');
    dom.assignState = byId('assign_estado_id');
    dom.assignMessage = byId('assign-message');

    dom.statusForm = byId('status-form');
    dom.statusTicket = byId('status_ticket_id');
    dom.statusState = byId('status_estado_id');
    dom.statusMessage = byId('status-message');

    dom.closeForm = byId('close-form');
    dom.closeTicket = byId('close_ticket_id');
    dom.closeMessage = byId('close-message');
}

document.addEventListener('DOMContentLoaded', async function () {
    cacheDom();
    setupAvatarMenu();
    setupCreateForm();
    setupAssignForm();
    setupStatusForm();
    setupCloseForm();

    if (dom.search) {
        dom.search.addEventListener('input', applySearch);
    }

    if (dom.refreshButton) {
        dom.refreshButton.addEventListener('click', async function () {
            const button = this;
            setButtonLoading(button, true, 'Actualizando...');
            try {
                await refreshTicketsView();
            } finally {
                setButtonLoading(button, false);
            }
        });
    }

    await loadCombos();
    await loadTecnicos();
    await loadComments();
    await loadTickets();
});


