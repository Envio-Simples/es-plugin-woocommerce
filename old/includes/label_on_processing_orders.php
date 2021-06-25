<?php 
add_action('woocommerce_order_status_processing','enviosimples_order_processing');
function only_numbers($val){
    	return preg_replace("/[^0-9]/","",$val);
    }
function enviosimples_order_processing($order_id){

	//verificar o método de envio e ver se foi um método do enviosimples
	$order = wc_get_order( $order_id );

	$shp_main_data = current($order->get_shipping_methods());
	
	// if ("woocommerce_enviosimples" != trim($shp_main_data->get_method_id())) return;	
	if (strpos($shp_main_data->get_method_id(),"woocommerce_enviosimples")===false) return;

	//verificar o ID e o tipo de envio
	$meta_data = [];
	foreach($shp_main_data->get_meta_data() as $meta){
		$meta_data[$meta->key] = $meta->value;
	}

	$id = $meta_data['_enviosimples_id'];
	$shipping_type = $meta_data['_type_send'];
	$token = $meta_data['_enviosimples_token'];
	$sandbox = $meta_data['_enviosimples_sandbox'];

	//buscar os dados do destinatário
	if ($order->has_shipping_address()){
		$destinatario_nome = $order->get_formatted_shipping_full_name();
		$destinatario_cnpjCpf = "bazinga!";
		$destinatario_endereco = $order->get_shipping_address_1() ;
		$destinatario_numero = 0;
		$destinatario_complemento = $order->get_shipping_address_2();
		$destinatario_bairro = "";
		$destinatario_cidade = $order->get_shipping_city();
		$destinatario_uf = $order->get_shipping_state();
		$destinatario_cep = $order->get_shipping_postcode();
		$destinatario_celular = $order->get_billing_phone();	
	} else {
		$destinatario_nome = $order->get_formatted_billing_full_name();
		$destinatario_cnpjCpf = "bazinga!";
		$destinatario_endereco = $order->get_billing_address_1() ;
		$destinatario_numero = 0;
		$destinatario_complemento =  $order->get_billing_address_2();
		$destinatario_bairro = "";
		$destinatario_cidade = $order->get_billing_city();
		$destinatario_uf = $order->get_billing_state();
		$destinatario_cep = $order->get_billing_postcode();
		$destinatario_celular = $order->get_billing_phone();			
	}

	//valor total da encomeda
	$total_value=$order->get_subtotal();

	//fazer um loop nos itens, configurando os volumes e somando o texto para a formatação do conteúdo. Busca também o peso total
	$total_weight = 0;
	$titles = [];
	$volumes = [];

	$items = $order->get_items();

	foreach($items as $item){
		$prod = $item->get_product();
		$weight = wc_get_weight($prod->get_weight(),'kg');
		$volume = [			
			'altura' => wc_get_dimension($prod->get_height(),'cm'),
			'comprimento' => wc_get_dimension($prod->get_length(),'cm'),
			'largura' => wc_get_dimension($prod->get_width(),'cm'),
			'peso' => $weight,
		];
		$total_weight += $weight;
		$volumes[] = $volume;
		$titles[] = $prod->get_title();
	}

	$lista_de_produtos = implode(",",$titles);
	
	//prepara objeto para envio para api
	$label_data = [
		'_id' => $id,
		'conteudo' => $lista_de_produtos,
		'peso_total' => $total_weight,
		'valor_total' => $total_value,
		'tipo_envio' => $shipping_type,
		'destinatario' => [
			'nome' => $destinatario_nome,
			'cnpjCpf' => only_numbers($destinatario_cnpjCpf),
			'endereco' => $destinatario_endereco,
			'numero' => $destinatario_numero,
			'complemento' => $destinatario_complemento,
			'bairro' => $destinatario_bairro,
			'cidade' => $destinatario_cidade,
			'uf' => $destinatario_uf,
			'cep' => only_numbers($destinatario_cep),
			'celular' => only_numbers($destinatario_celular)
		],
		'volume' => $volumes,
		'pedido' => $order->get_order_number(),
		'origem' => 'woocommerce-enviosimples',
		'email' => $order->get_billing_email()
	];

	$meta_group = $order->get_meta_data();
	if (count($meta_group)>0){		
		foreach($meta_group as $meta){
			if ($meta->key=="_billing_persontype") $persontype = $meta->value;
			if ($meta->key=="_billing_cpf") $cpf = only_numbers($meta->value);
			if ($meta->key=="_billing_cnpj") $cnpj = only_numbers($meta->value);
			if ($meta->key=="_billing_number") $billing_number = $meta->value;
			if ($meta->key=="_billing_neighborhood") $billing_neighborhood = $meta->value;
			if ($meta->key=="_billing_cellphone") $billing_cellphone = only_numbers($meta->value);
			if ($meta->key=="_shipping_number") $shipping_number = $meta->value;
			if ($meta->key=="_shipping_neighborhood") $shipping_neighborhood = $meta->value;
		}
		if (isset($persontype)){
			if ($persontype==1){
				$label_data['destinatario']['cnpjCpf'] = $cpf;
			} else {
				$label_data['destinatario']['cnpjCpf'] = $cnpj;
			}
		}
		if ($order->has_shipping_address()){
			if (isset($shipping_number)) $label_data['destinatario']['numero'] = $shipping_number;
			if (isset($shipping_neighborhood)) $label_data['destinatario']['bairro'] = $shipping_neighborhood;
		} else {
			if (isset($billing_number)) $label_data['destinatario']['numero'] = $billing_number;
			if (isset($billing_neighborhood)) $label_data['destinatario']['bairro'] = $billing_neighborhood;			
		}
		if (isset($billing_cellphone) && trim($billing_cellphone)!="") $label_data['destinatario']['celular'] = $billing_cellphone;
	}
	
   	$enviosimples = new Es_Plugin_Woocommerce_API($token, $sandbox);   	
   	$return = $enviosimples->send_labels($label_data);
   	if (isset($return->error)){ $order->add_order_note($return->message); return;}
   	$order->add_order_note($return->data->message);
}
 ?>