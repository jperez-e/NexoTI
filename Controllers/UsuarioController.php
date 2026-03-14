<?php
declare(strict_types=1);

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../Models/UsuarioModel.php";

class UsuarioController extends BaseController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    public function list(): void
    {
        $this->requireLogin();
        $rows = $this->model->getAll();
        $this->jsonOk("Usuarios cargados", $rows);
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $nombre = trim(strip_tags((string) ($_POST["nombre"] ?? "")));
        $email = trim((string) ($_POST["email"] ?? ""));
        $password = (string) ($_POST["password"] ?? "");
        $rolId = (int) ($_POST["rol_id"] ?? 0);

        if ($nombre === "" || $email === "" || $password === "" || $rolId <= 0) {
            $this->jsonError("Datos invalidos");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonError("Correo invalido");
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ok = $this->model->insert($nombre, $email, $hash, $rolId, 1);
        $ok ? $this->jsonOk("Usuario creado") : $this->jsonError("No se pudo crear");
    }
}
