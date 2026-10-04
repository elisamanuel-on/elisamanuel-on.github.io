<?php
/**
 * Idiomas (PT, EN, ES, FR). O idioma é escolhido por esta ordem:
 *   1. ?lang=xx no endereço (quem usa o seletor);
 *   2. o cookie de uma visita anterior;
 *   3. o idioma do navegador (Accept-Language), se for um dos quatro;
 *   4. inglês para qualquer outro.
 * Os textos estão em textos.php: cada chave tem as quatro traduções, pela ordem pt, en, es, fr.
 */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

const IDIOMAS = ['pt' => 'Português', 'en' => 'English', 'es' => 'Español', 'fr' => 'Français'];
const IDIOMA_OMISSAO = 'en';

function idiomaDoNavegador(?string $cabecalho): ?string
{
    if ($cabecalho === null || $cabecalho === '') {
        return null;
    }
    $candidatos = [];
    foreach (explode(',', $cabecalho) as $posicao => $parte) {
        $parte = trim($parte);
        if ($parte === '') {
            continue;
        }
        $q = 1.0;
        if (preg_match('/;\s*q=([0-9.]+)/i', $parte, $m) === 1) {
            $q = (float) $m[1];
        }
        $codigo = strtolower(substr($parte, 0, 2));
        if (array_key_exists($codigo, IDIOMAS) && $q > 0) {
            $candidatos[] = [$q, -$posicao, $codigo];
        }
    }
    if ($candidatos === []) {
        return null;
    }
    rsort($candidatos);
    return $candidatos[0][2];
}

function escolherIdioma(): string
{
    $pedido = $_GET['lang'] ?? null;
    if (is_string($pedido) && array_key_exists($pedido, IDIOMAS)) {
        setcookie('colegio_lang', $pedido, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        return $pedido;
    }
    $guardado = $_COOKIE['colegio_lang'] ?? null;
    if (is_string($guardado) && array_key_exists($guardado, IDIOMAS)) {
        return $guardado;
    }
    return idiomaDoNavegador($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null) ?? IDIOMA_OMISSAO;
}

/** Guarda (ou devolve) o idioma da página. */
function idiomaAtivo(?string $definir = null): string
{
    static $atual = IDIOMA_OMISSAO;
    if ($definir !== null && array_key_exists($definir, IDIOMAS)) {
        $atual = $definir;
    }
    return $atual;
}

function textosTodos(): array
{
    static $t = null;
    if ($t === null) {
        $t = require __DIR__ . '/textos.php';
    }
    return $t;
}

function existeTexto(string $chave): bool
{
    return isset(textosTodos()[$chave]);
}

/** Texto traduzido; os argumentos entram nos %s (vsprintf). */
function t(string $chave, string|int|float ...$args): string
{
    $linha = textosTodos()[$chave] ?? null;
    if ($linha === null) {
        return $chave;
    }
    $indice = array_search(idiomaAtivo(), array_keys(IDIOMAS), true);
    $texto = $linha[$indice] ?? $linha[1] ?? $chave;
    return $args === [] ? $texto : vsprintf($texto, $args);
}
