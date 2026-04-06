<?php
// Este archivo PHP define el servicio de Notification.
// Contiene lógica de negocio reutilizable para mantener los controladores más simples y enfocados en la capa HTTP.
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

    public function marcarComoLeida(int $idNotificacion, int $idUsuario): bool
    {
        return $this->ejecutarSeguro(
            function () use ($idNotificacion, $idUsuario): bool {
                return $this->notificaciones->marcarComoLeida($idNotificacion, $idUsuario);
            },
            false
        );
    }

    public function marcarTodasComoLeidas(int $idUsuario): bool
    {
        return $this->ejecutarSeguro(
            function () use ($idUsuario): bool {
                return $this->notificaciones->marcarTodasComoLeidas($idUsuario);
            },
            false
        );
    }

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
     * @param int[] $ids
     * @return int[]
     */
    private function normalizarDestinatarios(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

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

    private function construirResumenTicket(array $ticket): string
    {
        $codigo = trim((string) ($ticket['codigo'] ?? 'Ticket'));
        $titulo = trim((string) ($ticket['titulo'] ?? ''));
        return $titulo === '' ? $codigo : $codigo . ' - ' . $titulo;
    }
}
