<?php
declare(strict_types=1);

require_once __DIR__ . '/CatalogModel.php';

class EstadoTicketModel extends CatalogModel
{
    public function __construct()
    {
        parent::__construct('estados_ticket', ['nombre']);
    }
}
