<?php 
class enviosimples{
    protected $zipCodeOrigin;
    protected $zipCodeDestiny;
    protected $valueDeclared; 
    protected $reverse; 
    protected $key; //f446202be53jNHwbSXKWwRq6U32WIOecK5ddefa3419f
    protected $volumes = array();
    
    protected $log_isw = true;
   
	 private $enviosimples_url = "";

    private $enviosimples_production_url = "https://api2.enviosimples.com.br";
    private $enviosimples_sandbox_url    = "https://sandbox-api2.enviosimples.com.br";
    private $esAppKey = "72jyDaLhTegEBrj9UCdJwO3cAGfbqvFK";

	public function __construct($key = '', $sandbox = 'no'){
      
		$this->key = $key;    
        
     if ($sandbox=='yes') 
         $this->enviosimples_url = $this->enviosimples_sandbox_url; 
     else 
         $this->enviosimples_url = $this->enviosimples_production_url;
	}
   
	public function call_curl($type,$url,$parameters){	
        $headers = [
            'Content-Type: application/json',
            'Accept-Encoding: gzip, deflate',
            'es-app-key: 72jyDaLhTegEBrj9UCdJwO3cAGfbqvFK'
        ];	

        if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$headers',$headers);}
        
      
        $params   = json_encode($parameters);	   if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$params',$params);}

        $curl_url = $this->enviosimples_url.$url;		
        
        $ch = curl_init();
           curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
           curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
           curl_setopt($ch, CURLOPT_POST, 1);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
           curl_setopt($ch, CURLOPT_TIMEOUT,30);
           curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
           curl_setopt($ch, CURLOPT_POSTFIELDS, $params);		
           curl_setopt($ch, CURLOPT_HEADER, false);		
           curl_setopt($ch, CURLOPT_URL,$curl_url);
           curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
           curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        
           $return = curl_exec($ch);
        
           $status = curl_getinfo($ch,CURLINFO_HTTP_CODE);

           if ($status==0) {
              if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$return',$return);}
              if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$status',$status);}
              if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','return',['error'=>1, 'message' => 'Não foi possível conectar-se com o Envio Simples. Tente novamente mais tarde']);}

               return (object)['error'=>1, 'message' => 'Não foi possível conectar-se com o Envio Simples. Tente novamente mais tarde'];
           }

           if ($status > 400){
                   if ($status==401) {
                        if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$return',$return);}
                        if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$status',$status);}
                        if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','return',['error'=>401, 'message' => 'Acesso não autorizado. Verifique se o seu token foi preenchido corretamente ou fale com a Envio Simples']);}
                        
                        return (object) ['error'=>401, 'message' => 'Acesso não autorizado. Verifique se o seu token foi preenchido corretamente ou fale com a Envio Simples'];
                   }
                   
                   $message = curl_error($ch);
                   curl_close($ch);                  

                   if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$return',$return);}
                   if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$status',$status);}
                   if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','return',['error'=>$status.'', 'message' => $message]);}

                   return (object)['error'=>$status.'', 'message' => $message];                            
           }

           $return_decode = json_decode($return);

           if (isset($return_decode->data->error)){            
                  if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$return_decode',$return_decode);}
                  if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$return',['error'=>$return_decode->data->error.'', 'message'=>$return_decode->data->message]);}
                  
                  return (object)(['error'=>$return_decode->data->error.'', 'message'=>$return_decode->data->message]);                            
           }              

        curl_close($ch);	
        
        if($this->log_isw){ $this->isw_log_envios('call_curl($type,$url,$parameters)','$return_decode',$return_decode);}
        
        return $return_decode; //Objetos Json 
	}
 
   public function calculate_shipping($zipCodeOrigin,$zipCodeDestiny,$valueDeclared,$reverse='false'){
      $data = [
         'zipCodeOrigin'  => "{$zipCodeOrigin}",       
         'zipCodeDestiny' => "{$zipCodeDestiny}",       
         'valueDeclared'  => $valueDeclared,    
         'reverse'        => "{$reverse}",
         'volumes'        => $this->volumes     
      ]; 

      
      //print_r($data); exit;
      
      if(trim($this->key) <> ""){
         $data['key'] = $this->key;
      }

      $return = $this->call_curl('POST','/es-calculator/calculator-v2',$data);          
            
      if (isset($return->error)){
         return;
      }

      $calculatorId = $return->data->calculatorId;
      
      $rates = array();
      
      foreach($return->data->prices->valid as $rate){
         $rates['rate'][] = $rate;
      }
      
      if($this->log_isw){ $this->isw_log_envios('calculate_shipping','$rates',$rates);}
      
      if(count($rates) > 0 ){
         $rates['calculatorId'] = $return->data->calculatorId;
      }else{
         return;
      }

      return $rates;
   }
    
    //public function set_nDocEmit ($valor){ $this->nDocEmit  = $valor; return $this; } 
    
    public function set_cDestCalc($valor){ 
    
        /****Buscando código do IBGE****/
        $valor = str_replace(".","",$valor);
        $valor = str_replace(".","",$valor);
        $valor = str_replace(".","",$valor);
        $valor = str_replace("-","",$valor);
        $valor = str_replace("-","",$valor);
        
        $url = "viacep.com.br/ws/{$valor}/json/";
        $ch = curl_init($url);
        $request_headers = array('Content-Type:application/json'); 
        //curl 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HTTPGET, true);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Execute the POST request
        $result = curl_exec($ch);
        // Close cURL resource
        curl_close($ch);
        $rs = json_decode($result);    
        
        $codigoIbgeCobranca = false; // Palhoça SC
        
        if(isset($rs->ibge)){
            $codigoIbgeCobranca = $rs->ibge;
        }    
        
        $this->cDestCalc = $codigoIbgeCobranca; 
        
        return $this; 
    }   
            
    public function addVolumes($valor){ array_push($this->volumes,$valor); return $this; }    
    
    public function getVolumes()
    {
        return $this->volumes;
    }    
    
    public function send_labels($label_data){

        $return = $this->call_curl('POST','/es-api/tickets',$label_data);
        return (object)$return;
    }

	private function arrayToParams($array, $prefix = null){
        if (!is_array($array)) {
            return $array;
        }
        $params = [];
        foreach ($array as $k => $v) {
            if (is_null($v)) {
                continue;
            }
            if ($prefix && $k && !is_int($k)) {
                $k = $prefix.'['.$k.']';
            } elseif ($prefix) {
                $k = $prefix.'[]';
            }
            if (is_array($v)) {
                $params[] = self::arrayToParams($v, $k);
            } else {
                $params[] = $k.'='.urlencode((string)$v);
            }
        }
        return implode('&', $params);
    }	

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return mixed
     */
    public function getSourceZipCode()
    {
        return $this->source_zip_code;
    }

    /**
     * @param mixed $source_zip_code
     *
     * @return self
     */
    public function setSourceZipCode($source_zip_code)
    {
        $this->source_zip_code = $this->only_numbers($source_zip_code);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTargetZipCode()
    {
        return $this->target_zip_code;
    }

    /**
     * @param mixed $target_zip_code
     *
     * @return self
     */
    public function setTargetZipCode($target_zip_code)
    {
        $this->target_zip_code = $this->only_numbers($target_zip_code);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param mixed $weight
     *
     * @return self
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRates()
    {

        $rates = [];

        foreach($this->rates as $rate){
            //$rates[] = $rate;
            if ($rate->disabled!='false') $rates[] = $rate;
        }

        return $rates;
    }

    /**
     * @param mixed $rates
     *
     * @return self
     */
    public function setRates($rates)
    {
        $this->rates = $rates;

        return $this;
    }

    public function setPackageValue($value){
    	$this->package_value = $value;
    }

    public function setHeight($height){
    	$this->height = $height;
    }

    public function setWidth($width){
    	$this->width = $width;
    }

    public function setLength($length){
    	$this->length = $length;
    }

    private function only_numbers($val){
    	return preg_replace("/[^0-9]/","",$val);
    }
    
    //DEBUG
   public function isw_log_envios($func = '',$variavel='', $conteudo=''){
      ob_start();
         var_dump($conteudo);
         $content = ob_get_contents();
      ob_end_clean();

      $path = plugin_dir_path(__FILE__);
      
      $fp1 = fopen($path.'log_envios.log', "a");

      fwrite($fp1, $func . " - " . $variavel . "\n" . $content ."\n\n");

      fclose($fp1);
   }    
}

?>