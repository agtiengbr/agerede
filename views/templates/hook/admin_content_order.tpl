<div id="agerede-tab" class='tab-pane'>
	<div class='panel'>
		<div class='panel-heading'>
			Análise de Risco - Rede
		</div>

		<dl class="dl-horizontal">
			<dt>Score</dt>
			<dd>{$agerede_transaction->antifraud_score}</dd>

			<dt>Risco</dt>
			<dd>{$agerede_transaction->antifraud_risk_level}</dd>

			<dt>Recomendação</dt>
			<dd>{$agerede_transaction->antifraud_recommendation}</dd>
		</dl>
	</div>

	<div class='panel'>
		<div class='panel-heading'>
			Dados da Transação
		</div>

		<dl class="dl-horizontal" id="request">
			<dt>TID</dt>
			<dd id="request-tid">{$agerede_transaction->tid}</dd>

			<dt>NSU</dt>
			<dd>{$agerede_transaction->nsu}</dd>

			<dt>Código de Autorizaçao</dt>
			<dd>{$agerede_transaction->authorization_code}</dd>

			<dt>Card BIN</dt>
			<dd>{$agerede_transaction->card_bin}</dd>

			<dt>Últ. 4 dígitos do cartão</dt>
			<dd>{$agerede_transaction->last4}</dd>

			<dt>Número de Parcelas</dt>
			<dd>{$agerede_transaction->installments}</dd>
		</dl>
	</div>
</div>