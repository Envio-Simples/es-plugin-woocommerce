<?php

require_once "class-es-plugin-woocommerce-api.php";

/**
 * Main Class of Plugin
 *
 * @link       https://github.com/Envio-Simples/es-plugin-woocommerce
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
 * @author     https://github.com/Envio-Simples/es-plugin-woocommerce <contato@ecomd.com.br>
 */
class Es_Plugin_Woocommerce_main
{

    public function woocommerce_enviosimples_logger($message)
    {
        $log = wc_get_logger();
        $context = array('source' => 'envio_simples');
        $log->debug($message, $context);
        return;
    }

    public function isw_woo_update_ticket($order_id)
    {
        global $wpdb;
        global $woocommerce;
        //Busca a Etiqueta e atualiza o post meta_data
        $meta_key = '_ticket_code';
        $ticket_code = get_post_meta($order_id, "{$meta_key}", true);
        if (!$ticket_code) { //Senão existe gera uma etiqueta 

            $calculatorId = isw_get_item_meta($order_id, '_calculatorId');
            $content      = 'PRODUTOS';
            $alias        = isw_get_item_meta($order_id, '_type_send');
            $document     = 'declaracao_conteudo';

            //item_comprados 
            $prefix  = $wpdb->prefix;
            $sql = "SELECT order_item_id
                   FROM  {$prefix}woocommerce_order_items items
                  WHERE  items.order_item_type = 'line_item' AND 
                         items.order_id    =  {$order_id}";
            $rs = $wpdb->get_results($sql);

            $declarationItens = [];
            foreach ($rs as $linha) {
                $order_item_id = $linha->order_item_id;
                $quantidade = isw_get_item_meta_id($order_item_id, '_qty');

                for ($i = 0; $i < (int)$quantidade; $i++) {
                    $item = get_the_title(isw_get_item_meta_id($order_item_id, '_product_id'));

                    $content = $item . ' e etc';

                    $subtotal   = isw_get_item_meta_id($order_item_id, '_line_subtotal');

                    $quantidade = isw_get_item_meta_id($order_item_id, '_qty');

                    $value = $subtotal / $quantidade;

                    $count = 1;

                    $declarationItens[] = array('item' => "{$item}", 'value' => $value, 'count' => $count);
                }
            }



            $type = get_post_meta($order_id, '_billing_persontype', true) == '1' ? 'physical-person' : 'legal-person';

            $name      = get_post_meta($order_id, '_shipping_first_name', true) . ' ' . get_post_meta($order_id, '_shipping_last_name', true);

            $document2 = $type == 'legal-person' ? get_post_meta($order_id, '_billing_cnpj', true)  :  get_post_meta($order_id, '_billing_cpf', true); //Claudio Sanches 

            $document2 = str_replace(".", "", $document2);
            $document2 = str_replace(".", "", $document2);
            $document2 = str_replace(".", "", $document2);
            $document2 = str_replace(".", "", $document2);
            $document2 = str_replace(".", "", $document2);
            $document2 = str_replace("-", "", $document2);
            $document2 = str_replace("-", "", $document2);
            $document2 = str_replace("-", "", $document2);
            $document2 = str_replace("-", "", $document2);
            $document2 = str_replace("-", "", $document2);
            $document2 = str_replace('/', "", $document2);
            $document2 = str_replace('/', "", $document2);
            $document2 = str_replace('/', "", $document2);
            $document2 = str_replace('/', "", $document2);
            $document2 = str_replace('/', "", $document2);

            $phone     = get_post_meta($order_id, '_billing_phone', true);
            $phone = str_replace(" ", "", $phone);
            $phone = str_replace("-", "", $phone);
            $phone = str_replace(".", "", $phone);
            $phone = str_replace("(", "", $phone);
            $phone = str_replace(")", "", $phone);
            $email     = get_post_meta($order_id, '_billing_email', true);

            $zipCode   = get_post_meta($order_id, '_shipping_postcode', true);
            $zipCode = preg_replace("/[^0-9]/", "", $zipCode);

            $street    = get_post_meta($order_id, '_shipping_address_1', true) . ' ' . get_post_meta($order_id, '_shipping_address_2', true);
            $number    = get_post_meta($order_id, '_shipping_number', true);
            $district  = get_post_meta($order_id, '_shipping_neighborhood', true);
            $city      = get_post_meta($order_id, '_shipping_city', true);
            $state     = get_post_meta($order_id, '_shipping_state', true);

            $sender = [
                'type'    => "{$type}",
                'name'    => "{$name}",
                'document' => "{$document2}", //declaracao_conteudo
                'phone'   => "{$phone}",
                'email'   => "{$email}",
                'zipCode' => "{$zipCode}",
                'street'  => "{$street}",
                'number'  => "{$number}",
                'district' => "{$district}",
                'city'    => "{$city}",
                'state'   => "{$state}"
            ];

            $additionalServices = ['deliveryNeighbor' => ['active' => false]];

            $ticket = [
                'calculatorId' => "{$calculatorId}",
                'content'      => substr($content, 0, 29),
                'alias'        => "{$alias}",
                'document'     => "{$document}",
                'docs'         => ['declarationItens' => $declarationItens],
                'sender'       => $sender,
                'additionalServices' => $additionalServices
            ];

            $token   = isw_get_item_meta($order_id, '_token');
            $sandbox = isw_get_item_meta($order_id, '_enviosimples_sandbox');

            $envio = new Es_Plugin_Woocommerce_API($token, $sandbox);

            $etiqueta = $envio->call_curl('POST', '/es-tickets/generate-ticket', $ticket);

            if (is_object($etiqueta)) {
                if (isset($etiqueta->data->ticket->code)) {
                    //update_post_meta($order_id, "{$meta_key}", $etiqueta->data->ticket->code.'');    
                    update_post_meta($order_id, "{$meta_key}", 'Emitida');
                } else {
                    update_post_meta($order_id, "{$meta_key}", 'Falha');
                }
            } else {
                //não faz nada 
            }
        }
    }

    public function isw_column_ticket_values($column)
    {
        global $post;

        $data = get_post_meta($post->ID, '_ticket_code', true) . '';

        //start editing, I was saving my fields for the orders as custom post meta
        //if you did the same, follow this code

        if ($column == 'isw_ticket') {
            echo $data;
        }
    }

    public function add_woocommerce_enviosimples($methods)
    {
        $methods['woocommerce_enviosimples'] = 'WC_woocommerce_enviosimples';
        return $methods;
    }

    public function enviosimples_enqueue_user_scripts()
    {
        wp_enqueue_script('auge_jquery_masks', plugins_url() . "/es-plugin-woocommerce/assets/jquery.mask.min.js", array(), 'custom', true);
        wp_enqueue_script('auge_jquery_mask_formats', plugins_url() . "/es-plugin-woocommerce/assets/auge_masks.js", array(), 'custom', true);
        wp_enqueue_script('enviosimples_scripts', plugins_url() . "/es-plugin-woocommerce/assets/enviosimples.js", array(), 'custom', true);
        return;
    }

    public function enviosimples_shipping_forecast_on_product_page()
    {
        global $woocommerce;
        if (!is_product()) return;

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

        $metodos_de_entrega = enviosimples_get_metodos_de_entrega($target_zip_code);



        if (count($metodos_de_entrega) == 0) return;

        for ($i = 0; $i < 20; $i++) {

            $metodo = $metodos_de_entrega[$i];

            if (is_object($metodo) && get_class($metodo) == "WC_woocommerce_enviosimples") {
                $enviosimples_class = $metodo;
                break;
            }
        }
        $enviosimples_class->forecast_shipping();
    }


    public function isw_column_ticket($columns)
    {
        $new_columns = (is_array($columns)) ? $columns : array();

        unset($new_columns['order_actions']);

        //edit this for your column(s)
        //all of your columns will be added before the actions column
        $new_columns['isw_ticket'] = 'Etiqueta';


        //stop editing
        $new_columns['order_actions'] = $columns['order_actions'];

        return $new_columns;
    }

    public function enviosimples_get_metodos_de_entrega($cep_destinatario)
    {


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

    public function enviosimples_is_cep_from_state($cep, $estado)
    {


        return true;
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

    public function isw_get_item_meta_id($order_item_id, $meta_key)
    {
        global $wpdb;
        $prefix     = $wpdb->prefix;

        $return = false;

        $sql = "SELECT itemmeta.meta_value AS value 
                 FROM  {$prefix}woocommerce_order_itemmeta itemmeta 
                WHERE  itemmeta.order_item_id ={$order_item_id}    AND 
                       itemmeta.meta_key = '{$meta_key}'";

        $rs = $wpdb->get_results($sql);
        foreach ($rs as $linha) {
            $return = $linha->value;
        }
        return $return;
    }

    public function isw_get_item_meta($order_id, $meta_key)
    {
        global $wpdb;

        $prefix     = $wpdb->prefix;

        $return = false;

        $sql = "SELECT itemmeta.meta_value AS value 
             FROM  {$prefix}woocommerce_order_items items, 
                   {$prefix}woocommerce_order_itemmeta itemmeta 
            WHERE  items.order_item_id = itemmeta.order_item_id AND 
                   items.order_item_type = 'shipping'           AND 
                   items.order_id    =  {$order_id}             AND 
                   itemmeta.meta_key = '{$meta_key}'";


        $rs = $wpdb->get_results($sql);
        foreach ($rs as $linha) {
            $return = $linha->value;
        }
        return $return;
    }

    public function woocommerce_enviosimples_init()
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
}
