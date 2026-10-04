<?php
/**
 * Ficheiros Excel (.xlsx) em PHP puro, sem bibliotecas nem a extensão zip.
 * Um .xlsx é um ZIP com ficheiros XML; aqui o ZIP é escrito sem compressão (método "store"), que o Excel, o LibreOffice
 * e o Google Sheets abrem sem problemas. Os textos são guardados como texto (nunca como fórmulas).
 */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

final class Xlsx
{
    // Estilos (índices em xl/styles.xml)
    public const NORMAL = 0;
    public const CABECALHO = 1;
    public const DINHEIRO = 2;
    public const DINHEIRO_NEGRITO = 3;
    public const NEGRITO = 4;
    public const TITULO = 5;
    public const PERCENT = 6;

    /** @var array<int, array{nome: string, linhas: array, larguras: array}> */
    private array $folhas = [];

    /**
     * Junta uma folha. Cada linha é uma lista de células; uma célula é um valor (texto ou número)
     * ou [valor, estilo]. Os números são escritos como números, o resto como texto.
     */
    public function folha(string $nome, array $linhas): void
    {
        $nome = mb_substr(preg_replace('/[\[\]:*?\/\\\\]/u', ' ', $nome) ?? 'Folha', 0, 31);
        $larguras = [];
        foreach ($linhas as $linha) {
            foreach (array_values($linha) as $i => $celula) {
                $v = is_array($celula) ? $celula[0] : $celula;
                $tamanho = is_float($v) || is_int($v) ? 14 : mb_strlen((string) $v);
                $larguras[$i] = max($larguras[$i] ?? 8, min(60, $tamanho + 3));
            }
        }
        $this->folhas[] = ['nome' => $nome !== '' ? $nome : 'Folha', 'linhas' => $linhas, 'larguras' => $larguras];
    }

    private static function xml(string $texto): string
    {
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $texto) ?? '';
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function coluna(int $i): string
    {
        $s = '';
        for ($n = $i + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $s = chr(65 + ($n - 1) % 26) . $s;
        }
        return $s;
    }

    private function xmlFolha(array $f): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0" showGridLines="1"/></sheetViews><cols>';
        foreach ($f['larguras'] as $i => $w) {
            $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $x .= '</cols><sheetData>';
        foreach ($f['linhas'] as $r => $linha) {
            $x .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($linha) as $i => $celula) {
                $v = is_array($celula) ? $celula[0] : $celula;
                $estilo = is_array($celula) ? (int) ($celula[1] ?? 0) : 0;
                if ($v === null || $v === '') {
                    continue;
                }
                $ref = self::coluna($i) . ($r + 1);
                if (is_int($v) || is_float($v)) {
                    $x .= '<c r="' . $ref . '" s="' . $estilo . '"><v>' . (is_float($v) ? rtrim(rtrim(sprintf('%.4F', $v), '0'), '.') : $v) . '</v></c>';
                } else {
                    $x .= '<c r="' . $ref . '" s="' . $estilo . '" t="inlineStr"><is><t xml:space="preserve">' . self::xml((string) $v) . '</t></is></c>';
                }
            }
            $x .= '</row>';
        }
        return $x . '</sheetData></worksheet>';
    }

    private function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00\ &quot;€&quot;;[Red]\-#,##0.00\ &quot;€&quot;"/></numFmts>'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF1D4ED8"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1D4ED8"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="7">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="164" fontId="2" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="10" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    /** O ficheiro .xlsx completo, como texto binário. */
    public function saida(): string
    {
        $n = count($this->folhas);
        $tipos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $livro = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $relLivro = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $ficheiros = [];
        foreach ($this->folhas as $i => $f) {
            $id = $i + 1;
            $tipos .= '<Override PartName="/xl/worksheets/sheet' . $id . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $livro .= '<sheet name="' . self::xml($f['nome']) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
            $relLivro .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
            $ficheiros['xl/worksheets/sheet' . $id . '.xml'] = $this->xmlFolha($f);
        }
        $relLivro .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $ficheiros['[Content_Types].xml'] = $tipos . '</Types>';
        $ficheiros['_rels/.rels'] = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $ficheiros['xl/workbook.xml'] = $livro . '</sheets></workbook>';
        $ficheiros['xl/_rels/workbook.xml.rels'] = $relLivro;
        $ficheiros['xl/styles.xml'] = $this->estilos();
        return self::zip($ficheiros);
    }

    /** ZIP sem compressão. */
    private static function zip(array $ficheiros): string
    {
        $hora = getdate();
        $dosHora = (($hora['hours'] << 11) | ($hora['minutes'] << 5) | intdiv($hora['seconds'], 2)) & 0xFFFF;
        $dosData = ((($hora['year'] - 1980) << 9) | ($hora['mon'] << 5) | $hora['mday']) & 0xFFFF;
        $dados = '';
        $central = '';
        $n = 0;
        foreach ($ficheiros as $nome => $conteudo) {
            $crc = crc32($conteudo) & 0xFFFFFFFF;
            $tam = strlen($conteudo);
            $pos = strlen($dados);
            $cab = pack('vvvvvVVVvv', 20, 0x0800, 0, $dosHora, $dosData, $crc, $tam, $tam, strlen($nome), 0);
            $dados .= "PK\x03\x04" . $cab . $nome . $conteudo;
            $central .= "PK\x01\x02" . pack('v', 20) . $cab . pack('vvvVV', 0, 0, 0, 0, $pos) . $nome;
            $n++;
        }
        return $dados . $central . "PK\x05\x06" . pack('vvvvVVv', 0, 0, $n, $n, strlen($central), strlen($dados), 0);
    }
}

/** Envia um .xlsx para descarregar e termina. */
function enviarXlsx(Xlsx $x, string $nome): never
{
    $conteudo = $x->saida();
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $nome = trim(preg_replace('/[^a-z0-9]+/', '_', semAcentos($nome)) ?? 'ficheiro', '_');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nome . '.xlsx"');
    header('Content-Length: ' . strlen($conteudo));
    header('Cache-Control: private, max-age=0');
    echo $conteudo;
    exit;
}
