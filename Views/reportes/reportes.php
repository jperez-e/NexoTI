<?php
declare(strict_types=1);

$idRolActual = (int) ($_SESSION['rol_id'] ?? 0);
$etiquetaRol = $idRolActual === 1 ? 'Administrador' : ($idRolActual === 2 ? 'Técnico' : 'Usuario');
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width,initial-scale=1.0'>
    <title>NexoTI - Reportes</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>
    <link rel='stylesheet' href='/NexoTI/Views/reportes/reportes.css?v=6'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/sidebar.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'>
    <script src='/NexoTI/Views/reportes/reportes.js?v=6' defer></script>
</head>
<body data-rol-id='<?php echo $idRolActual; ?>'>
    <div class='layout'>
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>
        <div class='content'>
            <header class='topbar'>
                <h1>Reportes y Exportación</h1>
                <p>Vista para <strong id='role-label'><?php echo htmlspecialchars($etiquetaRol, ENT_QUOTES, 'UTF-8'); ?></strong>. Los datos se filtran automáticamente según tu alcance.</p>
            </header>
            <main class='reports'>
                <section class='hero-card'>
                    <div>
                        <span class='section-kicker'>Dashboard</span>
                        <h2>Indicadores operativos por rol</h2>
                        <p>Analiza volumen, tiempos de atención, distribución por estado y evolución mensual de tickets.</p>
                    </div>
                    <a class='btn ghost' href='/NexoTI/index.php?r=home'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 3 3 10v11h6v-7h6v7h6V10l-9-7z' fill='currentColor'></path></svg></span><span class='btn-label'>Volver al inicio</span></a>
                </section>

                <section class='card filters-card'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Filtros</span>
                            <h2>Rango de fechas</h2>
                        </div>
                    </div>
                    <div class='filter-grid'>
                        <label>
                            Desde
                            <input id='filtro-desde' type='date'>
                        </label>
                        <label>
                            Hasta
                            <input id='filtro-hasta' type='date'>
                        </label>
                    </div>
                    <div class='actions'>
                        <button class='btn primary' id='aplicar-filtro' type='button'><span class='btn-label'>Aplicar filtro</span></button>
                        <button class='btn ghost' id='limpiar-filtro' type='button'><span class='btn-label'>Limpiar filtro</span></button>
                    </div>
                </section>

                <section class='stats-grid'>
                    <article class='stat-card'><span>Total tickets</span><strong id='stat-total'>0</strong></article>
                    <article class='stat-card'><span>Abiertos</span><strong id='stat-abiertos'>0</strong></article>
                    <article class='stat-card'><span>En progreso</span><strong id='stat-progreso'>0</strong></article>
                    <article class='stat-card'><span>Cerrados</span><strong id='stat-cerrados'>0</strong></article>
                </section>

                <section class='stats-grid stats-grid-secondary'>
                    <article class='stat-card'><span>Resueltos</span><strong id='stat-resueltos'>0</strong></article>
                    <article class='stat-card'><span>Promedio resolución (h)</span><strong id='stat-prom-resolucion'>0.00</strong></article>
                    <article class='stat-card'><span>Primera respuesta (h)</span><strong id='stat-prom-primera'>0.00</strong></article>
                    <article class='stat-card'><span>Backlog pendiente</span><strong id='stat-backlog'>0</strong></article>
                </section>

                <section class='card export-card'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Exportación</span>
                            <h2>Descargas del área actual</h2>
                        </div>
                    </div>
                    <p>Los archivos incluyen solo tickets visibles para tu rol y respetan el rango de fechas seleccionado.</p>
                    <div class='actions'>
                        <a class='btn primary' id='export-csv' href='/NexoTI/api.php?c=reporte&m=ticketsCsv'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M12 16 7 11h3V4h4v7h3l-5 5zm-7 2h14v2H5v-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Descargar CSV</span></a>
                        <a class='btn ghost' id='export-excel' href='/NexoTI/api.php?c=reporte&m=ticketsExcel'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M5 3h10l4 4v14H5V3zm9 1.5V8h3.5L14 4.5zM8 12h8v2H8v-2zm0 4h8v2H8v-2zm0-8h5v2H8V8z' fill='currentColor'></path></svg></span><span class='btn-label'>Descargar Excel</span></a>
                        <a class='btn' id='export-pdf' href='/NexoTI/api.php?c=reporte&m=ticketsPdf'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M6 2h9l5 5v15H6V2zm8 1.5V8h4.5L14 3.5zM9 13h6v2H9v-2zm0 4h6v2H9v-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Descargar PDF</span></a>
                    </div>
                </section>

                <section class='card insight-card'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Distribución</span>
                            <h2>Estados, prioridades y categorías</h2>
                        </div>
                        <button class='btn ghost' id='reload-reportes' type='button'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M20 11a8 8 0 1 1-2.34-5.66L20 8V3h-5l2.19 2.19A10 10 0 1 0 22 11h-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Actualizar</span></button>
                    </div>
                    <div class='insight-grid'>
                        <article class='mini-table'>
                            <h3>Estados</h3>
                            <table class='report-table'>
                                <thead><tr><th>Estado</th><th>Total</th></tr></thead>
                                <tbody id='report-estados'><tr><td colspan='2'>Cargando...</td></tr></tbody>
                            </table>
                        </article>
                        <article class='mini-table'>
                            <h3>Prioridades</h3>
                            <table class='report-table'>
                                <thead><tr><th>Prioridad</th><th>Total</th></tr></thead>
                                <tbody id='report-prioridades'><tr><td colspan='2'>Cargando...</td></tr></tbody>
                            </table>
                        </article>
                        <article class='mini-table'>
                            <h3>Categorías</h3>
                            <table class='report-table'>
                                <thead><tr><th>Categoría</th><th>Total</th></tr></thead>
                                <tbody id='report-categorias'><tr><td colspan='2'>Cargando...</td></tr></tbody>
                            </table>
                        </article>
                        <article class='mini-table'>
                            <h3>Backlog por prioridad</h3>
                            <table class='report-table'>
                                <thead><tr><th>Prioridad</th><th>Pendientes</th></tr></thead>
                                <tbody id='report-backlog'><tr><td colspan='2'>Cargando...</td></tr></tbody>
                            </table>
                        </article>
                    </div>
                </section>

                <section class='card'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Tendencia</span>
                            <h2>Comportamiento mensual (últimos 12 meses)</h2>
                        </div>
                    </div>
                    <div class='table-wrap'>
                        <table class='report-table'>
                            <thead><tr><th>Periodo</th><th>Total tickets</th></tr></thead>
                            <tbody id='report-tendencia'><tr><td colspan='2'>Cargando...</td></tr></tbody>
                        </table>
                    </div>
                </section>

                <section class='card hidden' id='tecnicos-section'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Productividad</span>
                            <h2>Rendimiento por técnico</h2>
                        </div>
                    </div>
                    <div class='table-wrap'>
                        <table class='report-table'>
                            <thead><tr><th>Técnico</th><th>Asignados</th><th>Cerrados</th><th>Promedio resolución (h)</th></tr></thead>
                            <tbody id='report-tecnicos'><tr><td colspan='4'>Cargando...</td></tr></tbody>
                        </table>
                    </div>
                </section>

                <section class='card'>
                    <div class='section-head'>
                        <div>
                            <span class='section-kicker'>Historial</span>
                            <h2>Historial reciente de tickets</h2>
                        </div>
                    </div>
                    <div class='table-wrap'>
                        <table class='report-table'>
                            <thead><tr><th>Código</th><th>Título</th><th>Usuario</th><th>Técnico</th><th>Estado</th><th>Fecha</th></tr></thead>
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
