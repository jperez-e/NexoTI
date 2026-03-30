<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1.0'>
    <title>NexoTI - Reportes</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>
    <link rel='stylesheet' href='/NexoTI/Views/reportes/reportes.css?v=3'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/sidebar.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
    <script src='/NexoTI/Views/reportes/reportes.js?v=3' defer></script>
</head>
<body>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <div class='content'>
            <header class='topbar'>
                <h1>Reportes y Exportacion</h1>
                <p>Consulta el resumen historico, revisa el historial reciente y descarga reportes listos para PDF, CSV y Excel.</p>
            </header>
            <main class='reports'>
                <section class='hero-card'>
                    <div>
                        <span class='section-kicker'>Dashboard</span>
                        <h2>Estado general del servicio</h2>
                        <p>Este bloque resume el comportamiento actual de la mesa de ayuda sin mezclarlo con la descarga de archivos.</p>
                    </div>
                    <a class='btn ghost' href='/NexoTI/index.php?r=home'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 3 3 10v11h6v-7h6v7h6V10l-9-7z' fill='currentColor'></path></svg></span><span class='btn-label'>Volver al inicio</span></a>
                </section>
                <section class='stats-grid'>
                    <article class='stat-card'><span>Total tickets</span><strong id='stat-total'>0</strong></article>
                    <article class='stat-card'><span>Abiertos</span><strong id='stat-abiertos'>0</strong></article>
                    <article class='stat-card'><span>En progreso</span><strong id='stat-progreso'>0</strong></article>
                    <article class='stat-card'><span>Cerrados</span><strong id='stat-cerrados'>0</strong></article>
                </section>
                <section class='card export-card'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Exportacion</span>
                            <h2>Archivos descargables</h2>
                        </div>
                    </div>
                    <p>Los reportes usan una sola fuente de datos y ahora incluyen opciones pensadas para abrir en Excel o compartir en PDF.</p>
                    <div class='actions'>
                        <a class='btn primary' href='/NexoTI/api.php?c=reporte&m=ticketsCsv'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 16 7 11h3V4h4v7h3l-5 5zm-7 2h14v2H5v-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Descargar CSV</span></a>
                        <a class='btn ghost' href='/NexoTI/api.php?c=reporte&m=ticketsExcel'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M5 3h10l4 4v14H5V3zm9 1.5V8h3.5L14 4.5zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z' fill='currentColor'></path></svg></span><span class='btn-label'>Descargar Excel</span></a>
                        <a class='btn' href='/NexoTI/api.php?c=reporte&m=ticketsPdf'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M6 2h9l5 5v15H6V2zm8 1.5V8h4.5L14 3.5zM9 13h6v2H9v-2zm0 4h6v2H9v-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Descargar PDF</span></a>
                    </div>
                </section>
                <section class='card'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Historial</span>
                            <h2>Historial reciente de tickets</h2>
                        </div>
                        <button class='btn ghost' id='reload-reportes' type='button'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M20 11a8 8 0 1 1-2.34-5.66L20 8V3h-5l2.19 2.19A10 10 0 1 0 22 11h-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Actualizar</span></button>
                    </div>
                    <div class='table-wrap'>
                        <table class='report-table'>
                            <thead><tr><th>Codigo</th><th>Titulo</th><th>Usuario</th><th>Tecnico</th><th>Estado</th><th>Fecha</th></tr></thead>
                            <tbody id='report-preview'><tr><td colspan='6'>Cargando datos...</td></tr></tbody>
                        </table>
                    </div>
                    <div class='pager' id='reportes-pager'>
                        <button class='btn ghost' id='reportes-prev' type='button'>
                            <span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M15.4 7.4 14 6l-6 6 6 6 1.4-1.4L10.8 12z' fill='currentColor'></path></svg></span>
                            <span class='btn-label'>Anterior</span>
                        </button>
                        <span id='reportes-page-info' class='pager-info' aria-live='polite'></span>
                        <button class='btn ghost' id='reportes-next' type='button'>
                            <span class='btn-label'>Siguiente</span>
                            <span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='m8.6 16.6 1.4 1.4 6-6-6-6-1.4 1.4 4.6 4.6z' fill='currentColor'></path></svg></span>
                        </button>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
