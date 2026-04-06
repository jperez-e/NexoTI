// Este archivo JavaScript define la lógica de interfaz del módulo login.
// Maneja eventos, consumo de API y renderizado dinámico del DOM para mejorar la experiencia de usuario.
const botonAlternar = document.getElementById('toggle-password');
const campoContrasena = document.getElementById('password');
const mensajeErrorLogin = document.getElementById('login-error');

function alternarVisibilidad(input, button) {
    if (!input || !button) { return; }
    const esContrasena = input.type === 'password';
    input.type = esContrasena ? 'text' : 'password';
    button.classList.toggle('is-visible', esContrasena);
}

if (botonAlternar && campoContrasena) {
    botonAlternar.addEventListener('click', function () {
        alternarVisibilidad(campoContrasena, botonAlternar);
    });
}

if (mensajeErrorLogin) {
    const eliminarMensaje = function () {
        if (mensajeErrorLogin.parentNode) {
            mensajeErrorLogin.parentNode.removeChild(mensajeErrorLogin);
        }
    };
    mensajeErrorLogin.addEventListener('animationend', eliminarMensaje, { once: true });
    window.setTimeout(eliminarMensaje, 5200);
}
