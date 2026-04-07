// Este archivo JavaScript define utilidades compartidas de interfaz para el sistema.
// Se reutiliza en múltiples vistas para mantener comportamiento consistente y reducir duplicación de código frontend.
// Este script proporciona una función para crear páginas de catálogo con operaciones 
// CRUD (Crear, Leer, Actualizar, Eliminar) utilizando la biblioteca NexoUI.

(function () {
    function valorTexto(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value);
    }

    function crearPaginaCatalogo(config) {
        const ui = window.NexoUI;
        if (!ui) {
            throw new Error('NexoUI no está disponible. Incluye ui-core.js antes de este script.');
        }

        const cfg = config || {};
        const controlador = cfg.controlador;
        const formId = cfg.formId;
        const listId = cfg.listId;
        const hiddenId = cfg.hiddenId;
        const campos = Array.isArray(cfg.campos) ? cfg.campos : [];
        const renderTituloItem = cfg.renderTituloItem || function (fila) { return valorTexto(fila.nombre); };
        const renderDescripcionItem = cfg.renderDescripcionItem || function () { return ''; };
        const mensajes = cfg.mensajes || {};
        const formTitleNuevo = mensajes.formTitleNuevo || 'Nuevo registro';
        const formTitleEditar = mensajes.formTitleEditar || 'Editar registro';
        const submitGuardar = mensajes.submitGuardar || 'Guardar';
        const submitActualizar = mensajes.submitActualizar || 'Actualizar';
        const listaVacia = mensajes.listaVacia || 'No hay registros.';
        const confirmarEliminar = mensajes.confirmarEliminar || function () { return '¿Deseas eliminar este registro?'; };
        const errorEliminar = mensajes.errorEliminar || 'No se pudo eliminar el registro.';
        const toastCreadoTitulo = mensajes.toastCreadoTitulo || 'Registro creado';
        const toastActualizadoTitulo = mensajes.toastActualizadoTitulo || 'Registro actualizado';
        const errorOperacion = mensajes.errorOperacion || 'No se pudo completar la operación.';
        const paginacion = cfg.paginacion || null;
        const paginacionActiva = !!paginacion;
        const tamanoPagina = paginacionActiva
            ? Math.max(1, Number(paginacion.tamano || 8))
            : 0;
        const textoAnterior = paginacionActiva && paginacion.anteriorTexto
            ? String(paginacion.anteriorTexto)
            : 'Anterior';
        const textoSiguiente = paginacionActiva && paginacion.siguienteTexto
            ? String(paginacion.siguienteTexto)
            : 'Siguiente';

        const form = ui.obtenerNodo(formId);
        const list = ui.obtenerNodo(listId);
        const refreshBtn = ui.obtenerNodo('refresh-btn');
        const submitBtn = ui.obtenerNodo('submit-btn');
        const formMessage = ui.obtenerNodo('form-message');
        const cardTitle = form ? form.closest('.card').querySelector('h2') : null;
        const actions = form ? form.querySelector('.actions') : null;

        let idEdicion = 0;
        let cancelBtn = ui.obtenerNodo('cancel-btn');
        let hiddenField = ui.obtenerNodo(hiddenId);
        let listadoCompleto = [];
        let paginaActual = 1;
        let totalPaginas = 1;
        let pager = null;
        let pagerInfo = null;
        let pagerPrev = null;
        let pagerNext = null;

        if (!form || !list || !submitBtn || !formMessage || !cardTitle || !actions) {
            return;
        }

        cardTitle.id = 'form-title';

        if (!hiddenField) {
            hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.id = hiddenId;
            hiddenField.name = 'id';
            form.prepend(hiddenField);
        }

        if (!cancelBtn) {
            cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.id = 'cancel-btn';
            cancelBtn.className = 'btn ghost hidden';
            ui.establecerContenidoBoton(cancelBtn, 'close', 'Cancelar edición');
            actions.insertBefore(cancelBtn, formMessage);
        }

        function mostrarMensaje(text, type, allowToast) {
            ui.mostrarMensajeEnNodo(formMessage, text, type, {
                baseClass: 'message',
                allowToast: allowToast !== false,
                autoClearMs: 4000,
                toastTitle: type === 'success' ? 'Operación completada' : 'Atención',
            });
        }

        function limpiarFormulario() {
            idEdicion = 0;
            form.reset();
            hiddenField.value = '';
            cardTitle.textContent = formTitleNuevo;
            ui.establecerContenidoBoton(submitBtn, 'save', submitGuardar);
            cancelBtn.classList.add('hidden');
            mostrarMensaje('', '', false);
        }

        function iniciarEdicion(fila) {
            idEdicion = Number(fila.id);
            hiddenField.value = String(fila.id);

            campos.forEach(function (campo) {
                const input = ui.obtenerNodo(campo.id);
                if (!input) {
                    return;
                }
                input.value = valorTexto(fila[campo.key]);
            });

            cardTitle.textContent = formTitleEditar;
            ui.establecerContenidoBoton(submitBtn, 'save', submitActualizar);
            cancelBtn.classList.remove('hidden');
            mostrarMensaje('', '', false);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function crearBotonAccion(className, icono, etiqueta, onClick) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = className;
            ui.establecerContenidoBoton(button, icono, etiqueta);
            button.addEventListener('click', onClick);
            return button;
        }

        function asegurarPaginador() {
            if (!paginacionActiva || pager) {
                return;
            }

            pager = document.createElement('div');
            pager.className = 'catalog-pager';

            pagerPrev = document.createElement('button');
            pagerPrev.type = 'button';
            pagerPrev.className = 'btn ghost';
            pagerPrev.textContent = textoAnterior;
            pagerPrev.addEventListener('click', function () {
                if (paginaActual <= 1) {
                    return;
                }
                paginaActual -= 1;
                renderizarListado(listadoCompleto);
            });

            pagerInfo = document.createElement('span');
            pagerInfo.className = 'catalog-pager-info';
            pagerInfo.setAttribute('aria-live', 'polite');

            pagerNext = document.createElement('button');
            pagerNext.type = 'button';
            pagerNext.className = 'btn ghost';
            pagerNext.textContent = textoSiguiente;
            pagerNext.addEventListener('click', function () {
                if (paginaActual >= totalPaginas) {
                    return;
                }
                paginaActual += 1;
                renderizarListado(listadoCompleto);
            });

            pager.appendChild(pagerPrev);
            pager.appendChild(pagerInfo);
            pager.appendChild(pagerNext);
            list.insertAdjacentElement('afterend', pager);
        }

        function actualizarPaginador() {
            if (!paginacionActiva || !pager || !pagerInfo || !pagerPrev || !pagerNext) {
                return;
            }

            const total = Array.isArray(listadoCompleto) ? listadoCompleto.length : 0;
            const mostrar = total > tamanoPagina;
            pager.style.display = mostrar ? 'flex' : 'none';
            if (!mostrar) {
                return;
            }

            pagerInfo.textContent = 'Página ' + paginaActual + ' de ' + totalPaginas + ' · ' + total + ' registro(s)';
            pagerPrev.disabled = paginaActual <= 1;
            pagerNext.disabled = paginaActual >= totalPaginas;
        }

        async function eliminarFila(fila, botonEliminar) {
            const confirmacion = typeof confirmarEliminar === 'function'
                ? confirmarEliminar(fila)
                : String(confirmarEliminar);
            if (!window.confirm(confirmacion)) {
                return;
            }

            ui.establecerBotonCargando(botonEliminar, true, 'Eliminando...');
            try {
                const data = await ui.obtenerJson('api.php?c=' + controlador + '&m=eliminar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': ui.obtenerTokenCsrf(),
                    },
                    body: JSON.stringify({ id: fila.id }),
                });

                if (!data.status) {
                    mostrarMensaje(data.message ? data.message : errorEliminar, 'error', false);
                    return;
                }

                mostrarMensaje('', '', false);
                if (idEdicion === Number(fila.id)) {
                    limpiarFormulario();
                }
                await cargarListado();
            } finally {
                ui.establecerBotonCargando(botonEliminar, false);
            }
        }

        function renderizarListado(lista) {
            listadoCompleto = Array.isArray(lista) ? lista.slice() : [];
            list.innerHTML = '';
            if (listadoCompleto.length === 0) {
                list.innerHTML = '<p class="message">' + listaVacia + '</p>';
                actualizarPaginador();
                return;
            }

            totalPaginas = paginacionActiva
                ? Math.max(1, Math.ceil(listadoCompleto.length / tamanoPagina))
                : 1;

            if (paginaActual > totalPaginas) {
                paginaActual = totalPaginas;
            }
            if (paginaActual < 1) {
                paginaActual = 1;
            }

            const listaVisible = paginacionActiva
                ? listadoCompleto.slice((paginaActual - 1) * tamanoPagina, paginaActual * tamanoPagina)
                : listadoCompleto;

            listaVisible.forEach(function (fila) {
                const item = document.createElement('div');
                item.className = 'item';

                const title = document.createElement('h3');
                title.textContent = renderTituloItem(fila);
                item.appendChild(title);

                const descripcion = renderDescripcionItem(fila);
                if (descripcion) {
                    const desc = document.createElement('p');
                    desc.textContent = descripcion;
                    item.appendChild(desc);
                }

                const actionWrap = document.createElement('div');
                actionWrap.className = 'item-actions';

                const editBtn = crearBotonAccion('btn ghost', 'edit', 'Editar', function () {
                    iniciarEdicion(fila);
                });
                const deleteBtn = crearBotonAccion('btn danger', 'delete', 'Eliminar', function () {
                    eliminarFila(fila, deleteBtn);
                });

                actionWrap.appendChild(editBtn);
                actionWrap.appendChild(deleteBtn);
                item.appendChild(actionWrap);
                list.appendChild(item);
            });

            actualizarPaginador();
        }

        async function cargarListado() {
            const data = await ui.obtenerJson('api.php?c=' + controlador + '&m=listar');
            const lista = data && Array.isArray(data.data) ? data.data : [];
            renderizarListado(lista);
        }

        async function manejarSubmit(event) {
            event.preventDefault();

            const formData = new FormData(form);
            formData.set('_token', ui.obtenerTokenCsrf());

            const editando = idEdicion > 0;
            if (editando) {
                formData.set('id', String(idEdicion));
            }

            const url = 'api.php?c=' + controlador + '&m=' + (editando ? 'actualizar' : 'crear');
            ui.establecerBotonCargando(submitBtn, true, editando ? 'Actualizando...' : 'Guardando...');
            try {
                const data = await ui.obtenerJson(url, {
                    method: 'POST',
                    body: formData,
                });

                if (!data.status) {
                    mostrarMensaje(data.message ? data.message : errorOperacion, 'error', true);
                    return;
                }

                mostrarMensaje('', '', false);
                ui.mostrarToast(
                    editando ? toastActualizadoTitulo : toastCreadoTitulo,
                    data.message ? data.message : 'Proceso completado.',
                    'success'
                );
                limpiarFormulario();
                await cargarListado();
            } finally {
                ui.establecerBotonCargando(submitBtn, false);
            }
        }

        async function manejarRefresh() {
            if (!refreshBtn) {
                await cargarListado();
                return;
            }
            ui.establecerBotonCargando(refreshBtn, true, 'Actualizando...');
            try {
                await cargarListado();
            } finally {
                ui.establecerBotonCargando(refreshBtn, false);
            }
        }

        cancelBtn.addEventListener('click', limpiarFormulario);
        form.addEventListener('submit', manejarSubmit);
        if (refreshBtn) {
            refreshBtn.addEventListener('click', manejarRefresh);
        }
        asegurarPaginador();

        limpiarFormulario();
        cargarListado();
    }

    window.NexoCatalogo = {
        crearPaginaCatalogo: crearPaginaCatalogo,
    };
})();
