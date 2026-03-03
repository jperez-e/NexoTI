<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/TicketModel.php";

class TicketController
{
    private TicketModel $model;

    public function __construct()
    {
        $this->model = new TicketModel();
    }

    public function index(): void
    {
        $tickets = $this->model->getAll();
        require __DIR__ . "/../Views/tickets/index.php";
    }

    public function create(): void
    {
        $prioridades = $this->model->getPrioridades();
        $categorias = $this->model->getCategorias();
        $error = null;
        require __DIR__ . "/../Views/tickets/create.php";
    }

    public function store(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?c=ticket&m=create");
            exit;
        }

        $titulo = trim((string) ($_POST["titulo"] ?? ""));
        $descripcion = trim((string) ($_POST["descripcion"] ?? ""));
        $categoriaId = (int) ($_POST["categoria_id"] ?? 0);
        $prioridadId = (int) ($_POST["prioridad_id"] ?? 0);

        if ($titulo === "" || $descripcion === "" || $categoriaId <= 0 || $prioridadId <= 0) {
            $prioridades = $this->model->getPrioridades();
            $categorias = $this->model->getCategorias();
            $error = "Todos los campos son obligatorios.";
            require __DIR__ . "/../Views/tickets/create.php";
            return;
        }

        $payload = [
            "codigo" => "TCK-" . date("YmdHis"),
            "titulo" => strip_tags($titulo),
            "descripcion" => strip_tags($descripcion),
            "usuario_id" => 1,
            "categoria_id" => $categoriaId,
            "prioridad_id" => $prioridadId,
            "estado_id" => 1,
        ];

        $this->model->create($payload);
        header("Location: index.php?c=ticket&m=index");
        exit;
    }
}
