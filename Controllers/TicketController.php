<?php
declare(strict_types=1);

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../Models/TicketModel.php";

class TicketController extends BaseController
{
    private TicketModel $model;

    public function __construct()
    {
        $this->model = new TicketModel();
    }

    public function list(): void
    {
        $this->requireLogin();
        $rows = $this->model->getAll();
        $this->jsonOk("Tickets cargados", $rows);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $codigo = trim(strip_tags((string) ($_POST["codigo"] ?? "")));
        $titulo = trim(strip_tags((string) ($_POST["titulo"] ?? "")));
        $descripcion = trim(strip_tags((string) ($_POST["descripcion"] ?? "")));
        $usuarioId = (int) ($_POST["usuario_id"] ?? 0);
        $categoriaId = (int) ($_POST["categoria_id"] ?? 0);
        $prioridadId = (int) ($_POST["prioridad_id"] ?? 0);
        $estadoId = (int) ($_POST["estado_id"] ?? 0);
        $tecnicoId = isset($_POST["tecnico_id"]) ? (int) $_POST["tecnico_id"] : null;

        if ($codigo === "" || $titulo === "" || $descripcion === "" || $usuarioId <= 0 || $categoriaId <= 0 || $prioridadId <= 0 || $estadoId <= 0) {
            $this->jsonError("Datos invalidos");
        }

        $ok = $this->model->insert(
            $codigo,
            $titulo,
            $descripcion,
            $usuarioId,
            $categoriaId,
            $prioridadId,
            $estadoId,
            $tecnicoId,
            null
        );
        $ok ? $this->jsonOk("Ticket creado") : $this->jsonError("No se pudo crear");
    }
}
