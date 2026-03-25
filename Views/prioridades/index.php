<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Prioridades</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'> 
    <link rel='stylesheet' href='/NexoTI/Views/prioridades/prioridades.css'> 
    <script src='/NexoTI/Views/prioridades/prioridades.js' defer></script> 
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
                <a class='btn' href='/NexoTI/index.php?r=home'>Volver al inicio</a> 
            </header> 
 
            <section class='card'> 
                <h2>Nueva prioridad</h2> 
                <form id='prioridad-form'> 
                    <label> 
                        Nombre 
                        <input name='nombre' type='text' placeholder='Ej: Alta' required> 
                    </label> 
                    <label> 
                        Nivel 
                        <input name='nivel' type='number' min='1' placeholder='1' required> 
                    </label> 
                    <div class='actions'> 
                        <button type='submit' class='btn primary'>Guardar prioridad</button> 
                        <span id='form-message' class='message'></span> 
                    </div> 
                </form> 
            </section> 
 
            <section class='card'> 
                <div class='list-header'> 
                    <h2>Listado</h2> 
                    <button class='btn ghost' id='refresh-btn' type='button'>Actualizar</button> 
                </div> 
                <div id='prioridades-list' class='list'></div> 
            </section> 
        </main> 
    </div> 
</body> 
</html>
