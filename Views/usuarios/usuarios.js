async function fetchJSON(url) { 
    const res = await fetch(url); 
    return res.json(); 
} 
 
function renderUsuarios(list) { 
    const container = document.getElementById('usuarios-list'); 
    container.innerHTML = ''; 
    list.forEach(function (row) { 
        const item = document.createElement('div'); 
        item.className = 'item'; 
        const title = document.createElement('h3'); 
        title.textContent = row.nombre; 
        const meta = document.createElement('p'); 
        meta.textContent = 'Correo: ' + row.email + ' - Rol ID: ' + row.rol_id + ' - Activo: ' + (row.activo == 1 ? 'Si' : 'No'); 
        item.appendChild(title); 
        item.appendChild(meta); 
        container.appendChild(item); 
    }); 
} 
 
function renderRolesOptions(list) { 
    const select = document.getElementById('rol-select'); 
    select.innerHTML = ''; 
    const defaultOption = document.createElement('option'); 
    defaultOption.value = ''; 
    defaultOption.textContent = 'Seleccione un rol'; 
    select.appendChild(defaultOption); 
    list.forEach(function (row) { 
        const option = document.createElement('option'); 
        option.value = row.id; 
        option.textContent = row.nombre; 
        select.appendChild(option); 
    }); 
} 
 
async function loadUsuarios() { 
    const data = await fetchJSON('api.php?c=usuario&m=list'); 
    const rows = data.data ? data.data : []; 
    renderUsuarios(rows); 
} 
 
async function loadRoles() { 
    const data = await fetchJSON('api.php?c=rol&m=list'); 
    const rows = data.data ? data.data : []; 
    renderRolesOptions(rows); 
} 
 
document.addEventListener('DOMContentLoaded', async function () { 
    await loadRoles(); 
    await loadUsuarios(); 
    document.getElementById('refresh-btn').addEventListener('click', loadUsuarios); 
    document.getElementById('usuario-form').addEventListener('submit', async function (e) { 
        e.preventDefault(); 
        const form = e.target; 
        const formData = new FormData(form); 
        const res = await fetch('api.php?c=usuario&m=create', { 
            method: 'POST', 
            body: formData 
        }); 
        const data = await res.json(); 
        const message = data.message ? data.message : ''; 
        document.getElementById('form-message').textContent = message; 
        if (data.status) { 
            form.reset(); 
            await loadUsuarios(); 
        } 
    }); 
});
