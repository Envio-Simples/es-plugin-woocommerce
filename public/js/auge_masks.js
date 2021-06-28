jQuery(document).ready(function(){

	jQuery(".as_mask_zip_code").mask('00000-000',{placeholder: "_____-___"});
	jQuery(".as_mask_date").mask('00/00/0000',{placeholder: "00/00/0000"});
	jQuery(".as_mask_small_qtd").mask('000.000',{reverse: true});
	jQuery(".as_mask_money").mask('000.000,00',{reverse: true});
	jQuery(".as_mask_percentage").mask('000,00',{reverse: true});
	jQuery(".as_mask_cnpj").mask('00.000.000/0000-00',{placeholder: "00.000.000/0000-00"});
	jQuery(".as_mask_cpf").mask('000.000.000-00',{placeholder: "000.000.000-00"});
	jQuery(".as_mask_bank_agency").mask('00000-0',{placeholder: "00000-0",reverse:true});
	jQuery(".as_mask_bank_account").mask('000.000.000',{placeholder: "000.000.000"});
	jQuery(".as_mask_bank_account_extended").mask('0.000.000.000-0',{placeholder: "0.000.000.000-0",reverse:true});
	jQuery(".as_mask_bank_digit").mask('0',{placeholder: "0"});
	jQuery(".as_mask_phone_prefix").mask('00',{placeholder: "00"});
	jQuery(".as_mask_phone_number").mask('Z0000-0000',{
		reverse: true,
		placeholder: "0000-0000",
		translation: {
			'Z': {
				pattern: /[0-9]/, optional: true
			}
		}
	}); 
	jQuery(".as_mask_full_phone").mask('(00) Z0000-0000',{
		reverse: false,
		placeholder: "(00) 0000-0000",
		translation: {
			'Z': {
				pattern: /[0-9]/, optional: true
			}
		}
	}); 	

});