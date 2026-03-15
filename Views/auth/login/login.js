
        const toggle = document.getElementById('toggle-password');  
        const passwordInput = document.getElementById('password');  
        if (toggle) {  
            toggle.addEventListener('click', function () {  
                if (!passwordInput) { return; }  
                const isPassword = passwordInput.type === 'password';  
                passwordInput.type = isPassword ? 'text' : 'password';  
                toggle.classList.toggle('is-visible', isPassword);  
            });  
        }  