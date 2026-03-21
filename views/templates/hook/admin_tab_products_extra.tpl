{* <div class="col-sm-12">
    <div class="row justify-content-center"> *}
<div class="col-xl-10">
    <div class="card">
        <h3 class="card-header">
            <i class="material-icons">link</i> Opções de parcelamento
        </h3>
        <div class="card-block row">
            <div class="card-text">
                <form>
                    <input type="hidden" name="id_product" id="id_product" value="{$id_product}">
                    <div class="form-group row">
                        <label class="form-control-label">
                            Habilitar parcelamento
                            <span class="help-box" data-toggle="popover"
                                data-content="Deseja realmente habilitar a opção de parcelamento para esse produto ?"
                                data-original-title="" title="">
                            </span>
                        </label>
                        <p class="sr-only">Deseja realmente habilitar a opção de parcelamento para esse produto
                            ?</p>
                        <div class="col-sm">
                            <div class="input-group">
                                <span class="ps-switch">
                                    {if $enable == 1}
                                        <input type="radio" id="enable_0" class="ps-switch" name="enable" value="0">
                                        <label for="enable_0">Não</label>
                                        <input type="radio" id="enable_1" class="ps-switch" name="enable" value="1" checked>
                                        <label for="enable_1">Sim</label><span class="slide-button"></span>
                                    {else}
                                        <input type="radio" id="enable_0" class="ps-switch" name="enable" value="0" checked>
                                        <label for="enable_0">Não</label>
                                        <input type="radio" id="enable_1" class="ps-switch" name="enable" value="1">
                                        <label for="enable_1">Sim</label><span class="slide-button"></span>
                                    {/if}
                                </span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-end">
                <button type="button" id="enable_submit" class="btn btn-primary">Salvar</button>
            </div>
        </div>
    </div>
</div>
{* </div>
</div> *}