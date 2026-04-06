<?php
// Este archivo PHP define una vista del sistema.
// Renderiza estructura HTML y datos dinámicos para la interfaz según el módulo y rol del usuario.
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
    <link rel='stylesheet' href='/NexoTI/Views/perfil/perfil.css?v=3'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/sidebar.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
 <link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'>
</head>
<body>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class='page'>
            <header class='page-header'>
                <div>
                    <h1>Ajustes de perfil</h1>
                    <p>Actualiza tu foto y consulta tus datos de cuenta.</p>
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
                    <section class='profile-block profile-block-data'>
                        <header class='profile-block-header'>
                            <h2>Datos de cuenta</h2>
                            <p>Información principal de tu usuario dentro de NexoTI.</p>
                        </header>
                        <div class='profile-data'>
                            <div class='profile-data-row'>
                                <span class='profile-data-label'>Nombre</span>
                                <strong class='profile-data-value'><?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div class='profile-data-row'>
                                <span class='profile-data-label'>Correo</span>
                                <strong class='profile-data-value'><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div class='profile-data-row'>
                                <span class='profile-data-label'>Rol</span>
                                <span class='role-badge'><?php echo htmlspecialchars($rol, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                    </section>

                    <section class='profile-block profile-block-note'>
                        <header class='profile-block-header'>
                            <h2>Seguridad</h2>
                            <p>El cambio de contraseña se gestiona desde el menú del avatar en la parte superior.</p>
                        </header>
                    </section>

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
                </div>
            </section>
        </main>
    </div>
</body>
</html>

