<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Csrf.php';

class BaseController
{
    // Estos helpers evitan repetir acceso directo a $_SESSION en cada controlador.
    protected function currentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    protected function currentRoleId(): int
    {
        return (int) ($_SESSION['rol_id'] ?? 0);
    }

    protected function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'No autorizado']);
            exit;
        }
    }

    protected function requireRole(array $roles): void
    {
        $rolId = $this->currentRoleId();
        if (!in_array($rolId, $roles, true)) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Acceso denegado']);
            exit;
        }
    }

    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => false, 'message' => 'Metodo no permitido']);
            exit;
        }

        // Toda operacion POST queda protegida contra CSRF antes de procesar datos.
        if (!Csrf::isValidRequest()) {
            $this->jsonError('Token CSRF invalido.', 403);
        }
    }

    protected function requestData(): array
    {
        // La API acepta tanto formularios clasicos como JSON enviado desde fetch().
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $_POST;
        }

        $payload = json_decode($raw, true);
        return is_array($payload) ? $payload : $_POST;
    }

    protected function jsonOk(string $message, array $data = []): void
    {
        echo json_encode(['status' => true, 'message' => $message, 'data' => $data]);
        exit;
    }

    protected function jsonError(string $message, int $statusCode = 400): void
    {
        http_response_code($statusCode);
        echo json_encode(['status' => false, 'message' => $message]);
        exit;
    }
}
