(function () {
    function obtenerNodo(id) {
        return document.getElementById(id);
    }

    function obtenerTokenCsrf() {
        const nodo = obtenerNodo('csrf-token');
        return nodo ? nodo.value : '';
    }

    async function obtenerJson(url, options) {
        const response = await fetch(url, options || {});
        return response.json();
    }

    function establecerContenidoBoton(button, iconName, label) {
        if (!button) {
            return;
        }
        button.innerHTML = window.UiIcons ? window.UiIcons.buttonContent(iconName, label) : label;
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

        const titulo = document.createElement('strong');
        titulo.textContent = title;
        const detalle = document.createElement('span');
        detalle.textContent = text;

        toast.appendChild(titulo);
        toast.appendChild(detalle);
        stack.appendChild(toast);

        window.setTimeout(function () {
            toast.remove();
        }, 3600);
    }

    function limpiarMensajeLuego(node, delay, className) {
        if (!node) {
            return;
        }

        if (node._messageTimer) {
            clearTimeout(node._messageTimer);
        }

        node._messageTimer = window.setTimeout(function () {
            node.textContent = '';
            node.className = className || 'message';
        }, delay);
    }

    function mostrarMensajeEnNodo(node, text, type, options) {
        if (!node) {
            return;
        }

        const cfg = options || {};
        const baseClass = cfg.baseClass || 'message';
        const toastTitle = cfg.toastTitle || (type === 'success' ? 'Operación completada' : 'Atención');
        const allowToast = cfg.allowToast !== false;
        const autoClearMs = Number(cfg.autoClearMs || 4000);

        node.textContent = text;
        node.className = type ? baseClass + ' ' + type : baseClass;

        if (allowToast && text !== '' && type) {
            limpiarMensajeLuego(node, autoClearMs, baseClass);
            mostrarToast(toastTitle, text, type);
        }
    }

    window.NexoUI = {
        obtenerNodo: obtenerNodo,
        obtenerTokenCsrf: obtenerTokenCsrf,
        obtenerJson: obtenerJson,
        establecerContenidoBoton: establecerContenidoBoton,
        establecerBotonCargando: establecerBotonCargando,
        mostrarToast: mostrarToast,
        limpiarMensajeLuego: limpiarMensajeLuego,
        mostrarMensajeEnNodo: mostrarMensajeEnNodo,
    };
})();
