<!DOCTYPE html>  
<html lang='es'>  
<head>  
    <meta charset='UTF-8'>  
    <meta name='viewport' content='width=device-width,initial-scale=1.0'>  
    <title>NexoTI - Reportes</title>  
    <link rel='stylesheet' href='/NexoTI/Views/reportes/reportes.css'>  
</head>  
<body>  
    <div class='layout'>  
        <?php require __DIR__ . '/../partials/sidebar.php'; ?>  
        <div class='content'>  
            <header class='topbar'>  
                <h1>Reportes</h1>  
                <p>Exporta listados de tickets en CSV o PDF.</p>  
            </header>  
            <main class='reports'>  
                <section class='card'>  
                    <h2>Reporte de Tickets</h2>  
                    <p>Descarga el reporte con los datos actuales del sistema.</p>  
                    <div class='actions'>  
                        <a class='btn primary' href='/NexoTI/api.php?c=reporte&m=ticketsCsv'>Descargar CSV</a>  
                        <a class='btn' href='/NexoTI/api.php?c=reporte&m=ticketsPdf'>Descargar PDF</a>  
                    </div>  
                </section>  
            </main>  
        </div>  
    </div>  
</body>  
</html> 
