<?php
// Este archivo PHP define el controlador de Reporte.
// Gestiona solicitudes HTTP, valida reglas de acceso y coordina la respuesta JSON o de vista según la operación.
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
        [$fechaDesde, $fechaHasta] = $this->obtenerFiltroFechas();

        $idRol = $this->obtenerIdRolActual();
        $idUsuario = $this->obtenerIdUsuarioActual();
        $resumen = $this->modelo->obtenerResumen($idRol, $idUsuario, $fechaDesde, $fechaHasta);

        $resumen['promedio_resolucion_horas'] = $this->modelo->obtenerPromedioResolucionHoras($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $resumen['promedio_primera_respuesta_horas'] = $this->modelo->obtenerPromedioPrimeraRespuestaHoras($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $resumen['estados'] = $this->modelo->obtenerDistribucionEstados($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $resumen['prioridades'] = $this->modelo->obtenerDistribucionPrioridades($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $resumen['categorias'] = $this->modelo->obtenerDistribucionCategorias($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $resumen['backlog_prioridad'] = $this->modelo->obtenerBacklogPorPrioridad($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $resumen['tendencia_mensual'] = $this->modelo->obtenerTendenciaMensual($idRol, $idUsuario, $fechaDesde, $fechaHasta);
        $resumen['rendimiento_tecnicos'] = $idRol === 1
            ? $this->modelo->obtenerRendimientoTecnicos($fechaDesde, $fechaHasta)
            : [];
        $resumen['rol_id'] = $idRol;

        $this->responderOkJson('Resumen cargado', $resumen);
    }

    public function vistaPrevia(): void
    {
        $this->requerirSesion();
        [$fechaDesde, $fechaHasta] = $this->obtenerFiltroFechas();
        // El historial se pagina en el navegador para que el admin avance por bloques sin perder contexto.
        $this->responderOkJson(
            'Vista previa cargada',
            $this->modelo->obtenerTicketsReporte($this->obtenerIdRolActual(), $this->obtenerIdUsuarioActual(), $fechaDesde, $fechaHasta)
        );
    }

    public function exportarTicketsCsv(): void
    {
        $this->requerirSesion();
        [$fechaDesde, $fechaHasta] = $this->obtenerFiltroFechas();
        $rows = $this->modelo->obtenerTicketsReporte($this->obtenerIdRolActual(), $this->obtenerIdUsuarioActual(), $fechaDesde, $fechaHasta);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_tickets.csv');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            $this->responderErrorJson('No se pudo generar el archivo CSV.', 500);
        }

        // BOM para acentos en Excel y delimitador coma explicito para CSV estandar.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Codigo', 'Titulo', 'Usuario', 'Tecnico', 'Categoria', 'Prioridad', 'Estado', 'Fecha Creacion', 'Fecha Cierre'], ',');
        foreach ($rows as $row) {
            fputcsv($out, [
                $this->valorSeguroCsv((string) ($row['codigo'] ?? '')),
                $this->valorSeguroCsv((string) ($row['titulo'] ?? '')),
                $this->valorSeguroCsv((string) ($row['usuario'] ?? '')),
                $this->valorSeguroCsv((string) ($row['tecnico'] ?? '')),
                $this->valorSeguroCsv((string) ($row['categoria'] ?? '')),
                $this->valorSeguroCsv((string) ($row['prioridad'] ?? '')),
                $this->valorSeguroCsv((string) ($row['estado'] ?? '')),
                $this->valorSeguroCsv((string) ($row['fecha_creacion'] ?? '')),
                $this->valorSeguroCsv((string) ($row['fecha_cierre'] ?? '')),
            ], ',');
        }
        fclose($out);
        exit;
    }

    public function exportarTicketsExcel(): void
    {
        $this->requerirSesion();
        [$fechaDesde, $fechaHasta] = $this->obtenerFiltroFechas();
        $rows = $this->modelo->obtenerTicketsReporte($this->obtenerIdRolActual(), $this->obtenerIdUsuarioActual(), $fechaDesde, $fechaHasta);

        $archivoXlsx = $this->crearArchivoXlsxTemporal($rows);
        if ($archivoXlsx === null) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'No se pudo generar el archivo Excel (.xlsx). Verifica que la extensión ZIP esté habilitada en PHP.';
            exit;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename=reporte_tickets.xlsx');
        header('Content-Length: ' . (string) filesize($archivoXlsx));
        header('X-Content-Type-Options: nosniff');
        readfile($archivoXlsx);
        @unlink($archivoXlsx);
        exit;
    }

    public function exportarTicketsPdf(): void
    {
        $this->requerirSesion();
        [$fechaDesde, $fechaHasta] = $this->obtenerFiltroFechas();
        $rows = $this->modelo->obtenerTicketsReporte($this->obtenerIdRolActual(), $this->obtenerIdUsuarioActual(), $fechaDesde, $fechaHasta);
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

    private function obtenerFiltroFechas(): array
    {
        $fechaDesde = trim((string) ($_GET['desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['hasta'] ?? ''));

        return [
            $fechaDesde !== '' ? $fechaDesde : null,
            $fechaHasta !== '' ? $fechaHasta : null,
        ];
    }

    private function valorSeguroCsv(string $valor): string
    {
        return $this->protegerFormula($valor);
    }

    private function protegerFormula(string $valor): string
    {
        $limpio = trim($valor);
        if ($limpio === '') {
            return '';
        }

        $primer = substr($limpio, 0, 1);
        if (in_array($primer, ['=', '+', '-', '@'], true)) {
            return "'" . $limpio;
        }

        return $limpio;
    }

    private function normalizarTextoExport(string $valor): string
    {
        $normalizado = strtolower((string) preg_replace('/\s+/', ' ', trim($valor)));
        return (string) str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $normalizado
        );
    }

    private function crearArchivoXlsxTemporal(array $filas): ?string
    {
        if (!class_exists(\ZipArchive::class)) {
            return null;
        }

        $rutaTemporal = tempnam(sys_get_temp_dir(), 'nexoti_xlsx_');
        if ($rutaTemporal === false) {
            return null;
        }

        $zip = new \ZipArchive();
        if ($zip->open($rutaTemporal, \ZipArchive::OVERWRITE) !== true) {
            @unlink($rutaTemporal);
            return null;
        }

        $zip->addFromString('[Content_Types].xml', $this->contenidoTiposXlsx());
        $zip->addFromString('_rels/.rels', $this->contenidoRelacionesRaizXlsx());
        $zip->addFromString('docProps/app.xml', $this->contenidoPropiedadesAppXlsx());
        $zip->addFromString('docProps/core.xml', $this->contenidoPropiedadesCoreXlsx());
        $zip->addFromString('xl/workbook.xml', $this->contenidoLibroXlsx());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->contenidoRelacionesLibroXlsx());
        $zip->addFromString('xl/styles.xml', $this->contenidoEstilosXlsx());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->contenidoHojaXlsx($filas));
        $zip->close();

        return $rutaTemporal;
    }

    private function contenidoTiposXlsx(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function contenidoRelacionesRaizXlsx(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function contenidoPropiedadesAppXlsx(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>NexoTI</Application><DocSecurity>0</DocSecurity><ScaleCrop>false</ScaleCrop>'
            . '<HeadingPairs><vt:vector size="2" baseType="variant">'
            . '<vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant>'
            . '<vt:variant><vt:i4>1</vt:i4></vt:variant>'
            . '</vt:vector></HeadingPairs>'
            . '<TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>Tickets</vt:lpstr></vt:vector></TitlesOfParts>'
            . '<Company>NexoTI</Company><LinksUpToDate>false</LinksUpToDate><SharedDoc>false</SharedDoc>'
            . '<HyperlinksChanged>false</HyperlinksChanged><AppVersion>1.0</AppVersion></Properties>';
    }

    private function contenidoPropiedadesCoreXlsx(): string
    {
        $ahora = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            . 'xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" '
            . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Reporte de Tickets</dc:title>'
            . '<dc:creator>NexoTI</dc:creator>'
            . '<cp:lastModifiedBy>NexoTI</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $ahora . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $ahora . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function contenidoLibroXlsx(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Tickets" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function contenidoRelacionesLibroXlsx(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function contenidoEstilosXlsx(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="6">'
            . '<font><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF92400E"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF0F766E"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF166534"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF1D4ED8"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1D4ED8"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right><top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function contenidoHojaXlsx(array $filas): string
    {
        $encabezados = ['Código', 'Título', 'Usuario', 'Técnico', 'Categoría', 'Prioridad', 'Estado', 'Fecha creación', 'Fecha cierre'];
        $datos = [];
        foreach ($filas as $fila) {
            $datos[] = [
                $this->valorSeguroCsv((string) ($fila['codigo'] ?? '')),
                $this->valorSeguroCsv((string) ($fila['titulo'] ?? '')),
                $this->valorSeguroCsv((string) ($fila['usuario'] ?? '')),
                $this->valorSeguroCsv((string) ($fila['tecnico'] ?? '')),
                $this->valorSeguroCsv((string) ($fila['categoria'] ?? '')),
                $this->valorSeguroCsv((string) ($fila['prioridad'] ?? '')),
                $this->valorSeguroCsv((string) ($fila['estado'] ?? '')),
                $this->valorSeguroCsv((string) ($fila['fecha_creacion'] ?? '')),
                $this->valorSeguroCsv((string) ($fila['fecha_cierre'] ?? '')),
            ];
        }

        $totalFilas = count($datos) + 1;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:I' . $totalFilas . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>'
            . '<col min="1" max="1" width="14" customWidth="1"/>'
            . '<col min="2" max="2" width="42" customWidth="1"/>'
            . '<col min="3" max="3" width="24" customWidth="1"/>'
            . '<col min="4" max="4" width="24" customWidth="1"/>'
            . '<col min="5" max="5" width="18" customWidth="1"/>'
            . '<col min="6" max="6" width="14" customWidth="1"/>'
            . '<col min="7" max="7" width="14" customWidth="1"/>'
            . '<col min="8" max="8" width="22" customWidth="1"/>'
            . '<col min="9" max="9" width="22" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>';

        // Fila de encabezado
        $xml .= '<row r="1" ht="22" customHeight="1">';
        foreach ($encabezados as $indice => $valor) {
            $col = $this->columnaExcelDesdeIndice($indice + 1);
            $xml .= $this->celdaTextoXlsx($col . '1', $valor, 1);
        }
        $xml .= '</row>';

        // Filas de datos
        foreach ($datos as $indiceFila => $fila) {
            $numeroFila = $indiceFila + 2;
            $xml .= '<row r="' . $numeroFila . '">';
            foreach ($fila as $indiceColumna => $valor) {
                $col = $this->columnaExcelDesdeIndice($indiceColumna + 1);
                $estiloBase = ($numeroFila % 2 === 0) ? 2 : 3;
                $estilo = $estiloBase;
                if ($indiceColumna === 6) {
                    $estilo = $this->estiloEstadoXlsx($valor);
                }
                $xml .= $this->celdaTextoXlsx($col . $numeroFila, $valor, $estilo);
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function celdaTextoXlsx(string $referencia, string $valor, int $estilo): string
    {
        $texto = htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        return '<c r="' . $referencia . '" s="' . $estilo . '" t="inlineStr"><is><t>' . $texto . '</t></is></c>';
    }

    private function estiloEstadoXlsx(string $estado): int
    {
        $normalizado = $this->normalizarTextoExport($estado);
        if (strpos($normalizado, 'progreso') !== false || strpos($normalizado, 'proceso') !== false) {
            return 5;
        }
        if (strpos($normalizado, 'resuelto') !== false) {
            return 6;
        }
        if (strpos($normalizado, 'cerrado') !== false) {
            return 7;
        }
        return 4;
    }

    private function columnaExcelDesdeIndice(int $indice): string
    {
        $resultado = '';
        $n = $indice;
        while ($n > 0) {
            $resto = ($n - 1) % 26;
            $resultado = chr(65 + $resto) . $resultado;
            $n = (int) floor(($n - 1) / 26);
        }
        return $resultado;
    }
}
