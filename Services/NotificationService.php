<?php
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
        try {
            return [
                'count' => $this->notificaciones->contarNoLeidasPorUsuario($idUsuario),
                'items' => $this->notificaciones->obtenerPorUsuario($idUsuario, $limite),
            ];
        } catch (Throwable $exception) {
            return [
                'count' => 0,
                'items' => [],
            ];
        }
    }

    public function marcarComoLeida(int $idNotificacion, int $idUsuario): bool
    {
        try {
            return $this->notificaciones->marcarComoLeida($idNotificacion, $idUsuario);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function marcarTodasComoLeidas(int $idUsuario): bool
    {
        try {
            return $this->notificaciones->marcarTodasComoLeidas($idUsuario);
        } catch (Throwable $exception) {
            return false;
        }
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
        $destinatarios = [];
        foreach ($this->usuarios->obtenerIdsAdmin() as $idAdmin) {
            if ($idAdmin !== $idActor) {
                $destinatarios[] = $idAdmin;
            }
        }

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
        foreach ($this->usuarios->obtenerIdsAdmin() as $idAdmin) {
            if ($idAdmin !== $idActor) {
                $destinatarios[] = $idAdmin;
            }
        }
        $destinatarios = array_values(array_unique(array_filter($destinatarios)));

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
        foreach (array_values(array_unique(array_filter($idsDestinatarios))) as $idDestinatario) {
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

    private function construirResumenTicket(array $ticket): string
    {
        $codigo = trim((string) ($ticket['codigo'] ?? 'Ticket'));
        $titulo = trim((string) ($ticket['titulo'] ?? ''));
        return $titulo === '' ? $codigo : $codigo . ' - ' . $titulo;
    }
}
