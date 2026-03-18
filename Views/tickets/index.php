<?php
declare(strict_types=1);

$userId = (int) ($_SESSION['user_id'] ?? 0);
$rolId = (int) ($_SESSION['rol_id'] ?? 0);
$isAdmin = $rolId === 1;
$isTech = $rolId === 2;
$isUser = $rolId === 3;
$nombreUsuario = (string) ($_SESSION['nombre'] ?? 'Usuario');
$foto = (string) ($_SESSION['foto'] ?? '');
$fotoUrl = $foto !== '' ? '/NexoTI/' . ltrim($foto, '/') : '';
$parts = preg_split('/\s+/', trim($nombreUsuario));
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
    <title>NexoTI - Tickets</title>
    <link rel='stylesheet' href='/NexoTI/Views/tickets/tickets.css'>
    <script src='/NexoTI/Views/tickets/tickets.js' defer></script>
</head>
<body>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class='page'>
            <header class='topbar'>
                <div class='search-bar'>
                    <span class='search-icon' aria-hidden='true'>
                        <svg viewBox='0 0 24 24' width='18' height='18' aria-hidden='true'>
                            <circle cx='11' cy='11' r='7' stroke='currentColor' stroke-width='2' fill='none'></circle>
                            <line x1='16.65' y1='16.65' x2='21' y2='21' stroke='currentColor' stroke-width='2'></line>
                        </svg>
                    </span>
                    <input id='ticket-search' type='search' placeholder='Buscar tickets, usuarios, estados...' autocomplete='off'>
                </div>
                <div class='user-area'>
                    <span class='user-name'><?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class='avatar-wrapper'>
                        <button class='avatar-btn' id='avatar-btn' type='button' aria-haspopup='true' aria-expanded='false'>
                            <?php if ($fotoUrl !== ''): ?>
                                <img src='<?php echo htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'); ?>' alt='Avatar'>
                            <?php else: ?>
                                <span class='avatar-initials'><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </button>
                        <div class='avatar-menu' id='avatar-menu'>
                            <a href='/NexoTI/index.php?r=perfil'>Ajustes de perfil</a>
                            <a href='/NexoTI/index.php?r=logout'>Cerrar sesion</a>
                        </div>
                    </div>
                </div>
            </header>

            <header class='page-header'>
                <div>
                    <h1>Tickets de Soporte</h1>
                    <p>Registra, asigna y gestiona solicitudes.</p>
                </div>
                <a class='btn' href='/NexoTI/index.php?r=home'>Volver al inicio</a>
            </header>

            <section class='card'>
                <h2>Crear ticket</h2>
                <form id='ticket-form' enctype='multipart/form-data'>
                    <input type='hidden' name='usuario_id' value='<?php echo $userId; ?>'>
                    <div class='grid'>
                        <label>
                            Titulo
                            <input name='titulo' type='text' placeholder='Ej: Impresora sin conexion' required>
                        </label>
                        <label>
                            Categoria
                            <select name='categoria_id' id='categoria_id' required></select>
                        </label>
                        <label>
                            Prioridad
                            <select name='prioridad_id' id='prioridad_id' required></select>
                        </label>
                        <label>
                            Estado
                            <select name='estado_id' id='estado_id' required></select>
                        </label>
                    </div>
                    <label>
                        Descripcion
                        <textarea name='descripcion' rows='4' placeholder='Describe el problema' required></textarea>
                    </label>
                    <label>
                        Adjuntar archivo
                        <input type='file' name='adjunto' accept='image/*,.pdf,.doc,.docx'>
                    </label>
                    <div class='actions'>
                        <button type='submit' class='btn primary'>Guardar ticket</button>
                        <span id='form-message' class='message'></span>
                    </div>
                </form>
            </section>

            <?php if ($isAdmin): ?>
            <section class='card'>
                <h2>Asignar ticket</h2>
                <form id='assign-form'>
                    <div class='grid'>
                        <label>
                            Ticket
                            <select name='ticket_id' id='assign_ticket_id' required></select>
                        </label>
                        <label>
                            Tecnico
                            <select name='tecnico_id' id='assign_tecnico_id'></select>
                        </label>
                        <label>
                            Estado
                            <select name='estado_id' id='assign_estado_id' required></select>
                        </label>
                    </div>
                    <div class='actions'>
                        <button type='submit' class='btn primary'>Actualizar ticket</button>
                        <span id='assign-message' class='message'></span>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <?php if ($isTech): ?>
            <section class='card'>
                <h2>Actualizar estado</h2>
                <form id='status-form'>
                    <div class='grid'>
                        <label>
                            Ticket
                            <select name='ticket_id' id='status_ticket_id' required></select>
                        </label>
                        <label>
                            Estado
                            <select name='estado_id' id='status_estado_id' required></select>
                        </label>
                    </div>
                    <div class='actions'>
                        <button type='submit' class='btn primary'>Actualizar estado</button>
                        <span id='status-message' class='message'></span>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <?php if ($isUser): ?>
            <section class='card'>
                <h2>Confirmar cierre</h2>
                <form id='close-form'>
                    <label>
                        Ticket
                        <select name='ticket_id' id='close_ticket_id' required></select>
                    </label>
                    <div class='actions'>
                        <button type='submit' class='btn primary'>Cerrar ticket</button>
                        <span id='close-message' class='message'></span>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <section class='card'>
                <div class='list-header'>
                    <h2>Listado</h2>
                    <button class='btn ghost' id='refresh-btn' type='button'>Actualizar</button>
                </div>
                <div id='tickets-list' class='list'></div>
            </section>
        </main>
    </div>
</body>
</html>
