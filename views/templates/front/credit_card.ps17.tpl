<form action="{$form_action}" method="post" id="agerede_credit_card">
    <input type="hidden" name="payment_mode" value="credit_card" />
    <div class="box cheque-box">
        <div class="row">
            <div id="cardnumber" class="col-xs-12 col-md-7 col-lg-7">
                <label class="col-xs-12 sr-only">Número do cartão</label>
                <input type="text" name="agerede_cardnumber" class="col-xs-12" tabindex="1" autocomplete="off" placeholder="Número do Cartão" />
                <div class="agerede_cardbanner"></div>
            </div>
            <div class="col-xs-12 col-md-4 col-lg-3">
                <label class="col-xs-12  sr-only">Cod. Segurança</label>
                <input type="text" name="agerede_cvv" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="4" placeholder="CVV" />
            </div>
        </div>
        <div class="row">
            <div id="cardholder" class="col-lg-10">
                <label class="col-xs-12  sr-only">Nome do Proprietário</label>
                <input type="text" name="agerede_name" class="col-xs-12" tabindex="1" autocomplete="off" maxlength="48" placeholder="Nome conforme exibido no cartão" />
            </div>
        </div>
        <div class="row">
            <div id="month" class="col-xs-12 col-md-7 col-lg-7">
                <label class="col-xs-12  sr-only">Parcelamento</label>
                <select name="agerede_installment" class="col-xs-12  is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
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
            <div class="col-xs-12 col-md-5 col-lg-5">
                <label class="col-xs-12  sr-only">Validade:</label>
                <span>
                    <select name="agerede_month" class="col-xs-7  is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
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
                </span>
                <span>
                    <select name="agerede_year" class="col-xs-5 is-not-selected" tabindex="1" autocomplete="off" maxlength="24">
                        <option value="-1">Ano</option> {for $year=0 to 10} {$y = date('Y') + $year} <option value="{$y}">{$y}</option> {/for}
                    </select>
                </span>
            </div>
        </div>
    </div>
</form>