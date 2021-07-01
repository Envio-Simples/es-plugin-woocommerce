<?php

require_once "class-es-plugin-woocommerce-api.php";
require_once "class-es-plugin-woocommerce-simples.php";

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

            $calculatorId = $this->isw_get_item_meta($order_id, '_calculatorId');
            $content      = 'PRODUTOS';
            $alias        = $this->isw_get_item_meta($order_id, '_type_send');
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
                $quantidade = $this->isw_get_item_meta_id($order_item_id, '_qty');

                for ($i = 0; $i < (int)$quantidade; $i++) {
                    $item = get_the_title($this->isw_get_item_meta_id($order_item_id, '_product_id'));

                    $content = $item . ' e etc';

                    $subtotal   = $this->isw_get_item_meta_id($order_item_id, '_line_subtotal');

                    $quantidade = $this->isw_get_item_meta_id($order_item_id, '_qty');

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

            $token   = $this->isw_get_item_meta($order_id, '_token');
            $sandbox = $this->isw_get_item_meta($order_id, '_enviosimples_sandbox');

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
        wp_enqueue_script('auge_jquery_masks', WC_ENVIOSIMPLES_URL . "public/js/jquery.mask.min.js", array(), 'custom', true);
        wp_enqueue_script('auge_jquery_mask_formats', WC_ENVIOSIMPLES_URL . "public/js/auge_masks.js", array(), 'custom', true);
        wp_enqueue_script('enviosimples_scripts', WC_ENVIOSIMPLES_URL . "public/js/enviosimples.js", array(), 'custom', true);
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

        $metodos_de_entrega = $this->enviosimples_get_metodos_de_entrega($target_zip_code);
        
        

        if (count($metodos_de_entrega) == 0) return;

        foreach($metodos_de_entrega as $k=>$v)
        {
            $metodo = $v;
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
                            if ($this->enviosimples_is_cep_from_state($cep_destinatario, $tmp[1])) {
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

}