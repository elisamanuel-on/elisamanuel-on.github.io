<?php
/**
 * Equipa não docente: cantina e refeitório, limpeza, vigilância e portaria.
 * A secretaria e a direção gerem as fichas (área, cargo, turno, contacto). Os salários e contratos são da contabilidade.
 * Estes funcionários não têm acesso à aplicação, exceto o porteiro, que pode ter uma conta «Portaria» para registar visitas.
 */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$db = bd();
$gere = temPerfil('secretaria', 'direcao');
$sugestoes = explode('|', t('cargos_sugeridos'));
$cargoPorArea = ['cantina' => $sugestoes[0] ?? '', 'limpeza' => $sugestoes[3] ?? '', 'vigilancia' => $sugestoes[5] ?? '', 'portaria' => $sugestoes[7] ?? ''];

function fichaPorId(int $id): ?array
{
    $st = bd()->prepare("SELECT u.id, u.nome, u.email, u.perfil, u.ativo, f.area, f.cargo, f.turno, f.telefone FROM utilizadores u JOIN funcionarios f ON f.utilizador_id = u.id WHERE u.id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    if (!$gere) {
        http_response_code(403);
        exit(t('erro_403_texto'));
    }
    $acao = $_POST['acao'] ?? '';
    $id = inteiro($_POST['id'] ?? '');

    if ($acao === 'criar' || $acao === 'editar') {
        $nome = limpar($_POST['nome'] ?? '', 80);
        $area = is_string($_POST['area'] ?? null) && in_array($_POST['area'], AREAS_PESSOAL, true) ? $_POST['area'] : null;
        $cargo = limpar($_POST['cargo'] ?? '', 40);
        $turno = is_string($_POST['turno'] ?? null) && isset(TURNOS[$_POST['turno']]) ? $_POST['turno'] : null;
        $tel = limpar($_POST['telefone'] ?? '', 20);
        $cargo = $cargo !== '' ? $cargo : ($cargoPorArea[$area] ?? '');
        if ($nome === '' || $area === null || $turno === null || $cargo === '' || preg_match('/^[0-9 +]*$/', $tel) !== 1) {
            aviso('erro', 'dados_invalidos');
            redirecionar(ligacao('equipa', $acao === 'editar' ? ['editar' => $id] : []));
        }
        if ($acao === 'criar') {
            $comConta = $area === 'portaria' && !empty($_POST['conta']);
            $pw = $comConta ? gerarPassword() : null;
            $perfil = $comConta ? 'portaria' : 'funcionario';
            $email = $comConta ? emailDisponivel($nome) : 'funcionario.' . bin2hex(random_bytes(4)) . '@interno.colegio.demo';
            $db->beginTransaction();
            $db->prepare('INSERT INTO utilizadores (nome, email, password_hash, perfil, criado_em) VALUES (?, ?, ?, ?, ?)')
               ->execute([$nome, $email, $pw !== null ? password_hash($pw, PASSWORD_DEFAULT) : '!', $perfil, date('Y-m-d H:i:s')]);
            $novo = (int) $db->lastInsertId();
            $db->prepare('INSERT INTO funcionarios (utilizador_id, area, cargo, turno, telefone) VALUES (?, ?, ?, ?, ?)')->execute([$novo, $area, $cargo, $turno, $tel]);
            $db->commit();
            aviso('ok', 'funcionario_criado', $nome);
            if ($pw !== null) {
                aviso('ok', 'conta_criada', $email, $pw);
            }
        } else {
            $f = fichaPorId($id);
            if (!$f) {
                aviso('erro', 'dados_invalidos');
            } elseif ($f['perfil'] === 'portaria' && $area !== 'portaria') {
                aviso('erro', 'portaria_area_fixa');
            } else {
                $db->beginTransaction();
                $db->prepare('UPDATE utilizadores SET nome = ? WHERE id = ?')->execute([$nome, $id]);
                $db->prepare('UPDATE funcionarios SET area = ?, cargo = ?, turno = ?, telefone = ? WHERE utilizador_id = ?')->execute([$area, $cargo, $turno, $tel, $id]);
                $db->commit();
                aviso('ok', 'dados_guardados');
            }
        }
    } elseif ($acao === 'estado' || $acao === 'password') {
        $f = fichaPorId($id);
        if (!$f) {
            aviso('erro', 'dados_invalidos');
        } elseif (in_array($f['email'], CONTAS_DEMO, true)) {
            aviso('erro', 'conta_demo_protegida');
        } elseif ($acao === 'estado') {
            $db->prepare('UPDATE utilizadores SET ativo = ? WHERE id = ?')->execute([$f['ativo'] ? 0 : 1, $id]);
            aviso('ok', $f['ativo'] ? 'funcionario_desativado' : 'conta_ativada');
        } elseif ($f['perfil'] === 'portaria') {
            $pw = gerarPassword();
            $db->prepare('UPDATE utilizadores SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $id]);
            aviso('ok', 'password_reposta', $f['email'], $pw);
        } else {
            aviso('erro', 'sem_conta');
        }
    }
    redirecionar(ligacao('equipa'));
}

$filtroArea = is_string($_GET['area'] ?? null) && in_array($_GET['area'], AREAS_PESSOAL, true) ? $_GET['area'] : '';
$editarId = $gere ? inteiro($_GET['editar'] ?? '') : 0;
$editar = $editarId ? fichaPorId($editarId) : null;

$sql = "SELECT u.id, u.nome, u.email, u.perfil, u.ativo, f.area, f.cargo, f.turno, f.telefone FROM utilizadores u JOIN funcionarios f ON f.utilizador_id = u.id WHERE 1 = 1";
$args = [];
if ($filtroArea !== '') { $sql .= ' AND f.area = ?'; $args[] = $filtroArea; }
$sql .= ' ORDER BY u.ativo DESC, f.area, u.nome';
$st = $db->prepare($sql);
$st->execute($args);
$lista = $st->fetchAll();

$contagem = array_fill_keys(AREAS_PESSOAL, 0);
foreach ($db->query('SELECT f.area, COUNT(*) AS n FROM funcionarios f JOIN utilizadores u ON u.id = f.utilizador_id WHERE u.ativo = 1 GROUP BY f.area')->fetchAll() as $l) {
    $contagem[$l['area']] = (int) $l['n'];
}
$agora = date('H:i');
$deTurno = array_values(array_filter($lista, static fn ($f) => $f['ativo'] && $agora >= TURNOS[$f['turno']][0] && $agora < TURNOS[$f['turno']][1]));
$f = $editar ?? ['nome' => '', 'area' => $filtroArea ?: 'cantina', 'cargo' => '', 'turno' => 'manha', 'telefone' => '', 'perfil' => 'funcionario'];
?>
<section class="kpis">
    <?php foreach (AREAS_PESSOAL as $a): ?>
        <a class="kpi" href="<?= e(ligacao('equipa', ['area' => $a])) ?>"><span class="kpi-valor"><?= (int) $contagem[$a] ?></span><span class="kpi-rotulo"><?= e(t('area_' . $a)) ?></span></a>
    <?php endforeach; ?>
</section>

<div class="duas-colunas<?= $gere ? ' largo-esq' : '' ?>">
<section class="cartao">
    <form method="get" class="filtros sem-cartao" data-auto>
        <input type="hidden" name="p" value="equipa">
        <label><span><?= e(t('area_funcao')) ?></span><select name="area"><option value=""><?= e(t('todas')) ?></option>
            <?php foreach (AREAS_PESSOAL as $a): ?><option value="<?= e($a) ?>"<?= $a === $filtroArea ? ' selected' : '' ?>><?= e(t('area_' . $a)) ?></option><?php endforeach; ?></select></label>
        <button type="submit" class="botao secundario"><?= e(t('filtrar')) ?></button>
    </form>
    <p class="suave"><?= e(t('n_resultados', count($lista))) ?></p>
    <div class="tabela-rolagem"><table class="tabela">
        <thead><tr><th><?= e(t('nome')) ?></th><th><?= e(t('area_funcao')) ?></th><th><?= e(t('cargo')) ?></th><th><?= e(t('turno')) ?></th><th><?= e(t('telefone')) ?></th><th><?= e(t('estado')) ?></th><?php if ($gere): ?><th></th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($lista as $p): ?>
            <tr class="<?= $p['ativo'] ? '' : 'inativo' ?>">
                <td><?= e($p['nome']) ?><?php if ($p['perfil'] === 'portaria'): ?><small class="motivo"><?= e(t('tem_conta_portaria')) ?></small><?php endif; ?></td>
                <td><?= e(t('area_' . $p['area'])) ?></td><td class="pequeno"><?= e($p['cargo']) ?></td>
                <td class="pequeno"><?= e(t('turno_' . $p['turno'])) ?><small class="motivo"><?= e(TURNOS[$p['turno']][0] . '–' . TURNOS[$p['turno']][1]) ?></small></td>
                <td class="pequeno"><?= e($p['telefone'] !== '' ? $p['telefone'] : '—') ?></td>
                <td><span class="etiqueta <?= $p['ativo'] ? 'pos' : '' ?>"><?= e(t($p['ativo'] ? 'ativo' : 'inativo')) ?></span></td>
                <?php if ($gere): ?><td class="acoes">
                    <a class="botao secundario pequeno" href="<?= e(ligacao('equipa', ['editar' => $p['id']])) ?>"><?= e(t('editar')) ?></a>
                    <form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <?php if ($p['perfil'] === 'portaria'): ?><button type="submit" name="acao" value="password" class="botao secundario pequeno" data-confirmar-botao="<?= e(t('confirmar_repor_password')) ?>"><?= e(t('repor_password')) ?></button><?php endif; ?>
                        <button type="submit" name="acao" value="estado" class="botao secundario pequeno"><?= e(t($p['ativo'] ? 'desativar' : 'ativar')) ?></button></form>
                </td><?php endif; ?></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <p class="suave pequeno"><?= e(t('nota_equipa')) ?></p>
</section>

<?php if ($gere): ?>
<section class="cartao">
    <h2><?= e($editar ? t('editar_funcionario') : t('novo_funcionario')) ?></h2>
    <form method="post" class="formulario">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="<?= $editar ? 'editar' : 'criar' ?>">
        <?php if ($editar): ?><input type="hidden" name="id" value="<?= (int) $editar['id'] ?>"><?php endif; ?>
        <label><span><?= e(t('nome')) ?></span><input type="text" name="nome" value="<?= e($f['nome']) ?>" maxlength="80" required></label>
        <label><span><?= e(t('area_funcao')) ?></span><select name="area">
            <?php foreach (AREAS_PESSOAL as $a): ?><option value="<?= e($a) ?>"<?= $a === $f['area'] ? ' selected' : '' ?>><?= e(t('area_' . $a)) ?></option><?php endforeach; ?></select></label>
        <label><span><?= e(t('cargo')) ?> <small>(<?= e(t('opcional')) ?>)</small></span><input type="text" name="cargo" list="cargos" value="<?= e($f['cargo']) ?>" maxlength="40">
            <datalist id="cargos"><?php foreach ($sugestoes as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></label>
        <div class="linha-campos">
            <label><span><?= e(t('turno')) ?></span><select name="turno">
                <?php foreach (TURNOS as $k => $horas): ?><option value="<?= e($k) ?>"<?= $k === $f['turno'] ? ' selected' : '' ?>><?= e(t('turno_' . $k)) ?> (<?= e($horas[0] . '–' . $horas[1]) ?>)</option><?php endforeach; ?></select></label>
            <label><span><?= e(t('telefone')) ?></span><input type="tel" name="telefone" value="<?= e($f['telefone']) ?>" maxlength="20" pattern="[0-9 +]*"></label>
        </div>
        <?php if (!$editar): ?>
            <label class="caixa-simples"><input type="checkbox" name="conta" value="1"><span><?= e(t('criar_conta_portaria')) ?></span></label>
            <p class="suave pequeno"><?= e(t('ajuda_conta_portaria')) ?></p>
        <?php endif; ?>
        <p class="barra-acoes"><button type="submit" class="botao"><?= $editar ? e(t('guardar')) : icone('mais') . '<span>' . e(t('adicionar_funcionario')) . '</span>' ?></button><?php if ($editar): ?> <a class="botao secundario" href="<?= e(ligacao('equipa')) ?>"><?= e(t('cancelar')) ?></a><?php endif; ?></p>
        <p class="suave pequeno"><?= e(t('equipa_sem_financeiro')) ?></p>
    </form>
</section>
<?php else: ?>
<section class="cartao">
    <h2><?= e(t('de_turno_agora')) ?> · <?= e($agora) ?></h2>
    <?php if (!$deTurno): ?><p class="suave"><?= e(t('ninguem_de_turno')) ?></p><?php else: ?>
    <ul class="lista-aulas">
        <?php foreach ($deTurno as $p): ?><li><span><strong><?= e($p['nome']) ?></strong><small><?= e(t('area_' . $p['area'])) ?> · <?= e($p['cargo']) ?></small></span></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>
<?php endif; ?>
</div>
