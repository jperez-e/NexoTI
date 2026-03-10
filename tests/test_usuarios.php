<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/UsuarioModel.php";

$model = new UsuarioModel();

$nombre = "Usuario Demo " . date("His");
$email = "demo_" . date("His") . "@nexoti.local";
$claveHash = password_hash("123456", PASSWORD_BCRYPT);
$rolId = 3;

echo "Insertando usuario...\n";
$ok = $model->insert($nombre, $email, $claveHash, $rolId, 1);
echo $ok ? "Insert OK\n" : "Insert FAIL\n";

echo "Listado de usuarios:\n";
$rows = $model->getAll();
foreach ($rows as $row) {
    echo "{$row['id']} | {$row['nombre']} | {$row['email']} | rol={$row['rol_id']} | activo={$row['activo']}\n";
}
