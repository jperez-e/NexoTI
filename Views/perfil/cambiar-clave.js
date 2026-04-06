// Este archivo JavaScript controla los toggles de visibilidad de contraseña en la vista de seguridad.
document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('[data-toggle-password]');

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
});
