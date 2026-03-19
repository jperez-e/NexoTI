async function fetchJSON(url) {  
    const res = await fetch(url);  
    return res.json();  
} 

function getCsrfToken() {
    const node = document.getElementById('csrf-token');
    return node ? node.value : '';
}
 
function renderPrioridades(list) {  
    const container = document.getElementById('prioridades-list');  
    container.innerHTML = '';  
    list.forEach(function (row) {  
        const item = document.createElement('div');  
        item.className = 'item';  
        const title = document.createElement('h3');  
        title.textContent = row.nombre + ' (Nivel ' + row.nivel + ')';  
        item.appendChild(title);  
        container.appendChild(item);  
    });  
} 
 
async function loadPrioridades() {  
    const data = await fetchJSON('api.php?c=prioridad&m=list');  
    const rows = data.data ? data.data : [];  
    renderPrioridades(rows);  
} 
 
document.addEventListener('DOMContentLoaded', async function () {  
    await loadPrioridades();  
    document.getElementById('refresh-btn').addEventListener('click', loadPrioridades);  
    document.getElementById('prioridad-form').addEventListener('submit', async function (e) {  
        e.preventDefault();  
        const form = e.target;  
        const formData = new FormData(form);  
        formData.set('_token', getCsrfToken());
        const res = await fetch('api.php?c=prioridad&m=create', {  
            method: 'POST',  
            body: formData  
        });  
        const data = await res.json();  
        const message = data.message ? data.message : '';  
        document.getElementById('form-message').textContent = message;  
        if (data.status) {  
            form.reset();  
            await loadPrioridades();  
        }  
    });  
}); 
