<?php
/**
 * Exercício 009 · Sistema de Gestão Escolar (PHP + SQLite)
 * Todos os pedidos passam por aqui: define o idioma, confirma o perfil e abre a página (paginas/*.php).
 */

declare(strict_types=1);

define('APP', true);

require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/util.php';
require __DIR__ . '/inc/idiomas.php';
require __DIR__ . '/inc/bd.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/dados.php';
require __DIR__ . '/inc/graficos.php';
require __DIR__ . '/inc/pdf.php';
require __DIR__ . '/inc/relatorios.php';
require __DIR__ . '/inc/financas.php';
require __DIR__ . '/inc/xlsx.php';
require __DIR__ . '/inc/pdffinancas.php';
require __DIR__ . '/inc/relatoriosfin.php';
require __DIR__ . '/inc/layout.php';

idiomaAtivo(escolherIdioma());

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'self'");
header('Cache-Control: no-store');

set_exception_handler(static function (Throwable $erro): void {
    error_log('[colegio] ' . $erro->getMessage() . ' em ' . $erro->getFile() . ':' . $erro->getLine());
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo paginaSimples('<div class="cartao"><h1>' . e(t('erro_titulo')) . '</h1><p>' . e(t('erro_texto')) . '</p><p><a class="botao" href="?">' . e(t('voltar_inicio')) . '</a></p></div>', t('erro_titulo'));
});

iniciarSessao();
header('Content-Type: text/html; charset=utf-8');

$pedida = is_string($_GET['p'] ?? null) ? $_GET['p'] : '';

// Terminar sessão (só por POST, com código CSRF)
if ($pedida === 'sair') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verificarCsrf();
        terminarSessao();
    }
    redirecionar('?');
}

if (utilizador() === null) {
    $pedida = 'login';
} elseif ($pedida === '' || $pedida === 'login') {
    redirecionar(ligacao('painel'));
}

$titulo = '';
$estado = 200;
if (!isset(PAGINAS[$pedida]) && $pedida !== 'login') {
    $estado = 404;
    $pedida = 'erro404';
} elseif ($pedida !== 'login' && !temPerfil(...PAGINAS[$pedida])) {
    $estado = 403;
    $pedida = 'erro403';
}

http_response_code($estado);
ob_start();
$titulo = t($pedida === 'login' ? 'entrar' : 'menu_' . $pedida);
(static function (string $ficheiro) use (&$titulo): void {
    require $ficheiro;
})(__DIR__ . '/paginas/' . $pedida . '.php');
$conteudo = (string) ob_get_clean();

echo $pedida === 'login' || $estado !== 200 && utilizador() === null
    ? paginaSimples($conteudo, $titulo)
    : paginaComMenu($conteudo, $titulo, $pedida);
