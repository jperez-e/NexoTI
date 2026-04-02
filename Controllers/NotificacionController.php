<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class NotificacionController extends BaseController
{
    private NotificationService $servicio;

    public function __construct()
    {
        $this->servicio = new NotificationService();
    }

    public function listar(): void
    {
        $this->requerirSesion();
        $this->responderOkJson('Notificaciones cargadas', $this->servicio->obtenerDatosPanel($this->obtenerIdUsuarioActual()));
    }

    public function marcarLeida(): void
    {
        $this->requerirSesion();
        $this->requerirPost();

        $payload = $this->obtenerDatosSolicitud();
        $id = (int) ($payload['id'] ?? 0);
        if ($id <= 0) {
            $this->responderErrorJson('Notificacion invalida');
        }

        $ok = $this->servicio->marcarComoLeida($id, $this->obtenerIdUsuarioActual());
        $ok ? $this->responderOkJson('Notificacion marcada como leida') : $this->responderErrorJson('No se pudo actualizar');
    }

    public function marcarTodasLeidas(): void
    {
        $this->requerirSesion();
        $this->requerirPost();

        $ok = $this->servicio->marcarTodasComoLeidas($this->obtenerIdUsuarioActual());
        $ok ? $this->responderOkJson('Notificaciones marcadas como leidas') : $this->responderErrorJson('No se pudo actualizar');
    }
}
