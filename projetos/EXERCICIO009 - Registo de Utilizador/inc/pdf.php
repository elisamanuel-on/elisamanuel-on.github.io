<?php
/**
 * Gerador de PDF simples, sem bibliotecas: texto, linhas e retângulos em A4 horizontal.
 * Usa as letras Helvetica que todos os leitores de PDF já têm (por isso o ficheiro é pequeno e não depende de nada instalado).
 */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

final class Pdf
{
    // Larguras (em milésimos de corpo) dos caracteres 32 a 126 da Helvetica e da Helvetica-Bold
    private const LARGURAS = [
        false => [278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
            556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556, 1015,
            667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611,
            278, 278, 278, 469, 556, 333,
            556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556, 556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500,
            334, 260, 334, 584],
        true => [278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278,
            556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611, 975,
            722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611,
            333, 278, 333, 584, 556, 333,
            556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611, 611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500,
            389, 280, 389, 584],
    ];
    // Letra base de cada caractere acentuado (0xC0 a 0xFF), para estimar a largura
    private const BASE_ACENTOS = 'AAAAAA@CEEEEIIIIDNOOOOOxOUUUUYPBaaaaaamceeeeiiiionooooo+ouuuuypy';

    public float $largura = 841.89;
    public float $altura = 595.28;
    private array $paginas = [];
    private int $atual = -1;

    public function __construct(private string $titulo, private string $autor = '')
    {
        $this->novaPagina();
    }

    public function novaPagina(): int
    {
        $this->paginas[] = '';
        return $this->atual = count($this->paginas) - 1;
    }

    public function irParaPagina(int $i): void
    {
        $this->atual = $i;
    }

    public function totalPaginas(): int
    {
        return count($this->paginas);
    }

    public function paginaAtual(): int
    {
        return $this->atual;
    }

    /** UTF-8 para Windows-1252 (o que as letras standard do PDF entendem). */
    private function codificar(string $texto): string
    {
        $texto = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $texto) ?? '';
        $texto = str_replace(["\u{2265}", "\u{2264}", "\u{2192}"], ['>=', '<=', '->'], $texto);
        return mb_convert_encoding($texto, 'Windows-1252', 'UTF-8');
    }

    private function larguraByte(int $b, bool $negrito): float
    {
        if ($b >= 32 && $b <= 126) {
            return self::LARGURAS[$negrito][$b - 32];
        }
        if ($b >= 0xC0) {
            return $this->larguraByte(ord(self::BASE_ACENTOS[$b - 0xC0]), $negrito);
        }
        return match ($b) {
            0x85, 0x97 => 1000,
            0xAA, 0xBA => 365,
            default => 556,
        };
    }

    public function larguraTexto(string $texto, float $tamanho, bool $negrito = false): float
    {
        $bytes = $this->codificar($texto);
        $soma = 0.0;
        for ($i = 0, $n = strlen($bytes); $i < $n; $i++) {
            $soma += $this->larguraByte(ord($bytes[$i]), $negrito);
        }
        return $soma * $tamanho / 1000;
    }

    /** Corta o texto com reticências para caber na largura. */
    public function ajustar(string $texto, float $larg, float $tamanho, bool $negrito = false): string
    {
        if ($this->larguraTexto($texto, $tamanho, $negrito) <= $larg) {
            return $texto;
        }
        $letras = mb_str_split($texto);
        while ($letras && $this->larguraTexto(implode('', $letras) . '…', $tamanho, $negrito) > $larg) {
            array_pop($letras);
        }
        return rtrim(implode('', $letras)) . '…';
    }

    /** Parte o texto em linhas que cabem na largura (por palavras; palavras muito longas são cortadas). */
    public function quebrar(string $texto, float $larg, float $tamanho, bool $negrito = false): array
    {
        $linhas = [];
        foreach (preg_split('/\R/u', $texto) ?: [''] as $paragrafo) {
            $atual = '';
            foreach (preg_split('/\s+/u', trim($paragrafo)) ?: [] as $palavra) {
                if ($palavra === '') {
                    continue;
                }
                $teste = $atual === '' ? $palavra : $atual . ' ' . $palavra;
                if ($this->larguraTexto($teste, $tamanho, $negrito) <= $larg) {
                    $atual = $teste;
                    continue;
                }
                if ($atual !== '') {
                    $linhas[] = $atual;
                }
                while ($this->larguraTexto($palavra, $tamanho, $negrito) > $larg && mb_strlen($palavra) > 1) {
                    $corte = $palavra;
                    while (mb_strlen($corte) > 1 && $this->larguraTexto($corte, $tamanho, $negrito) > $larg) {
                        $corte = mb_substr($corte, 0, -1);
                    }
                    $linhas[] = $corte;
                    $palavra = mb_substr($palavra, mb_strlen($corte));
                }
                $atual = $palavra;
            }
            $linhas[] = $atual;
        }
        return $linhas;
    }

    private function cor(array $c): string
    {
        return sprintf('%.3F %.3F %.3F', $c[0], $c[1], $c[2]);
    }

    private function y(float $y): float
    {
        return $this->altura - $y;
    }

    /** Texto com a linha de base em $y (contado a partir do topo). $alinhar: e (esquerda), c (centro), d (direita) dentro da caixa $larg. */
    public function texto(float $x, float $y, string $texto, float $tamanho = 10, bool $negrito = false, string $alinhar = 'e', float $larg = 0, array $cor = [0, 0, 0]): void
    {
        if ($texto === '') {
            return;
        }
        if ($alinhar !== 'e' && $larg > 0) {
            $w = $this->larguraTexto($texto, $tamanho, $negrito);
            $x += $alinhar === 'c' ? ($larg - $w) / 2 : $larg - $w;
        }
        $bytes = $this->codificar($texto);
        $escapado = strtr($bytes, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
        $this->paginas[$this->atual] .= sprintf("BT /%s %.2F Tf %s rg %.2F %.2F Td (%s) Tj ET\n", $negrito ? 'F2' : 'F1', $tamanho, $this->cor($cor), $x, $this->y($y), $escapado);
    }

    public function retangulo(float $x, float $y, float $w, float $h, ?array $preenchimento, ?array $contorno = null, float $espessura = 0.5): void
    {
        $op = '';
        if ($preenchimento !== null) {
            $op .= $this->cor($preenchimento) . " rg\n";
        }
        if ($contorno !== null) {
            $op .= $this->cor($contorno) . sprintf(" RG %.2F w\n", $espessura);
        }
        $pintar = $preenchimento !== null && $contorno !== null ? 'B' : ($preenchimento !== null ? 'f' : 'S');
        $this->paginas[$this->atual] .= $op . sprintf("%.2F %.2F %.2F %.2F re %s\n", $x, $this->y($y + $h), $w, $h, $pintar);
    }

    public function linha(float $x1, float $y1, float $x2, float $y2, float $espessura = 0.5, array $cor = [0.6, 0.6, 0.6]): void
    {
        $this->paginas[$this->atual] .= sprintf("%s RG %.2F w %.2F %.2F m %.2F %.2F l S\n", $this->cor($cor), $espessura, $x1, $this->y($y1), $x2, $this->y($y2));
    }

    /** Monta o ficheiro PDF completo. */
    public function saida(): string
    {
        $objetos = [];
        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $n = count($this->paginas);
        $filhos = [];
        for ($i = 0; $i < $n; $i++) {
            $filhos[] = (5 + $i * 2) . ' 0 R';
        }
        $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $filhos) . '] /Count ' . $n . ' >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        foreach ($this->paginas as $i => $conteudo) {
            $idPagina = 5 + $i * 2;
            $objetos[$idPagina] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                $this->largura,
                $this->altura,
                $idPagina + 1
            );
            $objetos[$idPagina + 1] = "<< /Length " . strlen($conteudo) . " >>\nstream\n" . $conteudo . "endstream";
        }
        $info = count($objetos) + 1;
        $objetos[$info] = '<< /Title (' . strtr($this->codificar($this->titulo), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']) . ') /Producer (Sistema de Gestao Escolar) /CreationDate (D:' . date('YmdHis') . ') >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $posicoes = [];
        ksort($objetos);
        foreach ($objetos as $id => $corpo) {
            $posicoes[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $corpo . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($info + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= $info; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $posicoes[$id]);
        }
        $pdf .= "trailer\n<< /Size " . ($info + 1) . ' /Root 1 0 R /Info ' . $info . " 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
        return $pdf;
    }
}

/** Desenha o relatório (pauta) em PDF: cabeçalho, tabela com cabeçalho repetido, resumo, observações e assinaturas. */
function pdfRelatorio(array $r): string
{
    $pdf = new Pdf($r['titulo']);
    $margem = 36.0;
    $largUtil = $pdf->largura - 2 * $margem;
    $limite = $pdf->altura - 46;
    $azul = [0.145, 0.388, 0.922];
    $azulEscuro = [0.114, 0.255, 0.600];
    $cinza = [0.42, 0.45, 0.50];
    $vermelho = [0.78, 0.13, 0.13];
    $verde = [0.08, 0.50, 0.24];
    $nCol = count($r['colunas']);

    // ---- cabeçalho da primeira página ----
    $pdf->texto($margem, 56, $r['instituicao'] !== '' ? $r['instituicao'] : t('escola'), 17, true, 'e', 0, $azulEscuro);
    $pdf->texto($margem, 76, $r['titulo'], 12.5, true);
    $pdf->linha($margem, 86, $pdf->largura - $margem, 86, 1.2, $azul);

    $campos = [
        [t('curso_turma'), $r['curso']], [t('disciplina_modulo'), $r['disciplina']],
        [t('formador'), $r['formador']], [t('periodo'), $r['periodo_texto']],
        [t('data'), dataFmt($r['data'])], [t('alunos'), (string) count($r['linhas'])],
    ];
    $y = 106.0;
    $colunaMeta = $largUtil / 2;
    foreach ($campos as $k => [$rotulo, $valor]) {
        $x = $margem + ($k % 2) * $colunaMeta;
        $pdf->texto($x, $y, mb_strtoupper($rotulo), 7.5, true, 'e', 0, $cinza);
        $pdf->texto($x, $y + 12, $pdf->ajustar($valor === '' ? '—' : $valor, $colunaMeta - 16, 10.5), 10.5);
        if ($k % 2 === 1) {
            $y += 30;
        }
    }
    $y += 4;

    // ---- colunas da tabela ----
    $wNum = 30.0;
    $wMedia = 44.0;
    $wFinal = 44.0;
    $wRes = 58.0;
    $restante = $largUtil - $wNum - $wMedia - $wFinal - $wRes;
    $wNome = $nCol > 0 ? max(120.0, min(210.0, $restante - $nCol * 54)) : $restante;
    $wNota = $nCol > 0 ? max(34.0, min(76.0, ($restante - $wNome) / $nCol)) : 0;
    $wNome = $restante - $nCol * $wNota; // o nome aproveita o que sobra

    $fonteCab = $wNota < 50 ? 7.0 : 8.0;   // colunas estreitas: letra mais pequena no cabeçalho
    $titulosCol = [];
    $maxLinhas = 1;
    foreach ($r['colunas'] as $c) {
        $partes = $pdf->quebrar($c['titulo'], $wNota - 4, $fonteCab, true);
        if (count($partes) > 3) {
            $partes = array_slice($partes, 0, 3);
            $partes[2] = $pdf->ajustar($partes[2] . '…', $wNota - 4, $fonteCab, true);
        }
        $titulosCol[] = $partes;
        $maxLinhas = max($maxLinhas, count($partes));
    }
    $alturaCab = 12 + $maxLinhas * ($fonteCab + 1.6) + 10;
    $alturaLinha = 18.0;

    $cabecalho = static function (float $topo) use ($pdf, $margem, $largUtil, $wNum, $wNome, $wNota, $wMedia, $wFinal, $wRes, $r, $titulosCol, $alturaCab, $azulEscuro, $fonteCab): float {
        $pdf->retangulo($margem, $topo, $largUtil, $alturaCab, $azulEscuro);
        $branco = [1, 1, 1];
        $base = $topo + 15;
        $pdf->texto($margem, $base, t('numero_abrev'), 8, true, 'c', $wNum, $branco);
        $pdf->texto($margem + $wNum + 5, $base, t('aluno'), 8, true, 'e', 0, $branco);
        $x = $margem + $wNum + $wNome;
        foreach ($r['colunas'] as $j => $c) {
            foreach ($titulosCol[$j] as $k => $parte) {
                $pdf->texto($x, $base + $k * ($fonteCab + 1.6), $parte, $fonteCab, true, 'c', $wNota, $branco);
            }
            $pdf->texto($x, $topo + $alturaCab - 6, $c['peso'] . '%', 7, false, 'c', $wNota, [0.80, 0.87, 1]);
            $x += $wNota;
        }
        $pdf->texto($x, $base, t('media'), 8, true, 'c', $wMedia, $branco);
        $pdf->texto($x + $wMedia, $base, t('nota_final'), 8, true, 'c', $wFinal, $branco);
        $pdf->texto($x + $wMedia + $wFinal, $base, t('resultado'), 8, true, 'c', $wRes, $branco);
        return $topo + $alturaCab;
    };

    $y = $cabecalho($y);

    // ---- linhas ----
    $positivas = $negativas = 0;
    $somaFinais = 0;
    $comNota = 0;
    foreach ($r['linhas'] as $i => $linha) {
        if ($y + $alturaLinha > $limite) {
            $pdf->novaPagina();
            $pdf->texto($margem, 30, $r['titulo'] . ' · ' . $r['instituicao'], 8, false, 'e', 0, [0.45, 0.45, 0.45]);
            $y = $cabecalho(40.0);
        }
        if ($i % 2 === 1) {
            $pdf->retangulo($margem, $y, $largUtil, $alturaLinha, [0.955, 0.965, 0.98]);
        }
        $base = $y + 12.5;
        $calc = relatorioCalcularLinha($r['colunas'], $linha);
        $pdf->texto($margem, $base, $linha['numero'], 9, false, 'c', $wNum);
        $pdf->texto($margem + $wNum + 5, $base, $pdf->ajustar($linha['nome'], $wNome - 10, 9.5), 9.5);
        $x = $margem + $wNum + $wNome;
        foreach ($r['colunas'] as $j => $c) {
            $v = $linha['notas'][$j] ?? null;
            $pdf->texto($x, $base, $v === null ? '' : numFmt((float) $v, 1), 9.5, false, 'c', $wNota, $v !== null && $v < NOTA_POSITIVA ? $vermelho : [0, 0, 0]);
            $x += $wNota;
        }
        $pdf->texto($x, $base, $calc['media'] === null ? '—' : numFmt($calc['media'], 1), 9.5, false, 'c', $wMedia);
        if ($calc['final'] === null) {
            $pdf->texto($x + $wMedia, $base, '—', 9.5, false, 'c', $wFinal);
            $pdf->texto($x + $wMedia + $wFinal, $base, '—', 9, false, 'c', $wRes);
        } else {
            $ok = $calc['final'] >= NOTA_POSITIVA;
            $ok ? $positivas++ : $negativas++;
            $comNota++;
            $somaFinais += $calc['final'];
            $pdf->texto($x + $wMedia, $base, (string) $calc['final'], 10, true, 'c', $wFinal, $ok ? [0, 0, 0] : $vermelho);
            $pdf->texto($x + $wMedia + $wFinal, $base, t($ok ? 'positiva' : 'negativa'), 8.5, true, 'c', $wRes, $ok ? $verde : $vermelho);
        }
        $pdf->linha($margem, $y + $alturaLinha, $margem + $largUtil, $y + $alturaLinha, 0.4, [0.86, 0.88, 0.91]);
        $y += $alturaLinha;
    }
    if (!$r['linhas']) {
        $pdf->texto($margem + 6, $y + 16, t('sem_alunos'), 9.5, false, 'e', 0, $cinza);
        $y += 26;
    }

    // ---- resumo, observações e assinaturas ----
    $observacoes = $r['observacoes'] !== '' ? $pdf->quebrar($r['observacoes'], $largUtil, 9) : [];
    $preciso = 34 + ($observacoes ? 18 + count($observacoes) * 12 : 0) + 74;
    if ($y + $preciso > $limite) {
        $pdf->novaPagina();
        $pdf->texto($margem, 30, $r['titulo'] . ' · ' . $r['instituicao'], 8, false, 'e', 0, [0.45, 0.45, 0.45]);
        $y = 40.0;
    }
    $y += 22;
    $media = $comNota ? $somaFinais / $comNota : null;
    $resumo = t('positivas') . ': ' . $positivas . '     ' . t('negativas') . ': ' . $negativas . '     ' . t('media_turma') . ': ' . numFmt($media, 1);
    $pdf->texto($margem, $y, $resumo, 10, true);
    $y += 8;
    if ($observacoes) {
        $y += 14;
        $pdf->texto($margem, $y, mb_strtoupper(t('observacoes')), 7.5, true, 'e', 0, $cinza);
        foreach ($observacoes as $linha) {
            $y += 12;
            $pdf->texto($margem, $y, $linha, 9);
        }
        $y += 4;
    }
    $y += 48;
    $larguraAss = 240.0;
    $pdf->linha($margem, $y, $margem + $larguraAss, $y, 0.7, [0.2, 0.2, 0.2]);
    $pdf->texto($margem, $y + 12, t('assinatura_formador') . ($r['formador'] !== '' ? ': ' . $r['formador'] : ''), 8.5, false, 'e', 0, [0.3, 0.3, 0.3]);
    $x2 = $pdf->largura - $margem - $larguraAss;
    $pdf->linha($x2, $y, $x2 + $larguraAss, $y, 0.7, [0.2, 0.2, 0.2]);
    $pdf->texto($x2, $y + 12, t('assinatura_direcao'), 8.5, false, 'e', 0, [0.3, 0.3, 0.3]);

    // ---- rodapé com número de página ----
    $total = $pdf->totalPaginas();
    for ($p = 0; $p < $total; $p++) {
        $pdf->irParaPagina($p);
        $yr = $pdf->altura - 24;
        $pdf->linha($margem, $yr - 11, $pdf->largura - $margem, $yr - 11, 0.4, [0.8, 0.8, 0.8]);
        $pdf->texto($margem, $yr, t('gerado_em', dataFmt(hojeISO())) . ' · ' . t('escola') . ' · v' . VERSAO, 7.5, false, 'e', 0, $cinza);
        $pdf->texto($margem, $yr, t('pagina_de', $p + 1, $total), 7.5, false, 'd', $largUtil, $cinza);
    }
    return $pdf->saida();
}
