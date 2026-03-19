<!DOCTYPE html>  
<html lang=es>  
<head>  
    <meta charset=UTF-8>  
    <meta name=viewport content=width=device-width,initial-scale=1.0>  
    <title>NexoTI | Iniciar sesión</title>  
    <link rel=stylesheet href=/NexoTI/Views/auth/login/login.css> 
    <script src=/NexoTI/Views/auth/login/login.js defer></script> 
</head>  
<body>  
    <div class=card>  
        <section class=brand>  
            <div class=logo>NexoTI</div>  
            <h1>Mesa de Ayuda TI</h1>  
            <p>Gestiona incidencias, asignaciones y soluciones desde un solo lugar.</p>  
        </section>  
        <section class=form>  
            <h2>Iniciar sesión</h2>  
            <p>Ingresa tus datos para acceder al sistema.</p>  
            <?php if (!empty($_SESSION['flash_error'])) : ?>  
                <div class=error id=login-error><?php echo htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8'); ?></div>  
                <?php unset($_SESSION['flash_error']); ?>  
            <?php endif; ?>  
            <form method=POST action=index.php?r=login>  
                <input type=hidden name=_token value='<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>
                <label for=login>Correo o usuario</label>  
                <input id=login name=login type=text placeholder=correo@dominio.com required>  
                <label for=password>Contraseña</label>  
                <div class=password-field>  
                    <input id=password name=password type=password required>  
                    <button type=button class=toggle-password id=toggle-password aria-label='Mostrar u ocultar contraseña'>  
                        <span class=icon-eye aria-hidden=true>  
                            <svg viewBox='0 0 24 24' role='img' focusable='false'>  
                                <path d='M12 5c5.2 0 9.5 3.6 11 7-1.5 3.4-5.8 7-11 7S2.5 15.4 1 12c1.5-3.4 5.8-7 11-7zm0 2.5c-3.6 0-6.7 2.1-8.2 4.5 1.5 2.4 4.6 4.5 8.2 4.5s6.7-2.1 8.2-4.5c-1.5-2.4-4.6-4.5-8.2-4.5zm0 1.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6z'></path>  
                            </svg>  
                        </span> 
                        <span class=icon-eye-off aria-hidden=true>  
                            <svg viewBox='0 0 24 24' role='img' focusable='false'>  
                                <path d='M4.5 3.5 2.9 5.1l3.1 3.1C3.5 9.6 1.9 11.7 1 13c1.5 3.4 5.8 7 11 7 1.8 0 3.5-.4 5-1.1l3 3 1.6-1.6-17.1-16.8zM12 18c-3.6 0-6.7-2.1-8.2-4.5.7-1.1 1.8-2.3 3.2-3.3l2 2A3 3 0 0 0 12 15a2.9 2.9 0 0 0 2-.8l1.6 1.6c-1 .4-2.2.6-3.6.6zm0-8.5c-.2 0-.5 0-.7.1l-2-2c.9-.3 1.8-.5 2.7-.5 3.6 0 6.7 2.1 8.2 4.5-.6.9-1.4 1.9-2.5 2.7l-2.1-2.1a3 3 0 0 0-2.6-2.7z'></path>  
                            </svg>  
                        </span>  
                    </button>  
                </div>  
                <button type=submit>Ingresar</button>  
            </form> 
            <button type=button class=m365-btn aria-disabled=true>
               <span class=m365-icon aria-hidden=true></span>
                Entrar con M365/Microsoft
             </button>
            <div class=register>Solicita tu usuario al administrador del sistema.</div>  
        </section>  
    </div>  
</body>  
</html> 
