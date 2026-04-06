<?php
// Este archivo PHP define el modelo de EstadoTicket.
// Centraliza el acceso a base de datos para consultas y operaciones de persistencia relacionadas con esta entidad.
declare(strict_types=1);

require_once __DIR__ . '/CatalogModel.php';

class EstadoTicketModel extends CatalogModel
{
    public function __construct()
    {
        parent::__construct('estados_ticket', ['nombre']);
    }
}
