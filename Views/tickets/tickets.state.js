let pageTickets = [];
let assignableTickets = [];
let closableTickets = [];
let allComments = [];
let allAttachments = [];
let availableStatuses = [];
let availableTechnicians = [];
let notificationItems = [];
let notificationTotal = 0;
let expandedTicketId = null;
let openReplyTicketId = null;
let activeStatusFilter = 'todos';
let activeAssignmentFilter = 'todos';
let currentTicketPage = 1;
let searchTimer = null;
let currentTicketMeta = {
    page: 1,
    per_page: 5,
    total: 0,
    total_pages: 1,
    query: '',
    estado: null,
    asignacion: null,
};
const ticketsPerPage = 5;
const dom = {};

function byId(id) { return document.getElementById(id); }
function bodyData(name) { return document.body.dataset[name] ? String(document.body.dataset[name]) : ''; }
function getCsrfToken() { const node = byId('csrf-token'); return node ? node.value : ''; }
function currentRoleId() { return Number(document.body.dataset.roleId || 0); }
function isAdminUser() { return currentRoleId() === 1; }
function isTechUser() { return currentRoleId() === 2; }
function isEndUser() { return currentRoleId() === 3; }

async function fetchJSON(url) {
    const response = await fetch(url);
    const payload = await response.json();
    return payload && payload.status !== false && payload.data ? payload.data : [];
}

async function fetchPayload(url) {
    const response = await fetch(url);
    const payload = await response.json();
    return payload && payload.status !== false ? payload : { status: false, message: 'No se pudo cargar la información.', data: {} };
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

function ensureToastStack() {
    let stack = document.querySelector('.toast-stack');
    if (stack) { return stack; }
    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
    return stack;
}

function showToast(title, text, type) {
    const stack = ensureToastStack();
    const toast = document.createElement('div');
    toast.className = 'toast' + (type ? ' ' + type : '');
    toast.innerHTML = '<strong>' + title + '</strong><span>' + text + '</span>';
    stack.appendChild(toast);
    window.setTimeout(function () { toast.remove(); }, 3600);
}

function clearMessageLater(node, baseClass, delay) {
    if (!node) { return; }
    if (node._messageTimer) { clearTimeout(node._messageTimer); }
    node._messageTimer = window.setTimeout(function () {
        node.textContent = '';
        node.className = baseClass;
    }, delay);
}

function setMessage(node, text, type, title) {
    if (!node) { return; }
    node.textContent = text;
    node.className = type ? 'message ' + type : 'message';
    node.setAttribute('aria-hidden', text === '' ? 'true' : 'false');
    if (text !== '' && type) {
        clearMessageLater(node, 'message', 4000);
        showToast(title || (type === 'success' ? 'Operación completada' : 'Atención'), text, type);
    }
}

function setInlineMessage(node, text, type) {
    if (!node) { return; }
    node.textContent = text;
    node.className = type ? 'message inline-message ' + type : 'message inline-message';
    node.setAttribute('aria-hidden', text === '' ? 'true' : 'false');
    if (text !== '' && type) {
        clearMessageLater(node, 'message inline-message', 4000);
    }
}

function fillSelect(select, rows, labelResolver, valueKey) {
    if (!select) { return; }
    select.innerHTML = '';
    rows.forEach(function (row) {
        const option = document.createElement('option');
        option.value = row[valueKey];
        option.textContent = typeof labelResolver === 'function' ? labelResolver(row) : row[labelResolver];
        select.appendChild(option);
    });
}

function initialsFromName(name) {
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) { return 'NT'; }
    return (parts[0][0] || 'N').toUpperCase() + ((parts[1] && parts[1][0]) ? parts[1][0].toUpperCase() : '');
}

function normalizeRoleLabel(roleName) {
    const value = String(roleName || '').toLowerCase();
    if (value.includes('admin')) { return 'Admin'; }
    if (value.includes('tecn')) { return 'Técnico'; }
    return 'Usuario';
}

function roleClassName(roleName) {
    return normalizeRoleLabel(roleName).toLowerCase();
}

function formatTicketDate(value) {
    if (!value) { return 'Sin fecha'; }
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('es-DO');
}

function normalizeStatusName(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function statusClassSuffix(value) {
    return normalizeStatusName(value).replace(/\s+/g, '-');
}

function normalizedSearchQuery() {
    return dom.search ? dom.search.value.trim() : '';
}

function appendHighlightedText(node, text, query) {
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

function buildHighlightedTextElement(tagName, className, text, query) {
    const element = document.createElement(tagName);
    if (className) {
        element.className = className;
    }
    appendHighlightedText(element, text, query);
    return element;
}

function buildMeta(text) {
    const meta = document.createElement('p');
    meta.className = 'ticket-meta';
    meta.textContent = text;
    return meta;
}

function countCommentsForTicket(ticketId) {
    return allComments.filter(function (comment) {
        return String(comment.ticket_id) === String(ticketId);
    }).length;
}

function buildRoleBadge(roleName) {
    const badge = document.createElement('span');
    badge.className = 'role-badge role-' + roleClassName(roleName);
    badge.textContent = normalizeRoleLabel(roleName);
    return badge;
}

function createAvatar(name, photoUrl, className) {
    const avatar = document.createElement('div');
    avatar.className = className;
    if (photoUrl) {
        const image = document.createElement('img');
        image.src = photoUrl;
        image.alt = name;
        avatar.appendChild(image);
    } else {
        const fallback = document.createElement('span');
        fallback.textContent = initialsFromName(name);
        avatar.appendChild(fallback);
    }
    return avatar;
}

function setButtonContent(button, iconName, label) {
    if (!button) {
        return;
    }
    button.innerHTML = window.UiIcons ? window.UiIcons.buttonContent(iconName, label) : label;
}

function resolvePhoto(photoPath) {
    return photoPath ? '/NexoTI/' + String(photoPath).replace(/^\/+/, '') : '';
}

function resolveAttachment(path) {
    return path ? '/NexoTI/' + String(path).replace(/^\/+/, '') : '';
}

function attachmentsForTicket(ticketId) {
    return allAttachments.filter(function (attachment) {
        return String(attachment.ticket_id) === String(ticketId);
    });
}

function isImageAttachment(name) {
    return /\.(png|jpe?g|gif|webp|bmp|svg)$/i.test(String(name || ''));
}
