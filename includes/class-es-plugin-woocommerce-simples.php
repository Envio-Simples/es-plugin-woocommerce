<?php

require_once "class-es-plugin-woocommerce-api.php";

/**
 * Main Class of Plugin
 *
 * @link       https://github.com/srgoogle23/es-plugin-woocommerce
 * @since      1.0.0
 *
 * @package    Es_Plugin_Woocommerce
 * @subpackage Es_Plugin_Woocommerce/includes
 */

/**
 * Main Class of Plugin
 *
 * @since      1.0.0
 * @package    Es_Plugin_Woocommerce
 * @subpackage Es_Plugin_Woocommerce/includes
 * @author     https://github.com/srgoogle23/es-plugin-woocommerce <contato@ecomd.com.br>
 */


function woocommerce_enviosimples_init()
{
    if (!class_exists('WC_woocommerce_enviosimples')) {
        class WC_woocommerce_enviosimples extends WC_Shipping_Method
        {

            public function __construct($instance_id = 0)
            {

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

            public function init()
            {
                $this->init_form_fields();
                $this->init_instance_settings();
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
            }

            public function init_form_fields()
            {

                $this->instance_form_fields = [
                    'enabled' => [
                        'title' => __('Ativo', 'woocommerce_enviosimples'),
                        'type' => 'checkbox',
                        'label' => 'Ativo',
                        'default' => 'yes',
                        'description' => 'Informe se este método de frete é válido'
                    ],
                    'sandbox' => [
                        'title' => __('Ambiente de testes (Sandbox)', 'woocommerce_enviosimples'),
                        'type' => 'checkbox',
                        'label' => 'Ambiente Sandbox',
                        'default' => 'yes',
                        'description' => 'Informe se está utilizando ambiente de testes (Sandbox)'
                    ],
                    'key' => [
                        'title' => __('Seu token/key de acesso ao enviosimples', 'woocommerce_enviosimples'),
                        'type' => 'text',
                        'default' => '', //f446202be53jNHwbSXKWwRq6U32WIOecK5ddefa3419f
                        'description' => 'Caso ainda não tenha seu token, entre em contato com a enviosimples'
                    ],
                    'zipCodeOrigin' => [
                        'title' => __('CEP do Município de origem para cálculo'),
                        'type' => 'number',
                        'default' => '', //35171099
                        'class' => '',
                        'description' => 'Exemplo: 35171099 - O CEP precisa estar cadastrado como um endereço na plataforma da Envio Simples'
                    ],
                    'show_delivery_time' => [
                        'title' => __('Mostrar prazo de entrega', 'woocommerce_enviosimples'),
                        'type' => 'checkbox',
                        'label' => 'Mostrar prazo de entrega',
                        'default' => '',
                        'description' => 'Informe se devemos mostrar o prazo de entrega'
                    ],
                    'show_estimate_on_product_page' => [
                        'title' => __('Calcular frete na página do produto', 'woocommerce_enviosimples'),
                        'type' => 'checkbox',
                        'label' => 'Frete na página do produto',
                        'default' => '',
                        'description' => 'Informe se quer que o cliente tenha uma previsão do frete na página do produto'
                    ],
                    'calculate_shipping' => [
                        'title' => __('Informe a porcentagem a ser segurada'),
                        'type' => 'number',
                        'default' => '100',
                        'class' => '',
                        'description' => 'Percentual do total da venda que será utilizado como Valor Segurado.'
                    ],
                ];
            }

            function admin_options()
            {

                if (!$this->instance_id) {
                    echo '<h2>' . esc_html($this->get_method_title()) . '</h2>';
                }
                echo wp_kses_post(wpautop($this->get_method_description()));
                echo $this->get_admin_options_html();
            }

            public function calculate_shipping($package = false)
            {
                $use_this_method = $this->validate_shipping($package);

                if (!$use_this_method) {
                    return false;
                }

                if ($package['destination']['postcode'] == '') {
                    return;
                }

                $key         = $this->instance_settings['key'];

                $sandbox     = $this->instance_settings['sandbox'];

                $enviosimples = new Es_Plugin_Woocommerce_API($key, $sandbox);

                foreach ($package['contents'] as $item) {

                    $product = $item['data'];

                    //print_r($product); exit;

                    $height = (int)preg_replace("/[^0-9]/", "", $product->get_height());
                    $width  = (int)preg_replace("/[^0-9]/", "", $product->get_width());
                    $length = (int)preg_replace("/[^0-9]/", "", $product->get_length());

                    $quantity = $item['quantity'];

                    for ($i = 0; $i < $quantity; $i++) {
                        $volume = [];

                        $volume['length'] = wc_get_dimension($length, 'cm'); //comprimento 
                        $volume['width'] = wc_get_dimension($width, 'cm'); //largura 
                        $volume['height'] = wc_get_dimension($height, 'cm'); //altura
                        $volume['weight'] = wc_get_weight($product->get_weight(), 'kg'); //Peso Bruto do produto 

                        $enviosimples->addVolumes($volume);
                    }
                }

                $zipCodeOrigin  = $this->instance_settings['zipCodeOrigin'];
                $zipCodeOrigin = str_replace('.', '', $zipCodeOrigin);
                $zipCodeOrigin = str_replace('.', '', $zipCodeOrigin);
                $zipCodeOrigin = str_replace('-', '', $zipCodeOrigin);
                $zipCodeOrigin = str_replace('-', '', $zipCodeOrigin);

                $zipCodeDestiny = $package['destination']['postcode'];
                $zipCodeDestiny = str_replace('.', '', $zipCodeDestiny);
                $zipCodeDestiny = str_replace('.', '', $zipCodeDestiny);
                $zipCodeDestiny = str_replace('-', '', $zipCodeDestiny);
                $zipCodeDestiny = str_replace('-', '', $zipCodeDestiny);

                $porc = $this->instance_settings['calculate_shipping'];

                if ($porc == '' || $porc > 100) {
                    $porc = 100;
                }

                $valueDeclared  = $package['cart_subtotal'] * ($porc / 100); //aqui 

                $shipping = $enviosimples->calculate_shipping($zipCodeOrigin, $zipCodeDestiny, $valueDeclared, 'false');

                if (is_array($shipping)) {
                    $calculatorId = $shipping['calculatorId'];

                    foreach ($shipping['rate'] as $rate) {
                        $meta_delivery = array(
                            '_calculatorId' => $calculatorId, //Obrigadorio
                            '_shippingId'   => $rate->shippingId, //no caso deste o ID é muito importnte 
                            '_valueDeclared'   => number_format($valueDeclared, 2, ',', '.'),
                            '_token' => $this->instance_settings['key'], //no caso deste o ID é muito importnte 
                            '_type_send'    => $rate->alias,
                            '_enviosimples_sandbox' => $this->instance_settings['sandbox'],
                        );

                        $price = (float)$rate->priceFinish;

                        $prazo_texto = "";

                        $show_delivery_time = $this->instance_settings['show_delivery_time'];

                        if ('yes' === $show_delivery_time) $prazo_texto = " (" . $rate->deadline . ")";

                        $rates_woo = [
                            'id'        => 'woocommerce_enviosimples' . $rate->name,
                            'label'     => $rate->name . $prazo_texto,
                            'cost'      => $price,
                            'meta_data' => $meta_delivery
                        ];
                        $this->add_rate($rates_woo, $package);
                    }
                } else {
                    return;
                }
                return;
            }
            /****************************/
            public function forecast_shipping($enviosimples_product = false)
            {
                global $product;
                global $woocommerce;


                // echo "<pre>";print_r($product);echo "</pre>";				
                // echo "<pre>";print_r($this->instance_settings);echo "</pre>";
                if ($this->instance_settings['enabled'] != 'yes') return;

                if ($this->instance_settings['show_estimate_on_product_page'] != 'yes') return;


                $key         = $this->instance_settings['key'];
                $sandbox     = $this->instance_settings['sandbox'];

                $height = (int)preg_replace("/[^0-9]/", "", $product->get_height());
                $width  = (int)preg_replace("/[^0-9]/", "", $product->get_width());
                $length = (int)preg_replace("/[^0-9]/", "", $product->get_length());
                $weight = (int)preg_replace("/[^0-9]/", "", wc_get_weight($product->get_weight(), 'kg'));

                $quantity = 0;
                $cart            = $woocommerce->cart->get_cart();
                $product_cart_id = $woocommerce->cart->generate_cart_id($product->get_id());
                if ($woocommerce->cart->find_product_in_cart($product_cart_id)) {
                    $quantity = $cart[$product_cart_id]['quantity'];
                }
                if ($quantity <= 0) {
                    $quantity = 1;
                }

                $volume = [];
                $volume['length'] = wc_get_dimension($length, 'cm'); //comprimento 
                $volume['width'] = wc_get_dimension($width, 'cm'); //largura 
                $volume['height'] = wc_get_dimension($height, 'cm'); //altura
                $volume['weight'] = wc_get_weight($product->get_weight(), 'kg'); //Peso Bruto do produto 
                $price = $product->get_price();

                if ($weight == 0 || $height == 0 || $width == 0 || $length == 0) return;



                $product_link = $product->get_permalink();

                if (isset($_POST['enviosimples_forecast_zip_code'])) {
                    $target_zip_code = $_POST['enviosimples_forecast_zip_code'];
                } else {
                    $shipping_zip_code = $woocommerce->customer->get_shipping_postcode();
                    if (trim($shipping_zip_code) != "") {
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


                if (trim($target_zip_code) == "") return;

                $target_zip_code = preg_replace("/[^0-9]/", "", $target_zip_code);
                $zipCodeOrigin   = preg_replace("/[^0-9]/", "", $this->instance_settings['zipCodeOrigin']);
                // $zipCodeOrigin = get_option('woocommerce_store_postcode');

                $rates = [];

                /*Sua programação de Frete */

                $enviosimples = new Es_Plugin_Woocommerce_API($key, $sandbox);

                for ($i = 0; $i < $quantity; $i++) {
                    $enviosimples->addVolumes($volume);
                }

                $porc = $this->instance_settings['calculate_shipping'];

                if ($porc == '' || $porc > 100) {
                    $porc = 100;
                }

                $valueDeclared  = ($quantity * $price) * ($porc / 100); //aqui 

                $shipping       = $enviosimples->calculate_shipping($zipCodeOrigin, $target_zip_code, $valueDeclared, 'false');


                if (!is_array($shipping)) {
                    echo "<pre>";
                    echo "<p> Não foi possivel calcular o frete na págna do produto.</p>";
                    echo "</pre>";
                    return;
                }

                /***************************************************/
                $show_delivery_time = $this->instance_settings['show_delivery_time'];

                $rates = [];

                foreach ($shipping['rate'] as $rate) {

                    $rate_item = [];

                    $prazo_texto = "";
                    if ('yes' === $show_delivery_time) $prazo_texto = " (" . $rate->deadline . ")";

                    $rate_item['label'] = $rate->name . $prazo_texto;
                    // $rate_item['cost'] = wc_price($rate->price_enviosimples);
                    $rate_item['cost'] = $price = wc_price($rate->priceFinish);
                    //$rate_item['cost'] = wc_price($rate->price);

                    $rates[] = $rate_item;
                }

                if (count($rates) == 0) return;

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

                foreach ($rates as $rate) {

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
            /***************************/



            private function validate_shipping($package = false)
            {

                if ($this->instance_settings['enabled'] != 'yes') return false;

                return true;
            }
        }
    }
}
