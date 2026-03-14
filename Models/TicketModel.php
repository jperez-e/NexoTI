<?php
declare(strict_types=1);

require_once __DIR__ . "/../Config/Conexion.php";

class TicketModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::get();
    }

    public function insert(
        string $codigo,
        string $titulo,
        string $descripcion,
        int $usuarioId,
        int $categoriaId,
        int $prioridadId,
        int $estadoId,
        ?int $tecnicoId = null,
        ?string $fechaCierre = null
    ): bool {
        $sql = "INSERT INTO tickets
                (codigo, titulo, descripcion, usuario_id, tecnico_id, categoria_id, prioridad_id, estado_id, fecha_cierre)
                VALUES
                (:codigo, :titulo, :descripcion, :usuario_id, :tecnico_id, :categoria_id, :prioridad_id, :estado_id, :fecha_cierre)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ":codigo" => $codigo,
            ":titulo" => $titulo,
            ":descripcion" => $descripcion,
            ":usuario_id" => $usuarioId,
            ":tecnico_id" => $tecnicoId,
            ":categoria_id" => $categoriaId,
            ":prioridad_id" => $prioridadId,
            ":estado_id" => $estadoId,
            ":fecha_cierre" => $fechaCierre,
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT id, codigo, titulo, descripcion, usuario_id, tecnico_id, categoria_id,
                       prioridad_id, estado_id, fecha_creacion, fecha_cierre
                FROM tickets
                ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll();
    }
}
