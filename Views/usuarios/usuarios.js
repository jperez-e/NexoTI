

let idEdicion = 0;
let cacheUsuarios = [];

function obtenerUi() {
    if (!window.NexoUI) {
        throw new Error('NexoUI no está disponible. Incluye ui-core.js antes de usuarios.js.');
    }
    return window.NexoUI;
}

function mostrarMensaje(texto, tipo, permitirToast) {
    const ui = obtenerUi();
    const nodo = ui.obtenerNodo('form-message');
    ui.mostrarMensajeEnNodo(nodo, texto, tipo, {
        baseClass: 'message',
        allowToast: permitirToast !== false,
        autoClearMs: 4000,
        toastTitle: tipo === 'success' ? 'Operación completada' : 'Atención',
    });
}

function asegurarHerramientasFormulario() {
    const ui = obtenerUi();
    const form = ui.obtenerNodo('usuario-form');
    if (!form) {
        return;
    }

    const actions = form.querySelector('.actions');
    const title = form.closest('.card').querySelector('h2');
    if (title) {
        title.id = 'form-title';
    }

    const password = form.querySelector('input[name="password"]');
    if (password) {
        password.required = false;
    }

    if (!ui.obtenerNodo('cancel-btn') && actions) {
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.id = 'cancel-btn';
        cancel.className = 'btn ghost hidden';
        ui.establecerContenidoBoton(cancel, 'close', 'Cancelar edición');
        actions.insertBefore(cancel, ui.obtenerNodo('form-message'));
    }

    if (!ui.obtenerNodo('usuario-id')) {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.id = 'usuario-id';
        hidden.name = 'id';
        form.prepend(hidden);
    }
}

function llenarRoles(roles) {
    const ui = obtenerUi();
    const select = ui.obtenerNodo('rol-select');
    if (!select) {
        return;
    }

    select.innerHTML = '';
    const opcionInicial = document.createElement('option');
    opcionInicial.value = '';
    opcionInicial.textContent = 'Seleccione un rol';
    select.appendChild(opcionInicial);

    roles.forEach(function (row) {
        const option = document.createElement('option');
        option.value = row.id;
        option.textContent = row.nombre;
        select.appendChild(option);
    });
}

function reiniciarFormulario() {
    const ui = obtenerUi();
    idEdicion = 0;

    const form = ui.obtenerNodo('usuario-form');
    if (form) {
        form.reset();
    }

    const idNode = ui.obtenerNodo('usuario-id');
    if (idNode) {
        idNode.value = '';
    }

    const submitBtn = ui.obtenerNodo('submit-btn');
    if (submitBtn) {
        ui.establecerContenidoBoton(submitBtn, 'save', 'Crear usuario');
    }

    const title = ui.obtenerNodo('form-title');
    if (title) {
        title.textContent = 'Registrar usuario';
    }

    const cancelBtn = ui.obtenerNodo('cancel-btn');
    if (cancelBtn) {
        cancelBtn.classList.add('hidden');
    }

    mostrarMensaje('', '', false);
}

function iniciarEdicion(usuario) {
    const ui = obtenerUi();
    idEdicion = Number(usuario.id);

    const idNode = ui.obtenerNodo('usuario-id');
    if (idNode) {
        idNode.value = usuario.id;
    }

    const nombre = ui.obtenerNodo('nombre');
    if (nombre) {
        nombre.value = usuario.nombre;
    }

    const email = ui.obtenerNodo('email');
    if (email) {
        email.value = usuario.email;
    }

    const password = ui.obtenerNodo('password');
    if (password) {
        password.value = '';
    }

    const rol = ui.obtenerNodo('rol-select');
    if (rol) {
        rol.value = usuario.rol_id;
    }

    const submitBtn = ui.obtenerNodo('submit-btn');
    if (submitBtn) {
        ui.establecerContenidoBoton(submitBtn, 'save', 'Actualizar usuario');
    }

    const title = ui.obtenerNodo('form-title');
    if (title) {
        title.textContent = 'Editar usuario';
    }

    const cancelBtn = ui.obtenerNodo('cancel-btn');
    if (cancelBtn) {
        cancelBtn.classList.remove('hidden');
    }

    mostrarMensaje('', '', false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function eliminarUsuario(usuario, deleteBtn) {
    const ui = obtenerUi();
    const confirmar = window.confirm('Se eliminará el usuario ' + usuario.nombre + '. ¿Deseas continuar?');
    if (!confirmar) {
        return;
    }

    ui.establecerBotonCargando(deleteBtn, true, 'Eliminando...');
    try {
        const data = await ui.obtenerJson('api.php?c=usuario&m=eliminar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': ui.obtenerTokenCsrf(),
            },
            body: JSON.stringify({ id: usuario.id }),
        });

        if (!data.status) {
            mostrarMensaje(data.message ? data.message : 'No se pudo eliminar el usuario.', 'error', false);
            return;
        }

        mostrarMensaje('', '', false);
        if (idEdicion === Number(usuario.id)) {
            reiniciarFormulario();
        }
        await cargarUsuarios();
    } finally {
        ui.establecerBotonCargando(deleteBtn, false);
    }
}

function renderizarUsuarios(lista) {
    const ui = obtenerUi();
    const contenedor = ui.obtenerNodo('usuarios-list');
    if (!contenedor) {
        return;
    }

    contenedor.innerHTML = '';
    if (!lista.length) {
        contenedor.innerHTML = '<p class="message">No hay usuarios registrados.</p>';
        return;
    }

    lista.forEach(function (row) {
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
        ui.establecerContenidoBoton(editBtn, 'edit', 'Editar');
        editBtn.addEventListener('click', function () {
            iniciarEdicion(row);
        });

        const deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn danger';
        ui.establecerContenidoBoton(deleteBtn, 'delete', 'Eliminar');
        deleteBtn.addEventListener('click', function () {
            eliminarUsuario(row, deleteBtn);
        });

        actions.appendChild(editBtn);
        actions.appendChild(deleteBtn);
        item.appendChild(title);
        item.appendChild(meta);
        item.appendChild(actions);
        contenedor.appendChild(item);
    });
}

async function cargarRoles() {
    const ui = obtenerUi();
    const data = await ui.obtenerJson('api.php?c=rol&m=listar');
    llenarRoles(data.data ? data.data : []);
}

async function cargarUsuarios() {
    const ui = obtenerUi();
    const data = await ui.obtenerJson('api.php?c=usuario&m=listar');
    cacheUsuarios = data.data ? data.data : [];
    renderizarUsuarios(cacheUsuarios);
}

async function guardarUsuario(event) {
    event.preventDefault();
    const ui = obtenerUi();
    const form = ui.obtenerNodo('usuario-form');
    const submitBtn = ui.obtenerNodo('submit-btn');
    if (!form || !submitBtn) {
        return;
    }

    const formData = new FormData(form);
    formData.set('_token', ui.obtenerTokenCsrf());

    const editando = idEdicion > 0;
    if (editando) {
        formData.set('id', String(idEdicion));
    }

    const url = editando ? 'api.php?c=usuario&m=actualizar' : 'api.php?c=usuario&m=crear';
    ui.establecerBotonCargando(submitBtn, true, editando ? 'Actualizando...' : 'Guardando...');
    try {
        const data = await ui.obtenerJson(url, { method: 'POST', body: formData });
        if (!data.status) {
            mostrarMensaje(data.message ? data.message : 'No se pudo completar la operación.', 'error', true);
            return;
        }

        mostrarMensaje('', '', false);
        ui.mostrarToast(
            editando ? 'Usuario actualizado' : 'Usuario registrado',
            data.message ? data.message : 'Proceso completado.',
            'success'
        );
        reiniciarFormulario();
        await cargarUsuarios();
    } finally {
        ui.establecerBotonCargando(submitBtn, false);
    }
}

async function refrescarListado() {
    const ui = obtenerUi();
    const refreshBtn = ui.obtenerNodo('refresh-btn');
    if (!refreshBtn) {
        await cargarUsuarios();
        return;
    }

    ui.establecerBotonCargando(refreshBtn, true, 'Actualizando...');
    try {
        await cargarUsuarios();
    } finally {
        ui.establecerBotonCargando(refreshBtn, false);
    }
}

document.addEventListener('DOMContentLoaded', async function () {
    const ui = obtenerUi();
    asegurarHerramientasFormulario();
    reiniciarFormulario();
    await cargarRoles();
    await cargarUsuarios();

    const refreshBtn = ui.obtenerNodo('refresh-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refrescarListado);
    }

    const cancelBtn = ui.obtenerNodo('cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', reiniciarFormulario);
    }

    const form = ui.obtenerNodo('usuario-form');
    if (form) {
        form.addEventListener('submit', guardarUsuario);
    }
});
