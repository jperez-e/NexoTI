let editingId = 0;
let prioridadesCache = [];

function getNode(id) {
    return document.getElementById(id);
}

function setButtonContent(button, iconName, label) {
    if (!button) {
        return;
    }
    button.innerHTML = window.UiIcons ? window.UiIcons.buttonContent(iconName, label) : label;
}

async function fetchJSON(url, options = {}) {
    const response = await fetch(url, options);
    return response.json();
}

function getCsrfToken() {
    const node = getNode('csrf-token');
    return node ? node.value : '';
}

function setButtonLoading(button, loading, loadingText) {
    if (!button) {
        return;
    }

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
    if (stack) {
        return stack;
    }

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
    window.setTimeout(function () {
        toast.remove();
    }, 3600);
}

function clearMessageLater(node, delay) {
    if (!node) {
        return;
    }

    if (node._messageTimer) {
        clearTimeout(node._messageTimer);
    }

    node._messageTimer = window.setTimeout(function () {
        node.textContent = '';
        node.className = 'message';
    }, delay);
}

function showMessage(text, type) {
    const node = getNode('form-message');
    if (!node) {
        return;
    }

    node.textContent = text;
    node.className = type ? 'message ' + type : 'message';
    if (text !== '' && type) {
        clearMessageLater(node, 4000);
        showToast(type === 'success' ? 'Operacion completada' : 'Atencion', text, type);
    }
}

function ensureFormTools() {
    const form = getNode('prioridad-form');
    const actions = form.querySelector('.actions');
    const title = form.closest('.card').querySelector('h2');
    title.id = 'form-title';

    if (!getNode('prioridad-id')) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'prioridad-id';
        hidden.name = 'id';
        form.prepend(hidden);
    }

    if (!getNode('cancel-btn')) {
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.id = 'cancel-btn';
        cancel.className = 'btn ghost hidden';
        setButtonContent(cancel, 'close', 'Cancelar edicion');
        actions.insertBefore(cancel, getNode('form-message'));
    }
}

function resetForm() {
    editingId = 0;
    getNode('prioridad-form').reset();
    getNode('prioridad-id').value = '';
    getNode('form-title').textContent = 'Nueva prioridad';
    setButtonContent(getNode('submit-btn'), 'save', 'Guardar prioridad');
    getNode('cancel-btn').classList.add('hidden');
    showMessage('', '');
}

function startEdit(row) {
    editingId = Number(row.id);
    getNode('prioridad-id').value = row.id;
    getNode('nombre').value = row.nombre;
    getNode('nivel').value = row.nivel;
    getNode('form-title').textContent = 'Editar prioridad';
    setButtonContent(getNode('submit-btn'), 'save', 'Actualizar prioridad');
    getNode('cancel-btn').classList.remove('hidden');
    showMessage('', '');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderPrioridades(list) {
    const container = getNode('prioridades-list');
    container.innerHTML = '';

    if (list.length === 0) {
        container.innerHTML = '<p class="message">No hay prioridades registradas.</p>';
        return;
    }

    list.forEach(function (row) {
        const item = document.createElement('div');
        item.className = 'item';

        const title = document.createElement('h3');
        title.textContent = row.nombre + ' (Nivel ' + row.nivel + ')';

        const actions = document.createElement('div');
        actions.className = 'item-actions';

        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'btn ghost';
        setButtonContent(editBtn, 'edit', 'Editar');
        editBtn.addEventListener('click', function () {
            startEdit(row);
        });

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn danger';
        setButtonContent(deleteBtn, 'delete', 'Eliminar');
        deleteBtn.addEventListener('click', async function () {
            const ok = window.confirm('Se eliminara la prioridad ' + row.nombre + '. Deseas continuar?');
            if (!ok) {
                return;
            }

            setButtonLoading(deleteBtn, true, 'Eliminando...');
            try {
                const data = await fetchJSON('api.php?c=prioridad&m=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken(),
                    },
                    body: JSON.stringify({ id: row.id }),
                });
                showMessage(data.message ? data.message : '', data.status ? 'success' : 'error');
                if (data.status) {
                    if (editingId === Number(row.id)) {
                        resetForm();
                    }
                    await loadPrioridades();
                }
            } finally {
                setButtonLoading(deleteBtn, false);
            }
        });

        actions.appendChild(editBtn);
        actions.appendChild(deleteBtn);
        item.appendChild(title);
        item.appendChild(actions);
        container.appendChild(item);
    });
}

async function loadPrioridades() {
    const data = await fetchJSON('api.php?c=prioridad&m=list');
    prioridadesCache = data.data ? data.data : [];
    renderPrioridades(prioridadesCache);
}

document.addEventListener('DOMContentLoaded', async function () {
    ensureFormTools();
    resetForm();
    await loadPrioridades();

    getNode('refresh-btn').addEventListener('click', async function () {
        const button = this;
        setButtonLoading(button, true, 'Actualizando...');
        try {
            await loadPrioridades();
        } finally {
            setButtonLoading(button, false);
        }
    });

    getNode('cancel-btn').addEventListener('click', resetForm);

    getNode('prioridad-form').addEventListener('submit', async function (event) {
        event.preventDefault();

        const form = event.target;
        const submitButton = getNode('submit-btn');
        const formData = new FormData(form);
        formData.set('_token', getCsrfToken());

        const url = editingId > 0 ? 'api.php?c=prioridad&m=update' : 'api.php?c=prioridad&m=create';
        if (editingId > 0) {
            formData.set('id', String(editingId));
        }

        setButtonLoading(submitButton, true, editingId > 0 ? 'Actualizando...' : 'Guardando...');
        try {
            const data = await fetchJSON(url, {
                method: 'POST',
                body: formData,
            });
            showMessage(data.message ? data.message : '', data.status ? 'success' : 'error');
            if (data.status) {
                resetForm();
                await loadPrioridades();
            }
        } finally {
            setButtonLoading(submitButton, false);
        }
    });
});
