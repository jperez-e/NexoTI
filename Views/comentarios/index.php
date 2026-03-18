<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Comentarios</title> 
    <link rel='stylesheet' href='/NexoTI/Views/comentarios/comentarios.css'> 
    <script src='/NexoTI/Views/comentarios/comentarios.js' defer></script> 
</head> 
<body> 
    <div class='layout'> 
        <?php require __DIR__ . '/../partials/sidebar.php'; ?> 
        <main class='page'> 
            <header class='page-header'> 
                <div> 
                    <h1>Comentarios</h1> 
                    <p>Gestiona comentarios asociados a tickets.</p> 
                </div> 
                <a class='btn' href='/NexoTI/index.php?r=home'>Volver al inicio</a> 
            </header> 
 
            <section class='card'> 
                <h2>Nuevo comentario</h2> 
                <form id='comentario-form'> 
                    <label> 
                        Ticket 
                        <select name='ticket_id' id='ticket_id' required></select> 
                    </label> 
                    <label> 
                        Usuario 
                        <select name='usuario_id' id='usuario_id' required></select> 
                    </label> 
                    <label> 
                        Comentario 
                        <textarea name='comentario' rows='3' placeholder='Describe el comentario' required></textarea> 
                    </label> 
                    <div class='actions'> 
                        <button type='submit' class='btn primary'>Guardar comentario</button> 
                        <span id='form-message' class='message'></span> 
                    </div> 
                </form> 
            </section> 
 
            <section class='card'> 
                <div class='list-header'> 
                    <h2>Listado</h2> 
                    <button class='btn ghost' id='refresh-btn' type='button'>Actualizar</button> 
                </div> 
                <div id='comentarios-list' class='list'></div> 
            </section> 
        </main> 
    </div> 
</body> 
</html>
