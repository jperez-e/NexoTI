from pathlib import Path 
Path('favicon.svg').write_text('''<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 64 64\">\n  <rect width=\"64\" height=\"64\" rx=\"14\" fill=\"#1d4ed8\"/>\n  <path d=\"M16 46V18h7l18 18V18h7v28h-7L23 28v18z\" fill=\"#ffffff\"/>\n</svg>\n''', encoding='utf-8') 
link = \"    ^<link rel='icon' type='image/svg+xml' href='/NexoTI/favicon.svg'^>\"  
files = ['Views/auth/login/login.php','Views/auth/register/register.php','Views/categorias/index.php','Views/estados/index.php','Views/home/home.php','Views/perfil/index.php','Views/prioridades/index.php','Views/reportes/index.php','Views/roles/index.php','Views/tickets/index.php','Views/usuarios/index.php'] 
for file in files: 
    path = Path(file) 
    content = path.read_text(encoding='utf-8') 
    if '/NexoTI/favicon.svg' not in content: 
        content = content.replace('</title>', '</title>\n' + link, 1) 
        path.write_text(content, encoding='utf-8') 
