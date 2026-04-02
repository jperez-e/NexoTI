<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class CatalogModel
{
    private PDO $db;
    private string $tabla;
    /** @var string[] */
    private array $campos;
    private string $orden;

    /**
     * @param string[] $fields
     */
    public function __construct(string $tabla, array $campos, string $orden = 'id DESC')
    {
        $this->db = Conexion::obtener();
        $this->tabla = $tabla;
        $this->campos = $campos;
        $this->orden = $orden;
    }

    public function obtenerTodos(): array
    {
        $columns = implode(', ', array_merge(['id'], $this->campos));
        $sql = "SELECT {$columns} FROM {$this->tabla} ORDER BY {$this->orden}";
        return $this->db->query($sql)->fetchAll();
    }

    public function insertar(array $data): bool
    {
        $payload = $this->filtrarCarga($data);
        $columns = array_keys($payload);
        $placeholders = array_map(static fn (string $field): string => ':' . $field, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->tabla,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($this->vincularCarga($payload));
    }

    public function actualizar(int $id, array $data): bool
    {
        $payload = $this->filtrarCarga($data);
        $assignments = array_map(static fn (string $field): string => $field . ' = :' . $field, array_keys($payload));
        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $this->tabla,
            implode(', ', $assignments)
        );

        $stmt = $this->db->prepare($sql);
        $bindings = $this->vincularCarga($payload);
        $bindings[':id'] = $id;
        return $stmt->execute($bindings);
    }

    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM {$this->tabla} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    private function filtrarCarga(array $data): array
    {
        $payload = [];
        foreach ($this->campos as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        return $payload;
    }

    private function vincularCarga(array $payload): array
    {
        $bindings = [];
        foreach ($payload as $field => $value) {
            $bindings[':' . $field] = $value;
        }

        return $bindings;
    }
}
