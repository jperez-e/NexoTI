<?php
declare(strict_types=1);

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../Models/ComentarioModel.php";

class ComentarioController extends BaseController
{
    private ComentarioModel $model;

    public function __construct()
    {
        $this->model = new ComentarioModel();
    }

    public function list(): void
    {
        $this->requireLogin();
        $rows = $this->model->getAll();
        $this->jsonOk("Comentarios cargados", $rows);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $ticketId = (int) ($_POST["ticket_id"] ?? 0);
        $usuarioId = (int) ($_POST["usuario_id"] ?? 0);
        $comentario = trim(strip_tags((string) ($_POST["comentario"] ?? "")));

        if ($ticketId <= 0 || $usuarioId <= 0 || $comentario === "") {
            $this->jsonError("Datos invalidos");
        }

        $ok = $this->model->insert($ticketId, $usuarioId, $comentario);
        $ok ? $this->jsonOk("Comentario creado") : $this->jsonError("No se pudo crear");
    }
}
