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
 
function renderEstados(list) {  
    const container = document.getElementById('estados-list');  
    container.innerHTML = '';  
    list.forEach(function (row) {  
        const item = document.createElement('div');  
        item.className = 'item';  
        const title = document.createElement('h3');  
        title.textContent = row.nombre;  
        item.appendChild(title);  
        container.appendChild(item);  
    });  
} 
 
async function loadEstados() {  
    const data = await fetchJSON('api.php?c=estado&m=list');  
    const rows = data.data ? data.data : [];  
    renderEstados(rows);  
} 
 
document.addEventListener('DOMContentLoaded', async function () {  
    await loadEstados();  
    document.getElementById('refresh-btn').addEventListener('click', async function () {
        const button = this;
        setButtonLoading(button, true, 'Actualizando');
        try {
            await loadEstados();
        } finally {
            setButtonLoading(button, false);
        }
    });  
    document.getElementById('estado-form').addEventListener('submit', async function (e) {  
        e.preventDefault();  
        const form = e.target;  
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);  
        formData.set('_token', getCsrfToken());
        setButtonLoading(submitButton, true, 'Guardando');
        try {
            const res = await fetch('api.php?c=estado&m=create', {  
                method: 'POST',  
                body: formData  
            });  
            const data = await res.json();  
            const message = data.message ? data.message : '';  
            document.getElementById('form-message').textContent = message;  
            if (data.status) {  
                form.reset();  
                await loadEstados();  
            }  
        } finally {
            setButtonLoading(submitButton, false);
        }  
    });  
}); 
