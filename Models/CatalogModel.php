<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class CatalogModel
{
    private PDO $db;
    private string $table;
    /** @var string[] */
    private array $fields;
    private string $orderBy;

    /**
     * @param string[] $fields
     */
    public function __construct(string $table, array $fields, string $orderBy = 'id DESC')
    {
        $this->db = Conexion::get();
        $this->table = $table;
        $this->fields = $fields;
        $this->orderBy = $orderBy;
    }

    public function getAll(): array
    {
        $columns = implode(', ', array_merge(['id'], $this->fields));
        $sql = "SELECT {$columns} FROM {$this->table} ORDER BY {$this->orderBy}";
        return $this->db->query($sql)->fetchAll();
    }

    public function insert(array $data): bool
    {
        $payload = $this->filterPayload($data);
        $columns = array_keys($payload);
        $placeholders = array_map(static fn (string $field): string => ':' . $field, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($this->bindPayload($payload));
    }

    public function update(int $id, array $data): bool
    {
        $payload = $this->filterPayload($data);
        $assignments = array_map(static fn (string $field): string => $field . ' = :' . $field, array_keys($payload));
        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $this->table,
            implode(', ', $assignments)
        );

        $stmt = $this->db->prepare($sql);
        $bindings = $this->bindPayload($payload);
        $bindings[':id'] = $id;
        return $stmt->execute($bindings);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    private function filterPayload(array $data): array
    {
        $payload = [];
        foreach ($this->fields as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        return $payload;
    }

    private function bindPayload(array $payload): array
    {
        $bindings = [];
        foreach ($payload as $field => $value) {
            $bindings[':' . $field] = $value;
        }

        return $bindings;
    }
}
