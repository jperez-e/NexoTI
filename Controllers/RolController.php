<?php 
declare(strict_types=1); 
 
require_once __DIR__ . '/BaseController.php'; 
require_once __DIR__ . '/../Models/RolModel.php'; 
 
class RolController extends BaseController 
{ 
    private RolModel $model; 
 
    public function __construct() 
    { 
        $this->model = new RolModel(); 
    } 
 
    public function list(): void 
    { 
        $this->requireLogin(); 
        $this->requireRole([1]); 
        $rows = $this->model->getAll(); 
        $this->jsonOk('Roles cargados', $rows); 
    } 
 
    public function create(): void 
    { 
        $this->requireLogin(); 
        $this->requireRole([1]); 
        $this->requirePost(); 
 
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? ''))); 
        if ($nombre === '') { 
            $this->jsonError('Nombre requerido'); 
        } 

        $ok = $this->model->insert($nombre); 
        $ok ? $this->jsonOk('Rol creado') : $this->jsonError('No se pudo crear'); 
    } 

    public function update(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $payload = $this->requestData();
        $id = (int) ($payload['id'] ?? 0);
        $nombre = trim(strip_tags((string) ($payload['nombre'] ?? '')));

        if ($id <= 0 || $nombre === '') {
            $this->jsonError('Datos invalidos');
        }

        $ok = $this->model->update($id, $nombre);
        $ok ? $this->jsonOk('Rol actualizado') : $this->jsonError('No se pudo actualizar');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $payload = $this->requestData();
        $id = (int) ($payload['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Rol invalido');
        }

        $ok = $this->model->delete($id);
        $ok ? $this->jsonOk('Rol eliminado') : $this->jsonError('No se pudo eliminar');
    }
}
