<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/TicketModel.php';
require_once __DIR__ . '/../Models/AdjuntoModel.php';

class TicketController extends BaseController
{
    private TicketModel $model;
    private AdjuntoModel $adjuntos;

    public function __construct()
    {
        $this->model = new TicketModel();
        $this->adjuntos = new AdjuntoModel();
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
        $this->jsonOk('Tickets cargados', $rows);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $codigo = trim(strip_tags((string) ($_POST['codigo'] ?? '')));
        $titulo = trim(strip_tags((string) ($_POST['titulo'] ?? '')));
        $descripcion = trim(strip_tags((string) ($_POST['descripcion'] ?? '')));
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $prioridadId = (int) ($_POST['prioridad_id'] ?? 0);
        $estadoId = (int) ($_POST['estado_id'] ?? 0);
        $fechaOcurrenciaRaw = trim((string) ($_POST['fecha_ocurrencia'] ?? ''));

        $sessionUserId = $this->currentUserId();
        $rolId = $this->currentRoleId();
        $usuarioId = (int) ($_POST['usuario_id'] ?? $sessionUserId);
        $fechaCreacion = null;
        if ($fechaOcurrenciaRaw !== '') {
            $date = \DateTime::createFromFormat('Y-m-d\TH:i', $fechaOcurrenciaRaw);
            if ($date === false) {
                $this->jsonError('Fecha de ocurrencia invalida.');
            }
            $fechaCreacion = $date->format('Y-m-d H:i:s');
        }
        if ($rolId === 3) {
            // Regla de negocio: los tickets creados por el usuario final siempre nacen abiertos.
            $usuarioId = $sessionUserId;
            $estadoInicialId = $this->model->getEstadoIdByNombre('Abierto');
            if ($estadoInicialId !== null) {
                $estadoId = $estadoInicialId;
            }
        }

        if ($codigo === '' || $titulo === '' || $descripcion === '' || $categoriaId <= 0 || $prioridadId <= 0 || $estadoId <= 0) {
            $this->jsonError('Completa todos los campos.');
        }

        $ok = $this->model->insert(
            $codigo,
            $titulo,
            $descripcion,
            $usuarioId,
            $categoriaId,
            $prioridadId,
            $estadoId,
            $fechaCreacion,
            null,
            null
        );
        if (!$ok) {
            $this->jsonError('No se pudo guardar el ticket.');
        }

        $ticketId = $this->model->getLastInsertId();
        $this->handleAdjunto($ticketId);

        $this->jsonOk('Ticket creado', ['id' => $ticketId]);
    }

    private function handleAdjunto(int $ticketId): void
    {
        if (!isset($_FILES['adjunto']) || $_FILES['adjunto']['error'] !== UPLOAD_ERR_OK) {
            return;
        }

        $baseDir = dirname(__DIR__);
        $dir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tickets';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $tmp = $_FILES['adjunto']['tmp_name'];
        $name = basename((string) $_FILES['adjunto']['name']);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $filename = 'ticket_' . $ticketId . '_' . time();
        if ($ext !== '') {
            $filename .= '.' . $ext;
        }
        $dest = $dir . DIRECTORY_SEPARATOR . $filename;

        if (move_uploaded_file($tmp, $dest)) {
            $relative = 'uploads/tickets/' . $filename;
            $this->adjuntos->insert($ticketId, $relative, $name);
        }
    }

    public function assign(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $payload = $this->requestData();

        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $estadoId = (int) ($payload['estado_id'] ?? 0);
        $tecnicoRaw = $payload['tecnico_id'] ?? null;
        $tecnicoId = $tecnicoRaw === null || $tecnicoRaw === '' ? null : (int) $tecnicoRaw;

        if ($ticketId <= 0 || $estadoId <= 0) {
            $this->jsonError('Datos incompletos.');
        }

        if (!$this->model->assign($ticketId, $tecnicoId, $estadoId)) {
            $this->jsonError('No se pudo actualizar.');
        }

        $this->jsonOk('Asignacion actualizada');
    }

    public function updateStatus(): void
    {
        $this->requireLogin();
        $this->requireRole([1, 2]);
        $this->requirePost();

        $payload = $this->requestData();

        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $estadoId = (int) ($payload['estado_id'] ?? 0);

        if ($ticketId <= 0 || $estadoId <= 0) {
            $this->jsonError('Datos incompletos.');
        }

        $ticket = $this->model->getById($ticketId);
        if (!$ticket) {
            $this->jsonError('Ticket no encontrado.');
        }

        $rolId = $this->currentRoleId();
        $userId = $this->currentUserId();
        if ($rolId === 2 && (int) $ticket['tecnico_id'] !== $userId) {
            $this->jsonError('No puedes actualizar este ticket.', 403);
        }

        $cerradoId = $this->model->getEstadoIdByNombre('Cerrado');
        $fechaCierre = null;
        if ($cerradoId !== null && $estadoId === $cerradoId) {
            $fechaCierre = date('Y-m-d H:i:s');
        }

        if (!$this->model->updateEstado($ticketId, $estadoId, $fechaCierre)) {
            $this->jsonError('No se pudo actualizar el estado.');
        }

        $this->jsonOk('Estado actualizado');
    }

    public function closeTicket(): void
    {
        $this->requireLogin();
        $this->requireRole([3]);
        $this->requirePost();

        $payload = $this->requestData();

        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            $this->jsonError('Selecciona un ticket.');
        }

        $ticket = $this->model->getById($ticketId);
        if (!$ticket) {
            $this->jsonError('Ticket no encontrado.', 404);
        }

        $userId = $this->currentUserId();
        if ((int) $ticket['usuario_id'] !== $userId) {
            $this->jsonError('No puedes cerrar este ticket.', 403);
        }

        $cerradoId = $this->model->getEstadoIdByNombre('Cerrado');
        if ($cerradoId === null) {
            $this->jsonError('No existe el estado Cerrado.');
        }

        $fechaCierre = date('Y-m-d H:i:s');
        if (!$this->model->updateEstado($ticketId, $cerradoId, $fechaCierre)) {
            $this->jsonError('No se pudo cerrar el ticket.');
        }

        $this->jsonOk('Ticket cerrado');
    }
}
