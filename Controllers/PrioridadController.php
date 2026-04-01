<?php 
declare(strict_types=1);

require_once __DIR__ . '/CatalogController.php';
require_once __DIR__ . '/../Models/PrioridadModel.php';

class PrioridadController extends CatalogController
{
    public function __construct()
    {
        $this->model = new PrioridadModel();
    }

    protected function listMessage(): string { return 'Prioridades cargadas'; }
    protected function createMessage(): string { return 'Prioridad creada'; }
    protected function updateMessage(): string { return 'Prioridad actualizada'; }
    protected function deleteMessage(): string { return 'Prioridad eliminada'; }
    protected function invalidDataMessage(): string { return 'Datos invalidos'; }
    protected function invalidEntityMessage(): string { return 'Prioridad invalida'; }

    protected function buildCreatePayload(array $payload): array
    {
        return [
            'nombre' => $this->sanitizeText($payload, 'nombre'),
            'nivel' => (int) ($payload['nivel'] ?? 0),
        ];
    }

    protected function buildUpdatePayload(array $payload): array
    {
        return $this->buildCreatePayload($payload);
    }

    protected function isCreatePayloadValid(array $payload): bool
    {
        return $payload['nombre'] !== '' && (int) $payload['nivel'] > 0;
    }

    protected function isUpdatePayloadValid(int $id, array $payload): bool
    {
        return $id > 0 && $this->isCreatePayloadValid($payload);
    }
}
