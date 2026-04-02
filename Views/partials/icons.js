(function () {
    const iconos = {
        actualizar: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 1-2.34-5.66L20 8V3h-5l2.19 2.19A10 10 0 1 0 22 11h-2z" fill="currentColor"></path></svg>',
        guardar: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zm-5 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm3-10H5V5h10v4z" fill="currentColor"></path></svg>',
        editar: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25zm14.71-9.04a1.003 1.003 0 0 0 0-1.42l-2.5-2.5a1.003 1.003 0 0 0-1.42 0l-1.96 1.96 3.75 3.75 2.13-1.79z" fill="currentColor"></path></svg>',
        eliminar: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2h4v2H4V6h4l1-2z" fill="currentColor"></path></svg>',
        responder: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-.5-5-3-10-11-11z" fill="currentColor"></path></svg>',
        cerrar: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z" fill="currentColor"></path></svg>',
        usuarios: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.94 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" fill="currentColor"></path></svg>',
        notificaciones: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm6-6V11a6 6 0 1 0-12 0v5l-2 2v1h16v-1l-2-2z" fill="currentColor"></path></svg>',
    };

    const aliasIconos = {
        refresh: 'actualizar',
        save: 'guardar',
        edit: 'editar',
        delete: 'eliminar',
        reply: 'responder',
        close: 'cerrar',
        users: 'usuarios',
        bell: 'notificaciones',
    };

    function resolverNombreIcono(nombre) {
        if (iconos[nombre]) {
            return nombre;
        }
        return aliasIconos[nombre] || '';
    }

    function obtenerIcono(nombre) {
        const nombreNormalizado = resolverNombreIcono(nombre);
        return nombreNormalizado ? iconos[nombreNormalizado] : '';
    }

    function contenidoBoton(nombre, etiqueta) {
        return '<span class="btn-icon">' + obtenerIcono(nombre) + '</span><span class="btn-label">' + etiqueta + '</span>';
    }

    window.IconosUi = {
        obtenerIcono: obtenerIcono,
        contenidoBoton: contenidoBoton,
        resolverNombreIcono: resolverNombreIcono,
    };

    // Compatibilidad retroactiva mientras se migra el resto del frontend.
    window.UiIcons = {
        icon: obtenerIcono,
        buttonContent: contenidoBoton,
    };
})();
