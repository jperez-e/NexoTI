<?php
// Este archivo PHP define una vista del sistema.
// Renderiza estructura HTML y datos dinámicos para la interfaz según el módulo y rol del usuario.
declare(strict_types=1);

$nombreUsuario = (string) ($_SESSION['nombre'] ?? 'Usuario');
$rolId = (int) ($_SESSION['rol_id'] ?? 0);
$rolNombre = (string) ($_SESSION['rol_nombre'] ?? ($rolId === 1 ? 'Admin' : ($rolId === 2 ? 'Técnico' : 'Usuario')));
$rolClaseTopbar = $rolId === 1 ? 'admin' : ($rolId === 2 ? 'tecnico' : 'usuario');
$foto = (string) ($_SESSION['foto'] ?? '');
$fotoUrl = $foto !== '' ? '/NexoTI/' . ltrim($foto, '/') : '';
$partesNombre = preg_split('/\s+/', trim($nombreUsuario));
$iniciales = 'U';
if (is_array($partesNombre) && count($partesNombre) > 0 && $partesNombre[0] !== '') {
    $iniciales = strtoupper(substr($partesNombre[0], 0, 1));
    if (count($partesNombre) > 1 && $partesNombre[1] !== '') {
        $iniciales .= strtoupper(substr($partesNombre[1], 0, 1));
    }
}
?>
<!DOCTYPE html>  
<html lang='es'>  
<head>  
    <meta charset='UTF-8'>  
    <meta name='viewport' content='width=device-width,initial-scale=1.0'>  
    <title>NexoTI - Inicio</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>  
    <link rel='stylesheet' href='/NexoTI/Views/home/home.css?v=5'>  
    <link rel='stylesheet' href='/NexoTI/Views/partials/sidebar.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
 <link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'>
    <script src='/NexoTI/Views/home/home.js?v=1' defer></script>
</head>  
<body>  
    <div class='layout'>  
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>  
        <div class='content'>  
            <header class='topbar'>  
                <div class='topbar-text'>  
                    <h1>Mesa de Ayuda TI</h1>  
                    <p>Panel principal del sistema de tickets</p>  
                </div>  
                <div class='topbar-actions'>
                    <button class='btn ghost notice-btn' id='notice-btn' type='button' aria-label='Notificaciones' aria-haspopup='dialog' aria-expanded='false' aria-controls='notice-panel'>
                        <span class='btn-icon'>
                            <svg viewBox='0 0 24 24' aria-hidden='true'>
                                <path d='M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22zm6-6V11a6 6 0 1 0-12 0v5l-2 2v1h16v-1l-2-2z' fill='currentColor'></path>
                            </svg>
                        </span>
                        <span class='notice-count hidden' id='notice-count' aria-hidden='true'>0</span>
                    </button>
                    <div class='user-area'>
                        <div class='user-identity'>
                            <span class='user-name'><?php echo htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class='role-badge role-<?php echo $rolClaseTopbar; ?> user-role-badge'><?php echo htmlspecialchars($rolNombre, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class='avatar-wrapper'>
                            <button class='avatar-btn' id='avatar-btn' type='button' aria-haspopup='menu' aria-expanded='false' aria-controls='avatar-menu' aria-label='Abrir menú de perfil'>
                                <?php if ($fotoUrl !== ''): ?>
                                    <img src='<?php echo htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8'); ?>' alt='Avatar'>
                                <?php else: ?>
                                    <span class='avatar-initials'><?php echo htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </button>
                            <div class='avatar-menu' id='avatar-menu' role='menu' aria-hidden='true'>
                                <a href='/NexoTI/index.php?r=perfil' role='menuitem'>Ajustes de perfil</a>
                                <a href='/NexoTI/index.php?r=cambiar-clave' role='menuitem'>Cambiar contraseña</a>
                                <a href='/NexoTI/index.php?r=logout' role='menuitem'>Cerrar sesión</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>  
            <div class='notice-panel' id='notice-panel' role='dialog' aria-live='polite' aria-hidden='true' tabindex='-1'></div>
            <main class='home'>  
                <section class='hero'>  
                    <div class='hero-copy'>
                        <span class='hero-kicker'>Centro de operaciones</span>
                        <h2>Panel de control de soporte TI</h2> 
                    </div>
                    <div class='hero-actions'>
                        <a class='btn primary' href='/NexoTI/index.php?r=tickets'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M4 6h16v5a2 2 0 0 0 0 4v5H4v-5a2 2 0 0 0 0-4V6zm4 4v4h8v-4H8z' fill='currentColor'></path></svg></span><span class='btn-label'>Ir a tickets</span></a>
                        <a class='btn ghost' href='/NexoTI/index.php?r=reportes'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M5 3h10l4 4v14H5V3zm9 1.5V8h3.5L14 4.5zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z' fill='currentColor'></path></svg></span><span class='btn-label'>Ver reportes</span></a>
                    </div>
                </section>  
                <section class='dashboard-section'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Dashboard</span>
                            <h3>Resumen operativo</h3>
                        </div>
                    </div>
                    <div class='summary summary-dashboard'>  
                        <article class='metric-card'>  
                            <span>Tickets abiertos</span>
                            <strong><?php echo (int) ($dashboard['abiertos'] ?? 0); ?></strong>
                        </article>
                        <article class='metric-card'>  
                            <span>En progreso</span>
                            <strong><?php echo (int) ($dashboard['en_progreso'] ?? 0); ?></strong>
                        </article>
                        <article class='metric-card'>  
                            <span>Cerrados hoy</span>
                            <strong><?php echo (int) ($dashboard['cerrados_hoy'] ?? 0); ?></strong>
                        </article>
                    </div>
                </section>
            </main>  
        </div>  
    </div>  
</body>  
</html> 
