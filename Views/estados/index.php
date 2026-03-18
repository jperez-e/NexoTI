<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Estados</title> 
    <link rel='stylesheet' href='/NexoTI/Views/estados/estados.css'> 
    <script src='/NexoTI/Views/estados/estados.js' defer></script> 
</head> 
<body> 
    <div class='layout'> 
        <?php require __DIR__ . '/../partials/sidebar.php'; ?> 
        <main class='page'> 
            <header class='page-header'> 
                <div> 
                    <h1>Estados</h1> 
                    <p>Configura estados del ticket.</p> 
                </div> 
                <a class='btn' href='/NexoTI/index.php?r=home'>Volver al inicio</a> 
            </header> 
 
            <section class='card'> 
                <h2>Nuevo estado</h2> 
                <form id='estado-form'> 
                    <label> 
                        Nombre 
                        <input name='nombre' type='text' placeholder='Ej: En progreso' required> 
                    </label> 
                    <div class='actions'> 
                        <button type='submit' class='btn primary'>Guardar estado</button> 
                        <span id='form-message' class='message'></span> 
                    </div> 
                </form> 
            </section> 
 
            <section class='card'> 
                <div class='list-header'> 
                    <h2>Listado</h2> 
                    <button class='btn ghost' id='refresh-btn' type='button'>Actualizar</button> 
                </div> 
                <div id='estados-list' class='list'></div> 
            </section> 
        </main> 
    </div> 
</body> 
</html>
