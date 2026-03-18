<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Usuarios</title> 
    <link rel='stylesheet' href='/NexoTI/Views/usuarios/usuarios.css'> 
    <script src='/NexoTI/Views/usuarios/usuarios.js' defer></script> 
</head> 
<body> 
    <div class='layout'> 
        <?php require __DIR__ . '/../partials/sidebar.php'; ?> 
        <main class='page'> 
            <header class='page-header'> 
                <div> 
                    <h1>Usuarios</h1> 
                    <p>Gestiona usuarios del sistema.</p> 
                </div> 
                <a class='btn' href='/NexoTI/index.php?r=home'>Volver al inicio</a> 
            </header> 
 
            <section class='card'> 
                <h2>Nuevo usuario</h2> 
                <form id='usuario-form'> 
                    <label> 
                        Nombre 
                        <input name='nombre' type='text' placeholder='Ej: Ana Perez' required> 
                    </label> 
                    <label> 
                        Correo 
                        <input name='email' type='email' placeholder='ana@nexoti.com' required> 
                    </label> 
                    <label> 
                        Contrasena 
                        <input name='password' type='password' placeholder='********' required> 
                    </label> 
                    <label> 
                        Rol 
                        <select name='rol_id' id='rol-select' required></select> 
                    </label> 
                    <div class='actions'> 
                        <button type='submit' class='btn primary'>Crear usuario</button> 
                        <span id='form-message' class='message'></span> 
                    </div> 
                </form> 
            </section> 
 
            <section class='card'> 
                <div class='list-header'> 
                    <h2>Listado</h2> 
                    <button class='btn ghost' id='refresh-btn' type='button'>Actualizar</button> 
                </div> 
                <div id='usuarios-list' class='list'></div> 
            </section> 
        </main> 
    </div> 
</body> 
</html>
