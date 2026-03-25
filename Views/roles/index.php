<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Roles</title>
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'> 
    <link rel='stylesheet' href='/NexoTI/Views/roles/roles.css'> 
    <script src='/NexoTI/Views/roles/roles.js' defer></script> 
</head> 
<body> 
    <div class='layout'> 
        <?php require __DIR__ . '/../partials/sidebar.php'; ?> 
        <main class='page'> 
            <header class='page-header'> 
                <div> 
                    <h1>Roles</h1> 
                    <p>Gestiona roles del sistema.</p> 
                </div> 
                <a class='btn' href='/NexoTI/index.php?r=home'>Volver al inicio</a> 
            </header> 
 
            <section class='card'> 
                <h2>Nuevo rol</h2> 
                <form id='rol-form'> 
                    <label> 
                        Nombre 
                        <input name='nombre' type='text' placeholder='Ej: Supervisor' required> 
                    </label> 
                    <div class='actions'> 
                        <button type='submit' class='btn primary'>Guardar rol</button> 
                        <span id='form-message' class='message'></span> 
                    </div> 
                </form> 
            </section> 
 
            <section class='card'> 
                <div class='list-header'> 
                    <h2>Listado</h2> 
                    <button class='btn ghost' id='refresh-btn' type='button'>Actualizar</button> 
                </div> 
                <div id='roles-list' class='list'></div> 
            </section> 
        </main> 
    </div> 
</body> 
</html>
