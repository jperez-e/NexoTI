<?php
// Este archivo PHP define la vista de seguridad para cambio de contraseña.
// Renderiza el formulario protegido con CSRF y mensajes de resultado para el usuario autenticado.
declare(strict_types=1);

$mensajeFlash = $mensaje ?? $message ?? null;
$errorFlash = $error ?? null;
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1.0'>
    <title>NexoTI - Cambiar contraseña</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>
    <link rel='stylesheet' href='/NexoTI/Views/perfil/cambiar-clave.css?v=2'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/sidebar.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'>
    <script src='/NexoTI/Views/perfil/cambiar-clave.js?v=2' defer></script>
</head>
<body>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class='page'>
            <header class='page-header'>
                <div>
                    <h1>Seguridad de la cuenta</h1>
                    <p>Actualiza tu contraseña para mantener tu acceso protegido.</p>
                </div>
                <a class='btn ghost' href='/NexoTI/index.php?r=perfil'>Volver a perfil</a>
            </header>

            <section class='card security-card'>
                <?php if ($mensajeFlash !== null || $errorFlash !== null): ?>
                    <div class='flash-stack'>
                        <?php if ($mensajeFlash !== null): ?>
                            <p class='message success'><?php echo htmlspecialchars((string) $mensajeFlash, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if ($errorFlash !== null): ?>
                            <p class='message error'><?php echo htmlspecialchars((string) $errorFlash, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action='/NexoTI/index.php?r=cambiar-clave-update' method='post' class='security-form'>
                    <input type='hidden' name='_token' value='<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>

                    <label>
                        Contraseña actual
                        <div class='password-input-wrap'>
                            <input id='clave-actual' type='password' name='password_actual' autocomplete='current-password' required>
                            <button type='button' class='password-toggle' data-toggle-password data-target='clave-actual' aria-label='Mostrar u ocultar contraseña actual'>
                                <span class='icon-eye' aria-hidden='true'>
                                    <svg viewBox='0 0 24 24' role='img' focusable='false'>
                                        <path d='M12 5c5.2 0 9.5 3.6 11 7-1.5 3.4-5.8 7-11 7S2.5 15.4 1 12c1.5-3.4 5.8-7 11-7zm0 2.5c-3.6 0-6.7 2.1-8.2 4.5 1.5 2.4 4.6 4.5 8.2 4.5s6.7-2.1 8.2-4.5c-1.5-2.4-4.6-4.5-8.2-4.5zm0 1.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6z'></path>
                                    </svg>
                                </span>
                                <span class='icon-eye-off' aria-hidden='true'>
                                    <svg viewBox='0 0 24 24' role='img' focusable='false'>
                                        <path d='M4.5 3.5 2.9 5.1l3.1 3.1C3.5 9.6 1.9 11.7 1 13c1.5 3.4 5.8 7 11 7 1.8 0 3.5-.4 5-1.1l3 3 1.6-1.6-17.1-16.8zM12 18c-3.6 0-6.7-2.1-8.2-4.5.7-1.1 1.8-2.3 3.2-3.3l2 2A3 3 0 0 0 12 15a2.9 2.9 0 0 0 2-.8l1.6 1.6c-1 .4-2.2.6-3.6.6zm0-8.5c-.2 0-.5 0-.7.1l-2-2c.9-.3 1.8-.5 2.7-.5 3.6 0 6.7 2.1 8.2 4.5-.6.9-1.4 1.9-2.5 2.7l-2.1-2.1a3 3 0 0 0-2.6-2.7z'></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </label>

                    <label>
                        Nueva contraseña
                        <div class='password-input-wrap'>
                            <input id='clave-nueva' type='password' name='password_nueva' autocomplete='new-password' required>
                            <button type='button' class='password-toggle' data-toggle-password data-target='clave-nueva' aria-label='Mostrar u ocultar nueva contraseña'>
                                <span class='icon-eye' aria-hidden='true'>
                                    <svg viewBox='0 0 24 24' role='img' focusable='false'>
                                        <path d='M12 5c5.2 0 9.5 3.6 11 7-1.5 3.4-5.8 7-11 7S2.5 15.4 1 12c1.5-3.4 5.8-7 11-7zm0 2.5c-3.6 0-6.7 2.1-8.2 4.5 1.5 2.4 4.6 4.5 8.2 4.5s6.7-2.1 8.2-4.5c-1.5-2.4-4.6-4.5-8.2-4.5zm0 1.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6z'></path>
                                    </svg>
                                </span>
                                <span class='icon-eye-off' aria-hidden='true'>
                                    <svg viewBox='0 0 24 24' role='img' focusable='false'>
                                        <path d='M4.5 3.5 2.9 5.1l3.1 3.1C3.5 9.6 1.9 11.7 1 13c1.5 3.4 5.8 7 11 7 1.8 0 3.5-.4 5-1.1l3 3 1.6-1.6-17.1-16.8zM12 18c-3.6 0-6.7-2.1-8.2-4.5.7-1.1 1.8-2.3 3.2-3.3l2 2A3 3 0 0 0 12 15a2.9 2.9 0 0 0 2-.8l1.6 1.6c-1 .4-2.2.6-3.6.6zm0-8.5c-.2 0-.5 0-.7.1l-2-2c.9-.3 1.8-.5 2.7-.5 3.6 0 6.7 2.1 8.2 4.5-.6.9-1.4 1.9-2.5 2.7l-2.1-2.1a3 3 0 0 0-2.6-2.7z'></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </label>

                    <label>
                        Confirmar nueva contraseña
                        <div class='password-input-wrap'>
                            <input id='clave-confirmacion' type='password' name='password_confirmacion' autocomplete='new-password' required>
                            <button type='button' class='password-toggle' data-toggle-password data-target='clave-confirmacion' aria-label='Mostrar u ocultar confirmación de contraseña'>
                                <span class='icon-eye' aria-hidden='true'>
                                    <svg viewBox='0 0 24 24' role='img' focusable='false'>
                                        <path d='M12 5c5.2 0 9.5 3.6 11 7-1.5 3.4-5.8 7-11 7S2.5 15.4 1 12c1.5-3.4 5.8-7 11-7zm0 2.5c-3.6 0-6.7 2.1-8.2 4.5 1.5 2.4 4.6 4.5 8.2 4.5s6.7-2.1 8.2-4.5c-1.5-2.4-4.6-4.5-8.2-4.5zm0 1.5a3 3 0 1 1 0 6 3 3 0 0 1 0-6z'></path>
                                    </svg>
                                </span>
                                <span class='icon-eye-off' aria-hidden='true'>
                                    <svg viewBox='0 0 24 24' role='img' focusable='false'>
                                        <path d='M4.5 3.5 2.9 5.1l3.1 3.1C3.5 9.6 1.9 11.7 1 13c1.5 3.4 5.8 7 11 7 1.8 0 3.5-.4 5-1.1l3 3 1.6-1.6-17.1-16.8zM12 18c-3.6 0-6.7-2.1-8.2-4.5.7-1.1 1.8-2.3 3.2-3.3l2 2A3 3 0 0 0 12 15a2.9 2.9 0 0 0 2-.8l1.6 1.6c-1 .4-2.2.6-3.6.6zm0-8.5c-.2 0-.5 0-.7.1l-2-2c.9-.3 1.8-.5 2.7-.5 3.6 0 6.7 2.1 8.2 4.5-.6.9-1.4 1.9-2.5 2.7l-2.1-2.1a3 3 0 0 0-2.6-2.7z'></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </label>

                    <button type='submit' class='btn primary security-submit'>Actualizar contraseña</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
