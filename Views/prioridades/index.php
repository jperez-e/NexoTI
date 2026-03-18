<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>NexoTI | Prioridades</title>
    <link rel="stylesheet" href="/NexoTI/Views/prioridades/prioridades.css">
    <script src="/NexoTI/Views/prioridades/prioridades.js" defer></script>
</head>
<body>
    <main class="page">
        <header class="page-header">
            <div>
                <h1>Prioridades</h1>
                <p>Define niveles de atencion.</p>
            </div>
            <a class="btn" href="/NexoTI/index.php?r=home">Volver al inicio</a>
        </header>

        <section class="card">
            <h2>Nueva prioridad</h2>
            <form id="prioridad-form">
                <div class="grid">
                    <label>
                        Nombre
                        <input name="nombre" type="text" placeholder="Ej: Alta" required>
                    </label>
                    <label>
                        Nivel
                        <input name="nivel" type="number" min="1" placeholder="1" required>
                    </label>
                </div>
                <div class="actions">
                    <button type="submit" class="btn primary">Guardar prioridad</button>
                    <span id="form-message" class="message"></span>
                </div>
            </form>
        </section>

        <section class="card">
            <div class="list-header">
                <h2>Listado</h2>
                <button class="btn ghost" id="refresh-btn" type="button">Actualizar</button>
            </div>
            <div id="prioridades-list" class="list"></div>
        </section>
    </main>
</body>
</html>
