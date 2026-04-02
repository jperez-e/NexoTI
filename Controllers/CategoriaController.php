<?php 
declare(strict_types=1);

require_once __DIR__ . '/CatalogController.php';
require_once __DIR__ . '/../Models/CategoriaModel.php';

class CategoriaController extends CatalogController
{
    public function __construct()
    {
        $this->modelo = new CategoriaModel();
    }

    protected function mensajeListado(): string { return 'Categorias cargadas'; }
    protected function mensajeCreacion(): string { return 'Categoria creada'; }
    protected function mensajeActualizacion(): string { return 'Categoria actualizada'; }
    protected function mensajeEliminacion(): string { return 'Categoria eliminada'; }
    protected function mensajeDatosInvalidos(): string { return 'Datos invalidos'; }
    protected function mensajeEntidadInvalida(): string { return 'Categoria invalida'; }

    protected function construirCargaCrear(array $payload): array
    {
        return [
            'nombre' => $this->sanearTexto($payload, 'nombre'),
            'descripcion' => $this->sanearTexto($payload, 'descripcion'),
        ];
    }

    protected function construirCargaActualizar(array $payload): array
    {
        return $this->construirCargaCrear($payload);
    }

    protected function esValidaCargaCrear(array $payload): bool
    {
        return $payload['nombre'] !== '';
    }

    protected function esValidaCargaActualizar(int $id, array $payload): bool
    {
        return $id > 0 && $this->esValidaCargaCrear($payload);
    }
}
