<!DOCTYPE html> 
<html lang='es'> 
<head> 
    <meta charset='UTF-8'> 
    <meta name='viewport' content='width=device-width,initial-scale=1.0'> 
    <title>NexoTI - Inicio</title> 
    <link rel='stylesheet' href='/NexoTI/Views/home/home.css'> 
</head> 
<body> 
    <div class='layout'> 
        <?php require __DIR__ . '/../partials/sidebar.php'; ?> 
        <div> 
            <header class='topbar'> 
                <div class='logo'>NexoTI</div> 
                <div class='topbar-text'> 
                    <h1>Mesa de Ayuda TI</h1> 
                    <p>Panel principal del sistema de tickets</p> 
                </div> 
                <a class='btn' href='/NexoTI/index.php?r=logout'>Cerrar sesion</a> 
            </header> 
            <main class='home'> 
                <section class='hero'> 
                    <h2>Bienvenido</h2> 
                    <p>Gestiona tickets, prioridades y estados desde un solo lugar.</p> 
                    <div class='hero-actions'> 
                        <button class='ghost' type='button'>Crear ticket</button> 
                        <button class='ghost' type='button'>Ver mis tickets</button> 
                    </div> 
                </section> 
                <section class='summary'> 
                    <div class='card'> 
                        <h3>Resumen rapido</h3> 
                        <ul> 
                            <li>Tickets abiertos: 0</li> 
                            <li>En progreso: 0</li> 
                            <li>Cerrados hoy: 0</li> 
                        </ul> 
                    </div> 
                    <div class='card'> 
                        <h3>Atajos</h3> 
                        <ul> 
                            <li>Registrar incidencia</li> 
                            <li>Actualizar estado</li> 
                            <li>Revisar comentarios</li> 
                        </ul> 
                    </div> 
                </section> 
            </main> 
        </div> 
    </div> 
</body> 
</html>
