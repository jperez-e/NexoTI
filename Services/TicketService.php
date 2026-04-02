<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/TicketModel.php';
require_once __DIR__ . '/NotificationService.php';

class TicketService
{
    private TicketModel $tickets;
    private NotificationService $notificaciones;

    public function __construct()
    {
        $this->tickets = new TicketModel();
        $this->notificaciones = new NotificationService();
    }

    public function listarTickets(array $filtros, int $pagina, int $porPagina, int $idRol, int $idUsuario): array
    {
        $pagina = max(1, $pagina);
        $porPagina = max(1, min(20, $porPagina));
        $filtrosNormalizados = $this->normalizarFiltros($filtros);

        $total = $this->tickets->contarBusqueda($filtrosNormalizados, $idRol, $idUsuario);
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        if ($pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }

        return [
            'items' => $this->tickets->buscar($filtrosNormalizados, $pagina, $porPagina, $idRol, $idUsuario),
            'meta' => [
                'page' => $pagina,
                'per_page' => $porPagina,
                'total' => $total,
                'total_pages' => $totalPaginas,
                'query' => $filtrosNormalizados['query'],
                'estado' => $filtrosNormalizados['estado'],
                'asignacion' => $filtrosNormalizados['asignacion'],
            ],
            'assignable' => $idRol === 1 ? $this->tickets->obtenerAbiertosParaAsignacion() : [],
            'closable' => $idRol === 3 ? $this->tickets->obtenerResueltosPorUsuario($idUsuario) : [],
            'notifications' => $this->notificaciones->obtenerDatosPanel($idUsuario, 8),
        ];
    }

    public function generarCodigoTicket(): string
    {
        do {
            $codigo = 'TCK-' . (string) random_int(1000000000000, 9999999999999);
        } while ($this->tickets->existeCodigo($codigo));

        return $codigo;
    }

    private function normalizarFiltros(array $filtros): array
    {
        $query = trim((string) ($filtros['query'] ?? ''));
        $estado = $this->normalizarFiltroEstado((string) ($filtros['estado'] ?? 'todos'));

        return [
            'query' => $query,
            'estado' => $estado,
            'asignacion' => $this->normalizarFiltroAsignacion((string) ($filtros['asignacion'] ?? 'todos')),
        ];
    }

    private function normalizarFiltroEstado(string $valor): ?string
    {
        $normalizado = $this->normalizarEtiqueta($valor);
        if ($normalizado === '' || $normalizado === 'todos') {
            return null;
        }
        if ($normalizado === 'en progreso') {
            return 'En proceso';
        }

        return ucfirst($normalizado);
    }

    private function normalizarFiltroAsignacion(string $valor): ?string
    {
        $normalizado = $this->normalizarEtiqueta($valor);
        if ($normalizado === '' || $normalizado === 'todos') {
            return null;
        }
        if ($normalizado === 'asignados') {
            return 'asignados';
        }
        if ($normalizado === 'no asignados' || $normalizado === 'no_asignados' || $normalizado === 'sin asignar' || $normalizado === 'sin_asignar') {
            return 'sin_asignar';
        }
        return null;
    }

    private function normalizarEtiqueta(string $valor): string
    {
        $normalizado = mb_strtolower(trim($valor));
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizado) ?: $normalizado;
        return preg_replace('/\s+/', ' ', $normalizado) ?? $normalizado;
    }
}
