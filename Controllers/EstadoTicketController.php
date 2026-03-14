<?php
declare(strict_types=1);

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../Models/EstadoTicketModel.php";

class EstadoTicketController extends BaseController
{
    private EstadoTicketModel $model;

    public function __construct()
    {
        $this->model = new EstadoTicketModel();
    }

    public function list(): void
    {
        $this->requireLogin();
        $rows = $this->model->getAll();
        $this->jsonOk("Estados cargados", $rows);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $nombre = trim(strip_tags((string) ($_POST["nombre"] ?? "")));
        if ($nombre === "") {
            $this->jsonError("Nombre requerido");
        }

        $ok = $this->model->insert($nombre);
        $ok ? $this->jsonOk("Estado creado") : $this->jsonError("No se pudo crear");
    }
}
