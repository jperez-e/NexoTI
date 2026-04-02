let idEdicion = 0;  
let cacheUsuarios = [];  
  
async function obtenerJson(url, options = {}) {  
    const response = await fetch(url, options);  
    return response.json();  
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

function obtenerTokenCsrf() {
    const node = obtenerNodo('csrf-token');
    return node ? node.value : '';
}
  
function obtenerNodo(id) {  
    return document.getElementById(id);  
}  

function establecerContenidoBoton(button, iconName, label) {
    if (!button) {
        return;
    }
    button.innerHTML = window.UiIcons ? window.UiIcons.buttonContent(iconName, label) : label;
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
    if (!node) { return; }  
    node.textContent = text;  
    node.className = type ? 'message ' + type : 'message';  
    if (allowToast && text !== '' && type) {
        limpiarMensajeLuego(node, 4000);
    }
}  
  
function asegurarHerramientasFormulario() {  
    const form = obtenerNodo('usuario-form');  
    if (!form) { return; }  
    const actions = form.querySelector('.actions');  
    const title = form.closest('.card').querySelector('h2');  
    if (title) { title.id = 'form-title'; }  
    const password = form.querySelector('input[name=\"password\"]');  
    if (password) { password.required = false; } 
    if (!obtenerNodo('cancel-btn') && actions) {  
        const cancel = document.createElement('button');  
        cancel.type = 'button';  
        cancel.id = 'cancel-btn';  
        cancel.className = 'btn ghost hidden';  
        establecerContenidoBoton(cancel, 'close', 'Cancelar edición');
        actions.insertBefore(cancel, obtenerNodo('form-message'));  
    }  
    if (!obtenerNodo('usuario-id')) {  
        const hidden = document.createElement('input');  
        hidden.type = 'hidden';  
        hidden.id = 'usuario-id';  
        hidden.name = 'id';  
        form.prepend(hidden);  
    }  
} 
  
function llenarRoles(roles) {  
    const select = obtenerNodo('rol-select');  
    if (!select) { return; }  
    select.innerHTML = '';  
    const first = document.createElement('option');  
    first.value = '';  
    first.textContent = 'Seleccione un rol';  
    select.appendChild(first);  
    roles.forEach(function (row) {  
        const option = document.createElement('option');  
        option.value = row.id;  
        option.textContent = row.nombre;  
        select.appendChild(option);  
    });  
}  
  
function reiniciarFormulario() {  
    idEdicion = 0;  
    const form = obtenerNodo('usuario-form');  
    form.reset();  
    obtenerNodo('usuario-id').value = '';  
    obtenerNodo('submit-btn') ? establecerContenidoBoton(obtenerNodo('submit-btn'), 'save', 'Crear usuario') : null;  
    obtenerNodo('form-title') ? obtenerNodo('form-title').textContent = 'Registrar usuario' : null;  
    obtenerNodo('cancel-btn') ? obtenerNodo('cancel-btn').classList.add('hidden') : null;  
    mostrarMensaje('', '');  
} 
  
function iniciarEdicion(user) {  
    idEdicion = Number(user.id);  
    obtenerNodo('usuario-id').value = user.id;  
    obtenerNodo('nombre') ? obtenerNodo('nombre').value = user.nombre : null;  
    obtenerNodo('email') ? obtenerNodo('email').value = user.email : null;  
    obtenerNodo('password') ? obtenerNodo('password').value = '' : null;  
    obtenerNodo('rol-select') ? obtenerNodo('rol-select').value = user.rol_id : null;  
    obtenerNodo('submit-btn') ? establecerContenidoBoton(obtenerNodo('submit-btn'), 'save', 'Actualizar usuario') : null;  
    obtenerNodo('form-title') ? obtenerNodo('form-title').textContent = 'Editar usuario' : null;  
    obtenerNodo('cancel-btn') ? obtenerNodo('cancel-btn').classList.remove('hidden') : null;  
    mostrarMensaje('', '');  
    window.scrollTo({ top: 0, behavior: 'smooth' });  
}  
  
function renderizarUsuarios(list) {  
    const container = obtenerNodo('usuarios-list');  
    if (!container) { return; }  
    container.innerHTML = '';  
    if (list.length === 0) {  
        container.innerHTML = '<p class=\"message\">No hay usuarios registrados.</p>';  
        return;  
    }  
    list.forEach(function (row) {  
        const item = document.createElement('div');  
        item.className = 'item'; 
        const title = document.createElement('h3');  
        title.textContent = row.nombre;  
        const meta = document.createElement('p');  
        meta.textContent = 'Correo: ' + row.email + ' - Rol: ' + row.rol_nombre + ' - Activo: ' + (Number(row.activo) === 1 ? 'Si' : 'No');  
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
            const ok = window.confirm('Se eliminará el usuario ' + row.nombre + '. ¿Deseas continuar?');  
            if (!ok) { return; }  
            establecerBotonCargando(deleteBtn, true, 'Eliminando...');
            try {
                const data = await obtenerJson('api.php?c=usuario&m=eliminar', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': obtenerTokenCsrf() }, body: JSON.stringify({ id: row.id }) });  
                if (!data.status) {
                    mostrarMensaje(data.message ? data.message : 'No se pudo eliminar el usuario.', 'error', false);
                } else {
                    mostrarMensaje('', '', false);
                }
                if (data.status) {  
                    if (idEdicion === Number(row.id)) { reiniciarFormulario(); }  
                    await cargarUsuarios();  
                }
            } finally {
                establecerBotonCargando(deleteBtn, false);
            }
        }); 
        actions.appendChild(editBtn);  
        actions.appendChild(deleteBtn);  
        item.appendChild(title);  
        item.appendChild(meta);  
        item.appendChild(actions);  
        container.appendChild(item);  
    });  
}  
  
async function cargarRoles() {  
    const data = await obtenerJson('api.php?c=rol&m=listar');  
    llenarRoles(data.data ? data.data : []);  
}  
  
async function cargarUsuarios() {  
    const data = await obtenerJson('api.php?c=usuario&m=listar');  
    cacheUsuarios = data.data ? data.data : [];  
    renderizarUsuarios(cacheUsuarios);  
}  
  
document.addEventListener('DOMContentLoaded', async function () {  
    asegurarHerramientasFormulario();  
    reiniciarFormulario();  
    await cargarRoles();  
    await cargarUsuarios();  
    obtenerNodo('refresh-btn').addEventListener('click', async function () {
        const button = this;
        establecerBotonCargando(button, true, 'Actualizando...');
        try {
            await cargarUsuarios();
        } finally {
            establecerBotonCargando(button, false);
        }
    });  
    obtenerNodo('cancel-btn').addEventListener('click', reiniciarFormulario);  
    obtenerNodo('usuario-form').addEventListener('submit', async function (event) {  
        event.preventDefault();  
        const formData = new FormData(obtenerNodo('usuario-form'));  
        const submitButton = obtenerNodo('submit-btn');
        formData.set('_token', obtenerTokenCsrf());
        const url = idEdicion > 0 ? 'api.php?c=usuario&m=actualizar' : 'api.php?c=usuario&m=crear';  
        if (idEdicion > 0) { formData.set('id', String(idEdicion)); } 
        establecerBotonCargando(submitButton, true, idEdicion > 0 ? 'Actualizando...' : 'Guardando...');
        try {
            const data = await obtenerJson(url, { method: 'POST', body: formData });  
            if (data.status) {  
                mostrarMensaje('', '');
                mostrarToast(
                    idEdicion > 0 ? 'Usuario actualizado' : 'Usuario registrado',
                    data.message ? data.message : 'Proceso completado.',
                    'success'
                );
                reiniciarFormulario();  
                await cargarUsuarios();  
            } else {
                mostrarMensaje(data.message ? data.message : 'No se pudo completar la operación.', 'error');
            }
        } finally {
            establecerBotonCargando(submitButton, false);
        }
    });  
}); 
