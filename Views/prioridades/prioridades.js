async function fetchJSON(url) {  
    const res = await fetch(url);  
    return res.json();  
} 

function getCsrfToken() {
    const node = document.getElementById('csrf-token');
    return node ? node.value : '';
}

function setButtonLoading(button, loading, loadingText) {
    if (!button) {
        return;
    }

    if (loading) {
        button.dataset.label = button.textContent;
        button.textContent = loadingText;
        button.disabled = true;
        button.classList.add('is-loading');
        return;
    }

    button.textContent = button.dataset.label ? button.dataset.label : button.textContent;
    button.disabled = false;
    button.classList.remove('is-loading');
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
    document.getElementById('refresh-btn').addEventListener('click', async function () {
        const button = this;
        setButtonLoading(button, true, 'Actualizando');
        try {
            await loadPrioridades();
        } finally {
            setButtonLoading(button, false);
        }
    });  
    document.getElementById('prioridad-form').addEventListener('submit', async function (e) {  
        e.preventDefault();  
        const form = e.target;  
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);  
        formData.set('_token', getCsrfToken());
        setButtonLoading(submitButton, true, 'Guardando');
        try {
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
        } finally {
            setButtonLoading(submitButton, false);
        }  
    });  
}); 
