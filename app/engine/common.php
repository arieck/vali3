<?php


class Engine_Route
{
  
  public $cmd = '';
  public $page = '';
  public $input = array();
  public $xhr = false;
  public $api = false;
  public $history = array();
  public $error = 0;
  
  private $urlErrAccess = '';
  private $urlErrMissing = '';
  private $urlErrSite = '';
      
  private $internalError = false;
  private $routeFunc = '';
  private $errorFunc = '';
  private $errorCount = 0;
  
  public function __construct($routeFunc, $errorFunc = null)
  {
     
    $this->routeFunc = $routeFunc;
    $this->errorFunc = $errorFunc;
        
  } // contructor
             
  
  public function route()
  {
    
    $this->doRouteCallback($className, $classFile);
    
    if (!file_exists($classFile))
    {
      $this->error = cn::ERR_ROUTE;
      $this->doError();    
    }
        
    try
    {
      $ViewModel = new $className();
    }
    catch(Exception $e)
    {
      Fx::logException($e);
      $this->error = cn::ERR_INTERNAL;
      $this->doError();
    }
    
    if (!is_callable(array($ViewModel, 'execute')))
    {
      $this->error = cn::ERR_INTERNAL;
      $this->doError();    
    }
          
    $ViewModel->execute();     
  
  } // route
  
  
  public function reRoute($cmd = '')
  {

    /*
      sends to a new viewmodel
    */
    
    $this->history[] = $this->cmd;
    $this->cmd = $cmd;
    $this->route();
  
  } // reRoute
  
  
  public function  inHistory($cmd)
  {
  
    return in_array($cmd, $this->history);
  
  } // inHistory
  
  
  public function doError()
  {
    
    // we use errorCount in case we are redirecting in a loop
    $this->errorCount += 1;
    
    if ($this->errorCount === 2)
    {
      trigger_error('Route error count = 2, maybe we are looping redirects');
      Fx::outputError(Fx::ERROR_SERVER);
    }
    
    $this->doErrorCallback();
    
    // if we get here we are not handled. We only output errors
        
    if ($this->xhr)
    {
      $errorCode = Fx::ERROR_UNAUTHORIZED;
    }
    else if ($this->api)
    {
      $errorCode = Fx::ERROR_SERVER;
    }
    else
    {
      
      if ($this->error === cn::ERR_INTERNAL)
      {
        $errorCode = Fx::ERROR_SERVER;
      }
      else
      {
        $errorCode = Fx::ERROR_NOT_FOUND;
      }
      
    }
    
    // deal with access errors first     
    if ($this->error === cn::ERR_ACCESS)
    {
      // we must log the user out
      fn::logout();
    }
    
    Fx::outputError($errorCode);
                            
  } // doError
  
  
  private function doRouteCallback(&$class, &$file)
  {
    
    $class = '';
    $file = '';
        
    $args = array($this);
    if ($ar = Fx::callFunction(array($this->routeFunc, $args)))
    {
      list($class, $file) = $ar;
    }
    
  } // doRouteCallback
  
  
  private function doErrorCallback()
  {
    
    if ($this->errorFunc)
    {
      $args = array($this);
      Fx::callFunction(array($this->errorFunc, $args));
      exit;    
    }
        
  } // doErrorCallback  
      
  
} // end class Engine_Route


class Engine_Error
{

  public $name = '';
  public $msg = '';
  public $value = '';

  
  public function __construct($name = null, $msg = null, $value = null)
  {
  
    $this->name = !is_null($name) ? $name : '';
    $this->msg = !is_null($msg) ? $msg : '';
    $this->value = !is_null($value) ? $value : '';
        
  } // contructor
  

} // end class Engine_Error


class Engine_Result
{

  /** @var object */
  public $Rec = null;
  
  /** @var array */
  public $items = array();
  
  /** @var Engine_Error[] */
  public $errors = array();
  
  /** @var array */
  public $errLines = array();
  
  /** @var string */
  public $content = '';
  
  /** @var array */
  public $includes = array();
  
  
  public function __construct($Rec = null)
  {

    if (is_object($Rec))
    {
      Fx::objSet($this->Rec, $Rec);
    }
    else
    {
      $this->Rec = new stdClass();
    }
        
  } // contructor
      
  
  public function addError($name, $msg = '', $value = '')
  {
    
    $this->errors[] = new Engine_Error($name, $msg, $value);
           
  } // addError
  
  
} // end class Engine_Result

  
class cn
{

  // a class for shared constants
                 
  const PM_VIEW = 'vm';
  
  const PGP_APP = 'index.php';
  
  // cookie name constants
  const CK_SESS_NAME = 'valisid';
                                         
  // general form param values 
  const PM_CMD = 'cmd';
  const PM_CID = 'cid';
  const PM_XHR = 'xhr';
  const PM_JSN = 'pjn';
    
  // standard page constants
  const PG_HOME = 'index';
  const PG_CONTACT = 'contact';
  const PG_SIGN_IN = 'signin';
  const PG_SIGN_OUT = 'signout';
  const PG_ERR_ACCESS = 'access';
  const PG_ERR_SITE = 'error';
  const PG_ERR_MISSING = 'missing';
  
  // route error constants
  const ERR_INTERNAL = 1;
  const ERR_ACCESS = 2;
  const ERR_ROUTE = 3;
                       
} // end class cn


class fn
{

  public static function divClear()
  {
  
    echo '<div class="clear"></div>', PHP_EOL;
  
  } // divClear
  
  
  public static function divClose()
  {
  
    echo '</div>', PHP_EOL;
  
  } // divClose
  

  public static function divOpen($id, $class, $style = '')
  {
  
    $id = $id ? 'id="' . $id . '" ' : '';
    $class = $class ? 'class="' . $class . '" ' : '';
    $style = $style ? 'style="' . $style . '" ' : '';
    
    echo '<div ', $id, $class, $style, '>', PHP_EOL;
    
  } // divOpen
  
  
  public static function getPageHref($page, $caption = '')
  {
  
    $baseUrl = Fx::getPath(Fx::PATH_WEB, cn::PGP_APP);
    
    $params = array(
      'page' => $page,
      'caption' => $caption
    );
    
    return self::getHrefHtml($baseUrl, $params);
      
  } // getPageHref
   
  
  public static function getHrefHtml($baseUrl, $params = array())
  {
        
    $page = Fx::varSet($params, 'page', cn::PG_HOME);
    $caption = Fx::varSet($params, 'caption', $page);
    $title = Fx::varSet($params, 'title', '');
    
    if ($id = Fx::varSet($params, 'id', ''))
    { 
      $id = " id='{$id}'";
    }
    
    if ($class = Fx::varSet($params, 'class', ''))
    {
      $class = " class='{$class}'";
    }
        
    $fragment = Fx::varSet($params, 'fragment', '');
    
    $url = self::getDisplayUrl($baseUrl, array('page' => $page));
    
    if ($fragment)
    {
      $url .= '#' . $fragment;
    }
    
    return "<a href='{$url}' title='{$title}'{$id}{$class}>{$caption}</a>"; 
      
  } // getHrefHtml
  
  
  public static function getPageUrl($page = '', $params = null)
  {
  
    $url = Fx::getPath(Fx::PATH_WEB, cn::PGP_APP);
    
    if (!$page)
    {
      $page = cn::PG_HOME;
    }
    
    if (!is_array($params))
    {
      $params = array();
    }
    
    $params['page'] = $page;
    
    return self::getDisplayUrl($url, $params);
      
  } // getPageUrl
  
  
  public static function getLastPageUrl()
  {

    $lastPage = Fx::get('sess') ? Model_Session::getToken('lastPage') : '';
    
    $invalid = array(
      cn::PG_SIGN_IN,
      cn::PG_SIGN_OUT,
      cn::PG_ERR_ACCESS,
      cn::PG_ERR_SITE,
      cn::PG_ERR_MISSING      
    );
    
    if (in_array($lastPage, $invalid))
    {
      $lastPage = '';
    }
    
    return self::getPageUrl($lastPage);
    
  } // getLastPageUrl
  
  
  public static function getDisplayUrl($url, $arParams = null)
  {
          
    if (basename($url) !== cn::PGP_APP)
    {
      throw new InvalidArgumentException('getDisplayUrl');
    }
    
    // if $arParams is null, or page is not a member, we use the main page           
    if (is_null($arParams) || !isset($arParams['page']))
    {
      $page = cn::PG_HOME;
    }
    else
    {
      $page = $arParams['page'];
      unset($arParams['page']); 
    }
               
    $replace = $page . '.html';
    $url = str_replace(cn::PGP_APP, $replace, $url);
    
    return fn::getRawUrl($url, $arParams);  
      
  } // getDisplayUrl
  
  
  public static function getRawUrl($host, $params = null)
  {
  
    $qs = '';
              
    if (is_array($params))
    {
      $qs = http_build_query($params, '', '&');
    }
    elseif (is_string($params))
    {
      $qs = $params;
    }
    
    if ($qs)
    {
      $qs = '?' . $qs;
    }
           
    return $host . $qs;
  
  } // getRawUrl 
  
  
  public static function getUniqueId()
  {
   
    return md5(uniqid(rand(), true));
  
  } // getUniqueId
  
  
  public static function loggedIn()
  {
    
    if (Fx::get('sess'))
    {
      return Model_Session::getToken('level') > 0;
    }
                   
  } // loggedIn
  
  
  public static function logout($url = '')
  {                                          
        
    Model_Session::logout();
             
    if ($url)
    {
      Fx::redirect($url);   
    }
          
  } // logout
  
  
  public static function simpleDecrypt($value, $key)
  {
    
    self::checkCryptKey($key);
    $bin = pack('H' . strlen($value), $value);
    $raw = mcrypt_decrypt(MCRYPT_BLOWFISH, $key, $bin, MCRYPT_MODE_ECB);
    // important to strip any null padding
    return rtrim($raw, "\x00");
  
  } // simpleDecrypt
    
  
  public static function simpleEncrypt($value, $key)
  {
    
    self::checkCryptKey($key);
    // any padding is default null bytes
    $bin = mcrypt_encrypt(MCRYPT_BLOWFISH, $key, $value, MCRYPT_MODE_ECB);
    return bin2hex($bin);
  
  } // simpleEncrypt  
  
  
  private static function checkCryptKey(&$key)
  {
    
    if (!$key)
    {
      throw new InvalidArgumentException('checkCryptKey');
    }
    
    if (strlen($key) > 56)
    {
      $key = substr($key, 0, 56);
    }
          
  } // checkCryptKey
  

} // end class fn


class Record_Login
{

  public $firstName = '';
  public $lastName = '';
  public $email = '';
  public $userId = 0;
  public $level = 0;
  public $impersonator = 0;
    
}


class Record_Login_Update
{

  public $level = 0;
      
} 

class Record_Modal
{
  public $msgLines = array();
  public $url = '';
}

?>
