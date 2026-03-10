<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/CategoriaModel.php";

$model = new CategoriaModel();

$nombre = "Categoria Demo " . date("His");
$descripcion = "Categoria creada desde script de prueba";

echo "Insertando categoria...\n";
$ok = $model->insert($nombre, $descripcion);
echo $ok ? "Insert OK\n" : "Insert FAIL\n";

echo "Listado de categorias:\n";
$rows = $model->getAll();
foreach ($rows as $row) {
    echo "{$row['id']} | {$row['nombre']} | {$row['descripcion']}\n";
}
