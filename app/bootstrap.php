<?php

ob_start();

include('system/fx.php');

AppBase::run();   
  
class AppBase
{

  // dont think this is used
  public static $data;
  
  private static $stdPages = array();
  
  public static function run()
  {
    
    self::init();
    
    if (self::isApi())
    {
      
      require Fx::getPath('API', 'vali-igc.php');
      $Vali = new Vali();
      $Vali->execute();
      
    }
    else
    {
      
      $routeFunc = array(__CLASS__, 'getRoute');
      $errorFunc = array(__CLASS__, 'routeError');
                  
      $Route = new Engine_Route($routeFunc, $errorFunc);
      $Route->api = false;
      
      $RouterMain = new Router_Main($Route);
      $RouterMain->execute();
          
    }
            
  } // run

  
  public static function autoload($className)
  {
    
    $classFile = strtolower(str_replace('_', '/', $className)) . '.class.php';
    require($classFile);
      
  } // autoload

  
  public static function displayError($className)
  {
  
    echo 'Sorry, but there has been an error';
  
  
  } // displayError
  
  
  public static function getRoute(Engine_Route $Route)
  {

    $route = '';
    
    if ($Route->page)
    {
      $ar = explode('-', $Route->page);
      $route =  $ar[0];    
    }
    else if ($Route->cmd)
    {
      $route = $Route->cmd;
    }
        
    if (in_array($route, self::$stdPages))
    {
      $className = 'ViewModel_Content';
    }
    else
    {
      $className = 'ViewModel_' . ucfirst($route);
    }
                
    if (strpos($className, '_'))
    {
      $file = strtolower(str_replace('_', '/', $className)) . '.class.php';
    }
    else
    {
      $file = strtolower($className) . '.php';
    }
        
    return array($className, Fx::getPath('ENGINE', $file)); 
  
  } // getRoute
  
  
  public static function routeError(Engine_Route $Route)
  {

    if ($Route->error === cn::ERR_ACCESS)
    {
      $url = fn::getPageUrl(cn::PG_ERR_ACCESS);
    }
    else if ($Route->error === cn::ERR_ROUTE)
    {
      $url = fn::getPageUrl(cn::PG_ERR_MISSING);
    }
    else
    {
      $url = fn::getPageUrl(cn::PG_ERR_SITE);
    }
    
    $redirect = false;
    
    if ($Route->xhr)
    {
      $errorCode = Fx::ERROR_UNAUTHORIZED;
    }
    else if ($Route->api)
    {
      $errorCode = Fx::ERROR_SERVER;
    }
    else
    {
      
      if ($Route->error === Cn::ERR_INTERNAL)
      {
        $errorCode = Fx::ERROR_SERVER;
      }
      else
      {
        $errorCode = Fx::ERROR_NOT_FOUND;
      }
      
      $redirect = true;
      
    }
        
    // deal with access errors first     
    if ($Route->error === cn::ERR_ACCESS)
    {
      
      // we must log the user out
      if ($redirect)
      {
        fn::logout($url);
      }
      else
      {
        fn::logout();
      }
            
    }
    
    if (!$redirect)
    {
      Fx::outputError($errorCode);
    }
    else
    {
      Fx::redirect($url);
    }

  } // routeError
      
  
  private static function init()
  {

    Fx::setErrorExceptionHandlers();
    
    Fx::setPhpIni(array(
      'date.timezone' => 'UTC',
      'error_log' => Fx::getPath(Fx::PATH_APP, 'logs/php-errors.log')
    ));  
    
    Fx::setPathEx('ENGINE', Fx::PATH_APP, 'engine', true);
    Fx::setPathEx('API', Fx::PATH_APP, 'api');
    Fx::setPathEx('CONFIG', Fx::PATH_APP, 'config');
      
    Fx::setAutoload(array(__CLASS__, 'autoload'));
        
    require Fx::getPath('ENGINE', 'common.php');
    
    Fx::set('appName', 'Open Validation Server');
    
    self::$stdPages = self::getRoutingTable();   
  
  } // init
  
  
  private static function isApi()
  {

    $api = false;
    
    if (Fx::isCli())
    {
      $script = basename($_SERVER['argv'][0]);
    }
    else
    {
      $script = basename($_SERVER['SCRIPT_NAME']);    
    }
    
    if (!$api = preg_match('/api.php/i', $script))
    {
      $api = Fx::varSet($_REQUEST, 'api', false);
    }
            
    return (bool) $api;
  
  } // isApi
  
  
  private static function getRoutingTable()
  {

    $arPages = array();
    
    // add standard pages
    $arPages[] = cn::PG_HOME;
    $arPages[] = cn::PG_ERR_ACCESS;
    $arPages[] = cn::PG_ERR_MISSING;
    $arPages[] = cn::PG_ERR_SITE;
     
      
    $ini = Fx::getPath('CONFIG', 'menu.ini');    
    $arIni = Fx::iniParse($ini);
            
    foreach($arIni as $section)
    {
    
      foreach ($section as $name => $value)
      {
      
        if ($name === 'page')
        {
          $arPages[] = $value;
        }
        
      }
    
    }
    
    return $arPages;
  
  } // getRoutingTable 
  

} // end class AppBase
 
?>
