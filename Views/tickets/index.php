<?php 
declare(strict_types=1); 
 
$userId = (int) ($_SESSION['user_id'] ?? 0); 
?> 
<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Tickets</title> 
    <link rel='stylesheet' href='/NexoTI/Views/tickets/tickets.css'> 
    <script src='/NexoTI/Views/tickets/tickets.js' defer></script> 
</head> 
<body> 
    <div class='layout'> 
        <?php require __DIR__ . '/../partials/sidebar.php'; ?> 
        <main class='page'> 
            <header class='page-header'> 
                <div> 
                    <h1>Tickets de Soporte</h1> 
                    <p>Registra y consulta solicitudes.</p> 
                </div> 
                <a class='btn' href='/NexoTI/index.php?r=home'>Volver al inicio</a> 
            </header> 
 
            <section class='card'> 
                <h2>Crear ticket</h2> 
                <form id='ticket-form'> 
                    <input type='hidden' name='usuario_id' value='<?php echo $userId; ?>'> 
                    <div class='grid'> 
                        <label> 
                            Titulo 
                            <input name='titulo' type='text' placeholder='Ej: Impresora sin conexion' required> 
                        </label> 
                        <label> 
                            Categoria 
                            <select name='categoria_id' id='categoria_id' required></select> 
                        </label> 
                        <label> 
                            Prioridad 
                            <select name='prioridad_id' id='prioridad_id' required></select> 
                        </label> 
                        <label> 
                            Estado 
                            <select name='estado_id' id='estado_id' required></select> 
                        </label> 
                    </div> 
                    <label> 
                        Descripcion 
                        <textarea name='descripcion' rows='4' placeholder='Describe el problema' required></textarea> 
                    </label> 
                    <div class='actions'> 
                        <button type='submit' class='btn primary'>Guardar ticket</button> 
                        <span id='form-message' class='message'></span> 
                    </div> 
                </form> 
            </section> 
 
            <section class='card'> 
                <div class='list-header'> 
                    <h2>Listado</h2> 
                    <button class='btn ghost' id='refresh-btn' type='button'>Actualizar</button> 
                </div> 
                <div id='tickets-list' class='list'></div> 
            </section> 
        </main> 
    </div> 
</body> 
</html>
