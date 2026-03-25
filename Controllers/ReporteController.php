<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ReporteModel.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class ReporteController extends BaseController
{
    private ReporteModel $model;

    public function __construct()
    {
        $this->model = new ReporteModel();
    }

    public function resumen(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->jsonOk('Resumen cargado', $this->model->getResumen());
    }

    public function preview(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $this->jsonOk('Vista previa cargada', array_slice($this->model->getTicketsReporte(), 0, 12));
    }

    public function ticketsCsv(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $rows = $this->model->getTicketsReporte();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_tickets.csv');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fwrite($out, "sep=,\n");
        fputcsv($out, ['Codigo', 'Titulo', 'Usuario', 'Tecnico', 'Categoria', 'Prioridad', 'Estado', 'Fecha Creacion', 'Fecha Cierre']);
        foreach ($rows as $row) {
            fputcsv($out, [$row['codigo'], $row['titulo'], $row['usuario'], $row['tecnico'] ?? '', $row['categoria'], $row['prioridad'], $row['estado'], $row['fecha_creacion'], $row['fecha_cierre'] ?? '']);
        }
        fclose($out);
        exit;
    }

    public function ticketsExcel(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $rows = $this->model->getTicketsReporte();

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_tickets.xls');

        echo '<table border="1">';
        echo '<tr><th>Codigo</th><th>Titulo</th><th>Usuario</th><th>Tecnico</th><th>Categoria</th><th>Prioridad</th><th>Estado</th><th>Fecha Creacion</th><th>Fecha Cierre</th></tr>';
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars((string) $row['codigo'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string) $row['titulo'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string) $row['usuario'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string) ($row['tecnico'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string) $row['categoria'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string) $row['prioridad'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string) $row['estado'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string) $row['fecha_creacion'], ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars((string) ($row['fecha_cierre'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }

    public function ticketsPdf(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);
        $rows = $this->model->getTicketsReporte();
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = $this->renderPdfHtml($rows);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=reporte_tickets.pdf');
        echo $dompdf->output();
        exit;
    }

    private function renderPdfHtml(array $rows): string
    {
        ob_start();
        require __DIR__ . '/../Views/reportes/pdf.php';
        return (string) ob_get_clean();
    }
}
