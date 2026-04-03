const botonAlternar = document.getElementById('toggle-password');
const campoContrasena = document.getElementById('password');
const mensajeErrorLogin = document.getElementById('login-error');

if (botonAlternar) {
    botonAlternar.addEventListener('click', function () {
        if (!campoContrasena) { return; }
        const esContrasena = campoContrasena.type === 'password';
        campoContrasena.type = esContrasena ? 'text' : 'password';
        botonAlternar.classList.toggle('is-visible', esContrasena);
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
