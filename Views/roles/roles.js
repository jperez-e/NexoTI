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

function showMessage(text, type) {
    const node = document.getElementById('form-message');
    if (!node) {
        return;
    }

    if (node._messageTimer) {
        clearTimeout(node._messageTimer);
    }

    node.textContent = text;
    node.className = type ? 'message ' + type : 'message';

    if (text !== '' && type) {
        node._messageTimer = window.setTimeout(function () {
            node.textContent = '';
            node.className = 'message';
        }, 4000);
    }
}
 
function renderRoles(list) { 
    const container = document.getElementById('roles-list'); 
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
 
async function loadRoles() { 
    const data = await fetchJSON('api.php?c=rol&m=list'); 
    const rows = data.data ? data.data : []; 
    renderRoles(rows); 
} 
 
document.addEventListener('DOMContentLoaded', async function () { 
    await loadRoles(); 
    document.getElementById('refresh-btn').addEventListener('click', async function () {
        const button = this;
        setButtonLoading(button, true, 'Actualizando');
        try {
            await loadRoles();
        } finally {
            setButtonLoading(button, false);
        }
    }); 
    document.getElementById('rol-form').addEventListener('submit', async function (e) { 
        e.preventDefault(); 
        const form = e.target; 
        const submitButton = form.querySelector('button[type="submit"]');
        const formData = new FormData(form); 
        formData.set('_token', getCsrfToken());
        setButtonLoading(submitButton, true, 'Guardando');
        try {
            const res = await fetch('api.php?c=rol&m=create', { 
                method: 'POST', 
                body: formData 
            }); 
            const data = await res.json(); 
            const message = data.message ? data.message : ''; 
            showMessage(message, data.status ? 'success' : 'error');
            if (data.status) { 
                form.reset(); 
                await loadRoles(); 
            } 
        } finally {
            setButtonLoading(submitButton, false);
        } 
    }); 
});
