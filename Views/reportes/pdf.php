<?php
// Este archivo PHP genera un reporte en formato PDF de los tickets registrados en el sistema, 
// utilizando una plantilla HTML que incluye estilos CSS para formatear la presentación del reporte. 
// El reporte incluye información como el código, título, usuario, técnico asignado, categoría, prioridad, 
// estado, fecha de creación y fecha de cierre de cada ticket. Además, se incluye el logo de la 
// aplicación si está disponible.
declare(strict_types=1);

$logoPath = dirname(__DIR__, 2) . '/favicon.svg';
$logoData = '';
if (is_file($logoPath)) {
    $logoData = 'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents($logoPath));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Tickets</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        .header { display: table; width: 100%; margin-bottom: 18px; }
        .header-cell { display: table-cell; vertical-align: middle; }
        .logo-wrap { width: 74px; }
        .logo-wrap img { width: 58px; height: 58px; }
        h1 { margin: 0 0 6px; color: #1e3a8a; }
        p { margin: 0 0 16px; color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dbe4f0; padding: 8px; text-align: left; }
        th { background: #eff6ff; color: #1d4ed8; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="header">
        <?php if ($logoData !== ''): ?>
            <div class="header-cell logo-wrap">
                <img src="<?php echo htmlspecialchars($logoData, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo NexoTI">
            </div>
        <?php endif; ?>
        <div class="header-cell">
            <h1>Reporte de Tickets</h1>
            <p>Generado: <?php echo htmlspecialchars(date('Y-m-d H:i'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Título</th>
                <th>Usuario</th>
                <th>Técnico</th>
                <th>Categoría</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th>Fecha Creacion</th>
                <th>Fecha Cierre</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string) $row['codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $row['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $row['usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($row['tecnico'] ?? 'Sin asignar'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $row['categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $row['prioridad'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $row['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) $row['fecha_creacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string) ($row['fecha_cierre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
