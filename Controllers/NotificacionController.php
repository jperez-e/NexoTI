<?php
// Este archivo PHP define el controlador para la entidad "Notificacion",
// que maneja las operaciones relacionadas con las notificaciones en la aplicación.
// El controlador utiliza una clase de servicio para interactuar con la lógica de 
// negocio relacionada con las notificaciones, como obtener las notificaciones para el panel del usuario, 
// marcar una notificación como leída o marcar todas las notificaciones como leídas. 
// El controlador también asegura que el usuario tenga una sesión activa antes de permitir 
// el acceso a estas operaciones y responde con JSON para indicar el éxito o error de 
// las acciones realizadas.
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
