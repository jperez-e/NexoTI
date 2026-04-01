<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/NotificacionModel.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';

class NotificationService
{
    private NotificacionModel $notifications;
    private UsuarioModel $users;

    public function __construct()
    {
        $this->notifications = new NotificacionModel();
        $this->users = new UsuarioModel();
    }

    public function getPanelData(int $userId, int $limit = 8): array
    {
        try {
            return [
                'count' => $this->notifications->countUnreadByUsuario($userId),
                'items' => $this->notifications->getByUsuario($userId, $limit),
            ];
        } catch (Throwable $exception) {
            return [
                'count' => 0,
                'items' => [],
            ];
        }
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        try {
            return $this->notifications->markAsRead($notificationId, $userId);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function markAllAsRead(int $userId): bool
    {
        try {
            return $this->notifications->markAllAsRead($userId);
        } catch (Throwable $exception) {
            return false;
        }
    }

    public function notifyAssignment(array $ticket, ?int $tecnicoId, int $actorId): void
    {
        if ($tecnicoId === null || $tecnicoId <= 0 || $tecnicoId === $actorId) {
            return;
        }

        $this->notifyUsers(
            [$tecnicoId],
            (int) ($ticket['id'] ?? 0),
            $actorId,
            'asignacion',
            'Nuevo ticket asignado',
            $this->buildTicketSummary($ticket) . ' fue asignado a ti.'
        );
    }

    public function notifyReply(array $ticket, int $actorId, string $comment): void
    {
        $recipients = $this->collectTicketParticipants($ticket, $actorId);
        if ($recipients === []) {
            return;
        }

        $excerpt = trim($comment);
        if (mb_strlen($excerpt) > 90) {
            $excerpt = mb_substr($excerpt, 0, 87) . '...';
        }

        $this->notifyUsers(
            $recipients,
            (int) ($ticket['id'] ?? 0),
            $actorId,
            'respuesta',
            'Nueva respuesta en ticket',
            $this->buildTicketSummary($ticket) . ': ' . $excerpt
        );
    }

    public function notifyClosed(array $ticket, int $actorId): void
    {
        $recipients = $this->collectTicketParticipants($ticket, $actorId);
        foreach ($this->users->getAdminIds() as $adminId) {
            if ($adminId !== $actorId) {
                $recipients[] = $adminId;
            }
        }
        $recipients = array_values(array_unique(array_filter($recipients)));

        if ($recipients === []) {
            return;
        }

        $this->notifyUsers(
            $recipients,
            (int) ($ticket['id'] ?? 0),
            $actorId,
            'cierre',
            'Ticket cerrado',
            $this->buildTicketSummary($ticket) . ' fue cerrado por el usuario.'
        );
    }

    /**
     * @param int[] $recipientIds
     */
    private function notifyUsers(
        array $recipientIds,
        int $ticketId,
        int $actorId,
        string $type,
        string $title,
        string $message
    ): void {
        foreach (array_values(array_unique(array_filter($recipientIds))) as $recipientId) {
            if ($recipientId === $actorId) {
                continue;
            }

            try {
                $this->notifications->insert($recipientId, $ticketId > 0 ? $ticketId : null, $actorId, $type, $title, $message);
            } catch (Throwable $exception) {
                // Si la migracion aun no corre, el flujo principal del ticket no debe romperse.
            }
        }
    }

    /**
     * @return int[]
     */
    private function collectTicketParticipants(array $ticket, int $actorId): array
    {
        $recipients = [];
        $usuarioId = (int) ($ticket['usuario_id'] ?? 0);
        $tecnicoId = (int) ($ticket['tecnico_id'] ?? 0);

        if ($usuarioId > 0 && $usuarioId !== $actorId) {
            $recipients[] = $usuarioId;
        }
        if ($tecnicoId > 0 && $tecnicoId !== $actorId) {
            $recipients[] = $tecnicoId;
        }

        return $recipients;
    }

    private function buildTicketSummary(array $ticket): string
    {
        $code = trim((string) ($ticket['codigo'] ?? 'Ticket'));
        $title = trim((string) ($ticket['titulo'] ?? ''));
        return $title === '' ? $code : $code . ' - ' . $title;
    }
}
