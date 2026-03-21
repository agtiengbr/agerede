<p class="alert alert-success">Seu pagamento foi realizado com sucesso!</p>

<div class="box">
    <p>
        <br />Referência do seu pedido: <span class="reference"><strong>{$order->reference|escape:'html':'UTF-8'}</strong></span>

        <p>
            <br />O seu pedido será enviado em breve. Se você tem alguma dúvida ou comentário sobre a compra, por favor entre em contato com a nossa <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">equipe especializada de atendimento ao cliente!</a>
        </p>
    </p>
</div>
