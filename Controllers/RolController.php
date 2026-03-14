<?php
declare(strict_types=1);

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../Models/RolModel.php";

class RolController extends BaseController
{
    private RolModel $model;

    public function __construct()
    {
        $this->model = new RolModel();
    }

    public function list(): void
    {
        $this->requireLogin();
        $rows = $this->model->getAll();
        $this->jsonOk("Roles cargados", $rows);
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
        $ok ? $this->jsonOk("Rol creado") : $this->jsonError("No se pudo crear");
    }
}
