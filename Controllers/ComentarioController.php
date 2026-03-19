<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/ComentarioModel.php';
require_once __DIR__ . '/../Models/TicketModel.php';

class ComentarioController extends BaseController
{
    private ComentarioModel $model;
    private TicketModel $tickets;

    public function __construct()
    {
        $this->model = new ComentarioModel();
        $this->tickets = new TicketModel();
    }

    public function list(): void
    {
        $this->requireLogin();
        $rolId = $this->currentRoleId();
        $userId = $this->currentUserId();

        if ($rolId === 3) {
            $rows = $this->model->getByUsuario($userId);
        } elseif ($rolId === 2) {
            $rows = $this->model->getByTecnico($userId);
        } else {
            $rows = $this->model->getAll();
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
        $ok ? $this->jsonOk('Comentario creado') : $this->jsonError('No se pudo crear.');
    }
}
