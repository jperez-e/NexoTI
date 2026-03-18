async function fetchJSON(url) { 
    const res = await fetch(url); 
    return res.json(); 
} 
 
function renderComentarios(list) { 
    const container = document.getElementById('comentarios-list'); 
    container.innerHTML = ''; 
    list.forEach(function (row) { 
        const item = document.createElement('div'); 
        item.className = 'item'; 
        const title = document.createElement('h3'); 
        title.textContent = 'Ticket #' + row.ticket_id + ' - Usuario ' + row.usuario_id; 
        const desc = document.createElement('p'); 
        desc.textContent = row.comentario; 
        const meta = document.createElement('p'); 
        meta.textContent = row.fecha; 
        item.appendChild(title); 
        item.appendChild(desc); 
        item.appendChild(meta); 
        container.appendChild(item); 
    }); 
} 
 
function renderOptions(selectId, list, labelFn) { 
    const select = document.getElementById(selectId); 
    select.innerHTML = ''; 
    const defaultOption = document.createElement('option'); 
    defaultOption.value = ''; 
    defaultOption.textContent = 'Seleccione una opcion'; 
    select.appendChild(defaultOption); 
    list.forEach(function (row) { 
        const option = document.createElement('option'); 
        option.value = row.id; 
        option.textContent = labelFn(row); 
        select.appendChild(option); 
    }); 
} 
 
async function loadTickets() { 
    const data = await fetchJSON('api.php?c=ticket&m=list'); 
    const rows = data.data ? data.data : []; 
    renderOptions('ticket_id', rows, function (row) { 
        return row.id + ' - ' + row.titulo; 
    }); 
} 
 
async function loadUsuarios() { 
    const data = await fetchJSON('api.php?c=usuario&m=list'); 
    const rows = data.data ? data.data : []; 
    renderOptions('usuario_id', rows, function (row) { 
        return row.id + ' - ' + row.nombre; 
    }); 
} 
 
async function loadComentarios() { 
    const data = await fetchJSON('api.php?c=comentario&m=list'); 
    const rows = data.data ? data.data : []; 
    renderComentarios(rows); 
} 
 
document.addEventListener('DOMContentLoaded', async function () { 
    await loadTickets(); 
    await loadUsuarios(); 
    await loadComentarios(); 
    document.getElementById('refresh-btn').addEventListener('click', loadComentarios); 
    document.getElementById('comentario-form').addEventListener('submit', async function (e) { 
        e.preventDefault(); 
        const form = e.target; 
        const formData = new FormData(form); 
        const res = await fetch('api.php?c=comentario&m=create', { 
            method: 'POST', 
            body: formData 
        }); 
        const data = await res.json(); 
        const message = data.message ? data.message : ''; 
        document.getElementById('form-message').textContent = message; 
        if (data.status) { 
            form.reset(); 
            await loadComentarios(); 
        } 
    }); 
});
