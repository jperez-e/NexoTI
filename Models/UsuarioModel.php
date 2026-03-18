<?php  
declare(strict_types=1);  
  
require_once __DIR__ . '/../Config/Conexion.php';  
  
class UsuarioModel  
{  
    private PDO $db;  
  
    public function __construct()  
    {  
        $this->db = Conexion::get();  
    }  
  
    public function insert(string $nombre, string $email, string $claveHash, int $rolId, int $activo = 1): bool  
    {  
        $sql = 'INSERT INTO usuarios (nombre, email, clave_hash, rol_id, activo) VALUES (?, ?, ?, ?, ?)';  
        $stmt = $this->db->prepare($sql);  
        return $stmt->execute([$nombre, $email, $claveHash, $rolId, $activo]);  
    }  
 
    public function updateFoto(int $userId, string $foto): bool  
    {  
        $sql = 'UPDATE usuarios SET foto = ? WHERE id = ?';  
        $stmt = $this->db->prepare($sql);  
        return $stmt->execute([$foto, $userId]);  
    }  
  
    public function getAll(): array  
    {  
        $sql = 'SELECT id, nombre, email, rol_id, activo, foto, creado_en FROM usuarios ORDER BY id DESC';  
        return $this->db->query($sql)->fetchAll();  
    }  
  
    public function getByRol(int $rolId): array  
    {  
        $sql = 'SELECT id, nombre, email, rol_id, activo, foto, creado_en FROM usuarios WHERE rol_id = ? ORDER BY id DESC';  
        $stmt = $this->db->prepare($sql);  
        $stmt->execute([$rolId]);  
        return $stmt->fetchAll();  
    }  

    public function getById(int $userId): ?array
    {
        $sql = 'SELECT id, nombre, email, rol_id, activo, foto, creado_en FROM usuarios WHERE id = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
  
    public function findByLogin(string $login): ?array  
    {  
        $sql = 'SELECT id, nombre, email, clave_hash, rol_id, activo, foto FROM usuarios WHERE email = ? OR nombre = ? LIMIT 1';  
        $stmt = $this->db->prepare($sql);  
        $stmt->execute([$login, $login]);  
        $row = $stmt->fetch();  
        return $row ?: null;  
    }  
} 
