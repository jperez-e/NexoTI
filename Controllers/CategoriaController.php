<?php 
declare(strict_types=1); 
 
require_once __DIR__ . '/BaseController.php'; 
require_once __DIR__ . '/../Models/CategoriaModel.php'; 
 
class CategoriaController extends BaseController 
{ 
    private CategoriaModel $model; 
 
    public function __construct() 
    { 
        $this->model = new CategoriaModel(); 
    } 
 
    public function list(): void 
    { 
        $this->requireLogin(); 
        $rows = $this->model->getAll(); 
        $this->jsonOk('Categorias cargadas', $rows); 
    } 
 
    public function create(): void 
    { 
        $this->requireLogin(); 
        $this->requireRole([1]); 
        $this->requirePost(); 
 
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? ''))); 
        $descripcion = trim(strip_tags((string) ($_POST['descripcion'] ?? ''))); 
 
        if ($nombre === '') { 
            $this->jsonError('Nombre requerido'); 
        } 
 
        $ok = $this->model->insert($nombre, $descripcion); 
        $ok ? $this->jsonOk('Categoria creada') : $this->jsonError('No se pudo crear'); 
    } 
}
