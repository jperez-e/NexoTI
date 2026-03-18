<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1.0'>
    <title>NexoTI - Reportes</title>
    <link rel='stylesheet' href='/NexoTI/Views/reportes/reportes.css'>
    <script src='/NexoTI/Views/reportes/reportes.js' defer></script>
</head>
<body>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <div class='content'>
            <header class='topbar'>
                <h1>Reportes y Exportacion</h1>
                <p>Consulta el resumen actual del sistema y descarga los reportes de tickets.</p>
            </header>
            <main class='reports'>
                <section class='stats-grid'>
                    <article class='stat-card'><span>Total tickets</span><strong id='stat-total'>0</strong></article>
                    <article class='stat-card'><span>Abiertos</span><strong id='stat-abiertos'>0</strong></article>
                    <article class='stat-card'><span>En progreso</span><strong id='stat-progreso'>0</strong></article>
                    <article class='stat-card'><span>Cerrados</span><strong id='stat-cerrados'>0</strong></article>
                </section>
                <section class='card'>
                    <h2>Reporte de Tickets</h2>
                    <p>Genera archivos.</p>
                    <div class='actions'>
                        <a class='btn primary' href='/NexoTI/api.php?c=reporte&m=ticketsCsv'>Descargar CSV</a>
                        <a class='btn' href='/NexoTI/api.php?c=reporte&m=ticketsPdf'>Descargar PDF</a>
                    </div>
                </section>
                <section class='card'>
                    <div class='section-head'><h2>Vista previa</h2><button class='btn ghost' id='reload-reportes' type='button'>Actualizar</button></div>
                    <div class='table-wrap'>
                        <table class='report-table'>
                            <thead><tr><th>Codigo</th><th>Titulo</th><th>Usuario</th><th>Tecnico</th><th>Estado</th></tr></thead>
                            <tbody id='report-preview'><tr><td colspan='5'>Cargando datos...</td></tr></tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
