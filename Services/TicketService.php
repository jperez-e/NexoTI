<?php
/*
    * TicketService es responsable de manejar la lógica de negocio relacionada con los tickets, incluyendo la generación de códigos únicos, la normalización de filtros de búsqueda y la interacción con el modelo de datos para obtener y manipular tickets.
    * También se encarga de coordinar con el NotificationService para enviar notificaciones relevantes a los usuarios cuando se crean, actualizan o cierran tickets.
*/
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

    /*
        * Lista los tickets según los filtros proporcionados, paginando los resultados y devolviendo información adicional como el total de tickets, el número de páginas y las opciones de asignación y cierre disponibles para el usuario.
        * También incluye las notificaciones relevantes para el usuario en el panel de tickets.
    */
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

    /*
        * Genera un código único para un nuevo ticket, asegurándose de que no exista ya en la base de datos.
        * El formato del código es "TCK-" seguido de un número aleatorio de 13 dígitos.
         * Se utiliza un bucle do-while para generar códigos hasta que se encuentra uno que
    */
    public function generarCodigoTicket(): string
    {
        do {
            $codigo = 'TCK-' . (string) random_int(1000000000000, 9999999999999);
        } while ($this->tickets->existeCodigo($codigo));

        return $codigo;
    }

    /*
        * Normaliza los filtros de búsqueda, asegurándose de que tengan valores válidos y consistentes.
        * Esto ayuda a evitar errores en la capa de datos y proporciona una experiencia de búsqueda más predecible.
    */  
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

    /*
        * Normaliza el filtro de estado, mapeando valores comunes a los estados internos del sistema.
        * Si el valor es "todos" o está vacío, se devuelve null para indicar que no se debe filtrar por estado.
        * Si el valor es "en progreso", se mapea a "En proceso" para coincidir con el estado interno.
        * Para otros valores, se capitaliza la primera letra para intentar coincidir con los estados internos.
    */
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

    /*
        * Normaliza el filtro de asignación, mapeando valores comunes a los estados internos del sistema.
        * Si el valor es "todos" o está vacío, se devuelve null para indicar que no se debe filtrar por asignación.
        * Si el valor es "asignados", se mapea a "asignados" para coincidir con el estado interno.
        * Si el valor es "no asignados", "sin asignar" o variantes similares, se mapea a "sin_asignar" para coincidir con el estado interno.
        * Para otros valores, se devuelve null para indicar que no se debe filtrar por asignación.
    */
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

    /*
        * Normaliza una etiqueta de texto, eliminando espacios extra, convirtiendo a minúsculas y eliminando acentos para facilitar la comparación y el mapeo de valores.
        * Esto es útil para normalizar los filtros de búsqueda y otros valores de entrada que pueden tener variaciones en formato o acentos.        
    */
    private function normalizarEtiqueta(string $valor): string
    {
        $normalizado = mb_strtolower(trim($valor));
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizado) ?: $normalizado;
        return preg_replace('/\s+/', ' ', $normalizado) ?? $normalizado;
    }
}
