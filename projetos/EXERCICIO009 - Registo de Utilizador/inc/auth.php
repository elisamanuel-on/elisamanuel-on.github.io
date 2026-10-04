<?php
/** Sessões, login, permissões por perfil e proteção CSRF. */

declare(strict_types=1);

if (!defined('APP')) {
    http_response_code(403);
    exit;
}

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('colegio_sessao');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
    ]);
    session_start();
}

/** Utilizador com sessão iniciada (ou null). Volta a ler da base de dados para apanhar mudanças e desativações. */
function utilizador(): ?array
{
    static $atual = false;
    if ($atual !== false) {
        return $atual;
    }
    $id = $_SESSION['utilizador_id'] ?? null;
    if (!is_int($id)) {
        return $atual = null;
    }
    $st = bd()->prepare('SELECT id, nome, email, perfil FROM utilizadores WHERE id = ? AND ativo = 1');
    $st->execute([$id]);
    $linha = $st->fetch();
    if (!$linha) {
        unset($_SESSION['utilizador_id']);
        return $atual = null;
    }
    return $atual = $linha;
}

function perfilAtual(): string
{
    return utilizador()['perfil'] ?? '';
}

function temPerfil(string ...$perfis): bool
{
    return in_array(perfilAtual(), $perfis, true);
}

function abrirSessaoDe(int $id): void
{
    session_regenerate_id(true); // evita fixação de sessão
    $_SESSION['utilizador_id'] = $id;
    unset($_SESSION['tentativas'], $_SESSION['bloqueio_ate']);
}

function terminarSessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

/** Tenta entrar com email e palavra-passe. Devolve a chave do erro ou null se correu bem. */
function entrar(string $email, string $password): ?string
{
    if (($_SESSION['bloqueio_ate'] ?? 0) > time()) {
        return 'login_bloqueado';
    }
    $st = bd()->prepare('SELECT id, password_hash FROM utilizadores WHERE email = ? AND ativo = 1');
    $st->execute([mb_strtolower(trim($email))]);
    $linha = $st->fetch();

    // password_verify também corre quando o email não existe, para a resposta demorar o mesmo
    $hash = $linha['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
    if ($linha && password_verify($password, $hash)) {
        abrirSessaoDe((int) $linha['id']);
        return null;
    }
    password_verify($password, $hash);

    $_SESSION['tentativas'] = (int) ($_SESSION['tentativas'] ?? 0) + 1;
    if ($_SESSION['tentativas'] >= 5) {
        $_SESSION['bloqueio_ate'] = time() + 30;
        $_SESSION['tentativas'] = 0;
        return 'login_bloqueado';
    }
    return 'login_invalido';
}

/** Entrada rápida nas contas de demonstração. */
function entrarDemo(string $perfil): bool
{
    if (!isset(CONTAS_DEMO[$perfil])) {
        return false;
    }
    $st = bd()->prepare('SELECT id FROM utilizadores WHERE email = ? AND ativo = 1');
    $st->execute([CONTAS_DEMO[$perfil]]);
    $id = $st->fetchColumn();
    if ($id === false) {
        return false;
    }
    abrirSessaoDe((int) $id);
    return true;
}

// ---------- CSRF ----------
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCampo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

/** Recusa qualquer formulário (POST) sem o código CSRF certo. */
function verificarCsrf(): void
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrfToken(), $enviado)) {
        http_response_code(400);
        exit(t('erro_csrf'));
    }
}
