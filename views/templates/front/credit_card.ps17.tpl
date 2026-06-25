<form action="{$form_action}" method="post" id="agerede_credit_card" class="mb-2">
    <div class="card-wrapper mb-2 mt-2"></div>

    <input type="hidden" name="payment_mode" value="credit_card" />
    <div class="box cheque-box">
        <div class="agerede-card-fields">
            <div id="cardnumber" class="agerede-field agerede-field--number">
                <label class="sr-only">Número do cartão</label>
                <input type="text" name="agerede_cardnumber" id="agerede_cardnumber" tabindex="1" autocomplete="off" placeholder="Número do Cartão" />
                <div class="agerede_cardbanner"></div>
            </div>

            <div id="cardholder" class="agerede-field agerede-field--name">
                <label class="sr-only">Nome do Proprietário</label>
                <input type="text" name="agerede_name" id="agerede_name" tabindex="1" autocomplete="off" maxlength="48" placeholder="Nome conforme exibido no cartão" />
            </div>

            <div id="month" class="agerede-field agerede-field--expiry">
                <label class="sr-only">Validade</label>
                <div class="agerede-expiry-group">
                    <select name="agerede_month" id="agerede_month" class="is-not-selected" tabindex="1" autocomplete="off">
                        <option value="-1">Mês</option>
                        <option value="01">Janeiro</option>
                        <option value="02">Fevereiro</option>
                        <option value="03">Março</option>
                        <option value="04">Abril</option>
                        <option value="05">Maio</option>
                        <option value="06">Junho</option>
                        <option value="07">Julho</option>
                        <option value="08">Agosto</option>
                        <option value="09">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                    <select name="agerede_year" id="agerede_year" class="is-not-selected" tabindex="1" autocomplete="off">
                        <option value="-1">Ano</option>
                        {for $year=0 to 10}
                            {$y = date('Y') + $year}
                            <option value="{$y}">{$y}</option>
                        {/for}
                    </select>
                </div>
            </div>

            <div class="agerede-field agerede-field--cvv">
                <label class="sr-only">Cod. Segurança</label>
                <input type="text" name="agerede_cvv" id="agerede_cvv" tabindex="1" autocomplete="off" maxlength="4" placeholder="CVV" />
            </div>

            <div id="installment" class="agerede-field agerede-field--installments">
                <label class="sr-only">Parcelamento</label>
                <select name="agerede_installment" id="agerede_installment" class="is-not-selected" tabindex="1" autocomplete="off">
                    <option value="-1">Escolha a quantidade de parcelas</option>
                    {if isset($enable_installments) && $enable_installments == 0}
                        {foreach from=$installments item=installment key=i}
                            {$qtt = $i+1}
                            {if $enable_installments == $i}
                                <option value="{$qtt}">{$qtt} x {$installment['installment_value']} ({$installment['total']})</option>
                                {break}
                            {/if}
                        {/foreach}
                    {else}
                        {foreach from=$installments item=installment key=i}
                            {$qtt = $i+1}
                            <option value="{$qtt}">{$qtt} x {$installment['installment_value']} ({$installment['total']})</option>
                        {/foreach}
                    {/if}
                </select>
            </div>
        </div>
    </div>
</form>
