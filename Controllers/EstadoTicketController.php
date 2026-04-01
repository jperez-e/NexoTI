<?php 
declare(strict_types=1);

require_once __DIR__ . '/CatalogController.php';
require_once __DIR__ . '/../Models/EstadoTicketModel.php';

class EstadoTicketController extends CatalogController
{
    public function __construct()
    {
        $this->model = new EstadoTicketModel();
    }

    protected function listMessage(): string { return 'Estados cargados'; }
    protected function createMessage(): string { return 'Estado creado'; }
    protected function updateMessage(): string { return 'Estado actualizado'; }
    protected function deleteMessage(): string { return 'Estado eliminado'; }
    protected function invalidDataMessage(): string { return 'Datos invalidos'; }
    protected function invalidEntityMessage(): string { return 'Estado invalido'; }

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
