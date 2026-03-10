<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/ComentarioModel.php";

$model = new ComentarioModel();

$ticketId = 1;
$usuarioId = 1;
$comentario = "Comentario demo " . date("His");

echo "Insertando comentario...\n";
$ok = $model->insert($ticketId, $usuarioId, $comentario);
echo $ok ? "Insert OK\n" : "Insert FAIL\n";

echo "Listado de comentarios:\n";
$rows = $model->getAll();
foreach ($rows as $row) {
    echo "{$row['id']} | ticket={$row['ticket_id']} | usuario={$row['usuario_id']} | {$row['comentario']}\n";
}
