const fs = require('fs');
function replace(path, from, to) { let text = fs.readFileSync(path, 'utf8'); text = text.split(from).join(to); fs.writeFileSync(path, text, 'utf8'); }
function append(path, text) { fs.appendFileSync(path, text, 'utf8'); }
