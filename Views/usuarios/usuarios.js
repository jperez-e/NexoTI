let editingId = 0;  
let usuariosCache = [];  
  
async function fetchJSON(url, options = {}) {  
    const response = await fetch(url, options);  
    return response.json();  
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

function getCsrfToken() {
    const node = getNode('csrf-token');
    return node ? node.value : '';
}
  
function getNode(id) {  
    return document.getElementById(id);  
}  

function setButtonContent(button, iconName, label) {
    if (!button) {
        return;
    }
    button.innerHTML = window.UiIcons ? window.UiIcons.buttonContent(iconName, label) : label;
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
    if (!node) { return; }  
    node.textContent = text;  
    node.className = type ? 'message ' + type : 'message';  
    if (text !== '' && type) {
        clearMessageLater(node, 4000);
    }
}  
  
function ensureFormTools() {  
    const form = getNode('usuario-form');  
    if (!form) { return; }  
    const actions = form.querySelector('.actions');  
    const title = form.closest('.card').querySelector('h2');  
    if (title) { title.id = 'form-title'; }  
    const password = form.querySelector('input[name=\"password\"]');  
    if (password) { password.required = false; } 
    if (!getNode('cancel-btn') && actions) {  
        const cancel = document.createElement('button');  
        cancel.type = 'button';  
        cancel.id = 'cancel-btn';  
        cancel.className = 'btn ghost hidden';  
        setButtonContent(cancel, 'close', 'Cancelar edición');
        actions.insertBefore(cancel, getNode('form-message'));  
    }  
    if (!getNode('usuario-id')) {  
        const hidden = document.createElement('input');  
        hidden.type = 'hidden';  
        hidden.id = 'usuario-id';  
        hidden.name = 'id';  
        form.prepend(hidden);  
    }  
} 
  
function fillRoles(roles) {  
    const select = getNode('rol-select');  
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
  
function resetForm() {  
    editingId = 0;  
    const form = getNode('usuario-form');  
    form.reset();  
    getNode('usuario-id').value = '';  
    getNode('submit-btn') ? setButtonContent(getNode('submit-btn'), 'save', 'Crear usuario') : null;  
    getNode('form-title') ? getNode('form-title').textContent = 'Registrar usuario' : null;  
    getNode('cancel-btn') ? getNode('cancel-btn').classList.add('hidden') : null;  
    showMessage('', '');  
} 
  
function startEdit(user) {  
    editingId = Number(user.id);  
    getNode('usuario-id').value = user.id;  
    getNode('nombre') ? getNode('nombre').value = user.nombre : null;  
    getNode('email') ? getNode('email').value = user.email : null;  
    getNode('password') ? getNode('password').value = '' : null;  
    getNode('rol-select') ? getNode('rol-select').value = user.rol_id : null;  
    getNode('submit-btn') ? setButtonContent(getNode('submit-btn'), 'save', 'Actualizar usuario') : null;  
    getNode('form-title') ? getNode('form-title').textContent = 'Editar usuario' : null;  
    getNode('cancel-btn') ? getNode('cancel-btn').classList.remove('hidden') : null;  
    showMessage('', '');  
    window.scrollTo({ top: 0, behavior: 'smooth' });  
}  
  
function renderUsuarios(list) {  
    const container = getNode('usuarios-list');  
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
        setButtonContent(editBtn, 'edit', 'Editar');
        editBtn.addEventListener('click', function () {  
            startEdit(row);  
        });  
        const deleteBtn = document.createElement('button');  
        deleteBtn.type = 'button';  
        deleteBtn.className = 'btn danger';  
        setButtonContent(deleteBtn, 'delete', 'Eliminar');
        deleteBtn.addEventListener('click', async function () {  
            const ok = window.confirm('Se eliminará el usuario ' + row.nombre + '. ¿Deseas continuar?');  
            if (!ok) { return; }  
            setButtonLoading(deleteBtn, true, 'Eliminando...');
            try {
                const data = await fetchJSON('api.php?c=usuario&m=delete', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() }, body: JSON.stringify({ id: row.id }) });  
                showMessage(data.message ? data.message : '', data.status ? 'success' : 'error');  
                if (data.status) {  
                    if (editingId === Number(row.id)) { resetForm(); }  
                    await loadUsuarios();  
                }
            } finally {
                setButtonLoading(deleteBtn, false);
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
  
async function loadRoles() {  
    const data = await fetchJSON('api.php?c=rol&m=list');  
    fillRoles(data.data ? data.data : []);  
}  
  
async function loadUsuarios() {  
    const data = await fetchJSON('api.php?c=usuario&m=list');  
    usuariosCache = data.data ? data.data : [];  
    renderUsuarios(usuariosCache);  
}  
  
document.addEventListener('DOMContentLoaded', async function () {  
    ensureFormTools();  
    resetForm();  
    await loadRoles();  
    await loadUsuarios();  
    getNode('refresh-btn').addEventListener('click', async function () {
        const button = this;
        setButtonLoading(button, true, 'Actualizando...');
        try {
            await loadUsuarios();
        } finally {
            setButtonLoading(button, false);
        }
    });  
    getNode('cancel-btn').addEventListener('click', resetForm);  
    getNode('usuario-form').addEventListener('submit', async function (event) {  
        event.preventDefault();  
        const formData = new FormData(getNode('usuario-form'));  
        const submitButton = getNode('submit-btn');
        formData.set('_token', getCsrfToken());
        const url = editingId > 0 ? 'api.php?c=usuario&m=update' : 'api.php?c=usuario&m=create';  
        if (editingId > 0) { formData.set('id', String(editingId)); } 
        setButtonLoading(submitButton, true, editingId > 0 ? 'Actualizando...' : 'Guardando...');
        try {
            const data = await fetchJSON(url, { method: 'POST', body: formData });  
            showMessage(data.message ? data.message : '', data.status ? 'success' : 'error');  
            if (data.status) {  
                resetForm();  
                await loadUsuarios();  
            }
        } finally {
            setButtonLoading(submitButton, false);
        }
    });  
}); 
