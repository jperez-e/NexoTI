<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/CatalogModel.php';

abstract class CatalogController extends BaseController
{
    protected CatalogModel $model;

    abstract protected function listMessage(): string;
    abstract protected function createMessage(): string;
    abstract protected function updateMessage(): string;
    abstract protected function deleteMessage(): string;
    abstract protected function invalidDataMessage(): string;
    abstract protected function invalidEntityMessage(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function buildCreatePayload(array $payload): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function buildUpdatePayload(array $payload): array;

    abstract protected function isCreatePayloadValid(array $payload): bool;
    abstract protected function isUpdatePayloadValid(int $id, array $payload): bool;

    public function list(): void
    {
        $this->requireLogin();
        $this->jsonOk($this->listMessage(), $this->model->getAll());
    }

    public function create(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $payload = $this->buildCreatePayload($this->requestData());
        if (!$this->isCreatePayloadValid($payload)) {
            $this->jsonError($this->invalidDataMessage());
        }

        $ok = $this->model->insert($payload);
        $ok ? $this->jsonOk($this->createMessage()) : $this->jsonError('No se pudo crear');
    }

    public function update(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $request = $this->requestData();
        $id = (int) ($request['id'] ?? 0);
        $payload = $this->buildUpdatePayload($request);

        if (!$this->isUpdatePayloadValid($id, $payload)) {
            $this->jsonError($this->invalidDataMessage());
        }

        $ok = $this->model->update($id, $payload);
        $ok ? $this->jsonOk($this->updateMessage()) : $this->jsonError('No se pudo actualizar');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->requirePost();

        $payload = $this->requestData();
        $id = (int) ($payload['id'] ?? 0);
        if ($id <= 0) {
            $this->jsonError($this->invalidEntityMessage());
        }

        $ok = $this->model->delete($id);
        $ok ? $this->jsonOk($this->deleteMessage()) : $this->jsonError('No se pudo eliminar');
    }

    protected function sanitizeText(array $payload, string $field): string
    {
        return trim(strip_tags((string) ($payload[$field] ?? '')));
    }
}
