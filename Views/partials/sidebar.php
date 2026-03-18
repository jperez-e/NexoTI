<?php  
$rolId = (int) ($_SESSION['rol_id'] ?? 0);  
$isAdmin = $rolId === 1;  
$route = (string) ($_GET['r'] ?? '');  
$showSidebarLogout = !in_array($route, ['tickets', 'perfil'], true);  
?>  
<aside class='sidebar'>  
    <div class='brand'>NexoTI</div>  
    <nav class='nav'>  
        <a class='nav-link' href='/NexoTI/index.php?r=home'>Inicio</a>  
        <a class='nav-link' href='/NexoTI/index.php?r=tickets'>Tickets</a>  
        <a class='nav-link' href='/NexoTI/index.php?r=perfil'>Mi perfil</a>  
        <?php if ($isAdmin): ?>  
            <a class='nav-link' href='/NexoTI/index.php?r=categorias'>Categorias</a>  
            <a class='nav-link' href='/NexoTI/index.php?r=prioridades'>Prioridades</a>  
            <a class='nav-link' href='/NexoTI/index.php?r=estados'>Estados</a>  
            <a class='nav-link' href='/NexoTI/index.php?r=roles'>Roles</a>  
            <a class='nav-link' href='/NexoTI/index.php?r=usuarios'>Usuarios</a>  
            <a class='nav-link' href='/NexoTI/index.php?r=usuarios#usuario-form'>Registrar usuario</a>  
            <a class='nav-link' href='/NexoTI/index.php?r=reportes'>Reportes</a>  
        <?php endif; ?>  
    </nav>  
    <?php if ($showSidebarLogout): ?>  
        <a class='nav-link logout' href='/NexoTI/index.php?r=logout'>Cerrar sesion</a>  
    <?php endif; ?>  
</aside> 
