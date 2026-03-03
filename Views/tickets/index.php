<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexoTI - Tickets</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, sans-serif; margin: 24px; background: #f6f8fa; }
        h1 { margin-bottom: 16px; color: #1f2937; }
        a.button { display: inline-block; padding: 10px 14px; background: #0f766e; color: #fff; text-decoration: none; border-radius: 6px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; }
        th { background: #111827; color: #fff; }
        .empty { padding: 18px; background: #fff; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <h1>NexoTI - Sistema de Tickets</h1>
    <a class="button" href="index.php?c=ticket&m=create">Nuevo Ticket</a>

    <?php if (count($tickets) === 0): ?>
        <div class="empty">No hay tickets registrados.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Codigo</th>
                    <th>Titulo</th>
                    <th>Descripcion</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?php echo (int) $ticket["id"]; ?></td>
                        <td><?php echo htmlspecialchars($ticket["codigo"], ENT_QUOTES, "UTF-8"); ?></td>
                        <td><?php echo htmlspecialchars($ticket["titulo"], ENT_QUOTES, "UTF-8"); ?></td>
                        <td><?php echo htmlspecialchars($ticket["descripcion"], ENT_QUOTES, "UTF-8"); ?></td>
                        <td><?php echo htmlspecialchars($ticket["prioridad"], ENT_QUOTES, "UTF-8"); ?></td>
                        <td><?php echo htmlspecialchars($ticket["estado"], ENT_QUOTES, "UTF-8"); ?></td>
                        <td><?php echo htmlspecialchars($ticket["fecha_creacion"], ENT_QUOTES, "UTF-8"); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
