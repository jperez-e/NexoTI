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
        $rolId = (int) ($_SESSION['rol_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $rows = $this->model->getAll();

        if ($rolId === 3) {
            $rows = array_values(array_filter($rows, static function (array $row) use ($userId): bool { return (int) ($row['ticket_usuario_id'] ?? 0) === $userId; }));
        } elseif ($rolId === 2) {
            $rows = array_values(array_filter($rows, static function (array $row) use ($userId): bool { return (int) ($row['ticket_tecnico_id'] ?? 0) === $userId; }));
        }

        $this->jsonOk('Comentarios cargados', $rows);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requirePost();
        $payload = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($payload)) { $payload = $_POST; }
        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $comentario = trim(strip_tags((string) ($payload['comentario'] ?? '')));
        $rolId = (int) ($_SESSION['rol_id'] ?? 0);
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($ticketId <= 0 || $comentario === '') { $this->jsonError('Datos invalidos'); }
        $ticket = $this->tickets->getById($ticketId);
        if (!$ticket) { $this->jsonError('Ticket no encontrado.'); }
        if ($rolId === 3 && (int) $ticket['usuario_id'] !== $userId) { $this->jsonError('No puedes comentar este ticket.'); }
        if ($rolId === 2 && (int) ($ticket['tecnico_id'] ?? 0) !== $userId) { $this->jsonError('No puedes comentar este ticket.'); }
        $ok = $this->model->insert($ticketId, $userId, $comentario);
        $ok ? $this->jsonOk('Comentario creado') : $this->jsonError('No se pudo crear');
    }
}
