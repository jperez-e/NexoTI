(() => {
    const CONFIRMACION_POR_DEFECTO = '¿Seguro que deseas cerrar sesión?';

    const enlazarConfirmacion = () => {
        const enlacesCerrarSesion = document.querySelectorAll("a[href*='r=logout']");
        enlacesCerrarSesion.forEach((enlace) => {
            if (enlace.dataset.logoutConfirmBound === '1') {
                return;
            }
            enlace.dataset.logoutConfirmBound = '1';
            enlace.addEventListener('click', (evento) => {
                const mensaje = enlace.getAttribute('data-logout-confirm') || CONFIRMACION_POR_DEFECTO;
                if (!window.confirm(mensaje)) {
                    evento.preventDefault();
                }
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enlazarConfirmacion);
        return;
    }
    enlazarConfirmacion();
})();
