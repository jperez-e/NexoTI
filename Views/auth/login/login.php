<!DOCTYPE html> 
<html lang=es> 
<head> 
    <meta charset=UTF-8> 
    <meta name=viewport content=width=device-width,initial-scale=1.0> 
    <title>NexoTI | Iniciar sesion</title> 
    <link rel=stylesheet href=/NexoTI/Views/auth/login/login.css> 
</head> 
<body> 
    <div class=card> 
        <section class=brand> 
            <div class=logo>NexoTI</div> 
            <h1>Mesa de Ayuda TI</h1> 
            <p>Gestiona incidencias, asignaciones y soluciones desde un solo lugar.</p> 
        </section> 
        <section class=form> 
            <h2>Iniciar sesión</h2> 
            <p>Ingresa tus datos para acceder al sistema.</p> 
            <form method=POST action=index.php?r=login> 
                <label for=login>Correo o usuario</label> 
                <input id=login name=login type=text placeholder=correo@dominio.com required> 
                <label for=password>Contraseña</label> 
                <input id=password name=password type=password required> 
                <button type=submit>Ingresar</button> 
            </form> 
            <div class=register>Solicita tu usuario al administrador del sistema.</div> 
        </section> 
    </div> 
</body> 
</html> 