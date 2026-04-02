const botonAlternar = document.getElementById('toggle-password');
const campoContrasena = document.getElementById('password');

if (botonAlternar) {
    botonAlternar.addEventListener('click', function () {
        if (!campoContrasena) { return; }
        const esContrasena = campoContrasena.type === 'password';
        campoContrasena.type = esContrasena ? 'text' : 'password';
        botonAlternar.classList.toggle('is-visible', esContrasena);
    });
}
