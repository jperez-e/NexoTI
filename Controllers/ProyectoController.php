<?php
declare(strict_types=1);

require_once __DIR__ . "/../Models/ProyectoModel.php";

class ProyectoController
{
    public function index(): void
    {
        $model = new ProyectoModel();
        $nombreProyecto = $model->getNombreProyecto();

        require __DIR__ . "/../Views/proyecto/index.php";
    }
}
