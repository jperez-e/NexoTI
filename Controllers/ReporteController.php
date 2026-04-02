<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/ReporteModel.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class ReporteController extends BaseController
{
    private ReporteModel $modelo;

    public function __construct()
    {
        $this->modelo = new ReporteModel();
    }

    public function resumen(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $this->responderOkJson('Resumen cargado', $this->modelo->obtenerResumen());
    }

    public function vistaPrevia(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        // El historial se pagina en el navegador para que el admin avance por bloques sin perder contexto.
        $this->responderOkJson('Vista previa cargada', $this->modelo->obtenerTicketsReporte());
    }

    public function exportarTicketsCsv(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $rows = $this->modelo->obtenerTicketsReporte();
        // El BOM y la linea sep=, ayudan a que Excel abra el archivo con acentos y columnas correctas.
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

    public function exportarTicketsExcel(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $rows = $this->modelo->obtenerTicketsReporte();

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

    public function exportarTicketsPdf(): void
    {
        $this->requerirSesion();
        $this->requerirRol([1]);
        $rows = $this->modelo->obtenerTicketsReporte();
        // DOMPDF permite reutilizar una plantilla HTML parecida a la vista web en lugar de construir el PDF manualmente.
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $html = $this->renderizarHtmlPdf($rows);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=reporte_tickets.pdf');
        echo $dompdf->output();
        exit;
    }

    private function renderizarHtmlPdf(array $rows): string
    {
        ob_start();
        require __DIR__ . '/../Views/reportes/pdf.php';
        return (string) ob_get_clean();
    }
}
