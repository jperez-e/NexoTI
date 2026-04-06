<?php
// Este archivo PHP define el controlador para la entidad "EstadoTicket",
// que extiende de CatalogController para implementar las operaciones de listado, creación, 
// actualización y eliminación de estados de tickets en la aplicación.
// El controlador utiliza el modelo EstadoTicketModel para interactuar con la base de datos
// y proporciona métodos específicos para validar los datos de entrada y 
// construir las cargas de datos necesarias para  realizar las operaciones correspondientes.
 
declare(strict_types=1);

require_once __DIR__ . '/CatalogController.php';
require_once __DIR__ . '/../Models/EstadoTicketModel.php';

class EstadoTicketController extends CatalogController
{
    public function __construct()
    {
        $this->modelo = new EstadoTicketModel();
    }

    protected function mensajeListado(): string { return 'Estados cargados'; }
    protected function mensajeCreacion(): string { return 'Estado creado'; }
    protected function mensajeActualizacion(): string { return 'Estado actualizado'; }
    protected function mensajeEliminacion(): string { return 'Estado eliminado'; }
    protected function mensajeDatosInvalidos(): string { return 'Datos invalidos'; }
    protected function mensajeEntidadInvalida(): string { return 'Estado invalido'; }

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
