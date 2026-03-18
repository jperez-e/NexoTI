<?php
declare(strict_types=1);

require_once __DIR__ . "/BaseController.php";
require_once __DIR__ . "/../Models/ReporteModel.php";

class ReporteController extends BaseController
{
    private ReporteModel $model;

    public function __construct()
    {
        $this->model = new ReporteModel();
    }

    public function ticketsCsv(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);

        $rows = $this->model->getTicketsReporte();

        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"reporte_tickets.csv\"");

        $out = fopen("php://output", "w");
        fputcsv($out, [
            "ID",
            "Codigo",
            "Titulo",
            "Usuario",
            "Tecnico",
            "Categoria",
            "Prioridad",
            "Estado",
            "Fecha Creacion",
            "Fecha Cierre",
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row["id"],
                $row["codigo"],
                $row["titulo"],
                $row["usuario"],
                $row["tecnico"] ?? "",
                $row["categoria"],
                $row["prioridad"],
                $row["estado"],
                $row["fecha_creacion"],
                $row["fecha_cierre"] ?? "",
            ]);
        }

        fclose($out);
        exit;
    }

    public function ticketsPdf(): void
    {
        $this->requireLogin();
        $this->requireRole([1]);

        $rows = $this->model->getTicketsReporte();
        $lines = [];
        $lines[] = "Reporte de Tickets";
        $lines[] = "Generado: " . date("Y-m-d H:i");
        $lines[] = "";
        foreach ($rows as $row) {
            $lines[] = sprintf(
                "#%s %s | %s | %s | %s",
                $row["id"],
                $row["codigo"],
                $row["titulo"],
                $row["estado"],
                $row["usuario"]
            );
        }

        $pdf = $this->buildSimplePdf($lines);

        header("Content-Type: application/pdf");
        header("Content-Disposition: attachment; filename=\"reporte_tickets.pdf\"");
        header("Content-Length: " . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function buildSimplePdf(array $lines): string
    {
        $content = "BT\n/F1 12 Tf\n50 760 Td\n";
        foreach ($lines as $line) {
            $safe = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $line);
            $content .= "(" . $safe . ") Tj\n0 -16 Td\n";
        }
        $content .= "ET";

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream\nendobj\n";
        $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }
        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . count($offsets) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < count($offsets); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . count($offsets) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }
}
