// Este archivo JavaScript define la lógica de interfaz del módulo perfil.
// Maneja eventos, consumo de API y renderizado dinámico del DOM para mejorar la experiencia de usuario.
// Este código se ejecuta cuando el DOM ha sido completamente cargado y listo para ser manipulado.

document.addEventListener('DOMContentLoaded', function () {
    var toggles = document.querySelectorAll('[data-toggle-password]');
    toggles.forEach(function (button) {
        var targetId = button.getAttribute('data-target') || '';
        var input = document.getElementById(targetId);
        if (!input) {
            return;
        }
        button.addEventListener('click', function () {
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.classList.toggle('is-visible', isPassword);
        });
    });
});
