<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/RolModel.php";

$model = new RolModel();
$nombre = "Operador TI " . date("His");

echo "Insertando rol...\n";
$ok = $model->insert($nombre);
echo $ok ? "Insert OK\n" : "Insert FAIL\n";

echo "Listado de roles:\n";
$rows = $model->getAll();
foreach ($rows as $row) {
    echo "{$row['id']} | {$row['nombre']}\n";
}
