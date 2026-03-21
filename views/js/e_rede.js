$(document).ready(() => {

    const container = $('#request');
    const tid       = $('#request-tid').text();
    const paid      = $("#id_order_state option:selected").val();
    const tam       = getStates.length;

    const message   = (message, type) => {
        event.stopPropagation();
        if (type === 'notice') {
            $.growl.notice(
                {
                    title: '',
                    size: "large",
                    message: message
                });
        }
        else {
            $.growl.error(
                {
                    title: '',
                    size: "large",
                    message: message
                });
        }
    }

    ajaxProcess = (href) => {

        if (window.confirm("Deseja realmente cancelar o pedido? Essa operação é irreversível.")){

            $.ajax(
                {
                    url: 'index.php',
                    data: {
                        'ajax': true,
                        'controller': 'AdminModules',
                        'token': token_url,
                        'configure': 'agerede',
                        'cancelTransaction': 1,
                        'tid': tid,
                        'id_rede_order': id_rede_order
                    },
                    method: "POST",
                    dataType: "json",
                    cache: false,
                    beforeSend: (load) => {
                        loadingOverlay().activate();
                        return;
                    },
                    success: (data) => {

                        if(data.type === 'success'){
                            localStorage.setItem('agerede_message', data.message);
                            window.location.reload();
                        }
                        if (data.type === 'error') {
                            message(data.message, 'error');
                        }
                        return;
                    },
                    complete: () => {
                        $('.lo-wrap').remove();
                    },
                    error: (error) => {
                        message(error, 'error');
                        return;
                    }
                });
        }
    }

    if(localStorage.getItem('agerede_message') != null){
        message(localStorage.getItem('agerede_message'), 'notice');
        localStorage.removeItem('agerede_message');
    }

    for (let i = 0; i < tam; i++) {
        if(getStates[i].id_order_state == paid && getStates[i].paid == 1){
            container.append(`<center style="margin-top: 20px"><button class="btn btn-primary" onclick="ajaxProcess(${tid})" id="btn-ajax">CANCELAR TRANSAÇÃO</button></center>`);
            false;
        }
    }
});