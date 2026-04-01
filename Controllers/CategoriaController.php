<?php 
declare(strict_types=1);

require_once __DIR__ . '/CatalogController.php';
require_once __DIR__ . '/../Models/CategoriaModel.php';

class CategoriaController extends CatalogController
{
    public function __construct()
    {
        $this->model = new CategoriaModel();
    }

    protected function listMessage(): string { return 'Categorias cargadas'; }
    protected function createMessage(): string { return 'Categoria creada'; }
    protected function updateMessage(): string { return 'Categoria actualizada'; }
    protected function deleteMessage(): string { return 'Categoria eliminada'; }
    protected function invalidDataMessage(): string { return 'Datos invalidos'; }
    protected function invalidEntityMessage(): string { return 'Categoria invalida'; }

    protected function buildCreatePayload(array $payload): array
    {
        return [
            'nombre' => $this->sanitizeText($payload, 'nombre'),
            'descripcion' => $this->sanitizeText($payload, 'descripcion'),
        ];
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
