<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/ComentarioModel.php';
require_once __DIR__ . '/../Models/TicketModel.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class ComentarioController extends BaseController
{
    private ComentarioModel $model;
    private TicketModel $tickets;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->model = new ComentarioModel();
        $this->tickets = new TicketModel();
        $this->notifications = new NotificationService();
    }

    public function list(): void
    {
        $this->requireLogin();
        $rolId = $this->currentRoleId();
        $userId = $this->currentUserId();
        $ticketIds = array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['ticket_ids'] ?? '')))));

        if ($rolId === 3) {
            $rows = $this->model->getByUsuario($userId);
        } elseif ($rolId === 2) {
            $rows = $this->model->getByTecnico($userId);
        } else {
            $rows = $this->model->getAll();
        }

        if ($ticketIds !== []) {
            $allowedIds = array_flip($ticketIds);
            $rows = array_values(array_filter($rows, static function (array $row) use ($allowedIds): bool {
                return isset($allowedIds[(int) ($row['ticket_id'] ?? 0)]);
            }));
        }

        $this->jsonOk('Comentarios cargados', $rows);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $payload = $this->requestData();
        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $comentario = trim(strip_tags((string) ($payload['comentario'] ?? '')));
        $rolId = $this->currentRoleId();
        $userId = $this->currentUserId();

        if ($ticketId <= 0 || $comentario === '') {
            $this->jsonError('Datos invalidos.');
        }

        $ticket = $this->tickets->getById($ticketId);
        if (!$ticket) {
            $this->jsonError('Ticket no encontrado.', 404);
        }
        if ($rolId === 3 && (int) $ticket['usuario_id'] !== $userId) {
            $this->jsonError('No puedes comentar este ticket.', 403);
        }
        if ($rolId === 2 && (int) ($ticket['tecnico_id'] ?? 0) !== $userId) {
            $this->jsonError('No puedes comentar este ticket.', 403);
        }

        $ok = $this->model->insert($ticketId, $userId, $comentario);
        if (!$ok) {
            $this->jsonError('No se pudo crear.');
        }

        $commentId = $this->model->getLastInsertId();
        $this->notifications->notifyReply($ticket, $userId, $comentario);
        $this->jsonOk('Comentario creado', ['id' => $commentId]);
    }
}
