<?php 
declare(strict_types=1);

require_once __DIR__ . '/CatalogController.php';
require_once __DIR__ . '/../Models/RolModel.php';

class RolController extends CatalogController
{
    public function __construct()
    {
        $this->model = new RolModel();
    }

    public function list(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->jsonOk($this->listMessage(), $this->model->getAll());
    }

    protected function listMessage(): string { return 'Roles cargados'; }
    protected function createMessage(): string { return 'Rol creado'; }
    protected function updateMessage(): string { return 'Rol actualizado'; }
    protected function deleteMessage(): string { return 'Rol eliminado'; }
    protected function invalidDataMessage(): string { return 'Datos invalidos'; }
    protected function invalidEntityMessage(): string { return 'Rol invalido'; }

    protected function buildCreatePayload(array $payload): array
    {
        return ['nombre' => $this->sanitizeText($payload, 'nombre')];
    }

    protected function buildUpdatePayload(array $payload): array
    {
        return $this->buildCreatePayload($payload);
    }

    protected function isCreatePayloadValid(array $payload): bool
    {
        return $payload['nombre'] !== '';
    }

    protected function isUpdatePayloadValid(int $id, array $payload): bool
    {
        return $id > 0 && $this->isCreatePayloadValid($payload);
    }
}
