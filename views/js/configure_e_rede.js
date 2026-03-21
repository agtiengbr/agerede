$(document).ready(() => {
    $('#tabCreditCard .panel .form-wrapper').prepend(`
        <div class="alert alert-info">
            <ul class="list-unstyled">
                <li>
                    <div>
                        <strong>3DS não pode ser usado junto com o antifraude.</strong>
                    </div>
                </li>
            </ul>
        </div>
    `);

    $('#tabCreditCard .form-group').each(function(e){
        if(e == 4){
            $(`<div class="form-group">
                    <label class="control-label col-lg-3"> Ativar Antifraude </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                        ${
                            antifraud == 1
                            ?`<input type="radio" name="antifraud_enabled" id="antifraud_enabled_on_two" value="1" checked="checked">
                              <label for="antifraud_enabled_on_two">Sim</label>
                              <input type="radio" name="antifraud_enabled" id="antifraud_enabled_off_two" value="0">
                              <label for="antifraud_enabled_off_two">Não</label>`
                            :`<input type="radio" name="antifraud_enabled" id="antifraud_enabled_on_two" value="1">
                              <label for="antifraud_enabled_on_two">Sim</label>
                              <input type="radio" name="antifraud_enabled" id="antifraud_enabled_off_two" value="0" checked="checked">
                              <label for="antifraud_enabled_off_two">Não</label>` 
                        }
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
               </div>`).insertAfter(this);
            return false;
        }
    });

    $('#tabMappings .form-group').each(function(e){
        if(e == 0){
            $(`​<div class="form-group">
                    <label class="control-label col-lg-3"> Estado para pedido reembolsado </label>
                    <div class="col-lg-4">
                        <select name="status_mapping" class="fixed-width-xl" id="status_mapping">
                            ${
                                refunded == 0 || refunded == false
                                ?`<option value='0' selected>Sem Mapeamento</option>`
                                :`<option value='0'>Sem Mapeamento</option>`
                            }
                        </select>
                    </div>
                </div>`).insertAfter(this);
            return false;
        }
    });

    const credit_three_ds_on = $('#agerede_credit_card_3ds_on');
    const credit_three_ds_off= $('#agerede_credit_card_3ds_off');
    const antifraud_on       = $('#antifraud_enabled_on_two');
    const antifraud_off      = $('#antifraud_enabled_off_two');
    const tam                = getStates.length;

    $('#status_mapping option').each(function(e){
        if(e == 0){
            for (let i = 0; i < tam; i++) {
                $(`
                    ${
                        refunded == getStates[i].id_order_state
                        ?`<option value='${getStates[i].id_order_state}' selected>${getStates[i].name}</option>`
                        :`<option value='${getStates[i].id_order_state}'>${getStates[i].name}</option>`
                    }
                `).insertAfter(this);
            }
            return false;
        }
    });

    credit_three_ds_on.click(() => {
        credit_three_ds_on.attr('checked', 'checked');
        credit_three_ds_off.removeAttr('checked');

        if (antifraud_on.is(':checked')) {
            antifraud_on.removeAttr('checked');
            antifraud_off.attr('checked', 'checked');
        }
    });

    credit_three_ds_off.click(() => {
        credit_three_ds_off.attr('checked', 'checked');
        credit_three_ds_on.removeAttr('checked');
    });

    antifraud_on.click(() => {
        antifraud_on.attr('checked', 'checked');
        antifraud_off.removeAttr('checked');
        
        if (credit_three_ds_on.is(':checked')) {
            credit_three_ds_on.removeAttr('checked');
            credit_three_ds_off.attr('checked', 'checked');
        }
    });

    antifraud_off.click(() => {
        antifraud_off.attr('checked', 'checked');
        antifraud_on.removeAttr('checked');
    });
});