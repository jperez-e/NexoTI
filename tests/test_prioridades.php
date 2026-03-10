<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/PrioridadModel.php";

$model = new PrioridadModel();

$nombre = "Prioridad Demo " . date("His");
$nivel = rand(10, 99);

echo "Insertando prioridad...\n";
$ok = $model->insert($nombre, $nivel);
echo $ok ? "Insert OK\n" : "Insert FAIL\n";

echo "Listado de prioridades:\n";
$rows = $model->getAll();
foreach ($rows as $row) {
    echo "{$row['id']} | {$row['nombre']} | nivel={$row['nivel']}\n";
}
