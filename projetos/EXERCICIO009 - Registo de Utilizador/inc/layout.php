<?php
/** Estrutura das páginas: menu lateral por perfil, cabeçalho, mensagens e a página de entrada. */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

const ICONES = [
    'painel'      => 'M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
    'notas'       => 'M12 20h9 M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z',
    'pauta'       => 'M3 5h18v14H3z M3 10h18 M9 5v14',
    'relatorio'   => 'M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z M14 3v5h5 M9 13h6 M9 17h6',
    'boletim'     => 'M6 3h12v18l-6-4-6 4z',
    'faltas'      => 'M4 5h16v16H4z M4 10h16 M8 3v4 M16 3v4 M10 14l4 4 M14 14l-4 4',
    'avisos'      => 'M6 9a6 6 0 0 1 12 0c0 6 2 7 2 8H4c0-1 2-2 2-8z M10 21h4',
    'horario'     => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z M12 7v5l3 2',
    'alunos'      => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M2 21v-1a6 6 0 0 1 12 0v1 M17 3.5a4 4 0 0 1 0 7 M22 21v-1a6 6 0 0 0-4-5.6',
    'professores' => 'M3 8h18v12H3z M8 8V5h8v3 M3 13h18',
    'turmas'      => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
    'direcao'     => 'M4 20V10 M10 20V4 M16 20v-7 M22 20H2',
    'financeiro'  => 'M3 3v18h18 M7 15l4-4 3 3 6-7',
    'propinas'    => 'M3 6h18v12H3z M3 10h18 M7 15h4',
    'salarios'    => 'M3 7h18v13H3z M3 7l3-4h12l3 4 M12 11v5 M10 13h3',
    'lancamentos' => 'M6 3h12v18l-3-2-3 2-3-2-3 2z M9 8h6 M9 12h6',
    'conselho'    => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z M9 12l2 2 4-4',
    'equipa'      => 'M3 21v-1a5 5 0 0 1 10 0v1 M8 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z M15 21v-1a5 5 0 0 0-2.5-4.3 M17 4.5a3.5 3.5 0 0 1 0 6',
    'visitas'     => 'M4 4h16v16H4z M9 9h6 M9 13h6 M9 17h3 M4 8h2 M4 12h2',
    'excel'       => 'M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z M14 3v5h5 M9 12l5 6 M14 12l-5 6',
    'perfil'      => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z M4 21a8 8 0 0 1 16 0',
    'sair'        => 'M15 3h4a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-4 M10 17l5-5-5-5 M15 12H3',
    'menu'        => 'M3 6h18 M3 12h18 M3 18h18',
    'imprimir'    => 'M6 9V3h12v6 M6 18H4v-7h16v7h-2 M6 14h12v7H6z',
    'baixar'      => 'M12 3v12 M7 10l5 5 5-5 M4 21h16',
    'mais'        => 'M12 5v14 M5 12h14',
    'lixo'        => 'M4 7h16 M9 7V4h6v3 M6 7l1 14h10l1-14',
    'duplicar'    => 'M8 8h12v12H8z M4 16V4h12',
    'guardar'     => 'M5 3h12l4 4v14H3V3z M7 3v6h8V3 M7 21v-8h10v8',
    'alerta'      => 'M12 3l10 18H2z M12 10v5 M12 18v.5',
    'certo'       => 'M4 12l5 5L20 6',
];

function icone(string $nome): string
{
    $caminho = ICONES[$nome] ?? '';
    return '<svg class="icone" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $caminho . '"/></svg>';
}

/** Ligações PT · EN · ES · FR que mantêm a página em que se está. */
function seletorIdiomas(): string
{
    $html = '<nav class="idiomas" aria-label="' . e(t('idioma')) . '">';
    foreach (IDIOMAS as $codigo => $nome) {
        $query = http_build_query(array_merge($_GET, ['lang' => $codigo]));
        $atual = $codigo === idiomaAtivo();
        $html .= '<a href="?' . e($query) . '" lang="' . $codigo . '" title="' . e($nome) . '"' . ($atual ? ' class="ativo" aria-current="true"' : '') . '>' . strtoupper($codigo) . '</a>';
    }
    return $html . '</nav>';
}

function mensagensHtml(): string
{
    $html = '';
    foreach (avisosPendentes() as $m) {
        $classe = $m['tipo'] === 'erro' ? 'erro' : 'ok';
        $html .= '<div class="mensagem ' . $classe . '" role="' . ($classe === 'erro' ? 'alert' : 'status') . '">' . icone($classe === 'erro' ? 'alerta' : 'certo') . '<span>' . e($m['texto']) . '</span></div>';
    }
    return $html;
}

function cabecaHtml(string $titulo): string
{
    $v = rawurlencode(VERSAO);
    return '<!DOCTYPE html><html lang="' . idiomaAtivo() . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex">'
        . '<title>' . e($titulo) . ' · ' . e(t('escola')) . '</title>'
        . '<link rel="icon" href="logo.svg?v=' . $v . '" type="image/svg+xml">'
        . '<link rel="stylesheet" href="app.css?v=' . $v . '">'
        . '<script src="app.js?v=' . $v . '" defer></script></head>';
}

/** Página com menu lateral (utilizador com sessão). */
function paginaComMenu(string $conteudo, string $titulo, string $pagina): string
{
    $u = utilizador();
    $perfil = $u['perfil'];
    $menu = '';
    $nFinancas = count(array_intersect(MENUS[$perfil], MENU_FINANCAS));
    $primeiraFinanca = $nFinancas >= 2 ? (array_values(array_intersect(MENUS[$perfil], MENU_FINANCAS))[0] ?? '') : '';
    foreach (MENUS[$perfil] as $item) {
        if ($item === $primeiraFinanca) {
            $menu .= '<span class="menu-grupo">' . e(t('menu_grupo_financas')) . '</span>';
        }
        $ativo = $item === $pagina;
        $menu .= '<a class="menu-item' . ($ativo ? ' ativo' : '') . '" href="' . e(ligacao($item)) . '"' . ($ativo ? ' aria-current="page"' : '') . '>'
            . icone($item) . '<span>' . e(t('menu_' . $item)) . '</span></a>';
    }
    return cabecaHtml($titulo) . '<body class="com-menu">'
        . '<div class="app">'
        . '<input type="checkbox" id="menu-abrir" class="menu-abrir">'
        . '<header class="barra-topo"><label for="menu-abrir" class="botao-menu" aria-label="' . e(t('menu')) . '">' . icone('menu') . '</label>'
        . '<img src="logo.svg?v=' . rawurlencode(VERSAO) . '" alt="" width="28" height="28"><strong>' . e(t('escola')) . '</strong></header>'
        . '<label for="menu-abrir" class="cortina"></label>'
        . '<aside class="lateral">'
        . '<div class="marca"><img src="logo.svg?v=' . rawurlencode(VERSAO) . '" alt="" width="38" height="38"><div><strong>' . e(t('escola')) . '</strong><small>' . e(t('ano_letivo', ANO_LETIVO)) . '</small></div></div>'
        . '<nav class="menu" aria-label="' . e(t('menu')) . '">' . $menu . '</nav>'
        . '<div class="lateral-fundo">'
        . seletorIdiomas()
        . '<div class="cartao-utilizador"><span class="avatar" aria-hidden="true">' . e(iniciais($u['nome'])) . '</span>'
        . '<div><strong>' . e($u['nome']) . '</strong><small>' . e(t('perfil_' . $perfil)) . '</small></div></div>'
        . '<form method="post" action="?p=sair" class="form-sair">' . csrfCampo() . '<button type="submit" class="botao secundario pequeno bloco">' . icone('sair') . '<span>' . e(t('sair')) . '</span></button></form>'
        . '<small class="versao">' . e(t('versao')) . ' ' . e(VERSAO) . '</small>'
        . '</div></aside>'
        . '<main class="principal" id="conteudo"><div class="titulo-pagina"><h1>' . e($titulo) . '</h1></div>'
        . mensagensHtml() . $conteudo . '</main></div></body></html>';
}

/** Página sem menu (entrada e erros). */
function paginaSimples(string $conteudo, string $titulo): string
{
    return cabecaHtml($titulo) . '<body class="entrada"><div class="entrada-caixa">'
        . '<div class="entrada-idiomas">' . seletorIdiomas() . '</div>'
        . mensagensHtml() . $conteudo . '</div></body></html>';
}
