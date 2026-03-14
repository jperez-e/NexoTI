<!DOCTYPE html> 
<html lang=es> 
<head> 
    <meta charset=UTF-8> 
    <meta name=viewport content=width=device-width,initial-scale=1.0> 
    <title>NexoTI | Registro</title> 
    <link rel=stylesheet href=/NexoTI/Views/auth/register/register.css> 
</head> 
<body> 
    <div class=card> 
        <section class=brand> 
            <div class=logo>NexoTI</div> 
            <h1>Registro TI</h1> 
            <p>Crea tu cuenta para acceder a la mesa de ayuda.</p> 
        </section> 
        <section class=form> 
            <h2>Registro de usuario</h2> 
            <p>Completa los datos del nuevo usuario.</p> 
            <form method=POST action=index.php?r=register> 
                <label for=nombre>Nombre completo</label> 
                <input id=nombre name=nombre type=text required> 
                <label for=email>Correo</label> 
                <input id=email name=email type=email required> 
                <label for=password>Contrasena</label> 
                <input id=password name=password type=password required> 
                <button type=submit>Crear cuenta</button> 
            </form> 
            <div class=register><a href=/NexoTI/index.php?r=home>Volver al panel</a></div> 
        </section> 
    </div> 
</body> 
</html> 
