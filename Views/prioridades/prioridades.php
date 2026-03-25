<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Prioridades</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>
    <link rel='stylesheet' href='/NexoTI/Views/prioridades/prioridades.css'>
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
    <script src='/NexoTI/Views/partials/icons.js' defer></script>
    <script src='/NexoTI/Views/prioridades/prioridades.js?v=2' defer></script>
</head>
<body> 
    <div class='layout'> 
        <?php require __DIR__ . '/../partials/sidebar.php'; ?> 
        <main class='page'> 
            <header class='page-header'> 
                <div> 
                    <h1>Prioridades</h1> 
                    <p>Configura niveles de prioridad.</p> 
                </div> 
                <a class='btn' href='/NexoTI/index.php?r=home'><span class='btn-label'>Volver al inicio</span></a> 
            </header> 
 
            <section class='card'> 
                <h2>Nueva prioridad</h2> 
                <form id='prioridad-form'> 
                    <label> 
                        Nombre 
                        <input id='nombre' name='nombre' type='text' placeholder='Ej: Alta' required> 
                    </label> 
                    <label> 
                        Nivel 
                        <input id='nivel' name='nivel' type='number' min='1' placeholder='1' required> 
                    </label> 
                    <div class='actions'> 
                        <button type='submit' class='btn primary' id='submit-btn'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zm-5 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm3-10H5V5h10v4z' fill='currentColor'></path></svg></span><span class='btn-label'>Guardar prioridad</span></button> 
                        <span id='form-message' class='message'></span> 
                    </div> 
                </form> 
            </section> 
 
            <section class='card'> 
                <div class='list-header'> 
                    <h2>Listado</h2> 
                    <button class='btn ghost' id='refresh-btn' type='button'><span class='btn-icon'><svg viewBox='0 0 24 24' aria-hidden='true'><path d='M20 11a8 8 0 1 1-2.34-5.66L20 8V3h-5l2.19 2.19A10 10 0 1 0 22 11h-2z' fill='currentColor'></path></svg></span><span class='btn-label'>Actualizar</span></button> 
                </div> 
                <div id='prioridades-list' class='list'></div> 
            </section> 
        </main> 
    </div> 
</body> 
</html>
