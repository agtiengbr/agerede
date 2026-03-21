$(function(){
	function validateData(mark_input = true)
	{
		var error_card_number  = !validateCardNumber(mark_input);
		var error_cvv          = !validateCvv(mark_input);
		var error_name 		   = !validateName(mark_input);
		var error_installments = !validateInstallments(mark_input);
		var error_month 	   = !validateMonth(mark_input);
		var error_year 		   = !validateYear(mark_input);

		var has_error = error_card_number || error_cvv || error_name || error_installments || error_month || error_year;
        return !has_error;
	}

	function validateCardNumber(mark_input = true)
	{
		var input_bandeira = $('[name=agerede_cardnumber]');
		var bandeira = getBandeira(input_bandeira.val());

		var error = !bandeira;
		if (mark_input) {
			if (error) {
				$(input_bandeira).parent().addClass('has-error');
			} else {
				$(input_bandeira).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateCvv(mark_input = true)
	{
		var input_cvv = $('#agerede_credit_card [name=agerede_cvv]');
		var cvv = input_cvv.val();

		var error = cvv.length !== 3 && cvv.length !== 4;

		if (mark_input) {
			if (error) {
				$(input_cvv).parent().addClass('has-error');
			} else {
				$(input_cvv).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateName(mark_input = true)
	{
		var input_name = $('#agerede_credit_card [name=agerede_name]');
		var name = input_name.val().trim();
		var error = name.length == 0;

		if (mark_input) {
			if (error) {
				$(input_name).parent().addClass('has-error');
			} else {
				$(input_name).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateInstallments(mark_input = true)
	{
		var input_installments = $('#agerede_credit_card [name=agerede_installment]');
		var installments = input_installments.val();
		var error = installments < 0;

		if (mark_input) {
			if (error) {
				$(input_installments).parent().addClass('has-error');
			} else {
				$(input_installments).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateMonth(mark_input = true)
	{
		var input_month = $('#agerede_credit_card [name=agerede_month]');
		var month = input_month.val()
		var error = month < 0;

		if (mark_input) {
			if (error) {
				$(input_month).parent().addClass('has-error');
			} else {
				$(input_month).parent().removeClass('has-error');
			}
		}

		return !error;
	}

	function validateYear(mark_input = true)
	{
		var input_year = $('#agerede_credit_card [name=agerede_year]');
		var year = input_year.val();
		var error = year < 0;

		if (mark_input) {
			if (year < 0) {
				$(input_year).parent().addClass('has-error');
			} else {
				$(input_year).parent().removeClass('has-error');
			}
		}

		return !error;
	}

    function getBandeira(cardNumber)
    {
        var reg = new RegExp(' ', 'g');
        cardNumber = cardNumber.replace(reg, '');

        var reg = new RegExp('-', 'g');
        cardNumber = cardNumber.replace(reg, '');

        var regexVisa = /^4[0-9]{12}(?:[0-9]{3})?/;
        var regexMaster = /^5[1-5][0-9]{14}/;
        var regexAmex = /^3[47][0-9]{13}/;
        var regexDiners = /^3(?:0[0-5]|[6][0-9])[0-9]{11}/;
        var regexElo = /^(636368|438935|504175|451416|636297)([0-9]{10})$/;
        var regexElo2 = /^(5067|4576|4011|6550)([0-9]{12})$/;
        var regexHipercard = /^(60\d{11})|(60\d{14})|(60\d{17})|(3841\d{11})|(3841\d{14})|(3841\d{17})$/;

        if(!cardNumber) {
            return false;
        }
        
        if(regexVisa.test(cardNumber)) {
            return 'visa';
        }
        
        if(regexMaster.test(cardNumber)) {
            return 'mastercard';
        }
        
        if(regexAmex.test(cardNumber)) {
            return 'amex';
        }
        
        if(regexDiners.test(cardNumber)) {
            if(cardNumber.length==14 | cardNumber.length==16) {
                return 'diners';
            }
        }

        if(regexHipercard.test(cardNumber)) {
            if(cardNumber.length==13 | cardNumber.length==16 | cardNumber.length==19)  {
                return 'hipercard';
            }
        }
        
        if(regexElo.test(cardNumber)) {
            return 'elo';
        }

        if(regexElo2.test(cardNumber)) {
            return 'elo';
        }
        
        return false;
    }

    function getCardBannerCode(card_banner)
    {
    	if (card_banner === 'visa') {
    		return 3;
    	}

    	if (card_banner === 'mastercard') {
    		return 4;
    	}

    	if (card_banner === 'diners') {
    		return 2;
    	}

    	if (card_banner === 'amex') {
    		return 5;
    	}

    	if (card_banner === 'elo') {
    		return 16;
    	}

    	if (card_banner === 'aura') {
    		return 18;
    	}

    	if (card_banner === 'hipercard') {
    		return 20;
    	}

    	if (card_banner === 'hiper') {
    		return 25;
    	}

    	if (card_banner === 'jcb') {
    		return 19;
    	}

    	if (card_banner === 'discover') {
    		return 15;
    	}
    }

	$('#agerede_credit_card [name=agerede_cardnumber]').mask('0000 0000 0000 0099');
	$('#agerede_credit_card [name=agerede_cvv]').mask('0009');

	$('#agerede_credit_card [name=agerede_cardnumber]').keyup(function(){
		var bandeira = getBandeira($(this).val());

		if (bandeira) {
			document.querySelector('#agerede_credit_card .agerede_cardbanner').value=bandeira;
            document.querySelector('#agerede_credit_card .agerede_cardbanner').innerHTML='<img src="' + agerede.base_uri + 'modules/agerede/views/img/cardbanners/'+bandeira+'.png" />';
		} else {
			$('#agerede_cardbanner').empty();
		}
	});


	$('#agerede_credit_card select').change(function(){
		if ($(this).val() > 0) {
			$(this).removeClass('is-not-selected').addClass('is-selected');
		} else {
			$(this).removeClass('is-selected').addClass('is-not-selected');
		}
	});

	$('#agerede_credit_card input, #agerede_credit_card select').change(function(e){
		var has_error = !validateData(false);
		var terms = document.getElementById('conditions_to_approve[terms-and-conditions]');
		
		if (!has_error && (terms == null || terms.checked)) {
			$('#payment-confirmation button').prop('disabled', false);
		} else {
			$('#payment-confirmation button').prop('disabled', true);
		}

		if (e.target.name == 'agerede_cardnumber') {
			validateCardNumber();
		} else if (e.target.name == 'agerede_cvv') {
			validateCvv();
		} else if (e.target.name == 'agerede_name') {
			validateName();
		} else if (e.target.name == 'agerede_installment') {
			validateInstallments();
		} else if (e.target.name == 'agerede_month') {
			validateMonth();
		} else if (e.target.name == 'agerede_year') {
			validateYear();
		}
	});

	$('#agerede_credit_card').submit(function(){
		var _return = validateData();

		if (!_return) {
			return false;
		}

		var spinHandle = loadingOverlay().activate();

		return true;
	});
});