<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Services/NotificationService.php';

class NotificacionController extends BaseController
{
    private NotificationService $service;

    public function __construct()
    {
        $this->service = new NotificationService();
    }

    public function list(): void
    {
        $this->requireLogin();
        $this->jsonOk('Notificaciones cargadas', $this->service->getPanelData($this->currentUserId()));
    }

    public function read(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $payload = $this->requestData();
        $id = (int) ($payload['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError('Notificacion invalida');
        }

        $ok = $this->service->markAsRead($id, $this->currentUserId());
        $ok ? $this->jsonOk('Notificacion marcada como leida') : $this->jsonError('No se pudo actualizar');
    }

    public function readAll(): void
    {
        $this->requireLogin();
        $this->requirePost();

        $ok = $this->service->markAllAsRead($this->currentUserId());
        $ok ? $this->jsonOk('Notificaciones marcadas como leidas') : $this->jsonError('No se pudo actualizar');
    }
}
