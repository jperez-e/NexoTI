<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Categorias</title> 
    <link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'>
    <link rel='stylesheet' href='/NexoTI/Views/categorias/categorias.css'> 
    <link rel='stylesheet' href='/NexoTI/Views/partials/buttons.css'>
    <script src='/NexoTI/Views/categorias/categorias.js?v=2' defer></script> 
</head> 
<body> 
    <div class='layout'> 
        <?php require __DIR__ . '/../partials/sidebar.php'; ?> 
        <main class='page'> 
            <header class='page-header'> 
                <div> 
                    <h1>Categorias</h1> 
                    <p>Gestiona categorias del sistema.</p> 
                </div> 
                <a class='btn' href='/NexoTI/index.php?r=home'>Volver al inicio</a> 
            </header> 
 
            <section class='card'> 
                <h2>Nueva categoria</h2> 
                <form id='categoria-form'> 
                    <label> 
                        Nombre 
                        <input id='nombre' name='nombre' type='text' placeholder='Ej: Hardware' required> 
                    </label> 
                    <label> 
                        Descripcion 
                        <textarea id='descripcion' name='descripcion' rows='3' placeholder='Describe la categoria'></textarea> 
                    </label> 
                    <div class='actions'> 
                        <button type='submit' class='btn primary' id='submit-btn'>Guardar categoria</button> 
                        <span id='form-message' class='message'></span> 
                    </div> 
                </form> 
            </section> 
 
            <section class='card'> 
                <div class='list-header'> 
                    <h2>Listado</h2> 
                    <button class='btn ghost' id='refresh-btn' type='button'>Actualizar</button> 
                </div> 
                <div id='categorias-list' class='list'></div> 
            </section> 
        </main> 
    </div> 
</body> 
</html>
