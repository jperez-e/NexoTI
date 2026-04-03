<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/ComentarioModel.php';
require_once __DIR__ . '/../Models/TicketModel.php';
require_once __DIR__ . '/../Models/TicketParticipanteModel.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class ComentarioController extends BaseController
{
    private ComentarioModel $model;
    private TicketModel $tickets;
    private TicketParticipanteModel $participantes;
    private UsuarioModel $usuarios;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->model = new ComentarioModel();
        $this->tickets = new TicketModel();
        $this->participantes = new TicketParticipanteModel();
        $this->usuarios = new UsuarioModel();
        $this->notifications = new NotificationService();
    }

    public function listar(): void
    {
        $this->requerirSesion();
        $rolId = $this->obtenerIdRolActual();
        $userId = $this->obtenerIdUsuarioActual();
        $ticketIds = array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['ticket_ids'] ?? '')))));

        if ($rolId === 3) {
            $rows = $this->model->obtenerPorUsuario($userId);
        } elseif ($rolId === 2) {
            $rows = $this->model->obtenerPorTecnico($userId);
        } else {
            $rows = $this->model->obtenerTodos();
        }

        if ($ticketIds !== []) {
            $allowedIds = array_flip($ticketIds);
            $rows = array_values(array_filter($rows, static function (array $row) use ($allowedIds): bool {
                return isset($allowedIds[(int) ($row['ticket_id'] ?? 0)]);
            }));
        }

        $this->responderOkJson('Comentarios cargados', $rows);
    }

    public function crear(): void
    {
        $this->requerirSesion();
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();
        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $comentario = trim(strip_tags((string) ($payload['comentario'] ?? '')));
        $rolId = $this->obtenerIdRolActual();
        $userId = $this->obtenerIdUsuarioActual();

        if ($ticketId <= 0 || $comentario === '') {
            $this->responderErrorJson('Datos invalidos.');
        }

        $ticket = $this->tickets->obtenerPorId($ticketId);
        if (!$ticket) {
            $this->responderErrorJson('Ticket no encontrado.', 404);
        }
        $esParticipante = $this->participantes->esParticipante($ticketId, $userId);
        if ($rolId === 3 && (int) $ticket['usuario_id'] !== $userId && !$esParticipante) {
            $this->responderErrorJson('No puedes comentar este ticket.', 403);
        }
        if (
            $rolId === 2
            && (int) ($ticket['tecnico_id'] ?? 0) !== $userId
            && (int) ($ticket['usuario_id'] ?? 0) !== $userId
            && !$esParticipante
            && !$this->ticketCreadoPorTecnico($ticket)
        ) {
            $this->responderErrorJson('No puedes comentar este ticket.', 403);
        }

        $ok = $this->model->insertar($ticketId, $userId, $comentario);
        if (!$ok) {
            $this->responderErrorJson('No se pudo crear.');
        }

        $commentId = $this->model->obtenerUltimoIdInsertado();
        $this->notifications->notificarRespuesta($ticket, $userId, $comentario);
        $this->responderOkJson('Comentario creado', ['id' => $commentId]);
    }

    private function ticketCreadoPorTecnico(array $ticket): bool
    {
        $creadorId = (int) ($ticket['usuario_id'] ?? 0);
        if ($creadorId <= 0) {
            return false;
        }
        $creador = $this->usuarios->obtenerPorId($creadorId);
        return (int) ($creador['rol_id'] ?? 0) === 2;
    }
}
