<?php 
declare(strict_types=1); 
 
require_once __DIR__ . '/BaseController.php'; 
require_once __DIR__ . '/../Models/PrioridadModel.php'; 
 
class PrioridadController extends BaseController 
{ 
    private PrioridadModel $model; 
 
    public function __construct() 
    { 
        $this->model = new PrioridadModel(); 
    } 
 
    public function list(): void 
    { 
        $this->requireLogin(); 
        $rows = $this->model->getAll(); 
        $this->jsonOk('Prioridades cargadas', $rows); 
    } 
 
    public function create(): void 
    { 
        $this->requireLogin(); 
        $this->requireRole([1]); 
        $this->requirePost(); 
 
        $nombre = trim(strip_tags((string) ($_POST['nombre'] ?? ''))); 
        $nivel = (int) ($_POST['nivel'] ?? 0); 
 
        if ($nombre === '' || $nivel <= 0) { 
            $this->jsonError('Datos invalidos'); 
        } 

        $ok = $this->model->insert($nombre, $nivel); 
        $ok ? $this->jsonOk('Prioridad creada') : $this->jsonError('No se pudo crear'); 
    } 

    public function update(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $payload = $this->requestData();
        $id = (int) ($payload['id'] ?? 0);
        $nombre = trim(strip_tags((string) ($payload['nombre'] ?? '')));
        $nivel = (int) ($payload['nivel'] ?? 0);

        if ($id <= 0 || $nombre === '' || $nivel <= 0) {
            $this->jsonError('Datos invalidos');
        }

        $ok = $this->model->update($id, $nombre, $nivel);
        $ok ? $this->jsonOk('Prioridad actualizada') : $this->jsonError('No se pudo actualizar');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $payload = $this->requestData();
        $id = (int) ($payload['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonError('Prioridad invalida');
        }

        $ok = $this->model->delete($id);
        $ok ? $this->jsonOk('Prioridad eliminada') : $this->jsonError('No se pudo eliminar');
    }
}
