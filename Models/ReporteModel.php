<?php
// Este archivo PHP define el modelo de Reporte.
// Centraliza el acceso a base de datos para consultas y operaciones de persistencia relacionadas con esta entidad.
declare(strict_types=1);

require_once __DIR__ . '/../Config/Conexion.php';

class ReporteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::obtener();
    }

    public function obtenerTicketsReporte(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $sql = 'SELECT t.id, t.codigo, t.titulo, u.nombre AS usuario, tec.nombre AS tecnico, '
            . 'c.nombre AS categoria, p.nombre AS prioridad, e.nombre AS estado, t.fecha_creacion, t.fecha_cierre '
            . 'FROM tickets t '
            . 'INNER JOIN usuarios u ON t.usuario_id = u.id '
            . 'LEFT JOIN usuarios tec ON t.tecnico_id = tec.id '
            . 'INNER JOIN categorias c ON t.categoria_id = c.id '
            . 'INNER JOIN prioridades p ON t.prioridad_id = p.id '
            . 'INNER JOIN estados_ticket e ON t.estado_id = e.id '
            . $filtro['where']
            . ' ORDER BY t.fecha_creacion DESC, t.id DESC';

        return $this->ejecutarConsulta($sql, $filtro['params']);
    }

    public function obtenerResumen(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $sql = 'SELECT COUNT(*) AS total, '
            . 'SUM(CASE WHEN LOWER(e.nombre) = \'abierto\' THEN 1 ELSE 0 END) AS abiertos, '
            . 'SUM(CASE WHEN LOWER(e.nombre) IN (\'en progreso\', \'en proceso\') THEN 1 ELSE 0 END) AS en_progreso, '
            . 'SUM(CASE WHEN LOWER(e.nombre) = \'resuelto\' THEN 1 ELSE 0 END) AS resueltos, '
            . 'SUM(CASE WHEN LOWER(e.nombre) = \'cerrado\' THEN 1 ELSE 0 END) AS cerrados '
            . 'FROM tickets t '
            . 'INNER JOIN estados_ticket e ON e.id = t.estado_id '
            . $filtro['where'];

        $fila = $this->ejecutarConsultaUnaFila($sql, $filtro['params']);
        return $fila ?: [
            'total' => 0,
            'abiertos' => 0,
            'en_progreso' => 0,
            'resueltos' => 0,
            'cerrados' => 0,
        ];
    }

    public function obtenerPromedioResolucionHoras(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): float
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $where = $this->anexarCondicion($filtro['where'], 't.fecha_cierre IS NOT NULL');
        $sql = 'SELECT AVG(TIMESTAMPDIFF(MINUTE, t.fecha_creacion, t.fecha_cierre) / 60) AS promedio '
            . 'FROM tickets t '
            . $where;
        $fila = $this->ejecutarConsultaUnaFila($sql, $filtro['params']);
        return round((float) ($fila['promedio'] ?? 0), 2);
    }

    public function obtenerPromedioPrimeraRespuestaHoras(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): float
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $paramsSubconsulta = [];
        $filtroPrimeraRespuesta = 'WHERE u.rol_id = 2';
        if ($idRol === 2) {
            $filtroPrimeraRespuesta = 'WHERE c.usuario_id = ?';
            $paramsSubconsulta[] = $idUsuario;
        }

        $where = $this->anexarCondicion($filtro['where'], 'primera.fecha_primera IS NOT NULL');
        $sql = 'SELECT AVG(TIMESTAMPDIFF(MINUTE, t.fecha_creacion, primera.fecha_primera) / 60) AS promedio '
            . 'FROM tickets t '
            . 'LEFT JOIN ('
            . 'SELECT c.ticket_id, MIN(c.fecha) AS fecha_primera '
            . 'FROM comentarios_ticket c '
            . 'INNER JOIN usuarios u ON u.id = c.usuario_id '
            . $filtroPrimeraRespuesta
            . ' GROUP BY c.ticket_id'
            . ') primera ON primera.ticket_id = t.id '
            . $where;

        $fila = $this->ejecutarConsultaUnaFila($sql, array_merge($paramsSubconsulta, $filtro['params']));
        return round((float) ($fila['promedio'] ?? 0), 2);
    }

    public function obtenerDistribucionEstados(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $sql = 'SELECT e.nombre, COUNT(*) AS total '
            . 'FROM tickets t '
            . 'INNER JOIN estados_ticket e ON e.id = t.estado_id '
            . $filtro['where']
            . ' GROUP BY e.id, e.nombre '
            . 'ORDER BY total DESC, e.nombre ASC';

        return $this->ejecutarConsulta($sql, $filtro['params']);
    }

    public function obtenerDistribucionPrioridades(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $sql = 'SELECT p.nombre, COUNT(*) AS total '
            . 'FROM tickets t '
            . 'INNER JOIN prioridades p ON p.id = t.prioridad_id '
            . $filtro['where']
            . ' GROUP BY p.id, p.nombre '
            . 'ORDER BY total DESC, p.nombre ASC';

        return $this->ejecutarConsulta($sql, $filtro['params']);
    }

    public function obtenerDistribucionCategorias(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $sql = 'SELECT c.nombre, COUNT(*) AS total '
            . 'FROM tickets t '
            . 'INNER JOIN categorias c ON c.id = t.categoria_id '
            . $filtro['where']
            . ' GROUP BY c.id, c.nombre '
            . 'ORDER BY total DESC, c.nombre ASC';

        return $this->ejecutarConsulta($sql, $filtro['params']);
    }

    public function obtenerBacklogPorPrioridad(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $where = $this->anexarCondicion($filtro['where'], 'LOWER(e.nombre) <> \'cerrado\'');
        $sql = 'SELECT p.nombre, COUNT(*) AS total '
            . 'FROM tickets t '
            . 'INNER JOIN prioridades p ON p.id = t.prioridad_id '
            . 'INNER JOIN estados_ticket e ON e.id = t.estado_id '
            . $where
            . ' GROUP BY p.id, p.nombre '
            . 'ORDER BY total DESC, p.nombre ASC';

        return $this->ejecutarConsulta($sql, $filtro['params']);
    }

    public function obtenerRendimientoTecnicos(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $fechaDesdeNormalizada = $this->normalizarFecha($fechaDesde, false);
        $fechaHastaNormalizada = $this->normalizarFecha($fechaHasta, true);
        $params = [];
        $condiciones = [];

        if ($fechaDesdeNormalizada !== null) {
            $condiciones[] = 't.fecha_creacion >= ?';
            $params[] = $fechaDesdeNormalizada;
        }
        if ($fechaHastaNormalizada !== null) {
            $condiciones[] = 't.fecha_creacion <= ?';
            $params[] = $fechaHastaNormalizada;
        }

        $where = $condiciones === [] ? '' : ' WHERE ' . implode(' AND ', $condiciones);
        $sql = 'SELECT u.id, u.nombre, COUNT(*) AS asignados, '
            . 'SUM(CASE WHEN LOWER(e.nombre) = \'cerrado\' THEN 1 ELSE 0 END) AS cerrados, '
            . 'AVG(CASE WHEN t.fecha_cierre IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, t.fecha_creacion, t.fecha_cierre) / 60 END) AS promedio_resolucion_horas '
            . 'FROM tickets t '
            . 'INNER JOIN usuarios u ON u.id = t.tecnico_id '
            . 'INNER JOIN estados_ticket e ON e.id = t.estado_id '
            . $where
            . ' GROUP BY u.id, u.nombre '
            . 'ORDER BY cerrados DESC, asignados DESC, u.nombre ASC';

        $filas = $this->ejecutarConsulta($sql, $params);
        return array_map(static function (array $fila): array {
            $fila['promedio_resolucion_horas'] = round((float) ($fila['promedio_resolucion_horas'] ?? 0), 2);
            return $fila;
        }, $filas);
    }

    public function obtenerTendenciaMensual(int $idRol, int $idUsuario, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $filtro = $this->construirFiltroComun($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $sql = 'SELECT DATE_FORMAT(t.fecha_creacion, \'%Y-%m\') AS periodo, COUNT(*) AS total '
            . 'FROM tickets t '
            . $filtro['where']
            . ' GROUP BY DATE_FORMAT(t.fecha_creacion, \'%Y-%m\') '
            . 'ORDER BY periodo DESC '
            . 'LIMIT 12';

        return $this->ejecutarConsulta($sql, $filtro['params']);
    }

    private function construirFiltroComun(int $idRol, int $idUsuario, ?string $fechaDesde, ?string $fechaHasta): array
    {
        $params = [];
        $condiciones = [];
        foreach ($this->construirCondicionesVisibilidad($idRol, $idUsuario, $params) as $condicion) {
            $condiciones[] = $condicion;
        }
        foreach ($this->construirCondicionesFecha($fechaDesde, $fechaHasta, $params) as $condicion) {
            $condiciones[] = $condicion;
        }

        return [
            'where' => $this->construirWhere($condiciones),
            'params' => $params,
        ];
    }

    private function construirCondicionesVisibilidad(int $idRol, int $idUsuario, array &$params): array
    {
        if ($idRol === 3) {
            $params[] = $idUsuario;
            $params[] = $idUsuario;
            return ['(t.usuario_id = ? OR EXISTS (SELECT 1 FROM ticket_participantes tp WHERE tp.ticket_id = t.id AND tp.usuario_id = ?))'];
        }

        if ($idRol === 2) {
            $params[] = $idUsuario;
            $params[] = $idUsuario;
            return ['(t.tecnico_id = ? OR EXISTS (SELECT 1 FROM ticket_participantes tp WHERE tp.ticket_id = t.id AND tp.usuario_id = ?))'];
        }

        return [];
    }

    private function construirCondicionesFecha(?string $fechaDesde, ?string $fechaHasta, array &$params): array
    {
        $condiciones = [];
        $fechaDesdeNormalizada = $this->normalizarFecha($fechaDesde, false);
        $fechaHastaNormalizada = $this->normalizarFecha($fechaHasta, true);

        if ($fechaDesdeNormalizada !== null) {
            $condiciones[] = 't.fecha_creacion >= ?';
            $params[] = $fechaDesdeNormalizada;
        }
        if ($fechaHastaNormalizada !== null) {
            $condiciones[] = 't.fecha_creacion <= ?';
            $params[] = $fechaHastaNormalizada;
        }

        return $condiciones;
    }

    private function construirWhere(array $condiciones): string
    {
        if ($condiciones === []) {
            return '';
        }
        return ' WHERE ' . implode(' AND ', $condiciones);
    }

    private function anexarCondicion(string $where, string $condicion): string
    {
        if ($where === '') {
            return ' WHERE ' . $condicion;
        }
        return $where . ' AND ' . $condicion;
    }

    private function normalizarFecha(?string $valor, bool $finDelDia): ?string
    {
        $texto = trim((string) ($valor ?? ''));
        if ($texto === '') {
            return null;
        }

        $formatos = ['Y-m-d', 'Y-m-d\TH:i', 'Y-m-d H:i:s'];
        foreach ($formatos as $formato) {
            $fecha = \DateTime::createFromFormat($formato, $texto);
            if ($fecha instanceof \DateTime) {
                if ($formato === 'Y-m-d') {
                    $fecha->setTime($finDelDia ? 23 : 0, $finDelDia ? 59 : 0, $finDelDia ? 59 : 0);
                }
                return $fecha->format('Y-m-d H:i:s');
            }
        }

        return null;
    }

    private function ejecutarConsulta(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function ejecutarConsultaUnaFila(string $sql, array $params): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }
}
