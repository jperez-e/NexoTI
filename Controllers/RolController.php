<?php
// Este archivo PHP define el controlador de Rol.
// Gestiona solicitudes HTTP, valida reglas de acceso y coordina la respuesta JSON o de vista según la operación.
 
declare(strict_types=1);

require_once __DIR__ . '/CatalogController.php';
require_once __DIR__ . '/../Models/RolModel.php';

class RolController extends CatalogController
{
    public function __construct()
    {
        $this->modelo = new RolModel();
    }

    public function listar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->responderOkJson($this->mensajeListado(), $this->modelo->obtenerTodos());
    }

    protected function mensajeListado(): string { return 'Roles cargados'; }
    protected function mensajeCreacion(): string { return 'Rol creado'; }
    protected function mensajeActualizacion(): string { return 'Rol actualizado'; }
    protected function mensajeEliminacion(): string { return 'Rol eliminado'; }
    protected function mensajeDatosInvalidos(): string { return 'Datos invalidos'; }
    protected function mensajeEntidadInvalida(): string { return 'Rol invalido'; }

    protected function construirCargaCrear(array $payload): array
    {
        return ['nombre' => $this->sanearTexto($payload, 'nombre')];
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
