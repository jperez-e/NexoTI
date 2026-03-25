<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Tickets</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        h1 { margin: 0 0 6px; color: #1e3a8a; }
        p { margin: 0 0 16px; color: #475569; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dbe4f0; padding: 8px; text-align: left; }
        th { background: #eff6ff; color: #1d4ed8; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>Reporte de Tickets</h1>
    <p>Generado: <?php echo htmlspecialchars(date('Y-m-d H:i'), ENT_QUOTES, 'UTF-8'); ?></p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Codigo</th>
                <th>Titulo</th>
                <th>Usuario</th>
                <th>Tecnico</th>
                <th>Categoria</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th>Fecha Creacion</th>
                <th>Fecha Cierre</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
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
