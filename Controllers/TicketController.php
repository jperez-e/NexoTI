<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/TicketModel.php';
require_once __DIR__ . '/../Models/AdjuntoModel.php';
require_once __DIR__ . '/../Models/ComentarioModel.php';
require_once __DIR__ . '/../Models/TicketParticipanteModel.php';
require_once __DIR__ . '/../Models/UsuarioModel.php';
require_once __DIR__ . '/../Services/TicketService.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class TicketController extends BaseController
{
    private TicketModel $model;
    private AdjuntoModel $adjuntos;
    private ComentarioModel $comentarios;
    private TicketParticipanteModel $participantes;
    private UsuarioModel $usuarios;
    private TicketService $service;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->model = new TicketModel();
        $this->adjuntos = new AdjuntoModel();
        $this->comentarios = new ComentarioModel();
        $this->participantes = new TicketParticipanteModel();
        $this->usuarios = new UsuarioModel();
        $this->service = new TicketService();
        $this->notifications = new NotificationService();
    }

    public function listar(): void
    {
        $this->requerirSesion();
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = (int) ($_GET['per_page'] ?? 5);
        $query = trim((string) ($_GET['query'] ?? ''));
        $estado = trim((string) ($_GET['estado'] ?? 'todos'));
        $asignacion = trim((string) ($_GET['asignacion'] ?? 'todos'));

        $payload = $this->service->listarTickets(
            ['query' => $query, 'estado' => $estado, 'asignacion' => $asignacion],
            $page,
            $perPage,
            $this->obtenerIdRolActual(),
            $this->obtenerIdUsuarioActual()
        );

        $this->responderOkJson('Tickets cargados', $payload);
    }

    public function crear(): void
    {
        $this->requerirSesion();
        $this->requerirPost();

        $titulo = trim(strip_tags((string) ($_POST['titulo'] ?? '')));
        $descripcion = trim(strip_tags((string) ($_POST['descripcion'] ?? '')));
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $prioridadId = (int) ($_POST['prioridad_id'] ?? 0);
        $estadoId = (int) ($_POST['estado_id'] ?? 0);
        $fechaOcurrenciaRaw = trim((string) ($_POST['fecha_ocurrencia'] ?? ''));

        $codigo = $this->service->generarCodigoTicket();

        $sessionUserId = $this->obtenerIdUsuarioActual();
        $rolId = $this->obtenerIdRolActual();
        $usuarioId = (int) ($_POST['usuario_id'] ?? $sessionUserId);
        $fechaCreacion = null;
        if ($fechaOcurrenciaRaw !== '') {
            $date = \DateTime::createFromFormat('Y-m-d\TH:i', $fechaOcurrenciaRaw);
            if ($date === false) {
                $this->responderErrorJson('Fecha de ocurrencia invalida.');
            }
            $fechaCreacion = $date->format('Y-m-d H:i:s');
        }
        if ($rolId === 3) {
            // Regla de negocio: los tickets creados por el usuario final siempre nacen abiertos.
            $usuarioId = $sessionUserId;
            $estadoInicialId = $this->model->obtenerIdEstadoPorNombre('Abierto');
            if ($estadoInicialId !== null) {
                $estadoId = $estadoInicialId;
            }
        }

        if ($titulo === '' || $descripcion === '' || $categoriaId <= 0 || $prioridadId <= 0 || $estadoId <= 0) {
            $this->responderErrorJson('Completa todos los campos.');
        }

        $ok = $this->model->insertar(
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
            $this->responderErrorJson('No se pudo guardar el ticket.');
        }

        $ticketId = $this->model->obtenerUltimoIdInsertado();
        $this->procesarAdjuntos($ticketId);
        $createdTicket = $this->model->obtenerPorId($ticketId);
        if ($createdTicket !== null) {
            $this->notifications->notificarCreacion($createdTicket, $this->obtenerIdUsuarioActual());
        }

        $this->responderOkJson('Ticket creado', ['id' => $ticketId]);
    }

    public function listarAdjuntos(): void
    {
        $this->requerirSesion();

        $rolId = $this->obtenerIdRolActual();
        $userId = $this->obtenerIdUsuarioActual();
        $ticketIds = array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['ticket_ids'] ?? '')))));

        if ($rolId === 3) {
            $rows = $this->adjuntos->obtenerPorUsuario($userId);
        } elseif ($rolId === 2) {
            $rows = $this->adjuntos->obtenerPorTecnico($userId);
        } else {
            $rows = $this->adjuntos->obtenerTodos();
        }

        if ($ticketIds !== []) {
            $allowedIds = array_flip($ticketIds);
            $rows = array_values(array_filter($rows, static function (array $row) use ($allowedIds): bool {
                return isset($allowedIds[(int) ($row['ticket_id'] ?? 0)]);
            }));
        }

        $this->responderOkJson('Adjuntos cargados', $rows);
    }

    public function subirAdjuntos(): void
    {
        $this->requerirSesion();
        $this->requerirPost();

        $ticketId = (int) ($_POST['ticket_id'] ?? 0);
        $comentarioId = (int) ($_POST['comentario_id'] ?? 0);
        if ($ticketId <= 0) {
            $this->responderErrorJson('Selecciona un ticket.');
        }

        $ticket = $this->model->obtenerPorId($ticketId);
        if (!$ticket) {
            $this->responderErrorJson('Ticket no encontrado.', 404);
        }

        if (!$this->puedeAccederTicket($ticket)) {
            $this->responderErrorJson('No puedes adjuntar archivos en este ticket.', 403);
        }

        $uploadedCount = $this->procesarAdjuntos($ticketId, $comentarioId > 0 ? $comentarioId : null);
        if ($uploadedCount <= 0) {
            $this->responderErrorJson('No se pudo cargar ningun archivo.');
        }

        $this->responderOkJson('Evidencia cargada', ['count' => $uploadedCount]);
    }

    public function listarParticipantes(): void
    {
        $this->requerirSesion();

        $ticketIds = array_values(array_filter(array_map('intval', explode(',', (string) ($_GET['ticket_ids'] ?? '')))));
        if ($ticketIds === []) {
            $this->responderOkJson('Participantes cargados', []);
        }

        $rows = $this->participantes->obtenerPorIdsTicket($ticketIds);
        if ($this->obtenerIdRolActual() === 1) {
            $this->responderOkJson('Participantes cargados', $rows);
        }

        $allowedTicketIds = [];
        foreach ($ticketIds as $ticketId) {
            $ticket = $this->model->obtenerPorId($ticketId);
            if ($ticket && $this->puedeAccederTicket($ticket)) {
                $allowedTicketIds[$ticketId] = true;
            }
        }

        $filtered = array_values(array_filter($rows, static function (array $row) use ($allowedTicketIds): bool {
            return isset($allowedTicketIds[(int) ($row['ticket_id'] ?? 0)]);
        }));

        $this->responderOkJson('Participantes cargados', $filtered);
    }

    public function listarCandidatosParticipantes(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1, 2]);

        $rows = $this->usuarios->obtenerCandidatosParticipantes();
        $currentUserId = $this->obtenerIdUsuarioActual();
        $rolId = $this->obtenerIdRolActual();
        $rows = array_values(array_filter($rows, static function (array $row) use ($currentUserId): bool {
            return (int) ($row['id'] ?? 0) !== $currentUserId;
        }));
        if ($rolId === 2) {
            $rows = array_values(array_filter($rows, static function (array $row): bool {
                return in_array((int) ($row['rol_id'] ?? 0), [2, 3], true);
            }));
        }

        $this->responderOkJson('Candidatos cargados', $rows);
    }

    public function agregarParticipante(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1, 2]);
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();
        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $usuarioId = (int) ($payload['usuario_id'] ?? 0);

        if ($ticketId <= 0 || $usuarioId <= 0) {
            $this->responderErrorJson('Datos incompletos.');
        }

        $ticket = $this->model->obtenerPorId($ticketId);
        if (!$ticket) {
            $this->responderErrorJson('Ticket no encontrado.', 404);
        }
        if (!$this->puedeGestionarParticipantes($ticket)) {
            $this->responderErrorJson('No tienes permisos para gestionar participantes en este ticket.', 403);
        }

        $usuario = $this->usuarios->obtenerPorId($usuarioId);
        if (!$usuario || (int) ($usuario['activo'] ?? 0) !== 1) {
            $this->responderErrorJson('Usuario no disponible.');
        }
        if ($this->obtenerIdRolActual() === 2 && !in_array((int) ($usuario['rol_id'] ?? 0), [2, 3], true)) {
            $this->responderErrorJson('Solo puedes agregar técnicos o usuarios.');
        }
        $idSolicitante = (int) ($ticket['usuario_id'] ?? 0);
        $idTecnicoAsignado = (int) ($ticket['tecnico_id'] ?? 0);
        if ($idSolicitante === $usuarioId || $idTecnicoAsignado === $usuarioId) {
            $this->responderErrorJson('Ese usuario ya participa en el ticket.');
        }
        if ($this->participantes->esParticipante($ticketId, $usuarioId)) {
            $this->responderErrorJson('Ese usuario ya fue agregado.');
        }
        if (!$this->participantes->agregar($ticketId, $usuarioId)) {
            $this->responderErrorJson('No se pudo agregar el participante.');
        }

        $this->responderOkJson('Participante agregado');
    }

    public function quitarParticipante(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1, 2]);
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();
        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $usuarioId = (int) ($payload['usuario_id'] ?? 0);

        if ($ticketId <= 0 || $usuarioId <= 0) {
            $this->responderErrorJson('Datos incompletos.');
        }

        $ticket = $this->model->obtenerPorId($ticketId);
        if (!$ticket) {
            $this->responderErrorJson('Ticket no encontrado.', 404);
        }
        if (!$this->puedeGestionarParticipantes($ticket)) {
            $this->responderErrorJson('No tienes permisos para gestionar participantes en este ticket.', 403);
        }
        if ((int) $ticket['usuario_id'] === $usuarioId || (int) ($ticket['tecnico_id'] ?? 0) === $usuarioId) {
            $this->responderErrorJson('No puedes remover al solicitante o al técnico responsable.');
        }
        if (!$this->participantes->esParticipante($ticketId, $usuarioId)) {
            $this->responderErrorJson('Ese usuario no está como participante.');
        }
        if (!$this->participantes->quitar($ticketId, $usuarioId)) {
            $this->responderErrorJson('No se pudo remover el participante.');
        }

        $this->responderOkJson('Participante removido');
    }

    private function puedeAccederTicket(array $ticket): bool
    {
        $rolId = $this->obtenerIdRolActual();
        $userId = $this->obtenerIdUsuarioActual();

        if ($this->participantes->esParticipante((int) ($ticket['id'] ?? 0), $userId)) {
            return true;
        }

        if ($rolId === 1) {
            return true;
        }
        if ($rolId === 2) {
            return (int) ($ticket['tecnico_id'] ?? 0) === $userId;
        }

        return (int) ($ticket['usuario_id'] ?? 0) === $userId;
    }

    private function puedeGestionarParticipantes(array $ticket): bool
    {
        $rolId = $this->obtenerIdRolActual();
        if ($rolId === 1) {
            return true;
        }

        if ($rolId !== 2) {
            return false;
        }

        return (int) ($ticket['tecnico_id'] ?? 0) === $this->obtenerIdUsuarioActual();
    }

    private function normalizarAdjuntos(): array
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

    private function procesarAdjuntos(int $ticketId, ?int $comentarioId = null): int
    {
        $baseDir = dirname(__DIR__);
        $dir = $baseDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'tickets';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
        $uploaded = 0;

        foreach ($this->normalizarAdjuntos() as $index => $file) {
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
                if ($this->adjuntos->insertar($ticketId, $relative, $name, $this->obtenerIdUsuarioActual(), $comentarioId)) {
                    $uploaded++;
                }
            }
        }

        return $uploaded;
    }

    public function asignar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();

        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $estadoId = (int) ($payload['estado_id'] ?? 0);
        $tecnicoRaw = $payload['tecnico_id'] ?? null;
        $tecnicoId = $tecnicoRaw === null || $tecnicoRaw === '' ? null : (int) $tecnicoRaw;

        if ($ticketId <= 0 || $estadoId <= 0) {
            $this->responderErrorJson('Datos incompletos.');
        }

        if (!$this->model->asignar($ticketId, $tecnicoId, $estadoId)) {
            $this->responderErrorJson('No se pudo actualizar.');
        }

        // Si el tecnico pasa a ser responsable principal, se limpia de participantes extra para evitar duplicidad.
        if ($tecnicoId !== null && $tecnicoId > 0) {
            $this->participantes->quitar($ticketId, $tecnicoId);
        }

        $ticket = $this->model->obtenerPorId($ticketId);
        if ($ticket !== null) {
            $this->notifications->notificarAsignacion($ticket, $tecnicoId, $this->obtenerIdUsuarioActual());
        }

        $this->responderOkJson('Asignacion actualizada');
    }

    public function actualizarEstado(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1, 2]);
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();

        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        $estadoId = (int) ($payload['estado_id'] ?? 0);

        if ($ticketId <= 0 || $estadoId <= 0) {
            $this->responderErrorJson('Datos incompletos.');
        }

        $ticket = $this->model->obtenerPorId($ticketId);
        if (!$ticket) {
            $this->responderErrorJson('Ticket no encontrado.');
        }

        $rolId = $this->obtenerIdRolActual();
        $userId = $this->obtenerIdUsuarioActual();
        if ($rolId === 2 && (int) $ticket['tecnico_id'] !== $userId) {
            $this->responderErrorJson('No puedes actualizar este ticket.', 403);
        }

        $cerradoId = $this->model->obtenerIdEstadoPorNombre('Cerrado');
        $fechaCierre = null;
        if ($cerradoId !== null && $estadoId === $cerradoId) {
            $fechaCierre = date('Y-m-d H:i:s');
        }

        if (!$this->model->actualizarEstado($ticketId, $estadoId, $fechaCierre)) {
            $this->responderErrorJson('No se pudo actualizar el estado.');
        }

        $this->responderOkJson('Estado actualizado');
    }

    public function cerrarTicket(): void
    {
        $this->requerirSesion();
        $this->requerirRol([3]);
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();

        $ticketId = (int) ($payload['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            $this->responderErrorJson('Selecciona un ticket.');
        }

        $ticket = $this->model->obtenerPorId($ticketId);
        if (!$ticket) {
            $this->responderErrorJson('Ticket no encontrado.', 404);
        }

        $userId = $this->obtenerIdUsuarioActual();
        if ((int) $ticket['usuario_id'] !== $userId) {
            $this->responderErrorJson('No puedes cerrar este ticket.', 403);
        }

        // El cierre definitivo lo confirma quien reporto el incidente, no el tecnico que lo resolvio.
        $cerradoId = $this->model->obtenerIdEstadoPorNombre('Cerrado');
        if ($cerradoId === null) {
            $this->responderErrorJson('No existe el estado Cerrado.');
        }

        $fechaCierre = date('Y-m-d H:i:s');
        if (!$this->model->actualizarEstado($ticketId, $cerradoId, $fechaCierre)) {
            $this->responderErrorJson('No se pudo cerrar el ticket.');
        }

        // Dejamos trazabilidad funcional en el hilo: el usuario final confirma que acepta la solucion.
        $this->comentarios->insertar($ticketId, $userId, 'El usuario aceptó la solución y confirmó el cierre del ticket.');

        $this->notifications->notificarCierre($ticket, $userId);

        $this->responderOkJson('Ticket cerrado');
    }

    public function eliminar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();
        $ticketId = (int) ($payload['ticket_id'] ?? $payload['id'] ?? 0);
        if ($ticketId <= 0) {
            $this->responderErrorJson('Selecciona un ticket válido.');
        }

        $ticket = $this->model->obtenerPorId($ticketId);
        if (!$ticket) {
            $this->responderErrorJson('Ticket no encontrado.', 404);
        }

        $adjuntos = $this->adjuntos->obtenerPorTicket($ticketId);
        if (!$this->model->eliminarConDependencias($ticketId)) {
            $this->responderErrorJson('No se pudo eliminar el ticket.');
        }

        $baseDir = dirname(__DIR__);
        foreach ($adjuntos as $adjunto) {
            $rutaRelativa = str_replace('\\', '/', ltrim((string) ($adjunto['archivo'] ?? ''), '/'));
            if ($rutaRelativa === '' || !str_starts_with($rutaRelativa, 'uploads/tickets/')) {
                continue;
            }

            $rutaAbsoluta = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
            if (is_file($rutaAbsoluta)) {
                @unlink($rutaAbsoluta);
            }
        }

        $this->responderOkJson('Ticket eliminado');
    }
}
