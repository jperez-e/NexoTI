<?php
/*
este servicio se encarga de gestionar las notificaciones relacionadas a los tickets, 
incluyendo la creación, asignación, respuestas y cierres. 
Proporciona métodos para obtener las notificaciones de un usuario, 
marcar notificaciones como leídas 
y enviar notificaciones a los usuarios relevantes 
cuando ocurren eventos importantes en el ciclo de vida de un ticket. 
El servicio también maneja la lógica para determinar qué usuarios 
deben recibir notificaciones basándose en su rol y participación en el ticket.
*/

declare(strict_types=1);

require_once __DIR__ . '/../Models/NotificacionModel.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';

class NotificationService
{
    private NotificacionModel $notificaciones;
    private UsuarioModel $usuarios;

    public function __construct()
    {
        $this->notificaciones = new NotificacionModel();
        $this->usuarios = new UsuarioModel();
    }

    // Devuelve un array con 'count' (cantidad de notificaciones no leídas) 
    // e 'items' (array de notificaciones recientes).
    // El parámetro $limite controla cuántas notificaciones 
    // recientes se devuelven (por defecto 8).
    public function obtenerDatosPanel(int $idUsuario, int $limite = 8): array
    {
        return $this->ejecutarSeguro(
            function () use ($idUsuario, $limite): array {
                return [
                'count' => $this->notificaciones->contarNoLeidasPorUsuario($idUsuario),
                'items' => $this->notificaciones->obtenerPorUsuario($idUsuario, $limite),
                ];
            },
            [
                'count' => 0,
                'items' => [],
            ]
        );
    }

    // Marca una notificación específica como leída para un usuario dado.
    // Devuelve true si la operación fue exitosa, 
    // false en caso de error o si la notificación no pertenece al usuario.
    public function marcarComoLeida(int $idNotificacion, int $idUsuario): bool
    {
        return $this->ejecutarSeguro(
            function () use ($idNotificacion, $idUsuario): bool {
                return $this->notificaciones->marcarComoLeida($idNotificacion, $idUsuario);
            },
            false
        );
    }

    // Marca todas las notificaciones de un usuario como leídas.
    // Devuelve true si la operación fue exitosa, false en caso de error.
    public function marcarTodasComoLeidas(int $idUsuario): bool
    {
        return $this->ejecutarSeguro(
            function () use ($idUsuario): bool {
                return $this->notificaciones->marcarTodasComoLeidas($idUsuario);
            },
            false
        );
    }

    // Envía una notificación de asignación a un técnico cuando se le asigna un ticket.
    // Envía una notificación de creación a los administradores cuando se crea un nuevo ticket.
    // Envía una notificación de respuesta a los participantes de un ticket cuando se agrega un nuevo comentario.
    // Envía una notificación de cierre a los participantes de un ticket cuando se cierra un ticket.
    // Envía una notificación de cambio de estado a los usuarios finales cuando un técnico actualiza el estado de un ticket.
    public function notificarAsignacion(array $ticket, ?int $idTecnico, int $idActor): void
    {
        if ($idTecnico === null || $idTecnico <= 0 || $idTecnico === $idActor) {
            return;
        }

        $this->notificarUsuarios(
            [$idTecnico],
            (int) ($ticket['id'] ?? 0),
            $idActor,
            'asignacion',
            'Nuevo ticket asignado',
            $this->construirResumenTicket($ticket) . ' fue asignado a ti.'
        );
    }

    // Envía una notificación de creación a los administradores cuando se crea un nuevo ticket.
// Envía una notificación de respuesta a los participantes de un ticket cuando se agrega un nuevo comentario.
// Envía una notificación de cierre a los participantes de un ticket cuando se cierra un ticket.
// Envía una notificación de cambio de estado a los usuarios finales cuando un técnico actualiza el estado de un ticket.
// Envía una notificación de asignación a un técnico cuando se le asigna un ticket.
    public function notificarCreacion(array $ticket, int $idActor): void
    {
        $destinatarios = $this->obtenerIdsAdminSinActor($idActor);

        if ($destinatarios === []) {
            return;
        }

        $this->notificarUsuarios(
            $destinatarios,
            (int) ($ticket['id'] ?? 0),
            $idActor,
            'creacion',
            'Nuevo ticket creado',
            $this->construirResumenTicket($ticket) . ' fue creado y requiere revision.'
        );
    }

    /*
        * Envía una notificación de respuesta a los participantes de un ticket cuando se agrega un nuevo comentario.
        * Envía una notificación de cierre a los participantes de un ticket cuando se cierra un ticket.
        * Envía una notificación de cambio de estado a los usuarios finales cuando un técnico actualiza el estado de un ticket.
        * Envía una notificación de asignación a un técnico cuando se le asigna un ticket.  
    */
    public function notificarRespuesta(array $ticket, int $idActor, string $comentario): void
    {
        $destinatarios = $this->recolectarParticipantesTicket($ticket, $idActor);
        if ($destinatarios === []) {
            return;
        }

        $extracto = trim($comentario);
        if (mb_strlen($extracto) > 90) {
            $extracto = mb_substr($extracto, 0, 87) . '...';
        }

        $this->notificarUsuarios(
            $destinatarios,
            (int) ($ticket['id'] ?? 0),
            $idActor,
            'respuesta',
            'Nueva respuesta en ticket',
            $this->construirResumenTicket($ticket) . ': ' . $extracto
        );
    }

    /*
        * Envía una notificación de cierre a los participantes de un ticket cuando se cierra un ticket.
        * Envía una notificación de cambio de estado a los usuarios finales cuando un técnico actualiza el estado de un ticket.
        * Envía una notificación de asignación a un técnico cuando se le asigna un ticket.
    */
    public function notificarCierre(array $ticket, int $idActor): void
    {
        $destinatarios = $this->recolectarParticipantesTicket($ticket, $idActor);
        $destinatarios = $this->normalizarDestinatarios(array_merge($destinatarios, $this->obtenerIdsAdminSinActor($idActor)));

        if ($destinatarios === []) {
            return;
        }

        $this->notificarUsuarios(
            $destinatarios,
            (int) ($ticket['id'] ?? 0),
            $idActor,
            'cierre',
            'Ticket cerrado',
            $this->construirResumenTicket($ticket) . ' fue cerrado por el usuario.'
        );
    }

    /*
        * Envía una notificación de cambio de estado a los usuarios finales cuando un técnico actualiza el estado de un ticket.
        * Envía una notificación de asignación a un técnico cuando se le asigna un ticket.
    */
    public function notificarCambioEstadoPorTecnico(array $ticket, int $idActor): void
    {
        $idUsuario = (int) ($ticket['usuario_id'] ?? 0);
        if ($idUsuario <= 0 || $idUsuario === $idActor) {
            return;
        }

        $usuario = $this->usuarios->obtenerPorId($idUsuario);
        if (!$usuario || (int) ($usuario['rol_id'] ?? 0) !== 3) {
            return;
        }

        $this->notificarUsuarios(
            [$idUsuario],
            (int) ($ticket['id'] ?? 0),
            $idActor,
            'estado',
            'Estado del ticket actualizado',
            $this->construirResumenTicket($ticket) . ' fue actualizado por el técnico.'
        );
    }

    /*
        * Envía una notificación de asignación a un técnico cuando se le asigna un ticket.
    */
    /**
     * @param int[] $idsDestinatarios
     */
    private function notificarUsuarios(
        array $idsDestinatarios,
        int $idTicket,
        int $idActor,
        string $tipo,
        string $titulo,
        string $mensaje
    ): void {
        foreach ($this->normalizarDestinatarios($idsDestinatarios) as $idDestinatario) {
            if ($idDestinatario === $idActor) {
                continue;
            }

            try {
                $this->notificaciones->insertar(
                    $idDestinatario,
                    $idTicket > 0 ? $idTicket : null,
                    $idActor,
                    $tipo,
                    $titulo,
                    $mensaje
                );
            } catch (Throwable $exception) {
                // Si la migracion aun no corre, el flujo principal del ticket no debe romperse.
            }
        }
    }

    /*
        * Recolecta los IDs de los participantes relevantes de un ticket (usuario final y técnico) 
        * excluyendo al actor que genera la notificación, para evitar auto-notificaciones.
    */
    /**
     * @return int[]
     */
    private function recolectarParticipantesTicket(array $ticket, int $idActor): array
    {
        $destinatarios = [];
        $idUsuario = (int) ($ticket['usuario_id'] ?? 0);
        $idTecnico = (int) ($ticket['tecnico_id'] ?? 0);

        if ($idUsuario > 0 && $idUsuario !== $idActor) {
            $destinatarios[] = $idUsuario;
        }
        if ($idTecnico > 0 && $idTecnico !== $idActor) {
            $destinatarios[] = $idTecnico;
        }

        return $destinatarios;
    }

    
    /**
     * Normaliza un array de IDs de destinatarios, eliminando duplicados y valores no válidos.
     *
     * @param int[] $ids
     * @return int[]
     */
    private function normalizarDestinatarios(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /*
        * Obtiene los IDs de los administradores del sistema, excluyendo al actor que genera la notificación, para evitar auto-notificaciones.
        * Esto se utiliza principalmente para enviar notificaciones de creación de tickets a los administradores sin incluir al usuario que creó el ticket.
    */
    /**
     * @return int[]
     */
    private function obtenerIdsAdminSinActor(int $idActor): array
    {
        $ids = [];
        foreach ($this->usuarios->obtenerIdsAdmin() as $idAdmin) {
            if ((int) $idAdmin !== $idActor) {
                $ids[] = (int) $idAdmin;
            }
        }
        return $this->normalizarDestinatarios($ids);
    }

    /*
        * Normaliza los filtros de búsqueda, asegurando que tengan valores válidos y consistentes.
        * Esto ayuda a evitar errores en la capa de datos y proporciona una experiencia de búsqueda más predecible.
    */  
    /**
     * @template T
     * @param callable():T $operacion
     * @param T $fallback
     * @return T
     */
    private function ejecutarSeguro(callable $operacion, mixed $fallback): mixed
    {
        try {
            return $operacion();
        } catch (Throwable $exception) {
            return $fallback;
        }
    }

    /*
        * Construye un resumen legible de un ticket para usar en las notificaciones, combinando el código y el título del ticket.
        * Si el título está vacío, se devuelve solo el código. De lo contrario, se devuelve "Código - Título".
    */
    private function construirResumenTicket(array $ticket): string
    {
        $codigo = trim((string) ($ticket['codigo'] ?? 'Ticket'));
        $titulo = trim((string) ($ticket['titulo'] ?? ''));
        return $titulo === '' ? $codigo : $codigo . ' - ' . $titulo;
    }
}
