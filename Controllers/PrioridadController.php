<?php
// Este archivo PHP define el controlador de Prioridad.
// Gestiona solicitudes HTTP, valida reglas de acceso y coordina la respuesta JSON o de vista según la operación.
 
declare(strict_types=1);

require_once __DIR__ . '/CatalogController.php';
require_once __DIR__ . '/../Models/PrioridadModel.php';

class PrioridadController extends CatalogController
{
    public function __construct()
    {
        $this->modelo = new PrioridadModel();
    }

    protected function mensajeListado(): string { return 'Prioridades cargadas'; }
    protected function mensajeCreacion(): string { return 'Prioridad creada'; }
    protected function mensajeActualizacion(): string { return 'Prioridad actualizada'; }
    protected function mensajeEliminacion(): string { return 'Prioridad eliminada'; }
    protected function mensajeDatosInvalidos(): string { return 'Datos invalidos'; }
    protected function mensajeEntidadInvalida(): string { return 'Prioridad invalida'; }

    protected function construirCargaCrear(array $payload): array
    {
        return [
            'nombre' => $this->sanearTexto($payload, 'nombre'),
            'nivel' => (int) ($payload['nivel'] ?? 0),
        ];
    }

    protected function construirCargaActualizar(array $payload): array
    {
        return $this->construirCargaCrear($payload);
    }

    protected function esValidaCargaCrear(array $payload): bool
    {
        return $payload['nombre'] !== '' && (int) $payload['nivel'] > 0;
    }

    protected function esValidaCargaActualizar(int $id, array $payload): bool
    {
        return $id > 0 && $this->esValidaCargaCrear($payload);
    }
}
