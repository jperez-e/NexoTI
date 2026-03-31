<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/TicketModel.php';

class TicketService
{
    private TicketModel $tickets;

    public function __construct()
    {
        $this->tickets = new TicketModel();
    }

    public function listTickets(array $filters, int $page, int $perPage, int $roleId, int $userId): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(20, $perPage));
        $normalizedFilters = $this->normalizeFilters($filters);

        $total = $this->tickets->countSearch($normalizedFilters, $roleId, $userId);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'items' => $this->tickets->search($normalizedFilters, $page, $perPage, $roleId, $userId),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'query' => $normalizedFilters['query'],
                'estado' => $normalizedFilters['estado'],
            ],
            'assignable' => $roleId === 1 ? $this->tickets->getOpenTicketsForAssignment() : [],
            'closable' => $roleId === 3 ? $this->tickets->getResolvedTicketsByUsuario($userId) : [],
            'notifications' => [
                'count' => $this->tickets->countNotifications($roleId, $userId),
                'items' => $this->tickets->getNotifications($roleId, $userId, 6),
            ],
        ];
    }

    public function generateTicketCode(): string
    {
        do {
            $code = 'TCK-' . (string) random_int(1000000000000, 9999999999999);
        } while ($this->tickets->existsCodigo($code));

        return $code;
    }

    private function normalizeFilters(array $filters): array
    {
        $query = trim((string) ($filters['query'] ?? ''));
        $estado = $this->normalizeStatusFilter((string) ($filters['estado'] ?? 'todos'));

        return [
            'query' => $query,
            'estado' => $estado,
        ];
    }

    private function normalizeStatusFilter(string $value): ?string
    {
        $normalized = $this->normalizeLabel($value);
        if ($normalized === '' || $normalized === 'todos') {
            return null;
        }
        if ($normalized === 'en progreso') {
            return 'En proceso';
        }

        return ucfirst($normalized);
    }

    private function normalizeLabel(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?: $normalized;
        return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
    }
}
