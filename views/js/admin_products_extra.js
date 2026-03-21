document.addEventListener('DOMContentLoaded', function(){
    const enable_submit = $('#enable_submit');

    const message = (message, type) => {
      event.stopPropagation();
      if (type === 'notice') {
          $.growl.notice({title: '', size: 'large', message: message });
      } else {
          $.growl.error({title: '', size: 'large', message: message });
      }
    }

    enable_submit.click(function(){
      $('input[name="enable"]:checked').toArray().map(function(check) {
            const value = $(check).val();

            $.ajax({
                  url: 'index.php',
                  data: {
                      'ajax': true,
                      'controller': 'AdminModules',
                      'token': agerede_token_products_extra,
                      'configure': 'agerede',
                      'enableAgrede': 1,
                      'enableValue': value,
                      'id': $('#id_product').val()
                  },
                  method: "POST",
                  dataType: "json",
                  cache: false,
                  beforeSend: (load) => {
                      loadingOverlay().activate();
                      return;
                  },
                  success: (data) => {
                      if (data.type === 'success') {
                          message(data.message, 'notice');
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
                      message(error.responseText, 'error');
                      return;
                  }
            });
        });
    });
});
