document.addEventListener('DOMContentLoaded', function () {
    if (typeof Card === 'undefined') {
        return;
    }

    var cardTypes = [
        { type: 'amex', pattern: /^3[47]/ },
        { type: 'dinersclub', pattern: /^(36|38|30[0-5])/ },
        { type: 'discover', pattern: /^(6011|65|64[4-9]|622)/ },
        { type: 'elo', pattern: /401178|401179|431274|438935|451416|457393|457631|457632|504175|627780|636297|636369|636368|506699|5067[0-6]\d|50677[0-8]|50900\d|5090[1-9]\d|509[1-9]\d{2}|65003[1-3]|65003[5-9]|65004\d|65005[0-1]|65040[5-9]|6504[1-3]\d|65048[5-9]|65049\d|6505[0-2]\d|65053[0-8]|65054[1-9]|6505[5-8]\d|65059[0-8]|65070\d|65071[0-8]|65072[0-7]|65090[1-9]|65091\d|650920|65165[2-9]|6516[6-7]\d|65500\d|65501\d|65502[1-9]|6550[3-4]\d|65505[0-8]|65092[1-9]|65097[0-8]/ },
        { type: 'hipercard', pattern: /^(384100|384140|384160|606282|637095|637568|60(?!11))/ },
        { type: 'jcb', pattern: /^(308[8-9]|309[0-3]|3094[0]{4}|309[6-9]|310[0-2]|311[2-9]|3120|315[8-9]|333[7-9]|334[0-9]|35)/ },
        { type: 'maestro', pattern: /^(50|5[6-9]|6007|6220|6304|6703|6708|6759|676[1-3])/ },
        { type: 'mastercard', pattern: /^(5[1-5]|677189)|^(222[1-9]|2[3-6]\d{2}|27[0-1]\d|2720)/ },
        { type: 'visa', pattern: /^4/ },
    ];

    function initAgeredeCard(formSelector) {
        if (!document.querySelector(formSelector)) {
            return;
        }

        new Card({
            form: formSelector,
            container: formSelector + ' .card-wrapper',

            formSelectors: {
                numberInput: 'input[name=agerede_cardnumber]',
                expiryInput: 'select[name=agerede_month], select[name=agerede_year]',
                cvcInput: 'input[name=agerede_cvv]',
                nameInput: 'input[name=agerede_name]',
            },

            formatting: true,

            messages: {
                validDate: 'valido\nate',
                monthYear: 'mm/aaaa',
            },
            placeholders: {
                number: '•••• •••• •••• ••••',
                name: 'Nome Completo',
                expiry: '••/••••',
                cvc: 'CVV',
            },

            debug: false,
        });

        function applyBrandClass() {
            var cardNumber = $(formSelector + ' input[name=agerede_cardnumber]').val();
            var cardElement = $(formSelector + ' .card-wrapper .jp-card');

            cardTypes.forEach(function (c) {
                cardElement.removeClass('jp-card-' + c.type);
            });

            cardTypes.forEach(function (c) {
                if (c.pattern.test((cardNumber || '').replace(/\s+/g, ''))) {
                    cardElement.addClass('jp-card-' + c.type);
                }
            });
        }

        function syncExpiryDisplay() {
            $(formSelector + ' select[name=agerede_month], ' + formSelector + ' select[name=agerede_year]').trigger('keyup');
        }

        $(formSelector + ' input[name=agerede_cardnumber]').on('input change paste', function (e) {
            if (e.type === 'paste') {
                setTimeout(applyBrandClass, 0);
            } else {
                applyBrandClass();
            }
        });

        $(formSelector + ' select[name=agerede_month], ' + formSelector + ' select[name=agerede_year]').on('change', syncExpiryDisplay);
    }

    initAgeredeCard('#agerede_credit_card');
    initAgeredeCard('#agerede_debit_card');
});
