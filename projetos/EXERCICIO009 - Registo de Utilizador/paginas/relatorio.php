<?php
/**
 * Relatório: o professor ou formador preenche uma pauta com os seus dados (alunos, colunas, notas),
 * guarda-a e descarrega em PDF. Cada utilizador só vê os seus relatórios.
 */
if (!defined('APP')) { http_response_code(403); exit; }

$u = utilizador();
$combos = lecionacoesPermitidas();
$rel = null;          // relatório a mostrar no editor
$baixarAgora = false; // true depois de "Guardar e baixar PDF"

// ---------- Descarregar o PDF ----------
if (isset($_GET['pdf'])) {
    $r = relatorioPorId(inteiro($_GET['pdf']));
    if (!$r) {
        aviso('erro', 'relatorio_nao_encontrado');
        redirecionar(ligacao('relatorio'));
    }
    $nome = preg_replace('/[^a-z0-9]+/', '_', semAcentos($r['titulo'] . ' ' . $r['disciplina'] . ' ' . $r['data'])) ?: 'relatorio';
    $conteudo = pdfRelatorio($r);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . trim($nome, '_') . '.pdf"');
    header('Content-Length: ' . strlen($conteudo));
    header('Cache-Control: private, max-age=0');
    echo $conteudo;
    exit;
}

// ---------- Ações (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'guardar') {
        [$lido, $erro] = relatorioLerFormulario($_POST);
        if ($lido['id'] > 0 && !relatorioPorId((int) $lido['id'])) {
            $lido['id'] = 0; // não é dele: cria um novo em vez de mexer no de outra pessoa
        }
        if ($erro !== null) {
            aviso('erro', $erro, (int) ($lido['_invalidas'] ?? 0));
            $rel = $lido; // mostra o formulário como estava, sem perder o que foi escrito
        } else {
            $id = relatorioGuardar($lido);
            aviso('ok', 'relatorio_guardado');
            redirecionar(ligacao('relatorio', array_filter(['id' => $id, 'baixar' => isset($_POST['baixar']) ? '1' : null])));
        }
    } elseif ($acao === 'apagar') {
        $st = bd()->prepare('DELETE FROM relatorios WHERE id = ? AND professor_id = ?');
        $st->execute([inteiro($_POST['id'] ?? ''), (int) $u['id']]);
        aviso('ok', 'relatorio_apagado');
        redirecionar(ligacao('relatorio'));
    } elseif ($acao === 'duplicar') {
        $orig = relatorioPorId(inteiro($_POST['id'] ?? ''));
        if ($orig) {
            $orig['id'] = 0;
            $orig['titulo'] = mb_substr(t('copia_de', $orig['titulo']), 0, 80);
            $novo = relatorioGuardar($orig);
            aviso('ok', 'relatorio_duplicado');
            redirecionar(ligacao('relatorio', ['id' => $novo]));
        }
        redirecionar(ligacao('relatorio'));
    } elseif ($acao === 'importar') {
        $origem = is_string($_POST['t'] ?? null) ? $_POST['t'] : '';
        $periodo = max(1, min(3, inteiro($_POST['periodo'] ?? '1') ?: 1));
        if (preg_match('/^(\d+)-(\d+)$/', $origem, $m) === 1 && podeAbrirLecionacao((int) $m[1], (int) $m[2])) {
            $novo = relatorioGuardar(relatorioDaTurma((int) $m[1], (int) $m[2], $periodo));
            aviso('ok', 'relatorio_importado');
            redirecionar(ligacao('relatorio', ['id' => $novo]));
        }
        aviso('erro', 'sem_permissao_turma');
        redirecionar(ligacao('relatorio'));
    }
}

// ---------- Editor ou lista ----------
$idPedido = inteiro($_GET['id'] ?? '');
if ($rel === null && $idPedido) {
    $rel = relatorioPorId($idPedido);
    if (!$rel) {
        aviso('erro', 'relatorio_nao_encontrado');
        redirecionar(ligacao('relatorio'));
    }
    $baixarAgora = ($_GET['baixar'] ?? '') === '1';
} elseif ($rel === null && isset($_GET['novo'])) {
    $rel = relatorioEmBranco();
}

if ($rel === null) {
    // ----- lista dos meus relatórios -----
    $st = bd()->prepare('SELECT id, titulo, disciplina, curso, data, atualizado_em, linhas FROM relatorios WHERE professor_id = ? ORDER BY atualizado_em DESC, id DESC');
    $st->execute([(int) $u['id']]);
    $lista = $st->fetchAll();
    ?>
    <p class="suave"><?= e(t('relatorio_intro')) ?></p>
    <div class="duas-colunas largo-esq">
    <section class="cartao">
        <h2><?= e(t('os_meus_relatorios')) ?></h2>
        <?php if (!$lista): ?>
            <p class="suave"><?= e(t('sem_relatorios')) ?></p>
        <?php else: ?>
        <div class="tabela-rolagem"><table class="tabela">
            <thead><tr><th><?= e(t('titulo')) ?></th><th><?= e(t('disciplina')) ?></th><th class="num"><?= e(t('alunos')) ?></th><th><?= e(t('atualizado')) ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($lista as $r): ?>
                <tr>
                    <td><a href="<?= e(ligacao('relatorio', ['id' => $r['id']])) ?>"><strong><?= e($r['titulo']) ?></strong></a><small class="motivo"><?= e($r['curso']) ?></small></td>
                    <td><?= e($r['disciplina'] ?: '—') ?></td>
                    <td class="num"><?= count(json_decode((string) $r['linhas'], true) ?: []) ?></td>
                    <td class="pequeno"><?= e(dataFmt(substr($r['atualizado_em'], 0, 10))) ?></td>
                    <td class="acoes">
                        <a class="botao secundario pequeno" href="<?= e(ligacao('relatorio', ['id' => $r['id']])) ?>"><?= e(t('abrir')) ?></a>
                        <a class="botao pequeno" href="<?= e(ligacao('relatorio', ['pdf' => $r['id']])) ?>"><?= icone('baixar') ?><span>PDF</span></a>
                        <form method="post" class="form-linha"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" name="acao" value="duplicar" class="icone-botao" title="<?= e(t('duplicar')) ?>" aria-label="<?= e(t('duplicar')) ?>"><?= icone('duplicar') ?></button></form>
                        <form method="post" class="form-linha" data-confirmar="<?= e(t('confirmar_apagar_relatorio')) ?>"><?= csrfCampo() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" name="acao" value="apagar" class="icone-botao perigo" title="<?= e(t('apagar')) ?>" aria-label="<?= e(t('apagar')) ?>"><?= icone('lixo') ?></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </section>
    <div>
        <section class="cartao">
            <h2><?= e(t('novo_relatorio')) ?></h2>
            <p class="suave"><?= e(t('novo_em_branco_texto')) ?></p>
            <p><a class="botao" href="<?= e(ligacao('relatorio', ['novo' => 1])) ?>"><?= icone('mais') ?><span><?= e(t('novo_em_branco')) ?></span></a></p>
        </section>
        <?php if ($combos): ?>
        <section class="cartao">
            <h2><?= e(t('importar_turma')) ?></h2>
            <p class="suave"><?= e(t('importar_texto')) ?></p>
            <form method="post" class="formulario">
                <?= csrfCampo() ?><input type="hidden" name="acao" value="importar">
                <label><span><?= e(t('turma_disciplina')) ?></span><select name="t">
                    <?php foreach ($combos as $l): ?><option value="<?= (int) $l['turma_id'] . '-' . (int) $l['disciplina_id'] ?>"><?= e($l['turma'] . ' · ' . nomeDisciplina($l)) ?></option><?php endforeach; ?>
                </select></label>
                <label><span><?= e(t('periodo')) ?></span><select name="periodo"><?php for ($p = 1; $p <= 3; $p++): ?><option value="<?= $p ?>"><?= e(t('periodo_n', $p)) ?></option><?php endfor; ?></select></label>
                <button type="submit" class="botao secundario"><?= e(t('importar')) ?></button>
            </form>
        </section>
        <?php endif; ?>
    </div>
    </div>
    <?php
    return;
}

// ----- editor -----
$titulo = $rel['id'] ? $rel['titulo'] : t('novo_relatorio');
$nCol = count($rel['colunas']);
$fmt = static fn (?float $v): string => $v === null ? '' : numFmt($v, 1);
?>
<?php if ($baixarAgora): ?>
    <iframe class="oculto" src="<?= e(ligacao('relatorio', ['pdf' => $rel['id']])) ?>" title="PDF"></iframe>
    <div class="mensagem ok"><?= icone('baixar') ?><span><?= e(t('pdf_a_descarregar')) ?> <a href="<?= e(ligacao('relatorio', ['pdf' => $rel['id']])) ?>"><?= e(t('pdf_clique_aqui')) ?></a></span></div>
<?php endif; ?>

<form method="post" id="form-relatorio" class="editor-relatorio" data-relatorio
      data-max-linhas="<?= MAX_RELATORIO_LINHAS ?>" data-max-colunas="<?= MAX_RELATORIO_COLUNAS ?>" data-positiva="<?= NOTA_POSITIVA ?>"
      data-t-positiva="<?= e(t('positiva')) ?>" data-t-negativa="<?= e(t('negativa')) ?>" data-t-remover-coluna="<?= e(t('remover_coluna')) ?>"
      data-t-remover-aluno="<?= e(t('remover_aluno')) ?>" data-t-titulo-coluna="<?= e(t('titulo_coluna')) ?>" data-t-peso="<?= e(t('peso')) ?>"
      data-t-nome="<?= e(t('nome_aluno')) ?>" data-t-nota="<?= e(t('nota')) ?>" data-t-avaliacao="<?= e(t('avaliacao')) ?>"
      data-t-confirmar-limpar="<?= e(t('confirmar_limpar_notas')) ?>" data-t-limite="<?= e(t('limite_atingido')) ?>" data-decimal="<?= idiomaAtivo() === 'en' ? '.' : ',' ?>">
    <?= csrfCampo() ?>
    <span class="oculto" id="molde-lixo"><?= icone('lixo') ?></span>
    <input type="hidden" name="acao" value="guardar">
    <input type="hidden" name="id" value="<?= (int) $rel['id'] ?>">

    <section class="cartao">
        <h2><?= e(t('dados_relatorio')) ?></h2>
        <div class="grelha-campos">
            <label class="largo"><span><?= e(t('titulo_relatorio')) ?></span><input type="text" name="titulo" value="<?= e($rel['titulo']) ?>" maxlength="80" required></label>
            <label><span><?= e(t('instituicao')) ?></span><input type="text" name="instituicao" value="<?= e($rel['instituicao']) ?>" maxlength="80"></label>
            <label><span><?= e(t('curso_turma')) ?></span><input type="text" name="curso" value="<?= e($rel['curso']) ?>" maxlength="80"></label>
            <label><span><?= e(t('disciplina_modulo')) ?></span><input type="text" name="disciplina" value="<?= e($rel['disciplina']) ?>" maxlength="80"></label>
            <label><span><?= e(t('formador')) ?></span><input type="text" name="formador" value="<?= e($rel['formador']) ?>" maxlength="80"></label>
            <label><span><?= e(t('periodo')) ?></span><input type="text" name="periodo_texto" value="<?= e($rel['periodo_texto']) ?>" maxlength="40" placeholder="<?= e(t('periodo_n', 1)) ?>"></label>
            <label><span><?= e(t('data')) ?></span><input type="date" name="data" value="<?= e($rel['data']) ?>"></label>
        </div>
    </section>

    <section class="cartao">
        <h2><?= e(t('tabela_notas')) ?></h2>
        <p class="suave"><?= e(t('ajuda_editor')) ?></p>
        <div class="tabela-rolagem">
        <table class="tabela editor-pauta" id="tabela-relatorio">
            <thead><tr id="cabeca-relatorio">
                <th class="col-num"><?= e(t('numero_abrev')) ?></th>
                <th class="col-nome"><?= e(t('aluno')) ?></th>
                <?php foreach ($rel['colunas'] as $c): ?>
                    <th class="col-nota" data-coluna>
                        <input type="text" name="col_titulo[]" value="<?= e($c['titulo']) ?>" maxlength="30" aria-label="<?= e(t('titulo_coluna')) ?>">
                        <span class="peso-linha"><input type="number" name="col_peso[]" value="<?= (int) $c['peso'] ?>" min="1" max="100" aria-label="<?= e(t('peso')) ?> %">%
                        <button type="button" class="icone-botao perigo pequeno" data-acao="remover-coluna" title="<?= e(t('remover_coluna')) ?>" aria-label="<?= e(t('remover_coluna')) ?>"><?= icone('lixo') ?></button></span>
                    </th>
                <?php endforeach; ?>
                <th class="col-calc"><?= e(t('media')) ?></th><th class="col-calc"><?= e(t('nota_final')) ?></th><th class="col-calc"><?= e(t('resultado')) ?></th><th class="col-acao"></th>
            </tr></thead>
            <tbody id="corpo-relatorio">
            <?php foreach ($rel['linhas'] as $i => $l): $calc = relatorioCalcularLinha($rel['colunas'], $l); ?>
                <tr data-linha>
                    <td class="col-num"><input type="text" name="aluno_numero[]" value="<?= e($l['numero']) ?>" maxlength="6" aria-label="<?= e(t('numero_abrev')) ?>"></td>
                    <td class="col-nome"><input type="text" name="aluno_nome[]" value="<?= e($l['nome']) ?>" maxlength="80" aria-label="<?= e(t('nome_aluno')) ?>"></td>
                    <?php foreach ($rel['colunas'] as $j => $c): ?>
                        <td class="col-nota"><input type="text" inputmode="decimal" class="campo-nota" name="nota[<?= $i ?>][<?= $j ?>]" value="<?= e($fmt($l['notas'][$j] ?? null)) ?>" maxlength="4" aria-label="<?= e(t('nota')) ?>"></td>
                    <?php endforeach; ?>
                    <td class="col-calc js-media"><?= e(numFmt($calc['media'], 1)) ?></td>
                    <td class="col-calc js-final"><?= $calc['final'] === null ? '—' : e((string) $calc['final']) ?></td>
                    <td class="col-calc js-res"><?php if ($calc['final'] !== null): ?><span class="etiqueta <?= $calc['final'] >= NOTA_POSITIVA ? 'pos' : 'neg' ?>"><?= e(t($calc['final'] >= NOTA_POSITIVA ? 'positiva' : 'negativa')) ?></span><?php endif; ?></td>
                    <td class="col-acao"><button type="button" class="icone-botao perigo" data-acao="remover-linha" title="<?= e(t('remover_aluno')) ?>" aria-label="<?= e(t('remover_aluno')) ?>"><?= icone('lixo') ?></button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <p class="barra-acoes">
            <button type="button" class="botao secundario" data-acao="add-linha"><?= icone('mais') ?><span><?= e(t('adicionar_aluno')) ?></span></button>
            <button type="button" class="botao secundario" data-acao="add-coluna"><?= icone('mais') ?><span><?= e(t('adicionar_coluna')) ?></span></button>
            <button type="button" class="botao secundario" data-acao="limpar-notas"><?= e(t('limpar_notas')) ?></button>
        </p>
        <p class="resumo-pauta" id="resumo-relatorio">
            <span><?= e(t('positivas')) ?>: <strong data-resumo="pos">0</strong></span>
            <span><?= e(t('negativas')) ?>: <strong data-resumo="neg">0</strong></span>
            <span><?= e(t('media_turma')) ?>: <strong data-resumo="media">—</strong></span>
            <span><?= e(t('soma_pesos')) ?>: <strong data-resumo="pesos">0%</strong></span>
        </p>
    </section>

    <section class="cartao">
        <label class="formulario"><span><?= e(t('observacoes')) ?></span><textarea name="observacoes" rows="3" maxlength="600"><?= e($rel['observacoes']) ?></textarea></label>
    </section>

    <p class="barra-acoes barra-fixa">
        <button type="submit" class="botao"><?= icone('guardar') ?><span><?= e(t('guardar')) ?></span></button>
        <button type="submit" name="baixar" value="1" class="botao verde"><?= icone('baixar') ?><span><?= e(t('guardar_e_baixar')) ?></span></button>
        <?php if ($rel['id']): ?><a class="botao secundario" href="<?= e(ligacao('relatorio', ['pdf' => $rel['id']])) ?>"><?= icone('baixar') ?><span><?= e(t('baixar_ultimo_pdf')) ?></span></a><?php endif; ?>
        <a class="botao secundario" href="<?= e(ligacao('relatorio')) ?>"><?= e(t('voltar_lista')) ?></a>
    </p>
</form>
