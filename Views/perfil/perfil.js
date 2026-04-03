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
