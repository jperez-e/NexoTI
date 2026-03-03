<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Ticket - NexoTI</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, sans-serif; margin: 24px; background: #f6f8fa; }
        .card { max-width: 720px; background: #fff; padding: 18px; border: 1px solid #e5e7eb; border-radius: 8px; }
        h1 { margin-top: 0; color: #1f2937; }
        label { display: block; margin-top: 12px; margin-bottom: 6px; font-weight: 600; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
        textarea { min-height: 120px; resize: vertical; }
        .actions { margin-top: 16px; display: flex; gap: 8px; }
        button, a { padding: 10px 14px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; }
        button { background: #0f766e; color: #fff; }
        a { background: #e5e7eb; color: #111827; }
        .error { margin-top: 12px; color: #b91c1c; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Crear Ticket</h1>

        <form method="POST" action="index.php?c=ticket&m=store">
            <label for="titulo">Titulo</label>
            <input id="titulo" name="titulo" type="text" maxlength="120" required>

            <label for="descripcion">Descripcion</label>
            <textarea id="descripcion" name="descripcion" required></textarea>

            <label for="categoria_id">Categoria</label>
            <select id="categoria_id" name="categoria_id" required>
                <option value="">Seleccione...</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?php echo (int) $categoria["id"]; ?>">
                        <?php echo htmlspecialchars($categoria["nombre"], ENT_QUOTES, "UTF-8"); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="prioridad_id">Prioridad</label>
            <select id="prioridad_id" name="prioridad_id" required>
                <option value="">Seleccione...</option>
                <?php foreach ($prioridades as $prioridad): ?>
                    <option value="<?php echo (int) $prioridad["id"]; ?>">
                        <?php echo htmlspecialchars($prioridad["nombre"], ENT_QUOTES, "UTF-8"); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="actions">
                <button type="submit">Guardar</button>
                <a href="index.php?c=ticket&m=index">Cancelar</a>
            </div>
        </form>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
