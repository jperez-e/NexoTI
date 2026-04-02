<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/CatalogModel.php';

abstract class CatalogController extends BaseController
{
    protected CatalogModel $modelo;

    abstract protected function mensajeListado(): string;
    abstract protected function mensajeCreacion(): string;
    abstract protected function mensajeActualizacion(): string;
    abstract protected function mensajeEliminacion(): string;
    abstract protected function mensajeDatosInvalidos(): string;
    abstract protected function mensajeEntidadInvalida(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function construirCargaCrear(array $datos): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function construirCargaActualizar(array $datos): array;

    abstract protected function esValidaCargaCrear(array $datos): bool;
    abstract protected function esValidaCargaActualizar(int $id, array $datos): bool;

    public function listar(): void
    {
        $this->requerirSesion();
        $this->responderOkJson($this->mensajeListado(), $this->modelo->obtenerTodos());
    }

    public function crear(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        $carga = $this->construirCargaCrear($this->obtenerDatosSolicitud());
        if (!$this->esValidaCargaCrear($carga)) {
            $this->responderErrorJson($this->mensajeDatosInvalidos());
        }

        $ok = $this->modelo->insertar($carga);
        $ok ? $this->responderOkJson($this->mensajeCreacion()) : $this->responderErrorJson('No se pudo crear');
    }

    public function actualizar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        $datos = $this->obtenerDatosSolicitud();
        $id = (int) ($datos['id'] ?? 0);
        $carga = $this->construirCargaActualizar($datos);

        if (!$this->esValidaCargaActualizar($id, $carga)) {
            $this->responderErrorJson($this->mensajeDatosInvalidos());
        }

        $ok = $this->modelo->actualizar($id, $carga);
        $ok ? $this->responderOkJson($this->mensajeActualizacion()) : $this->responderErrorJson('No se pudo actualizar');
    }

    public function eliminar(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->requerirPost();

        $datos = $this->obtenerDatosSolicitud();
        $id = (int) ($datos['id'] ?? 0);
        if ($id <= 0) {
            $this->responderErrorJson($this->mensajeEntidadInvalida());
        }

        $ok = $this->modelo->eliminar($id);
        $ok ? $this->responderOkJson($this->mensajeEliminacion()) : $this->responderErrorJson('No se pudo eliminar');
    }

    protected function sanearTexto(array $datos, string $campo): string
    {
        return trim(strip_tags((string) ($datos[$campo] ?? '')));
    }
}
