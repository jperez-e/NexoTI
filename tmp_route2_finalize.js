const fs = require('fs'); 
let r = fs.readFileSync('Views/tickets/tickets.render.js','utf8'); 
r = r.replace('Notificaci¢nes','Notificaciones'); 
fs.writeFileSync('Views/tickets/tickets.render.js', r, 'utf8'); 
let p = fs.readFileSync('Views/tickets/tickets.php','utf8'); 
p = p.replace(\"\n ^<link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'^>\",\"\n    ^<link rel='stylesheet' href='/NexoTI/Views/partials/app-shell.css'^>\"); 
fs.writeFileSync('Views/tickets/tickets.php', p, 'utf8'); 
