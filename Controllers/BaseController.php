<?php
declare(strict_types=1);

class BaseController
{
    protected function requireLogin(): void
    {
        if (!isset($_SESSION["user_id"])) {
            http_response_code(401);
            echo json_encode(["status" => false, "message" => "No autorizado"]);
            exit;
        }
    }

    protected function requireRole(array $roles): void
    {
        $rolId = (int) ($_SESSION["rol_id"] ?? 0);
        if (!in_array($rolId, $roles, true)) {
            http_response_code(403);
            echo json_encode(["status" => false, "message" => "Acceso denegado"]);
            exit;
        }
    }

    protected function requirePost(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode(["status" => false, "message" => "Metodo no permitido"]);
            exit;
        }
    }

    protected function jsonOk(string $message, array $data = []): void
    {
        echo json_encode(["status" => true, "message" => $message, "data" => $data]);
        exit;
    }

    protected function jsonError(string $message): void
    {
        echo json_encode(["status" => false, "message" => $message]);
        exit;
    }
}
