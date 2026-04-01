<?php
declare(strict_types=1);

$userId = (int) ($_SESSION['user_id'] ?? 0);
$rolId = (int) ($_SESSION['rol_id'] ?? 0);
$isAdmin = $rolId === 1;
$isTech = $rolId === 2;
$isUser = $rolId === 3;
$nombreUsuario = (string) ($_SESSION['nombre'] ?? 'Usuario');
$rolNombre = (string) ($_SESSION['rol_nombre'] ?? ($isAdmin ? 'Admin' : ($isTech ? 'Técnico' : 'Usuario')));
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
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>
    <link rel='stylesheet' href='/NexoTI/Views/tickets/tickets.css?v=9'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/sidebar.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
 <link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'>
    <script src='/NexoTI/Views/partials/icons.js' defer></script>
    <script src='/NexoTI/Views/tickets/tickets.state.js?v=1' defer></script>
    <script src='/NexoTI/Views/tickets/tickets.api.js?v=1' defer></script>
    <script src='/NexoTI/Views/tickets/tickets.render.js?v=1' defer></script>
    <script src='/NexoTI/Views/tickets/tickets.events.js?v=1' defer></script>
</head>
<body
    data-role-id='<?php echo (int) $rolId; ?>'
    data-role-name='<?php echo htmlspecialchars($rolNombre, ENT_QUOTES, 'UTF-8'); ?>'
    data-user-name='<?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?>'
    data-user-photo='<?php echo htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'); ?>'
>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class='page'>
            <div class='topbar-shell'>
                <header class='topbar'>
                    <div class='search-bar'>
                        <span class='search-icon' aria-hidden='true'>
                            <svg viewBox='0 0 24 24' width='18' height='18' aria-hidden='true'>
                                <circle cx='11' cy='11' r='7' stroke='currentColor' stroke-width='2' fill='none'></circle>
                                <line x1='16.65' y1='16.65' x2='21' y2='21' stroke='currentColor' stroke-width='2'></line>
                            </svg>
                        </span>
                        <input id='ticket-search' type='search' placeholder='Buscar tickets, usuarios, estados...' autocomplete='off' aria-label='Buscar tickets'>
                    </div>
                    <div class='user-area'>
                        <button class='btn ghost notice-btn' id='notice-btn' type='button' aria-haspopup='dialog' aria-expanded='false' aria-controls='notice-panel'>
                            <span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm6-6V11a6 6 0 1 0-12 0v5l-2 2v1h16v-1l-2-2z' fill='currentColor'></path></svg></span>
                            <span class='btn-label'>Notificaciones</span>
                            <span class='notice-count' id='notice-count'>0</span>
                        </button>
                        <span class='user-name'><?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class='avatar-wrapper'>
                            <button class='avatar-btn' id='avatar-btn' type='button' aria-haspopup='menu' aria-expanded='false' aria-controls='avatar-menu' aria-label='Abrir menú de perfil'>
                                <?php if ($fotoUrl !== ''): ?>
                                    <img src='<?php echo htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'); ?>' alt='Avatar'>
                                <?php else: ?>
                                    <span class='avatar-initials'><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </button>
                            <div class='avatar-menu' id='avatar-menu' role='menu' aria-hidden='true'>
                                <a href='/NexoTI/index.php?r=perfil' role='menuitem'>Ajustes de perfil</a>
                                <a href='/NexoTI/index.php?r=logout' role='menuitem'>Cerrar sesión</a>
                            </div>
                        </div>
                    </div>
                </header>
                <div class='notice-panel' id='notice-panel' role='dialog' aria-live='polite' aria-hidden='true' tabindex='-1'></div>
            </div>
            <div class='page-body'>
                <header class='page-header'>
                    <div>
                        <h1>Tickets de Soporte</h1>
                        <p>Registra, asigna y gestiona solicitudes.</p>
                    </div>
                    <a class='btn' href='/NexoTI/index.php?r=home'><span class='btn-label'>Volver al inicio</span></a>
                </header>

                <section class='card'>
                    <h2>Crear ticket</h2>
                    <form id='ticket-form' enctype='multipart/form-data'>
                        <input type='hidden' name='usuario_id' value='<?php echo $userId; ?>'>
                        <div class='grid'>
                            <label>
                                Título
                                <input name='titulo' type='text' placeholder='Ej: Impresora sin conexión' required>
                            </label>
                            <label>
                                Categoría
                                <select name='categoria_id' id='categoria_id' required></select>
                            </label>
                            <label>
                                Prioridad
                                <select name='prioridad_id' id='prioridad_id' required></select>
                            </label>
                            <?php if (!$isUser): ?>
                            <label>
                                Estado
                                <select name='estado_id' id='estado_id' required></select>
                            </label>
                            <?php endif; ?>
                            <label>
                                Fecha de ocurrencia
                                <input name='fecha_ocurrencia' type='datetime-local'>
                            </label>
                        </div>
                        <label>
                            Descripción
                            <textarea name='descripcion' rows='4' placeholder='Describe el problema' required></textarea>
                        </label>
                        <label>
                        Adjuntar archivo
                        <input type='file' name='adjuntos[]' accept='image/*,.pdf,.doc,.docx' multiple>
                    </label>
                        <div class='actions'>
                            <button type='submit' class='btn primary'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zm-5 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm3-10H5V5h10v4z' fill='currentColor'></path></svg></span><span class='btn-label'>Guardar ticket</span></button>
                            <span id='form-message' class='message' role='status' aria-live='polite'></span>
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
                                Técnico
                                <select name='tecnico_id' id='assign_tecnico_id'></select>
                            </label>
                            <?php if (!$isUser): ?>
                            <label>
                                Estado
                                <select name='estado_id' id='assign_estado_id' required></select>
                            </label>
                            <?php endif; ?>
                        </div>
                        <div class='actions'>
                            <button type='submit' class='btn primary'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M20 11a8 8 0 1 1-2.34-5.66L20 8V3h-5l2.19 2.19A10 10 0 1 0 22 11h-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Actualizar ticket</span></button>
                            <span id='assign-message' class='message' role='status' aria-live='polite'></span>
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
                            <button type='submit' class='btn primary'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z' fill='currentColor'></path></svg></span><span class='btn-label'>Cerrar ticket</span></button>
                            <span id='close-message' class='message' role='status' aria-live='polite'></span>
                        </div>
                    </form>
                </section>
                <?php endif; ?>

                <section class='card'>
                    <div class='list-header'>
                        <div class='list-header-copy'>
                            <h2>Listado</h2>
                            <p id='results-info' class='results-info' aria-live='polite'></p>
                        </div>
                        <button class='btn ghost' id='refresh-btn' type='button'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M20 11a8 8 0 1 1-2.34-5.66L20 8V3h-5l2.19 2.19A10 10 0 1 0 22 11h-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Actualizar</span></button>
                    </div>
                    <div class='status-filters' id='status-filters' role='tablist' aria-label='Filtrar tickets por estado'>
                        <button class='status-filter is-active' type='button' data-status-filter='todos' aria-pressed='true'>Todos</button>
                        <button class='status-filter' type='button' data-status-filter='abierto' aria-pressed='false'>Abiertos</button>
                        <button class='status-filter' type='button' data-status-filter='en proceso' aria-pressed='false'>En progreso</button>
                        <button class='status-filter' type='button' data-status-filter='resuelto' aria-pressed='false'>Resueltos</button>
                        <button class='status-filter' type='button' data-status-filter='cerrado' aria-pressed='false'>Cerrados</button>
                    </div>
                    <div id='tickets-list' class='list'></div>
                    <div class='pager' id='tickets-pager'>
                        <button class='btn ghost' id='tickets-prev' type='button'>
                            <span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M15.4 7.4 14 6l-6 6 6 6 1.4-1.4L10.8 12z' fill='currentColor'></path></svg></span>
                            <span class='btn-label'>Anterior</span>
                        </button>
                        <span id='tickets-page-info' class='pager-info' aria-live='polite'></span>
                        <button class='btn ghost' id='tickets-next' type='button'>
                            <span class='btn-label'>Siguiente</span>
                            <span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='m8.6 16.6 1.4 1.4 6-6-6-6-1.4 1.4 4.6 4.6z' fill='currentColor'></path></svg></span>
                        </button>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>

