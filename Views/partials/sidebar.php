<?php
declare(strict_types=1);

$rolId = (int) ($_SESSION['rol_id'] ?? 0);
$isAdmin = $rolId === 1;
$route = (string) ($_GET['r'] ?? '');
$showSidebarLogout = !in_array($route, ['tickets', 'perfil'], true);

function sidebarIcon(string $name): string
{
    $icons = [
        'home' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 3 3 10v11h6v-7h6v7h6V10l-9-7z' fill='currentColor'></path></svg>",
        'tickets' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M4 6h16v5a2 2 0 0 0 0 4v5H4v-5a2 2 0 0 0 0-4V6zm4 4v4h8v-4H8z' fill='currentColor'></path></svg>",
        'perfil' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-4 0-7 2-7 4.5V21h14v-2.5C19 16 16 14 12 14z' fill='currentColor'></path></svg>",
        'usuarios' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.94 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z' fill='currentColor'></path></svg>",
        'categorias' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M3 7h8l2 2h8v10H3V7zm2 2v8h14V11h-7.17l-2-2H5z' fill='currentColor'></path></svg>",
        'prioridades' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 2 4 20h16L12 2zm0 5.2L16.4 18H7.6L12 7.2z' fill='currentColor'></path></svg>",
        'estados' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm-1 14-4-4 1.4-1.4L11 13.2l5.6-5.6L18 9z' fill='currentColor'></path></svg>",
        'roles' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5z' fill='currentColor'></path></svg>",
        'reportes' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M5 3h10l4 4v14H5V3zm9 1.5V8h3.5L14 4.5zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z' fill='currentColor'></path></svg>",
        'logout' => "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M10 17v-3H3v-4h7V7l5 5-5 5zm4-12h5v14h-5v2h7V3h-7v2z' fill='currentColor'></path></svg>",
    ];

    return $icons[$name] ?? '';
}
?>
<aside class='sidebar'>
    <input type='hidden' id='csrf-token' value='<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>
    <div class='brand'>
        <span class='brand-mark'>NT</span>
        <div class='brand-copy'>
            <strong>NexoTI</strong>
            <small>Mesa de ayuda</small>
        </div>
    </div>
    <nav class='nav'>
        <a class='nav-link<?php echo $route === 'home' || $route === '' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=home'><span class='nav-icon'><?php echo sidebarIcon('home'); ?></span><span>Inicio</span></a>
        <a class='nav-link<?php echo $route === 'tickets' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=tickets'><span class='nav-icon'><?php echo sidebarIcon('tickets'); ?></span><span>Tickets</span></a>
        <a class='nav-link<?php echo $route === 'perfil' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=perfil'><span class='nav-icon'><?php echo sidebarIcon('perfil'); ?></span><span>Mi perfil</span></a>
        <?php if ($isAdmin): ?>
            <a class='nav-link<?php echo $route === 'usuarios' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=usuarios'><span class='nav-icon'><?php echo sidebarIcon('usuarios'); ?></span><span>Usuarios</span></a>
            <a class='nav-link<?php echo $route === 'categorias' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=categorias'><span class='nav-icon'><?php echo sidebarIcon('categorias'); ?></span><span>Categorías</span></a>
            <a class='nav-link<?php echo $route === 'prioridades' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=prioridades'><span class='nav-icon'><?php echo sidebarIcon('prioridades'); ?></span><span>Prioridades</span></a>
            <a class='nav-link<?php echo $route === 'estados' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=estados'><span class='nav-icon'><?php echo sidebarIcon('estados'); ?></span><span>Estados</span></a>
            <a class='nav-link<?php echo $route === 'roles' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=roles'><span class='nav-icon'><?php echo sidebarIcon('roles'); ?></span><span>Roles</span></a>
            <a class='nav-link<?php echo $route === 'reportes' ? ' active' : ''; ?>' href='/NexoTI/index.php?r=reportes'><span class='nav-icon'><?php echo sidebarIcon('reportes'); ?></span><span>Reportes</span></a>
        <?php endif; ?>
    </nav>
    <?php if ($showSidebarLogout): ?>
        <a class='nav-link logout' href='/NexoTI/index.php?r=logout'><span class='nav-icon'><?php echo sidebarIcon('logout'); ?></span><span>Cerrar sesión</span></a>
    <?php endif; ?>
</aside>
