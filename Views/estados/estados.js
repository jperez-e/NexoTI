let idEdicion = 0;
let cacheEstados = [];

function obtenerNodo(id) {
    return document.getElementById(id);
}

function establecerContenidoBoton(button, iconName, label) {
    if (!button) {
        return;
    }
    button.innerHTML = window.UiIcons ? window.UiIcons.buttonContent(iconName, label) : label;
}

async function obtenerJson(url, options = {}) {
    const response = await fetch(url, options);
    return response.json();
}

function obtenerTokenCsrf() {
    const node = obtenerNodo('csrf-token');
    return node ? node.value : '';
}

function establecerBotonCargando(button, loading, loadingText) {
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

function asegurarPilaToasts() {
    let stack = document.querySelector('.toast-stack');
    if (stack) {
        return stack;
    }

    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
    return stack;
}

function mostrarToast(title, text, type) {
    const stack = asegurarPilaToasts();
    const toast = document.createElement('div');
    toast.className = 'toast' + (type ? ' ' + type : '');
    toast.innerHTML = '<strong>' + title + '</strong><span>' + text + '</span>';
    stack.appendChild(toast);
    window.setTimeout(function () {
        toast.remove();
    }, 3600);
}

function limpiarMensajeLuego(node, delay) {
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

function mostrarMensaje(text, type, allowToast = true) {
    const node = obtenerNodo('form-message');
    if (!node) {
        return;
    }

    node.textContent = text;
    node.className = type ? 'message ' + type : 'message';
    if (allowToast && text !== '' && type) {
        limpiarMensajeLuego(node, 4000);
        mostrarToast(type === 'success' ? 'Operación completada' : 'Atención', text, type);
    }
}

function asegurarHerramientasFormulario() {
    const form = obtenerNodo('estado-form');
    const actions = form.querySelector('.actions');
    const title = form.closest('.card').querySelector('h2');
    title.id = 'form-title';

    if (!obtenerNodo('estado-id')) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'estado-id';
        hidden.name = 'id';
        form.prepend(hidden);
    }

    if (!obtenerNodo('cancel-btn')) {
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.id = 'cancel-btn';
        cancel.className = 'btn ghost hidden';
        establecerContenidoBoton(cancel, 'close', 'Cancelar edición');
        actions.insertBefore(cancel, obtenerNodo('form-message'));
    }
}

function reiniciarFormulario() {
    idEdicion = 0;
    obtenerNodo('estado-form').reset();
    obtenerNodo('estado-id').value = '';
    obtenerNodo('form-title').textContent = 'Nuevo estado';
    establecerContenidoBoton(obtenerNodo('submit-btn'), 'save', 'Guardar estado');
    obtenerNodo('cancel-btn').classList.add('hidden');
    mostrarMensaje('', '');
}

function iniciarEdicion(row) {
    idEdicion = Number(row.id);
    obtenerNodo('estado-id').value = row.id;
    obtenerNodo('nombre').value = row.nombre;
    obtenerNodo('form-title').textContent = 'Editar estado';
    establecerContenidoBoton(obtenerNodo('submit-btn'), 'save', 'Actualizar estado');
    obtenerNodo('cancel-btn').classList.remove('hidden');
    mostrarMensaje('', '');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function renderizarEstados(list) {
    const container = obtenerNodo('estados-list');
    container.innerHTML = '';

    if (list.length === 0) {
        container.innerHTML = '<p class="message">No hay estados registrados.</p>';
        return;
    }

    list.forEach(function (row) {
        const item = document.createElement('div');
        item.className = 'item';

        const title = document.createElement('h3');
        title.textContent = row.nombre;

        const actions = document.createElement('div');
        actions.className = 'item-actions';

        const editBtn = document.createElement('button');
        editBtn.type = 'button';
        editBtn.className = 'btn ghost';
        establecerContenidoBoton(editBtn, 'edit', 'Editar');
        editBtn.addEventListener('click', function () {
            iniciarEdicion(row);
        });

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn danger';
        establecerContenidoBoton(deleteBtn, 'delete', 'Eliminar');
        deleteBtn.addEventListener('click', async function () {
            const ok = window.confirm('Se eliminará el estado ' + row.nombre + '. ¿Deseas continuar?');
            if (!ok) {
                return;
            }

            establecerBotonCargando(deleteBtn, true, 'Eliminando...');
            try {
                const data = await obtenerJson('api.php?c=estado&m=eliminar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': obtenerTokenCsrf(),
                    },
                    body: JSON.stringify({ id: row.id }),
                });
                if (!data.status) {
                    mostrarMensaje(data.message ? data.message : 'No se pudo eliminar el estado.', 'error', false);
                } else {
                    mostrarMensaje('', '', false);
                }
                if (data.status) {
                    if (idEdicion === Number(row.id)) {
                        reiniciarFormulario();
                    }
                    await cargarEstados();
                }
            } finally {
                establecerBotonCargando(deleteBtn, false);
            }
        });

        actions.appendChild(editBtn);
        actions.appendChild(deleteBtn);
        item.appendChild(title);
        item.appendChild(actions);
        container.appendChild(item);
    });
}

async function cargarEstados() {
    const data = await obtenerJson('api.php?c=estado&m=listar');
    cacheEstados = data.data ? data.data : [];
    renderizarEstados(cacheEstados);
}

document.addEventListener('DOMContentLoaded', async function () {
    asegurarHerramientasFormulario();
    reiniciarFormulario();
    await cargarEstados();

    obtenerNodo('refresh-btn').addEventListener('click', async function () {
        const button = this;
        establecerBotonCargando(button, true, 'Actualizando...');
        try {
            await cargarEstados();
        } finally {
            establecerBotonCargando(button, false);
        }
    });

    obtenerNodo('cancel-btn').addEventListener('click', reiniciarFormulario);

    obtenerNodo('estado-form').addEventListener('submit', async function (event) {
        event.preventDefault();

        const form = event.target;
        const submitButton = obtenerNodo('submit-btn');
        const formData = new FormData(form);
        formData.set('_token', obtenerTokenCsrf());

        const url = idEdicion > 0 ? 'api.php?c=estado&m=actualizar' : 'api.php?c=estado&m=crear';
        if (idEdicion > 0) {
            formData.set('id', String(idEdicion));
        }

        establecerBotonCargando(submitButton, true, idEdicion > 0 ? 'Actualizando...' : 'Guardando...');
        try {
            const data = await obtenerJson(url, {
                method: 'POST',
                body: formData,
            });
            if (data.status) {
                mostrarMensaje('', '');
                mostrarToast(
                    idEdicion > 0 ? 'Estado actualizado' : 'Estado registrado',
                    data.message ? data.message : 'Proceso completado.',
                    'success'
                );
                reiniciarFormulario();
                await cargarEstados();
            } else {
                mostrarMensaje(data.message ? data.message : 'No se pudo completar la operación.', 'error');
            }
        } finally {
            establecerBotonCargando(submitButton, false);
        }
    });
});
