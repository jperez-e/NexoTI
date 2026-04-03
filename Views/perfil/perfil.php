<?php
declare(strict_types=1);

$foto = (string) ($usuario['foto'] ?? '');
$fotoUrl = $foto !== '' ? '/NexoTI/' . ltrim($foto, '/') : '';
$nombre = (string) ($usuario['nombre'] ?? 'Usuario');
$email = (string) ($usuario['email'] ?? '');
$rol = (string) ($usuario['rol_nombre'] ?? 'Sin rol');
$mensajeFlash = $mensaje ?? $message ?? null;
$errorFlash = $error ?? null;
$parts = preg_split('/\s+/', trim($nombre));
$initials = 'U';
if (is_array($parts) && count($parts) > 0 && $parts[0] !== '') {
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1 && $parts[1] !== '') {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1.0'>
    <title>NexoTI - Perfil</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>
    <link rel='stylesheet' href='/NexoTI/Views/perfil/perfil.css?v=2'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/sidebar.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
 <link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'>
    <script src='/NexoTI/Views/perfil/perfil.js?v=2' defer></script>
</head>
<body>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class='page'>
            <header class='page-header'>
                <div>
                    <h1>Ajustes de perfil</h1>
                    <p>Actualiza tu foto y contraseña de acceso.</p>
                </div>
                <a class='btn ghost' href='/NexoTI/index.php?r=tickets'>Volver a Tickets</a>
            </header>

            <section class='card profile-card'>
                <div class='avatar-panel'>
                    <?php if ($fotoUrl !== ''): ?>
                        <img class='avatar' src='<?php echo htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'); ?>' alt='Foto de perfil'>
                    <?php else: ?>
                        <div class='avatar avatar-fallback'><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <section class='profile-block profile-block-photo'>
                        <header class='profile-block-header'>
                            <h2>Foto de perfil</h2>
                            <p>Sube una imagen para identificar tu cuenta dentro del sistema.</p>
                        </header>
                        <form action='/NexoTI/index.php?r=perfil-update' method='post' enctype='multipart/form-data' class='profile-form'>
                            <input type='hidden' name='_token' value='<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>
                            <label>
                                Imagen (JPG, PNG o WEBP, máximo 2 MB)
                                <input type='file' name='foto' accept='image/png,image/jpeg,image/webp' required>
                            </label>
                            <button type='submit' class='btn primary profile-submit'>Guardar foto</button>
                        </form>
                    </section>
                </div>

                <div class='profile-content'>
                    <div class='profile-data'>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Correo:</strong> <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Rol:</strong> <span class='role-badge'><?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?></span></p>
                    </div>

                    <div class='flash-stack'>
                        <?php if ($mensajeFlash !== null): ?>
                            <p class='message success'><?php echo htmlspecialchars((string) $mensajeFlash, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if ($errorFlash !== null): ?>
                            <p class='message error'><?php echo htmlspecialchars((string) $errorFlash, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>

                    <section class='profile-block profile-block-security'>
                        <header class='profile-block-header'>
                            <h2>Seguridad</h2>
                            <p>Cambia tu contraseña para mantener protegida tu cuenta.</p>
                        </header>
                        <form action='/NexoTI/index.php?r=perfil-update' method='post' class='profile-form password-form'>
                            <input type='hidden' name='_token' value='<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>
                            <label>
                                Contraseña actual
                                <div class='password-input-wrap'>
                                    <input id='perfil-password-actual' type='password' name='password_actual' autocomplete='current-password' required>
                                    <button type='button' class='password-toggle' data-toggle-password data-target='perfil-password-actual' aria-label='Mostrar u ocultar contraseña actual'>
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
                                    <input id='perfil-password-nueva' type='password' name='password_nueva' autocomplete='new-password' required>
                                    <button type='button' class='password-toggle' data-toggle-password data-target='perfil-password-nueva' aria-label='Mostrar u ocultar nueva contraseña'>
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
                                    <input id='perfil-password-confirmacion' type='password' name='password_confirmacion' autocomplete='new-password' required>
                                    <button type='button' class='password-toggle' data-toggle-password data-target='perfil-password-confirmacion' aria-label='Mostrar u ocultar confirmación de contraseña'>
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
                            <button type='submit' class='btn primary profile-submit'>Actualizar contraseña</button>
                        </form>
                    </section>
                </div>
            </section>
        </main>
    </div>
</body>
</html>




