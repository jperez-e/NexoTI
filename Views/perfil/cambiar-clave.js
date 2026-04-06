// Este archivo JavaScript controla los toggles de visibilidad de contraseña en la vista de seguridad.
document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('[data-toggle-password]');
    var flashStack = document.querySelector('.flash-stack');

    toggles.forEach(function (button) {
        var targetId = button.getAttribute('data-target') || '';
        var input = document.getElementById(targetId);
        if (!input) {
            return;
        }

        button.addEventListener('click', function () {
            var mostrar = input.type === 'password';
            input.type = mostrar ? 'text' : 'password';
            button.classList.toggle('is-visible', mostrar);
        });
    });

    // Los mensajes de validación/resultado se ocultan solos para no quedar fijos en pantalla.
    if (flashStack) {
        var mensajes = flashStack.querySelectorAll('.message');
        mensajes.forEach(function (mensaje) {
            window.setTimeout(function () {
                mensaje.remove();
                if (flashStack.querySelectorAll('.message').length === 0) {
                    flashStack.remove();
                }
            }, 5000);
        });
    }
});
