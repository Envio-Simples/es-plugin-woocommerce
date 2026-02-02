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
    private $etiqueta;

    public function woocommerce_enviosimples_logger($message)
    {
        $log = wc_get_logger();
        $context = array('source' => 'envio_simples');
        $log->debug($message, $context);
        return;
    }

    public function add_custom_action_button_css() {
        $action_slug = "my_column";
        
     
        echo '<style>.wc-action-button-'.$action_slug.'::after { font-family: woocommerce !important; content: "\e029" !important; }</style>';
    }
    
    /*
    * Verficando se todos os campos vão ser aceitos pela API do envio simples
    * 
    * Arquivo onde o hook é inicializado : class-es-plugin-woocommerce.php
    * @hook 'woocommerce_after_checkout_validation'
    * @author Ecom <contato@ecomd.com.br>
    * @developer Gabriel
    */
    public function ecomd_checking_fields($fields, $errors){
          
          $nome = $fields['shipping_first_name'] . $fields['shipping_last_name'] ;
          if(strlen($nome) > 50){
              $errors->add( 'woocommerce_password_error', __( 'O nome inserido é grande demais, acima de 50 caracteres' ) );
          }
          
          $telefone = $fields['billing_phone'];
          $telefone = str_replace(" ", "", $telefone);
          $telefone = str_replace("-", "", $telefone);
          $telefone = str_replace(".", "", $telefone);
          $telefone = str_replace("(", "", $telefone);
          $telefone = str_replace(")", "", $telefone);
          
          if(strlen($telefone) > 11 || strlen($telefone) < 10){
              $errors->add( 'woocommerce_password_error', __( 'O telefone inserido é grande demais, acima de 11 caracteres ou menor do que 10' ) );
          }
          $rua = $fields['shipping_address_1'];
           if(strlen($rua) > 50){
              $errors->add( 'woocommerce_password_error', __('O endereço inserido é grande demais, acima de 50 caracteres' ) );
          }
           $complemento = $fields['shipping_address_2'];
           if(strlen($complemento) > 20){
              $errors->add( 'woocommerce_password_error', __( 'O campo de complemento inserido é grande demais, acima de 20 caracteres' ) );
          }
          $numero = $fields['shipping_number'];
           if(strlen($numero) > 5){
              $errors->add( 'woocommerce_password_error', __( 'O número inserido é grande demais, acima de 5 caracteres' ) );
          }
           
           $bairro = $fields['shipping_neighborhood'];
           if(strlen($bairro) > 30){
              $errors->add( 'woocommerce_password_error', __('O bairro inserido é grande demais, acima de 30 caracteres' ) );
          }
           $cidade = $fields['shipping_city'];
           if(strlen($cidade) > 30){
              $errors->add( 'woocommerce_password_error', __('A cidade inserida é grande demais, acima de 30 caracteres' ) );
          }
          
         //$errors->add( 'woocommerce_password_error', __( print_r($fields,true) ) );
    }
    
    public function isw_woo_update_ticket()
    {
        global $wpdb;
        global $woocommerce;
        global $post;
    
    
        //Busca a Etiqueta e atualiza o post meta_data
        
        $order_id = $_POST['order_id'];
      
        
        $meta_key = '_ticket_code';
        $ticket_code = get_post_meta($order_id, "{$meta_key}", true);
        
        
        $order = wc_get_order( $order_id );
		$order_data = $order->get_data();
        
        /*
        * Array que contêm os nomes dos produtos do pedido
        * @var array $name_product
        */
            $name_product = array();
            
            foreach ( $order->get_items() as $item_id => $item ) {
                // Get product object
                $product = $item->get_product();
				
                $product_name = $product->get_data()['name'];

                array_push($name_product, $product_name);
                    
            }
        
        if (!$ticket_code || $ticket_code == 'Tente novamente'){ //Se não existe gera uma etiqueta 

            
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
            
        

            // $declarationItens = [];
            
            // foreach ($rs as $linha) {
            //     $order_item_id = $linha->order_item_id;
            //     $quantidade = $this->isw_get_item_meta_id($order_item_id, '_qty');

            //         $item = get_the_title($this->isw_get_item_meta_id($order_item_id, '_product_id'));

            //         // Obter o ID do produto
            //         $product_id = $this->isw_get_item_meta_id($order_item_id, '_product_id');
                    
            //         // Carregar o produto WooCommerce
            //         $product = wc_get_product($product_id);
                    
            //         // Obter os atributos do produto
            //         $attributes = $product->get_attributes();
            //         $attributes_text = '';
                    
            //         foreach ($attributes as $attribute_name => $attribute) {
            //             if ($attribute->is_taxonomy()) {
            //                 // Atributos como taxonomia (ex: cor, tamanho)
            //                 $terms = wp_get_post_terms($product_id, $attribute->get_name(), array('fields' => 'names'));
            //                 $attributes_text .= ucfirst(wc_attribute_label($attribute->get_name())) . ': ' . implode(', ', $terms) . '; ';
            //             } else {
            //                 // Atributos customizados
            //                 $attributes_text .= ucfirst(wc_attribute_label($attribute->get_name())) . ': ' . implode(', ', $attribute->get_options()) . '; ';
            //             }
            //         }
                    
            //         // Limpar possíveis hífens do nome original
            //         $item = str_replace("-", "", $item);
                    
            //         // Concatenar os atributos ao nome do produto
            //         $item .= ' - ' . trim(rtrim($attributes_text, '; '));
                    
            //         $content = $name_product[$i] . ' e etc';

            //         $subtotal   = $this->isw_get_item_meta_id($order_item_id, '_line_subtotal');

            //         $quantidade = $this->isw_get_item_meta_id($order_item_id, '_qty');

            //         $value = $subtotal / $quantidade;

            //         $count = 1;

            //         $declarationItens[] = array('item' => "{$item}", 'value' => $value, 'count' => $quantidade);
            //    }

            $declarationItens = [];

            foreach ($rs as $linha) {
                // ID do item do pedido
                $order_item_id = $linha->order_item_id;
        
                // Obter metadados
                $product_id    = $this->isw_get_item_meta_id($order_item_id, '_product_id');
                $variation_id  = $this->isw_get_item_meta_id($order_item_id, '_variation_id');
                $quantidade    = $this->isw_get_item_meta_id($order_item_id, '_qty');
                $subtotal      = $this->isw_get_item_meta_id($order_item_id, '_line_subtotal');
            
                // Se houver variação, carregue a variação; caso contrário, carregue o produto pai
                if ($variation_id && $variation_id != 0) {
                    $product = wc_get_product($variation_id);
                } else {
                    $product = wc_get_product($product_id);
                }
        
                 // Obter o título do produto (ou variação) atual
                $item = get_the_title($product->get_id());
        
                // Preparar string para os atributos
                $attributes_text = '';
        
                // Se for mesmo uma variação, podemos usar get_variation_attributes()
                if ($product->is_type('variation')) {
                // Retorna algo como ['attribute_pa_cor' => 'preto', 'attribute_pa_tamanho' => 'm']
                    $variation_attributes = $product->get_variation_attributes();
            
                    foreach ($variation_attributes as $attr_name => $attr_value) {
                        // Ex.: $attr_name = 'attribute_pa_cor'; precisamos do label da taxonomia
                        $taxonomy = str_replace('attribute_', '', $attr_name);
                        $productTest = wc_get_product( $variation_id );
                        $label = $productTest ? wc_attribute_label( 'cor', $productTest ) : wc_attribute_label( 'cor' );
            
                        // Monta o texto: "Cor: Preto; "
                        $attributes_text .= ucfirst($label) . ': ' . ucfirst($attr_value) . '; ';
                    }
                } else {
                    // Produto simples ou produto pai
                    $attributes = $product->get_attributes();
            
                    foreach ($attributes as $attribute_name => $attribute) {
                        if ($attribute->is_taxonomy()) {
                            // Atributos que são taxonomias (ex.: cor, tamanho)
                            $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
                            $attributes_text .= ucfirst(wc_attribute_label($attribute->get_name())) . ': ' . implode(', ', $terms) . '; ';
                        } else {
                        // Atributos customizados
                        $attributes_text .= ucfirst(wc_attribute_label($attribute->get_name())) . ': ' . implode(', ', $attribute->get_options()) . '; ';
                        }
                    }
                }

                // Remover hífens do nome original (se houver)
                $item = str_replace('-', '', $item);
            
                // Concatenar os atributos ao nome do produto
                $item .= ' - ' . trim(rtrim($attributes_text, '; '));
            
                // (Opcional) Se quiser usar algo como $name_product[$i], ajuste conforme sua lógica
                // $content = $name_product[$i] . ' e etc';
            
                // Calcular valor unitário
                $value = ($quantidade != 0) ? ($subtotal / $quantidade) : 0;
            
                // Montar array de retorno
                $declarationItens[] = [
                    'item'  => $item,
                    'value' => $value,
                    // Se quiser que "count" seja a quantidade do item
                    'count' => $quantidade,
                ];
            }
      
            $type = get_post_meta($order_id, '_billing_persontype', true) != '1' ?   'legal-person':'physical-person';
            
            $name      = get_post_meta($order_id, '_shipping_first_name', true) . ' ' . get_post_meta($order_id, '_shipping_last_name', true);

            $document2 = $type == 'legal-person' ? get_post_meta($order_id, '_billing_cnpj', true)  :  get_post_meta($order_id, '_billing_cpf', true); //Claudio Sanches 
             
             //Atualização feita devido ao fato do metadata '_billing_persontype' ficar vazio, então forçamos o sistema a pegar um campo que não esteja vazio
            if(empty($document2)){
                $document2 = $type != 'legal-person' ? get_post_meta($order_id, '_billing_cnpj', true)  :  get_post_meta($order_id, '_billing_cpf', true); //Claudio Sanches 
                $type = $type == 'legal-person' ? 'physical-person' : 'legal-person'; 
                
            }
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
            
           // $street = "Rua Vicente Ferreira";
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
                'additionalServices' => $additionalServices,
                'typeEmission' => 'integration'
            ];
   
            $ticketData = [$ticket];


            //$token   = $this->isw_get_item_meta($order_id, '_token');
            $token = get_post_meta($order_id, '_token', true);
            if (empty($token)) {
              $token = $this->isw_get_item_meta($order_id, '_token'); // tenta no shipping itemmeta
            }
            $sandbox = $this->isw_get_item_meta($order_id, '_enviosimples_sandbox');

            $envio = new Es_Plugin_Woocommerce_API($token, $sandbox);


            $etiqueta = $envio->call_curl('POST', '/es-tickets/generate-ticketv2/'.$token.'', $ticketData);

            $url    = esc_url(''.$etiqueta->data->link.'');

            $data_button = get_post_meta($post->ID, 'button_ticket',true).'';

            $id = $order_id;

            if (is_object($etiqueta)) {

                // 201 = criado / emissão concluída
                if ($etiqueta->code == 201 && isset($etiqueta->data)) {

                    $data = $etiqueta->data;

                    // Sucesso: verifica se existe array "success" com itens
                    if (isset($data->success) && is_array($data->success) && count($data->success) > 0) {

                        $ticket_success = $data->success[0]; // primeiro (e único) item

                        // Garante que existe link
                        if (isset($ticket_success->link) && !empty($ticket_success->link)) {
                            $url = esc_url($ticket_success->link);

                            update_post_meta(
                                $order_id,
                                "{$meta_key}",
                                '<a href="' . $url . '" title="Clique aqui para imprimir a etiqueta da Envio Simples" target="_blank">Imprimir</a>'
                            );
                            update_post_meta($order_id, 'button_ticket', '');

                            // Opcional: salvar outros dados úteis
                            // update_post_meta($order_id, '_ticket_id', $ticket_success->id);
                            // update_post_meta($order_id, '_internal_code', $ticket_success->internalCode);
                            // update_post_meta($order_id, '_tracking_link', $ticket_success->trackingLink);
                        } else {
                            // Sucesso mas sem link → erro genérico
                            update_post_meta($order_id, "{$meta_key}", 'Tente novamente');
                            update_post_meta(
                                $order_id,
                                'button_ticket',
                                '<p><button class="button getTicket" id="ticketButton" value="' . $id . '">Gerar Etiqueta</button></p>'
                            );
                        }

                    } 
                    // Verifica se há falhas
                    elseif (isset($data->failures) && is_array($data->failures) && count($data->failures) > 0) {

                        $failure = $data->failures[0]; // primeiro erro

                        // Identifica o tipo de erro
                        $errorCode = isset($failure->error) ? $failure->error : (isset($failure->code) ? $failure->code : null);

                        // Caso específico: ticket já existe
                        if ($errorCode === 'ticket_exist') {

                            // Se retornar link no erro, usa ele
                            $url = isset($failure->link) ? esc_url($failure->link) : '';

                            if (!empty($url)) {
                                update_post_meta(
                                    $order_id,
                                    "{$meta_key}",
                                    '<a href="' . $url . '" title="Clique aqui para imprimir a etiqueta da Envio Simples" target="_blank">Imprimir</a>'
                                );
                                update_post_meta($order_id, 'button_ticket', '');
                            } else {
                                // Ticket existe mas sem link → erro genérico
                                update_post_meta($order_id, "{$meta_key}", 'Tente novamente');
                                update_post_meta(
                                    $order_id,
                                    'button_ticket',
                                    '<p><button class="button getTicket" id="ticketButton" value="' . $id . '">Gerar Etiqueta</button></p>'
                                );
                            }

                        } else {
                            // Qualquer outro erro
                            update_post_meta($order_id, "{$meta_key}", 'Tente novamente');
                            update_post_meta(
                                $order_id,
                                'button_ticket',
                                '<p><button class="button getTicket" id="ticketButton" value="' . $id . '">Gerar Etiqueta</button></p>'
                            );
                        }

                    } else {
                        // 201 mas sem success nem failures → situação inesperada
                        update_post_meta($order_id, "{$meta_key}", 'Tente novamente');
                        update_post_meta(
                            $order_id,
                            'button_ticket',
                            '<p><button class="button getTicket" id="ticketButton" value="' . $id . '">Gerar Etiqueta</button></p>'
                        );
                    }

                } else {
                    // Código diferente de 201 (ex: 422, 400, 500)
                    $data = isset($etiqueta->data) ? $etiqueta->data : null;

                    // Tenta buscar failures
                    if ($data && isset($data->failures) && is_array($data->failures) && count($data->failures) > 0) {

                        $failure = $data->failures[0];
                        $errorCode = isset($failure->error) ? $failure->error : (isset($failure->code) ? $failure->code : null);

                        // ticket_exist
                        if ($errorCode === 'ticket_exist') {
                            $url = isset($failure->link) ? esc_url($failure->link) : '';

                            if (!empty($url)) {
                                update_post_meta(
                                    $order_id,
                                    "{$meta_key}",
                                    '<a href="' . $url . '" title="Clique aqui para imprimir a etiqueta da Envio Simples" target="_blank">Imprimir</a>'
                                );
                                update_post_meta($order_id, 'button_ticket', '');
                            } else {
                                update_post_meta($order_id, "{$meta_key}", 'Tente novamente');
                                update_post_meta(
                                    $order_id,
                                    'button_ticket',
                                    '<p><button class="button getTicket" id="ticketButton" value="' . $id . '">Gerar Etiqueta</button></p>'
                                );
                            }
                        } else {
                            // Outro erro
                            update_post_meta($order_id, "{$meta_key}", 'Tente novamente');
                            update_post_meta(
                                $order_id,
                                'button_ticket',
                                '<p><button class="button getTicket" id="ticketButton" value="' . $id . '">Gerar Etiqueta</button></p>'
                            );
                        }

                    } else {
                        // Erro sem detalhes → genérico
                        update_post_meta($order_id, "{$meta_key}", 'Tente novamente');
                        update_post_meta(
                            $order_id,
                            'button_ticket',
                            '<p><button class="button getTicket" id="ticketButton" value="' . $id . '">Gerar Etiqueta</button></p>'
                        );
                    }
                }

            } else {
                // Resposta não é um objeto → não faz nada
            }
        }
            
    }

    // Function para adicionar o botão de "Gerar Etiqueta" toda vez que um pedido alterar o status para "Novo pedido" ou "Completo"
    public function button_generate($order_id){

        global $post;
        
        $data_button = get_post_meta($post->ID, 'button_ticket',true).'';
        $id = $order_id;
       
        update_post_meta($order_id,'button_ticket', '<p><button class="button getTicket" id="ticketButton" value="'.$id.'">Gerar Etiqueta</button></p>');

    }

    // Function para adicionar a coluna customizada na página de pedidos

     public function add_example_column_contents( $column, $post_id ) {

        global $post;
        
        //start editing, I was saving my fields for the orders as custom post meta
        $data = get_post_meta($post->ID, '_ticket_code', true) . '';
        $data_button = get_post_meta($post->ID, 'button_ticket',true).'';
      
     
        //if you did the same, follow this code

        if ($column == 'isw_ticket') {
                
        echo $data;
        echo $data_button;
    
    	} 
    }
        
    //Function para chamar a função de gerar etiqueta ao clicar no botão em cada pedido.
    public function get_ticket($order_id){
        
        echo "<script>  
               
        jQuery(document).ready(function(){
		
		        if (!localStorage.getItem('reload')) {     
                localStorage.setItem('reload', 'true');
                location.reload();
            }
            else {
                localStorage.removeItem('reload');
            }

        jQuery('.getTicket').click(function(element){
            
        	jQuery.ajax({
                  method: 'POST',
                  url: '/wp-admin/admin-ajax.php',
                  data: {
				  	'action': 'isw_woo_update_ticket',
				  	'order_id': element.target.value
				  },
                  success: function(data) {
                   location.reload(true);
                  }
                  
                });
    })

});
       </script>";
    
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

        $new_columns['isw_ticket'] = 'Envio Simples';

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
