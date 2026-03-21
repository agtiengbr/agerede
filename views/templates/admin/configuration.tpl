<ul class="nav nav-tabs" role="tablist">
    <li class="active"><a data-toggle="tab" href="#tabAuth"><i class="icon-lock"></i> Autenticação</a></li>
    <li><a data-toggle="tab" href="#tabCreditCard"><i class="icon-credit-card"></i> Cartão de Crédito</a></li>
    <li><a data-toggle="tab" href="#tabDebitCard"><i class="icon-credit-card"></i> Cartão de Débito</a></li>
    <li><a data-toggle="tab" href="#tabMappings"><i class="icon-arrows-h"></i> Mapeamentos</a></li>
    <li><a data-toggle="tab" href="#tabHelp"><i class="icon-question-circle"></i> Ajuda</a></li>
</ul>

<div class='tab-content'>
    <div class='tab-pane active' id="tabAuth">{$tabs['auth']}</div>
    <div class='tab-pane' id="tabCreditCard">{$tabs['credit_card']}</div>
    <div class='tab-pane' id="tabDebitCard">{$tabs['debit_card']}</div>
    <div class='tab-pane' id="tabMappings">{$tabs['mappings']}</div>
    <div class="tab-pane" id="tabHelp"><div class="panel">{include file=$modules_path|cat:"agcliente/views/templates/hook/includes/tab_help.tpl"}</div></div>
</div>