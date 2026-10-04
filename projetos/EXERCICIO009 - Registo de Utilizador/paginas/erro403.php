<?php
if (!defined('APP')) { http_response_code(403); exit; }
$titulo = t('erro_403_titulo');
?>
<div class="cartao"><p><?= e(t('erro_403_texto')) ?></p><p><a class="botao" href="<?= e(ligacao('painel')) ?>"><?= e(t('voltar_inicio')) ?></a></p></div>
