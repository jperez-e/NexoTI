<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/Csrf.php';

class BaseController
{
    // Estos helpers evitan repetir acceso directo a $_SESSION en cada controlador.
    protected function obtenerIdUsuarioActual(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    protected function obtenerIdRolActual(): int
    {
        return (int) ($_SESSION['rol_id'] ?? 0);
    }

    protected function requerirSesion(): void
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'No autorizado']);
            exit;
        }
    }

    protected function requerirRol(array $roles): void
    {
        $rolId = $this->obtenerIdRolActual();
        if (!in_array($rolId, $roles, true)) {
            http_response_code(403);
            echo json_encode(['status' => false, 'message' => 'Acceso denegado']);
            exit;
        }
    }

    protected function requerirPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => false, 'message' => 'Metodo no permitido']);
            exit;
        }

        // Toda operacion POST queda protegida contra CSRF antes de procesar datos.
        if (!Csrf::esSolicitudValida()) {
            $this->responderErrorJson('Token CSRF invalido.', 403);
        }
    }

    protected function obtenerDatosSolicitud(): array
    {
        // La API acepta tanto formularios clasicos como JSON enviado desde fetch().
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return $_POST;
        }

        $datos = json_decode($raw, true);
        return is_array($datos) ? $datos : $_POST;
    }

    protected function responderOkJson(string $mensaje, array $datos = []): void
    {
        echo json_encode(['status' => true, 'message' => $mensaje, 'data' => $datos]);
        exit;
    }

    protected function responderErrorJson(string $mensaje, int $codigoEstado = 400): void
    {
        http_response_code($codigoEstado);
        echo json_encode(['status' => false, 'message' => $mensaje]);
        exit;
    }
}
