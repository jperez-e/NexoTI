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
 
function renderCategorias(list) {  
    const container = document.getElementById('categorias-list');  
    container.innerHTML = '';  
    list.forEach(function (row) {  
        const item = document.createElement('div');  
        item.className = 'item';  
        const title = document.createElement('h3');  
        title.textContent = row.nombre;  
        const desc = document.createElement('p');  
        const descText = row.descripcion ? row.descripcion : 'Sin descripcion';  
        desc.textContent = descText;  
        item.appendChild(title);  
        item.appendChild(desc);  
        container.appendChild(item);  
    });  
} 
 
async function loadCategorias() {  
    const data = await fetchJSON('api.php?c=categoria&m=list');  
    const rows = data.data ? data.data : [];  
    renderCategorias(rows);  
} 
 
document.addEventListener('DOMContentLoaded', async function () {  
    await loadCategorias();  
    document.getElementById('refresh-btn').addEventListener('click', async function () {
        const button = this;
        setButtonLoading(button, true, 'Actualizando');
        try {
            await loadCategorias();
        } finally {
            setButtonLoading(button, false);
        }
    });
    document.getElementById('categoria-form').addEventListener('submit', async function (e) {  
        e.preventDefault();  
        const form = e.target;  
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);  
        formData.set('_token', getCsrfToken());
        setButtonLoading(submitButton, true, 'Guardando');
        try {
            const res = await fetch('api.php?c=categoria&m=create', {  
                method: 'POST',  
                body: formData  
            });  
            const data = await res.json();  
            const message = data.message ? data.message : '';  
            document.getElementById('form-message').textContent = message;  
            if (data.status) {  
                form.reset();  
                await loadCategorias();  
            }  
        } finally {
            setButtonLoading(submitButton, false);
        }  
    });  
}); 
