<?php
  
class Model_Utils_GoogleLogin
{

  public $error = '';
  
  private $returnUrl = '';
  private $ax = array();
  private $endpoint = '';
  private $attempts = 0;
  
  const URL_DISCOVERY = 'https://www.google.com/accounts/o8/id';
  const URL_ENDPOINT = 'https://www.google.com/accounts/o8/ud';

  
  public function __construct($returnUrl)
  {

    if (!function_exists('curl_init'))
    {
      throw new Exception('curl library not enabled');
    }
    
    $this->error = '';
    $this->returnUrl = $this->getParamUrl($returnUrl, true);
    $this->endpoint = self::URL_ENDPOINT;
    $this->init();
    
  } // constructor

  
  public function getLoginUrl()
  {

    $this->attempts = 0;
    
    $params = $this->getParamsLogin();
    return $this->getLoginUrlWork($params);
      
  } // getLoginUrl
  
  
  public function getLoginResponse($response)
  {

    $this->attempts = 0;
    
    if (!isset($response['openid_mode']) || $response['openid_mode'] !== 'id_res')
    {
      return;
    }
    
    if ($this->returnUrl !== $response['openid_return_to'])
    {
      return;
    }
    
    $params = $this->getParamsValidate($response, $attributes);
    
    if ($this->validate($params))
    {
      return $this->checkAttributes($attributes);
    }
              
  } // getLoginResponse
  
  
  private function init()
  {
  
    $this->ax = array();
    
    $this->ax['email'] = 'http://axschema.org/contact/email';
    $this->ax['firstname'] = 'http://axschema.org/namePerson/first';
    $this->ax['lastname'] = 'http://axschema.org/namePerson/last';
      
  } // init
  
  
  private function getParamsLogin()
  {

    $ar = array();
    $ar['openid.ns'] = 'http://specs.openid.net/auth/2.0';
    $ar['openid.mode'] = 'checkid_setup';
    $ar['openid.return_to'] = $this->returnUrl;
    $ar['openid.realm'] = $this->getParamUrl($this->returnUrl, false);
    $ar['openid.claimed_id'] = 'http://specs.openid.net/auth/2.0/identifier_select';
    $ar['openid.identity'] = 'http://specs.openid.net/auth/2.0/identifier_select';
        
    $ar['openid.ns.ax'] = 'http://openid.net/srv/ax/1.0';
    $ar['openid.ax.mode'] = 'fetch_request';
    
    $required = array();
    
    foreach ($this->ax as $key => $schema)
    {
    
      $required[] = $key;
      $ar['openid.ax.type.' . $key] = $schema;
    }
    
    $ar['openid.ax.required'] = implode(',', $required);
        
    $ar['openid.ns.pape'] = 'http://specs.openid.net/extensions/pape/1.0';
    $ar['openid.pape.max_auth_age'] = '0';
    
    $ar['hl'] = 'en';  
  
    return $ar;
  
  } // getParamsLogin
  
  
  private function getParamUrl($urlIn, $forReturn)
  {

    $parts = parse_url($urlIn);
    
    $url = isset($parts['scheme']) ? $parts['scheme'] : 'http';
    $url .= '://';
    $url .= isset($parts['host']) ? $parts['host'] : $_SERVER['HTTP_HOST'];
    
    if (!$forReturn || !isset($parts['path']))
    {
      return $url;
    }
    
    if (strpos($parts['path'], '/') === 0)
    {
      $url .= $parts['path'];
    }
    else
    {
      $url .= '/' . $parts['path'];
    }
    
    
    if (isset($parts['query']))
    {
      $url .= '?' . $parts['query'];
    }
    
    return $url;    
  
  } // getParamUrl
  
  
  private function getParamsValidate($response, &$attributes)
  {

    $ar = array();
    $ar['openid.ns'] = 'http://specs.openid.net/auth/2.0';
    $ar['openid.mode'] = 'check_authentication';
    $ar['openid.assoc_handle'] = $response['openid_assoc_handle'];
    $ar['openid.signed'] = $response['openid_signed'];
    $ar['openid.sig'] = $response['openid_sig'];    
    
    // get alias for attributes
    $alias = $this->getAlias($response);
    $attributes = array();
        
    $magic = get_magic_quotes_gpc();
    $signed = explode(',', $response['openid_signed']);
    
    foreach ($signed as $item)
    {
      
      $key = str_replace('.', '_', $item); 
      $value = $response['openid_' . $key];
      $ar['openid.' . $item] = $magic ? stripslashes($value) : $value;
      
      $this->setAttribute($item, $alias, $response, $attributes);      
    }
    
    return $ar;
      
  } // getParamsValidate
  
  
  private function getAlias($response)
  {

    $alias = '';
        
    foreach ($response as $key => $value)
    {
    
      if (strpos($key, 'openid_ns_') === 0 && $value == 'http://openid.net/srv/ax/1.0')
      {
        $alias = substr($key, strlen('openid_ns_'));
        break;
      }
        
    }
    
    return $alias;  
  
  } // getAlias
  
  
  private function setAttribute($signedkey, $alias, $response, &$attributes)
  {

    $keyMatch = $alias . '.value.';
    
    if (substr($signedkey, 0, strlen($keyMatch)) !== $keyMatch)
    {
      return;
    }
    
    $key = substr($signedkey, strlen($keyMatch));
    
    $value = $response['openid_' . $alias . '_value_' . $key];
    $attributes[$key] = $value;  
  
  } // setAttribute
   
  
  private function getLoginUrlWork($params)
  {

    $this->attempts += 1;
    
    if ($this->attempts > 2)
    {
      return;
    }
    
    if (!$response = $this->request($params, false, $httpCode))
    {
      return;
    }
        
    if ($httpCode === 404)
    {
      $this->setEndpoint();
      return $this->getLoginUrlWork($params);
    }
    else if ($httpCode !== 302)
    {
      $this->error = 'Unexpected Http response: ' . $httpCode;
      return;
    }
    
    $location = '';
    $headers = explode("\r\n", substr($response, 0, strpos($response, "\r\n\r\n")));
    
    foreach ($headers as $header)
    {
      
      $pos = strpos($header, ':');
      
      if ($pos !== false)
      {
        
        $name = strtolower(trim(substr($header, 0, $pos)));
        
        if ($name === 'location')
        {
          $location = trim(substr($header, $pos + 1));
          break; 
        }
       
      }
          
    }
    
    if (!$location)
    {
      $this->error = 'Redirect location not found in headers';
    }
    
    return $location;      
   
  } // getLoginUrlWork
  
  
  private function request($params, $follow, &$httpCode)
  {

    $httpCode = 0;
    $params = http_build_query($params, '', '&');
    
    $curl = curl_init($this->endpoint);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $follow);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    
    if (!$response = curl_exec($curl))
    {
      $this->error = curl_error($curl); 
      return;
    }
        
    $httpCode = intval(curl_getinfo($curl, CURLINFO_HTTP_CODE));
    return $response;
  
  } // request
  
  
  private function setEndpoint()
  {
            
    if ($doc = simplexml_load_file(self::URL_DISCOVERY))
    {
      $this->endpoint = $doc->XRD->Service->URI[0];
    }
    
    $error = "Google endpoint has changed from '" . self::URL_ENDPOINT;
    $error .= "' to '" . $this->endpoint . "'";
    trigger_error($error);    
               
  } // setEndpoint
  
  
  private function validate($params)
  {
  
    $this->attempts += 1;
    
    if ($this->attempts > 2)
    {
      return;
    }
    
    if (!$response = $this->request($params, true, $httpCode))
    {
      return;
    }
        
    if ($httpCode === 404)
    {
      $this->setEndpoint();
      return $this->validate($params);
    }
    else if ($httpCode !== 200)
    {
      $this->error = 'Unexpected Http response: ' . $httpCode;
      return;
    }
    
    return preg_match('/is_valid\s*:\s*true/i', $response);    
               
  } // validate
  
  
  private function checkAttributes($attributes)
  {
  
    foreach ($this->ax as $key => $dummy)
    {
    
      if (!isset($attributes[$key]) || !$attributes[$key])
      {
        return;
      }    
    }
    
    return $attributes;
  
  } // checkAttributes
  

} // end class Model_Utils_GoogleLogin

?>
