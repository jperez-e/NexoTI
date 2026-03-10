<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/TicketModel.php";

$model = new TicketModel();

$codigo = "TCK-" . date("YmdHis");
$titulo = "Impresora sin conexion en Oficina 2";
$descripcion = "La impresora HP no responde desde las 9:00 AM. Se requiere revision.";
$usuarioId = 1;
$categoriaId = 1;
$prioridadId = 1;
$estadoId = 1;

echo "Insertando ticket...\n";
$ok = $model->insert(
    $codigo,
    $titulo,
    $descripcion,
    $usuarioId,
    $categoriaId,
    $prioridadId,
    $estadoId,
    null,
    null
);
echo $ok ? "Insert OK\n" : "Insert FAIL\n";

echo "Listado de tickets:\n";
$rows = $model->getAll();
foreach ($rows as $row) {
    echo "{$row['id']} | {$row['codigo']} | {$row['titulo']} | usuario={$row['usuario_id']} | estado={$row['estado_id']}\n";
}
