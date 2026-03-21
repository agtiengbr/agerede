<div class='panel'>
    <dl class="dl-horizontal">
        <dt>Endpoint</dt>
        <dd>{$obj->endpoint}</dd>

        <dt>Método</dt>
        <dd>{$obj->method}</dd>

    <dt>Headers</dt>
    <dd><pre>{print_r(unserialize($obj->headers), true)}</pre></dd>

    <dt>Body</dt>
    <dd><pre>{print_r(unserialize($obj->body), true)}</pre></dd>

        <dt>Código HTTP</dt>
        <dd>{$obj->http_code}</dd>

        <dt>Resposta</dt>
        {assign var=_response value=$obj->response}
        {if $_response|strlen}
            {assign var=_firstChar value=substr($_response, 0, 1)}
            {if ($_firstChar == '{' || $_firstChar == '[')}
                {assign var=_decoded value=json_decode($_response, true)}
                {assign var=_jsonError value=json_last_error()}
                {if $_jsonError == constant('JSON_ERROR_NONE')}
                    <dd><pre>{print_r($_decoded, true)}</pre></dd>
                {else}
                    <dd><pre>{$obj->response|escape:'htmlall':'UTF-8'}</pre></dd>
                {/if}
            {else}
                <dd><pre>{$obj->response|escape:'htmlall':'UTF-8'}</pre></dd>
            {/if}
        {else}
            <dd><em>Nenhum conteúdo retornado</em></dd>
        {/if}

        <dt>Data</dt>
        <dd>{Tools::displayDate($obj->date_add)}</dd>
    </dl>
</div>