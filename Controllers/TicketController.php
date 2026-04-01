<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/TicketModel.php';
require_once __DIR__ . '/../Models/AdjuntoModel.php';
require_once __DIR__ . '/../Services/TicketService.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class TicketController extends BaseController
{
    private TicketModel $model;
    private AdjuntoModel $adjuntos;
    private TicketService $service;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->model = new TicketModel();
        $this->adjuntos = new AdjuntoModel();
        $this->service = new TicketService();
        $this->notifications = new NotificationService();
    }

    public function list(): void
    {
        $this->requireLogin();
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = (int) ($_GET['per_page'] ?? 5);
        $query = trim((string) ($_GET['query'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? 'todos'));
        $asignacion = trim((string) ($_GET['asignacion'] ?? 'todos'));

        $payload = $this->service->listTickets(
            ['query' => $query, 'estado' => $estado, 'asignacion' => $asignacion],
            $page,
            $perPage,
            $this->currentRoleId(),
            $this->currentUserId()
        );

        $this->jsonOk('Tickets cargados', $payload);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $titulo = trim(strip_tags((string) ($_POST['titulo'] ?? '')));
        $descripcion = trim(strip_tags((string) ($_POST['descripcion'] ?? '')));
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $prioridadId = (int) ($_POST['prioridad_id'] ?? 0);
        $estadoId = (int) ($_POST['estado_id'] ?? 0);
        $fechaOcurrenciaRaw = trim((string) ($_POST['fecha_ocurrencia'] ?? ''));

        $codigo = $this->service->generateTicketCode();

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

        if ($titulo === '' || $descripcion === '' || $categoriaId <= 0 || $prioridadId <= 0 || $estadoId <= 0) {
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
        $this->handleAdjuntos($ticketId);
        $createdTicket = $this->model->getById($ticketId);
        if ($createdTicket !== null) {
            $this->notifications->notifyCreated($createdTicket, $this->currentUserId());
        }

        $this->jsonOk('Ticket creado', ['id' => $ticketId]);
    }

    public function listAdjuntos(): void
    {
        $this->requireLogin();

        $rolId = $this->currentRoleId();
        $userId = $this->currentUserId();
        $ticketIds = array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['ticket_ids'] ?? '')))));

        if ($rolId === 3) {
            $rows = $this->adjuntos->getByUsuario($userId);
        } elseif ($rolId === 2) {
            $rows = $this->adjuntos->getByTecnico($userId);
        } else {
            $rows = $this->adjuntos->getAll();
        }

        if ($ticketIds !== []) {
            $allowedIds = array_flip($ticketIds);
            $rows = array_values(array_filter($rows, static function (array $row) use ($allowedIds): bool {
                return isset($allowedIds[(int) ($row['ticket_id'] ?? 0)]);
            }));
        }

        $this->jsonOk('Adjuntos cargados', $rows);
    }

    public function uploadAdjuntos(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $comentarioId = (int) ($_POST['comentario_id'] ?? 0);
        if ($ticketId <= 0) {
            $this->jsonError('Selecciona un ticket.');
        }

        $ticket = $this->model->getById($ticketId);
        if (!$ticket) {
            $this->jsonError('Ticket no encontrado.', 404);
        }

        if (!$this->canAccessTicket($ticket)) {
            $this->jsonError('No puedes adjuntar archivos en este ticket.', 403);
        }

        $uploadedCount = $this->handleAdjuntos($ticketId, $comentarioId > 0 ? $comentarioId : null);
        if ($uploadedCount <= 0) {
            $this->jsonError('No se pudo cargar ningun archivo.');
        }

        $this->jsonOk('Evidencia cargada', ['count' => $uploadedCount]);
    }

    private function canAccessTicket(array $ticket): bool
    {
        $rolId = $this->currentRoleId();
        $userId = $this->currentUserId();

        if ($rolId === 1) {
            return true;
        }
        if ($rolId === 2) {
            return (int) ($ticket['tecnico_id'] ?? 0) === $userId;
        }

        return (int) ($ticket['usuario_id'] ?? 0) === $userId;
    }

    private function normalizeAdjuntos(): array
    {
        if (isset($_FILES['adjuntos'])) {
            $files = $_FILES['adjuntos'];
            $normalized = [];
            $total = is_array($files['name'] ?? null) ? count($files['name']) : 0;
            for ($i = 0; $i < $total; $i++) {
                $normalized[] = [
                    'name' => (string) ($files['name'][$i] ?? ''),
                    'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
                    'error' => (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                ];
            }
            return $normalized;
        }

        if (isset($_FILES['adjunto'])) {
            return [[
                'name' => (string) ($_FILES['adjunto']['name'] ?? ''),
                'tmp_name' => (string) ($_FILES['adjunto']['tmp_name'] ?? ''),
                'error' => (int) ($_FILES['adjunto']['error'] ?? UPLOAD_ERR_NO_FILE),
            ]];
        }

        return [];
    }

    private function handleAdjuntos(int $ticketId, ?int $comentarioId = null): int
    {
        $baseDir = dirname(__DIR__);
        $dir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tickets';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
        $uploaded = 0;

        foreach ($this->normalizeAdjuntos() as $index => $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmp = (string) ($file['tmp_name'] ?? '');
            $name = basename((string) ($file['name'] ?? ''));
            if ($tmp === '' || $name === '') {
                continue;
            }

            $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
            if ($ext === '' || !in_array($ext, $allowedExtensions, true)) {
                continue;
            }

            $filename = 'ticket_' . $ticketId . '_' . time() . '_' . $index . '.' . $ext;
            $dest = $dir . DIRECTORY_SEPARATOR . $filename;

            if (move_uploaded_file($tmp, $dest)) {
                $relative = 'uploads/tickets/' . $filename;
                if ($this->adjuntos->insert($ticketId, $relative, $name, $this->currentUserId(), $comentarioId)) {
                    $uploaded++;
                }
            }
        }

        return $uploaded;
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

        $ticket = $this->model->getById($ticketId);
        if ($ticket !== null) {
            $this->notifications->notifyAssignment($ticket, $tecnicoId, $this->currentUserId());
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

        // El cierre definitivo lo confirma quien reporto el incidente, no el tecnico que lo resolvio.
        $cerradoId = $this->model->getEstadoIdByNombre('Cerrado');
        if ($cerradoId === null) {
            $this->jsonError('No existe el estado Cerrado.');
        }

        $fechaCierre = date('Y-m-d H:i:s');
        if (!$this->model->updateEstado($ticketId, $cerradoId, $fechaCierre)) {
            $this->jsonError('No se pudo cerrar el ticket.');
        }

        $this->notifications->notifyClosed($ticket, $userId);

        $this->jsonOk('Ticket cerrado');
    }
}
