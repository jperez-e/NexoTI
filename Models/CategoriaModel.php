<?php
// Este archivo PHP define el modelo de Categoria.
// Centraliza el acceso a base de datos para consultas y operaciones de persistencia relacionadas con esta entidad.
declare(strict_types=1);

require_once __DIR__ . '/CatalogModel.php';

class CategoriaModel extends CatalogModel
{
    public function __construct()
    {
        parent::__construct('categorias', ['nombre', 'descripcion']);
    }
}
