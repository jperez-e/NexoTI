<!DOCTYPE html>  
<html lang='es'>  
<head>  
    <meta charset='UTF-8'>  
    <meta name='viewport' content='width=device-width,initial-scale=1.0'>  
    <title>NexoTI - Inicio</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>  
    <link rel='stylesheet' href='/NexoTI/Views/home/home.css?v=3'>  
    <link rel='stylesheet' href='/NexoTI/Views/partials/sidebar.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
 <link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'>
</head>  
<body>  
    <div class='layout'>  
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>  
        <div class='content'>  
            <header class='topbar'>  
                <div class='logo'>NexoTI</div>  
                <div class='topbar-text'>  
                    <h1>Mesa de Ayuda TI</h1>  
                    <p>Panel principal del sistema de tickets</p>  
                </div>  
            </header>  
            <main class='home'>  
                <section class='hero'>  
                    <div class='hero-copy'>
                        <span class='hero-kicker'>Centro de operaciones</span>
                        <h2>Panel de control de soporte TI</h2>  
                        <p>Consulta el estado del servicio, revisa actividad reciente y accede rápido a los módulos principales.</p>
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
                <section class='summary summary-panels'>  
                    <div class='card action-card'>  
                        <h3>Flujo principal</h3>  
                        <ul>  
                            <li>Registrar incidencia y dar seguimiento al ticket</li>  
                            <li>Asignar y responder según el rol del usuario</li>  
                            <li>Confirmar solución y cerrar el caso</li>  
                        </ul>  
                    </div>  
                    <div class='card report-card'>  
                        <h3>Área de reportes</h3>  
                        <p>Consulta resúmenes, historial y exportaciones filtradas por tu área de trabajo.</p>
                        <a class='btn ghost' href='/NexoTI/index.php?r=reportes'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M5 3h10l4 4v14H5V3zm9 1.5V8h3.5L14 4.5zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z' fill='currentColor'></path></svg></span><span class='btn-label'>Abrir reportes</span></a>
                    </div>  
                </section>  
            </main>  
        </div>  
    </div>  
</body>  
</html> 
