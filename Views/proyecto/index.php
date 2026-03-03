<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexoTI</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: #f3f4f6;
            color: #111827;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 28px 34px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        h1 {
            margin: 0;
            font-size: 1.9rem;
            line-height: 1.2;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1><?php echo htmlspecialchars($nombreProyecto, ENT_QUOTES, "UTF-8"); ?></h1>
    </div>
</body>
</html>
