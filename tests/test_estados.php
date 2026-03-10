<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/EstadoTicketModel.php";

$model = new EstadoTicketModel();

$nombre = "Estado Demo " . date("His");

echo "Insertando estado...\n";
$ok = $model->insert($nombre);
echo $ok ? "Insert OK\n" : "Insert FAIL\n";

echo "Listado de estados:\n";
$rows = $model->getAll();
foreach ($rows as $row) {
    echo "{$row['id']} | {$row['nombre']}\n";
}
