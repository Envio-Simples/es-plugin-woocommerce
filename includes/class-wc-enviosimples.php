<?php 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include WC_ENVIOSIMPLES_DIR . "/includes/class_api_enviosimples.php";
include WC_ENVIOSIMPLES_DIR . "/includes/label_on_processing_orders.php";

function woocommerce_enviosimples_logger($message){

	    $log = wc_get_logger();
        $context = array( 'source' => 'envio_simples' );
        $log->debug($message,$context);	
        return;
}

function woocommerce_enviosimples_init(){
	if (!class_exists('WC_woocommerce_enviosimples')){

		class WC_woocommerce_enviosimples extends WC_Shipping_Method {

			public function __construct($instance_id = 0){

				$this->id = 'woocommerce_enviosimples';
				$this->instance_id = absint($instance_id);
				$this->title = 'Envio Simples';
				$this->method_title = 'Envio Simples';
				$this->method_description = 'Calculadora de frete Envio Simples';	
				$this->supports           = array(
					'shipping-zones',
					'instance-settings',
					);	
				$this->init();  				
			}

			public function init(){
				$this->init_form_fields();
				$this->init_instance_settings();
				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			}

			public function init_form_fields(){

				$this->instance_form_fields = [
				'enabled' => [
				'title' => __('Ativo','woocommerce_enviosimples'),
				'type' => 'checkbox',
				'label' => 'Ativo',
				'default' => 'yes',
				'description' => 'Informe se este método de frete é válido'
				],	
				'sandbox' => [
				'title' => __('Ambiente de testes (Sandbox)','woocommerce_enviosimples'),
				'type' => 'checkbox',
				'label' => 'Ambiente Sandbox',
				'default' => 'yes',
				'description' => 'Informe se está utilizando ambiente de testes (Sandbox)'
				],					
				'token' => [
				'title' => __('Seu token de acesso ao enviosimples','woocommerce_enviosimples'),
				'type' => 'text',						
				'default' => '',
				'description' => 'Caso ainda não tenha seu token, entre em contato com a enviosimples'
				],									
				'source_zip_code' => [
				'title' => __('CEP de origem para cálculo'),
				'type' => 'text',
				'default' => '00000-000',
				'class' => 'as_mask_zip_code',
				'description' => 'Peso mínimo para o cliente poder escolher esta modalidade'
				],						
				'show_delivery_time' => [
				'title' => __('Mostrar prazo de entrega','woocommerce_enviosimples'),
				'type' => 'checkbox',
				'label' => 'Mostrar prazo de entrega',
				'default' => 'yes',
				'description' => 'Informe se devemos mostrar o prazo de entrega'
				],	
				'show_estimate_on_product_page' => [
				'title' => __('Calcular frete na página do produto','woocommerce_enviosimples'),
				'type' => 'checkbox',
				'label' => 'Frete na página do produto',
				'default' => 'yes',
				'description' => 'Informe se quer que o cliente tenha uma previsão do frete na página do produto'
				],						
				];
			}

			function admin_options() {

				if ( ! $this->instance_id ) {
					echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
				}
				echo wp_kses_post( wpautop( $this->get_method_description() ) );
				echo $this->get_admin_options_html();

			}

			public function calculate_shipping( $package = false) {
				
				$use_this_method = $this->validate_shipping($package);
				if (!$use_this_method) return false;
				
				$product_statements = $this->sumarize_package($package);

				$token = $this->instance_settings['token'];
				$sandbox = $this->instance_settings['sandbox'];

				$enviosimples = new enviosimples($token,$sandbox);
				$enviosimples->setWeight($product_statements['total_weight']);
				$enviosimples->setSourceZipCode($this->instance_settings['source_zip_code']);
				$enviosimples->setTargetZipCode($package['destination']['postcode']);
				$enviosimples->setPackageValue($product_statements['total_value']);
				$enviosimples->setWidth($product_statements['maximum_width']);
				$enviosimples->setHeight($product_statements['maximum_height']);
				$enviosimples->setLength($product_statements['maximum_length']);
				
				$enviosimples->calculate_shipping();
				
				$received_rates = $enviosimples->getRates();

				if (isset($received_rates->error)){
					echo "<pre>";
					print_r($received_rates->message);
					echo "</pre>";
					return;
				}

				if (count($received_rates)==0) return;

				$show_delivery_time = $this->instance_settings['show_delivery_time'];

				foreach($received_rates as $rate){

					// Display delivery.
					$meta_delivery_time = [];

					$prazo_em_dias = preg_replace("/[^0-9]/","",$rate->deadline);

					$meta_delivery = array(					
						'_enviosimples_id' => $rate->_id,
						'_type_send' => $rate->type_send,
						'_enviosimples_token' => $this->instance_settings['token'],
						'_enviosimples_sandbox' => $this->instance_settings['sandbox'],
						);

					if ( 'yes' === $show_delivery_time ) $meta_delivery['_enviosimples_delivery_estimate'] = intval( $prazo_em_dias );

					$prazo_texto = "";
					if ( 'yes' === $show_delivery_time ) $prazo_texto = " (".$rate->deadline.")";
					

					$rates = [
					'id' => 'woocommerce_enviosimples_'.$rate->name,
					'label' => $rate->name.$prazo_texto,
					// 'cost' => $rate->price_enviosimples,
					'cost' => $rate->price_finish,
					//'cost' => $rate->price,
					'meta_data' => $meta_delivery
					];	        		
	        		// echo "<pre>";
	        		// print_r($rates);
	        		// echo "</pre>";
	        		// die();					
					$this->add_rate($rates, $package);
				}

				return;    			

			}

			public function forecast_shipping( $enviosimples_product = false) {					        	

				global $product;
				global $woocommerce;
				// echo "<pre>";print_r($product);echo "</pre>";				
				// echo "<pre>";print_r($this->instance_settings);echo "</pre>";
				if ($this->instance_settings['enabled']!='yes') return;

				if ($this->instance_settings['show_estimate_on_product_page']!='yes') return;				

				$token = $this->instance_settings['token'];
				$sandbox = $this->instance_settings['sandbox'];

				$height = (integer)preg_replace("/[^0-9]/","",$product->get_height());
				$width = (integer)preg_replace("/[^0-9]/","",$product->get_width());
				$length = (integer)preg_replace("/[^0-9]/","",$product->get_length());

				$dimensions = [];
				$dimensions[] = wc_get_dimension($length,'cm');
				$dimensions[] = wc_get_dimension($width,'cm');
				$dimensions[] = wc_get_dimension($height,'cm');

				sort($dimensions);

				$length = $dimensions[2];
				$width = $dimensions[1];
				$height = $dimensions[0];

				$weight = wc_get_weight($product->get_weight(),'kg');
				$price = $product->get_price();

				if ($weight==0 || $height==0 || $width==0 || $length==0) return;

				$product_link = $product->get_permalink();

				if (isset($_POST['enviosimples_forecast_zip_code'])){
					$target_zip_code = $_POST['enviosimples_forecast_zip_code'];
				} else {
					$shipping_zip_code = $woocommerce->customer->get_shipping_postcode();			
					if (trim($shipping_zip_code)!="") {
						$target_zip_code = $shipping_zip_code;
					} else {
						$target_zip_code = $woocommerce->customer->get_billing_postcode();
					}
				}

				echo "
				<style>
					.as-row{margin:0px -15px;}
					.as-row::before,.as-row::after{display: table; content: ' ';}
					.as-row::after{clear:both;}
					.as-col-xs-12, .as-col-sm-4, .as-col-sm-8,.as-col-md-9,.as-col-md-3, as-col-sm-12, as-col-md-12{position: relative;min-height: 1px;padding-right: 15px;padding-left: 15px;}
					.as-col-xs-12{float:left;width:100%;}
					@media (min-width:600px) {.as-col-sm-4,.as-col-sm-8,as-col-sm-12{float:left;}.as-col-sm-4{width:33.33%;}.as-col-sm-8{width:66.33%;}.as-col-sm-12{width:100%;}}
					@media (min-width:992px){.as-col-md-3,.as-col-md-9,.as-col-md-12{float:left;}.as-col-md-3{width:25%;}.as-col-md-9{width:75%};as-col-md-12{width:100%;}}
					.enviosimples_shipping_forecast_form{padding-top:20px;}					
					.enviosimples_shipping_forecast_form input{max-width:100% !important;text-align:center;height:42px;}					
					.enviosimples_shipping_forecast_table{padding:20px 0px;}
					.enviosimples_shipping_forecast_table table{width:100%;}					
					.woocommerce div.product form.cart div.quantity, .woocommerce div.product form.cart .button{top:auto;}
				</style>
				<div style='clear:both;'></div>
				<div class='enviosimples_shipping_forecast_form as-row'>
					<div class=''>
						<form method='post' action='{$product_link}' id='enviosimples_shipping_forecast'>	
							<div class=''>				
								<div class='as-col-md-3 as-col-sm-4 as-col-xs-12'>
									<input type='text' value='{$target_zip_code}' class='as_mask_zip_code' name='enviosimples_forecast_zip_code'/>
								</div>
								<div class='as-col-md-9 as-col-sm-8 as-col-xs-12'>
									<button type='submit' id='enviosimples_shipping_forecast_submit' class='single_add_to_cart_button button alt'>Calcular frete</button>
								</div>
							</div>
						</form>
					</div>
				</div>";


				if (trim($target_zip_code)=="") return;

				$target_zip_code = preg_replace("/[^0-9]/","",$target_zip_code);
				$source_zip_code = preg_replace("/[^0-9]/","",$this->instance_settings['source_zip_code']);
				// $source_zip_code = get_option('woocommerce_store_postcode');

				$rates = [];

				$enviosimples = new enviosimples($token,$sandbox);
				$enviosimples->setWeight($weight);
				$enviosimples->setSourceZipCode($source_zip_code);
				$enviosimples->setTargetZipCode($target_zip_code);
				$enviosimples->setPackageValue($price);
				$enviosimples->setWidth($width);
				$enviosimples->setHeight($height);
				$enviosimples->setLength($length);
				
				$enviosimples->calculate_shipping();
				// $teste = [$token,$weight,$source_zip_code,$target_zip_code,$price,$width,$height,$length];
				$received_rates = $enviosimples->getRates();
			
				if (isset($received_rates->error)){
					echo "<pre>";
					echo "<p>".$received_rates->message."</p>";
					echo "</pre>";
					return;
				}				
				
				if (count($received_rates)==0) return [];

				$show_delivery_time = $this->instance_settings['show_delivery_time'];

				$rates = [];

				foreach($received_rates as $rate){

					$rate_item = [];

					$prazo_texto = "";
					if ( 'yes' === $show_delivery_time ) $prazo_texto = " (".$rate->deadline.")";

					$rate_item['label'] = $rate->name . $prazo_texto;
					// $rate_item['cost'] = wc_price($rate->price_enviosimples);
					$rate_item['cost'] = wc_price($rate->price_finish);
					//$rate_item['cost'] = wc_price($rate->price);

					$rates[] = $rate_item;					
				}				



				if (count($rates)==0) return;

				echo "
				<div class='enviosimples_shipping_forecast_table as-row'>
					<div class='as-col-xs-12 as-col-sm-12 as-col-md-12'>
						<table>
							<thead>
								<tr>
									<th>Modalidade de envio pelo Envio Simples</th>
									<th>Custo estimado</th>
								</tr>
							</thead>
							<tbody>";

								foreach($rates as $rate){

									echo "<tr>";
									echo "<td>" . $rate['label'] . "</td>";
									echo "<td>" . $rate['cost'] . "</td>";
									echo "</tr>";
								}

								echo "
							</tbody>
						</table>
					</div>
				</div>
				";

			}

			private function validate_shipping($package = false){

				if ($this->instance_settings['enabled']!='yes') return false;	        	

				return true;

			}

			private function sumarize_package($package){
	        	// print_r(get_option('woocommerce_weight_unit' ));
				$package_values = [
				'total_weight' => 0,
				'total_value' => 0,
				'maximum_length' => 0,
				'maximum_width' => 0,
				'maximum_height' => 0
				];	        	

				foreach($package['contents'] as $item){

					$product = $item['data'];	        		

					$height = (integer)preg_replace("/[^0-9]/","",$product->get_height());
					$width = (integer)preg_replace("/[^0-9]/","",$product->get_width());
					$length = (integer)preg_replace("/[^0-9]/","",$product->get_length());

					$dimensions = [];
					$dimensions[] = wc_get_dimension($length,'cm');
					$dimensions[] = wc_get_dimension($width,'cm');
					$dimensions[] = wc_get_dimension($height,'cm');

					sort($dimensions);

					$length = $dimensions[2];
					$width = $dimensions[1];
					$height = $dimensions[0];

					$weight = wc_get_weight($product->get_weight(),'kg');
					$package_values['total_weight']+= $weight;

					$value = $item['line_total'];
					$package_values['total_value'] += $value;

					if ($width > $package_values['maximum_width']) $package_values['maximum_width'] = $width;
					if ($height > $package_values['maximum_height']) $package_values['maximum_height'] = $height;
					if ($length > $package_values['maximum_length']) $package_values['maximum_length'] = $length;

				}	        	

				return $package_values;

			}

		}
	}
}
add_action('woocommerce_shipping_init','woocommerce_enviosimples_init');

function add_woocommerce_enviosimples( $methods ) {
	$methods['woocommerce_enviosimples'] = 'WC_woocommerce_enviosimples'; 
	return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_woocommerce_enviosimples' );

add_action('wp_enqueue_scripts','enviosimples_enqueue_user_scripts');
add_action('admin_enqueue_scripts','enviosimples_enqueue_user_scripts');
function enviosimples_enqueue_user_scripts(){

	wp_enqueue_script('auge_jquery_masks',plugins_url()."/woocommerce-enviosimples/assets/jquery.mask.min.js",array(),false,true);
	wp_enqueue_script('auge_jquery_mask_formats',plugins_url()."/woocommerce-enviosimples/assets/auge_masks.js",array(),false,true);	
	wp_enqueue_script('enviosimples_scripts',plugins_url()."/woocommerce-enviosimples/assets/enviosimples.js",array(),false,true);	
	return;
}

function enviosimples_shipping_forecast_on_product_page(){
	global $woocommerce;
	if (!is_product()) return;

	if (isset($_POST['enviosimples_forecast_zip_code'])){
		$target_zip_code = $_POST['enviosimples_forecast_zip_code'];
	} else {
		$shipping_zip_code = $woocommerce->customer->get_shipping_postcode();			
		if (trim($shipping_zip_code)!="") {
			$target_zip_code = $shipping_zip_code;
		} else {
			$target_zip_code = $woocommerce->customer->get_billing_postcode();
		}
	}

	$metodos_de_entrega = enviosimples_get_metodos_de_entrega($target_zip_code);

	if (count($metodos_de_entrega)==0) return;

	foreach($metodos_de_entrega as $metodo){
		if (is_object($metodo) && get_class($metodo)=="WC_woocommerce_enviosimples"){
			$enviosimples_class = $metodo;
			break;
		}
	}	

	$enviosimples_class->forecast_shipping();
	
}
add_action('woocommerce_after_add_to_cart_form','enviosimples_shipping_forecast_on_product_page',50);

function enviosimples_get_metodos_de_entrega($cep_destinatario) {

	$metodos_de_entrega = [];

	$delivery_zones = WC_Shipping_Zones::get_zones();

        // Temos zonas de entrega?
	if (count($delivery_zones) < 1) {		
		return $metodos_de_entrega;
	}

        // Inicia o array de métodos de entrega desta delivery_zone
	$metodos_de_entrega = [
	// 'retirar_no_local' => '',
	// 'frete_gratis' => '',
	'shipping_methods' => []
	];

        // Temos. Temos algum dos métodos de entrega suportados lá?
	foreach ($delivery_zones as $key_delivery_zone => $delivery_zone) {

            // Temos efetivamente algum Shipping Method cadastrado nesta Delivery Zone?
		if (count($delivery_zone['shipping_methods']) < 1) {
			continue;			
		}

            // O CEP informado participa desta delivery zone?
		$cep_destinatario_permitido = false;
		foreach ($delivery_zone['zone_locations'] as $zone_location) {
			switch ($zone_location->type) {
				case 'country':
                            // Pais: Brasil
				if ($zone_location->code == 'BR')
					$cep_destinatario_permitido = true;
				break;
				case 'postcode':
                        // CEPs Específicos
                        // Vamos dar um foreach nas linhas
				$ceps = explode(PHP_EOL, $zone_location->code);
				foreach ($ceps as $key => $value) {
                            // É um range?
					if (strpos($zone_location->code, '...') !== false) {
						$ranges = explode('...', $value);
						if (count($ranges) == 2 && is_numeric($ranges[0]) && is_numeric($ranges[1])) {
							if ($cep_destinatario > (int) $ranges[0] && $cep_destinatario < (int) $ranges[1]) {
								$cep_destinatario_permitido = true;
							}
						}
						continue;
					}
                            // É um wildcard?
					if (strpos($zone_location->code, '*') !== false) {
						$before_wildcard = strtok($zone_location->code, '*');
						$tamanho_string = strlen($before_wildcard);
						if (substr($cep_destinatario, 0, $tamanho_string) == $before_wildcard) {
							$cep_destinatario_permitido = true;
						}
					} else {
                                // É uma comparação literal?
						if ($cep_destinatario == $zone_location->code) {
							$cep_destinatario_permitido = true;
						}
					}
				}
				break;
				case 'state':
                        // Estados específicos
				$tmp = explode(':', $zone_location->code);
				if ($tmp[0] == 'BR') {
					if (enviosimples_is_cep_from_state($cep_destinatario, $tmp[1])) {
						$cep_destinatario_permitido = true;
					}
				}
				break;
			}

		}

            // Loop pelas shipping zones
		foreach ($delivery_zone['shipping_methods'] as $key => $shipping_method) {

   //              // Retirar no local?
			// if (get_class($shipping_method) == 'WC_Shipping_Local_Pickup') {

			// 		if ($shipping_method->enabled == 'yes' && $cep_destinatario_permitido) {
			// 			$metodos_de_entrega['retirar_no_local'] = 'sim';
			// 		}

			// 	continue;
			// }

   //              // Frete Grátis
			// if (get_class($shipping_method) == 'WC_Shipping_Free_Shipping') {
			// 	if ($this->options['exibir_frete_gratis'] == 'true') {
			// 		if ($cep_destinatario_permitido) {
			// 			if (empty($shipping_method->requires)) {
			// 				$metodos_de_entrega['frete_gratis'] = 'sim';
			// 			} elseif ($shipping_method->requires == 'min_amount' || $shipping_method->requires == 'either') {
			// 				if (is_numeric($shipping_method->min_amount)) {
			// 					if ($preco_produto > $shipping_method->min_amount) {
			// 						$metodos_de_entrega['frete_gratis'] = 'sim';
			// 					}
			// 				}
			// 			}
			// 		}
			// 	}
			// }

                // O método atual é permitido?
			if (get_class($shipping_method) ==  "WC_woocommerce_enviosimples") {
                    // O método atual está habilitado?
				if ($shipping_method->enabled == 'yes') {
					$metodos_de_entrega[$key] = $shipping_method;
				}
			}
		}
	}

	return $metodos_de_entrega;


}

function enviosimples_is_cep_from_state($cep, $estado) {

        $cep = substr($cep, 0, 5); // 5 primeiros dígitos
        $cep = (int)$cep;

        switch ($estado) {
        	case ('AC'):
        	if ($cep > 69900 && $cep < 69999)
        		return true;
        	break;
        	case ('AL'):
        	if ($cep > 57000 && $cep < 57999)
        		return true;
        	break;
        	case ('AP'):
        	if ($cep > 68900 && $cep < 68999)
        		return true;
        	break;
        	case ('AM'):
        	if ($cep > 69400 && $cep < 69899)
        		return true;
        	break;
        	case ('BA'):
        	if ($cep > 40000 && $cep < 48999)
        		return true;
        	break;
        	case ('CE'):
        	if ($cep > 60000 && $cep < 63999)
        		return true;
        	break;
        	case ('CE'):
        	if ($cep > 60000 && $cep < 63999)
        		return true;
        	break;
        	case ('DF'):
        	if ($cep > 70000 && $cep < 73699)
        		return true;
        	break;
        	case ('ES'):
        	if ($cep > 29000 && $cep < 29999)
        		return true;
        	break;
        	case ('GO'):
        	if ($cep > 72800 && $cep < 76799)
        		return true;
        	break;
        	case ('MA'):
        	if ($cep > 65000 && $cep < 65999)
        		return true;
        	break;
        	case ('MT'):
        	if ($cep > 78000 && $cep < 78899)
        		return true;
        	break;
        	case ('MS'):
        	if ($cep > 79000 && $cep < 79999)
        		return true;
        	break;
        	case ('MG'):
        	$debug[] = 'MG';
        	if ($cep > 30000 && $cep < 39999)
        		return true;
        	break;
        	case ('PA'):
        	if ($cep > 66000 && $cep < 68899)
        		return true;
        	break;
        	case ('PB'):
        	if ($cep > 58000 && $cep < 58999)
        		return true;
        	break;
        	case ('PR'):
        	if ($cep > 80000 && $cep < 87999)
        		return true;
        	break;
        	case ('PE'):
        	if ($cep > 50000 && $cep < 56999)
        		return true;
        	break;
        	case ('PI'):
        	if ($cep > 64000 && $cep < 64999)
        		return true;
        	break;
        	case ('RJ'):
        	if ($cep > 20000 && $cep < 28999)
        		return true;
        	break;
        	case ('RN'):
        	if ($cep > 59000 && $cep < 59999)
        		return true;
        	break;
        	case ('RS'):
        	if ($cep > 90000 && $cep < 99999)
        		return true;
        	break;
        	case ('RO'):
        	if ($cep > 78900 && $cep < 78999)
        		return true;
        	break;
        	case ('RR'):
        	if ($cep > 69300 && $cep < 69389)
        		return true;
        	break;
        	case ('SC'):
        	if ($cep > 88000 && $cep < 89999)
        		return true;
        	break;
        	case ('SP'):
        	if ($cep > 01000 && $cep < 19999)
        		return true;
        	break;
        	case ('SE'):
        	if ($cep > 49000 && $cep < 49999)
        		return true;
        	break;
        	case ('TO'):
        	if ($cep > 77000 && $cep < 77995)
        		return true;
        	break;
        	default:
        	return false;
        }
    }

//****************************************************************************************
// add_action( 'woocommerce_after_shipping_rate', 'enviosimples_shipping_delivery_estimate');


// function enviosimples_shipping_delivery_estimate( $shipping_method ) {

// 	$method_name = $shipping_method->get_method_id();	
// 	if ($method_name !='woocommerce_enviosimples') return;	

// 	$meta_data = $shipping_method->get_meta_data();

// 	$estimate     = isset( $meta_data['_enviosimples_delivery_estimate'] ) ? intval( $meta_data['_enviosimples_delivery_estimate'] ) : 0;

// 	if ( $estimate ) {		
// 		echo "<p><small>Entrega pelo enviosimples em {$estimate} dias úteis</small></p>";
// 	}
// }

    ?>