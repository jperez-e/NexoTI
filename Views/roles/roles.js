async function fetchJSON(url) { 
    const res = await fetch(url); 
    return res.json(); 
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
    document.getElementById('refresh-btn').addEventListener('click', loadRoles); 
    document.getElementById('rol-form').addEventListener('submit', async function (e) { 
        e.preventDefault(); 
        const form = e.target; 
        const formData = new FormData(form); 
        const res = await fetch('api.php?c=rol&m=create', { 
            method: 'POST', 
            body: formData 
        }); 
        const data = await res.json(); 
        const message = data.message ? data.message : ''; 
        document.getElementById('form-message').textContent = message; 
        if (data.status) { 
            form.reset(); 
            await loadRoles(); 
        } 
    }); 
});
