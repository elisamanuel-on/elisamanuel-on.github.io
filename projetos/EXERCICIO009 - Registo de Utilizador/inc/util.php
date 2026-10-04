<?php
/** Funções de apoio: escape, redirecionamentos, mensagens, formatação. */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

/** Escapa texto para HTML (sempre que se escreve algo vindo de utilizadores ou da base de dados). */
function e(string|int|float|null $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Endereço de uma página, mantendo o idioma escolhido no cookie. */
function ligacao(string $pagina, array $extra = []): string
{
    return '?' . http_build_query(['p' => $pagina] + $extra);
}

function redirecionar(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Guarda uma mensagem para mostrar na página seguinte (Post/Redirect/Get). */
function aviso(string $tipo, string $chave, string|int|float ...$args): void
{
    $_SESSION['avisos'][] = ['tipo' => $tipo, 'texto' => t($chave, ...$args)];
}

function avisosPendentes(): array
{
    $lista = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);
    return $lista;
}

function hojeISO(): string
{
    return date('Y-m-d');
}

/** Número com vírgula ou ponto conforme o idioma. */
function numFmt(?float $n, int $casas = 1): string
{
    if ($n === null) {
        return '—';
    }
    $sep = idiomaAtivo() === 'en' ? ['.', ','] : [',', ' '];
    return number_format($n, $casas, $sep[0], $sep[1]);
}

/** Data (AAAA-MM-DD) como o idioma pede. */
function dataFmt(?string $data): string
{
    if ($data === null || $data === '') {
        return '—';
    }
    $t = strtotime($data);
    if ($t === false) {
        return $data;
    }
    return idiomaAtivo() === 'en' ? date('m/d/Y', $t) : date('d/m/Y', $t);
}

/** Texto numa só linha, cortado ao tamanho pedido. */
function limpar(mixed $valor, int $max = 200): string
{
    $texto = is_string($valor) ? trim(preg_replace('/\s+/u', ' ', $valor) ?? '') : '';
    return mb_substr($texto, 0, $max);
}

/** Texto com várias linhas (avisos). */
function limparTexto(mixed $valor, int $max = 1000): string
{
    $texto = is_string($valor) ? trim(str_replace("\r\n", "\n", $valor)) : '';
    return mb_substr($texto, 0, $max);
}

/** Número inteiro positivo vindo do endereço ou de um formulário (0 se não for válido). */
function inteiro(mixed $valor): int
{
    return is_string($valor) && ctype_digit($valor) ? (int) $valor : (is_int($valor) ? $valor : 0);
}

/** Lê uma nota (0 a 20, aceita vírgula). Devolve null se estiver vazia, false se for inválida. */
function lerNota(mixed $valor): float|false|null
{
    if (!is_string($valor)) {
        return false;
    }
    $texto = trim(str_replace(',', '.', $valor));
    if ($texto === '') {
        return null;
    }
    if (!is_numeric($texto)) {
        return false;
    }
    $n = round((float) $texto, 1);
    return ($n >= 0 && $n <= 20) ? $n : false;
}

/** Iniciais de um nome (para o círculo do utilizador). */
function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome)) ?: [];
    $primeira = mb_substr($partes[0] ?? '?', 0, 1);
    $ultima = count($partes) > 1 ? mb_substr($partes[count($partes) - 1], 0, 1) : '';
    return mb_strtoupper($primeira . $ultima);
}

/** Palavra-passe inicial legível (sem 0/O, 1/l/I). */
function gerarPassword(int $tamanho = 8): string
{
    $letras = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $texto = '';
    for ($i = 0; $i < $tamanho; $i++) {
        $texto .= $letras[random_int(0, strlen($letras) - 1)];
    }
    return $texto;
}

/** Email único com base no nome (ana.duarte@colegio.demo, ana.duarte2@…). */
function emailDisponivel(string $nome): string
{
    $partes = preg_split('/\s+/u', trim($nome)) ?: [];
    $base = preg_replace('/[^a-z0-9.]/', '', semAcentos(($partes[0] ?? 'utilizador') . '.' . (count($partes) > 1 ? $partes[count($partes) - 1] : ''))) ?: 'utilizador';
    $base = rtrim($base, '.');
    $st = bd()->prepare('SELECT 1 FROM utilizadores WHERE email = ?');
    for ($n = 1; $n < 100; $n++) {
        $email = $base . ($n > 1 ? $n : '') . '@colegio.demo';
        $st->execute([$email]);
        if (!$st->fetchColumn()) {
            return $email;
        }
    }
    return $base . random_int(100, 999) . '@colegio.demo';
}
