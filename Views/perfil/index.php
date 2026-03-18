<?php
declare(strict_types=1);

$foto = (string) ($usuario['foto'] ?? '');
$fotoUrl = $foto !== '' ? '/NexoTI/' . ltrim($foto, '/') : '';
$nombre = (string) ($usuario['nombre'] ?? 'Usuario');
$email = (string) ($usuario['email'] ?? '');
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
    <link rel='stylesheet' href='/NexoTI/Views/perfil/perfil.css'>
</head>
<body>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class='page'>
            <header class='page-header'>
                <div>
                    <h1>Ajustes de perfil</h1>
                    <p>Actualiza tu foto para que aparezca en el sistema.</p>
                </div>
                <a class='btn ghost' href='/NexoTI/index.php?r=tickets'>Volver a tickets</a>
            </header>

            <section class='card profile-card'>
                <div class='avatar-panel'>
                    <?php if ($fotoUrl !== ''): ?>
                        <img class='avatar' src='<?php echo htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'); ?>' alt='Foto de perfil'>
                    <?php else: ?>
                        <div class='avatar avatar-fallback'><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                </div>

                <div class='profile-content'>
                    <div class='profile-data'>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?></p>
                        <p><strong>Correo:</strong> <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>

                    <?php if ($message !== null): ?>
                        <p class='message success'><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <?php if ($error !== null): ?>
                        <p class='message error'><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <form action='/NexoTI/index.php?r=perfil-update' method='post' enctype='multipart/form-data' class='profile-form'>
                        <label>
                            Foto de perfil
                            <input type='file' name='foto' accept='image/png,image/jpeg,image/webp' required>
                        </label>
                        <button type='submit' class='btn primary'>Guardar foto</button>
                    </form>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
