async function fetchJSON(url) {
    const res = await fetch(url);
    return res.json();
}

function fillSelect(select, rows, labelKey, valueKey) {
    select.innerHTML = "";
    rows.forEach(function (row) {
        const opt = document.createElement("option");
        opt.value = row[valueKey];
        opt.textContent = row[labelKey];
        select.appendChild(opt);
    });
}

async function loadCombos() {
    const results = await Promise.all([
        fetchJSON("api.php?c=categoria&m=list"),
        fetchJSON("api.php?c=prioridad&m=list"),
        fetchJSON("api.php?c=estado&m=list")
    ]);
    const cats = results[0].data || [];
    const prios = results[1].data || [];
    const estados = results[2].data || [];
    fillSelect(document.getElementById("categoria_id"), cats, "nombre", "id");
    fillSelect(document.getElementById("prioridad_id"), prios, "nombre", "id");
    fillSelect(document.getElementById("estado_id"), estados, "nombre", "id");
}

function renderTickets(list) {
    const container = document.getElementById("tickets-list");
    container.innerHTML = "";
    list.forEach(function (t) {
        const item = document.createElement("div");
        item.className = "ticket-item";
        const title = document.createElement("h3");
        title.textContent = t.codigo + " - " + t.titulo;
        const meta = document.createElement("div");
        meta.className = "ticket-meta";
        meta.textContent = "Usuario: " + t.usuario_id + " | Estado: " + t.estado_id;
        const desc = document.createElement("div");
        desc.textContent = t.descripcion;
        item.appendChild(title);
        item.appendChild(meta);
        item.appendChild(desc);
        container.appendChild(item);
    });
}

async function loadTickets() {
    const data = await fetchJSON("api.php?c=ticket&m=list");
    renderTickets(data.data || []);
}

document.addEventListener("DOMContentLoaded", async function () {
    await loadCombos();
    await loadTickets();

    document.getElementById("refresh-btn").addEventListener("click", loadTickets);

    document.getElementById("ticket-form").addEventListener("submit", async function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append("codigo", "TCK-" + Date.now());

        const res = await fetch("api.php?c=ticket&m=create", {
            method: "POST",
            body: formData
        });
        const data = await res.json();
        document.getElementById("form-message").textContent = data.message || "";

        if (data.status) {
            form.reset();
            await loadTickets();
        }
    });
});
