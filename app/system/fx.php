<?php

Fx::init();


class Fx
{

  public static $Db = null;
  private static $data = array();
  private static $html = array();
  private static $clean = array();
  private static $paths = array();
  private static $inis = array();
  private static $callbacks = array();
  private static $origs = array();
  private static $cli = false;
  private static $dev = false;
  private static $fatalError = false;
  private static $usedError = false;
  private static $usedException = false;
  private static $instance = false;
  
  const ITEMS_DATA = 'data';
  const ITEMS_HTML = 'html';
  const ITEMS_CLEAN = 'clean';
  const ITEMS_PATH = 'paths';
  
  const PATH_SYSTEM = 'SYSTEM';
  const PATH_APP = 'APP';
  const PATH_WEB = 'WEB';
  const PATH_DOC_ROOT = 'DOCROOT';
  const PATH_DOC_PATH = 'DOCPATH';
  const PATH_DOC_ENTRY = 'DOCENTRY';
  
  const ERROR_UNAUTHORIZED = 401;
  const ERROR_NOT_FOUND = 404;
  const ERROR_SERVER = 500;
    
  const DB_HOST = 'dbHost';
  const DB_NAME = 'dbName';
  const DB_USER = 'dbUser';
  const DB_PASS = 'dbPass';
  
  const ARG_ATTRIBS = 'attribs';
  const ARG_PATH = 'path';
  const ARG_INI = 'ini';
  
  const CB_AUTO = 'AUTOLOAD';
  const CB_EXCEPTION = 'EXCEPTION';
  
  
  public static function data($name, $value = '')
  {
    
    if (!$value)
    {
      return self::_get(self::$data, $name);
    }
    else
    {
      self::_set(self::$data, $name, $value);
    }
      
  } // Fx::data
  
  
  public static function get($name)
  {

    return self::_get(self::$data, $name);    
      
  } // Fx::get
  

  public static function set($name, $value = null)
  {
  
    self::_set(self::$data, $name, $value);
        
      
  } // Fx::set
  
  
  public static function setRef($name, &$value)
  {
  
    self::ref($name, $value, true);
    
      
  } // Fx::setRef
  
  
  public static function ref($name, &$var = null, $overwrite = false)
  {

    // special case for Db
    if ($name === 'Db')
    {
      self::_refDb($var);
      return;
    }
    
    if (isset(self::$data[$name]))
    {
      
      if ($var === null || !$overwrite)
      {
        $tmp = self::$data[$name];
        self::$data[$name] =& $var;
        self::$data[$name] = $tmp;
      }
      else 
      {
        self::$data[$name] =& $var;
      }
      
    }
    else
    {
      self::$data[$name] =& $var;
    }
              
  } // Fx::ref
  
  
  public static function refEx($obj, $props)
  {
  
    foreach ($props as $key)
    {
    
      if (property_exists($obj, $key))
      {
        self::ref($key, $obj->$key);
      }
      
    }
  
  } // Fx::refEx
  
  
  public static function clear($name = '')
  {
  
    if (!$name)
    {
      self::$data = array();
    }
    else if (isset(self::$data[$name]))
    {
      unset(self::$data[$name]);
    }
          
  } // Fx::clear
  
  
  public static function clean($name, $value = '')
  {
    
    if (!$value)
    {
      return self::_get(self::$clean, $name);
    }
    else
    {
      self::_set(self::$clean, $name, $value);
    }
      
  } // Fx::clean
  
  
  public static function html($name, $value = '')
  {
    
    if (!$value)
    {
      return self::_get(self::$html, $name);
    }
    else
    {
      self::_set(self::$html, $name, $value);
    }
      
  } // Fx::html
  
  public static function out($name)
  {
    
    echo self::_get(self::$html, $name);
      
  } // Fx::out
  
  
  public static function getPath($name, $pathToAdd = '')
  {
  
    $path = self::_get(self::$paths, $name);
    
    if ($pathToAdd)
    {
      $path = self::buildPath($path, $pathToAdd);
    }
    
    return $path;
      
  } // Fx::getPath
  
  
  public static function buildPath($base, $path)
  {
    
    $base = $base ? self::_fixSlashes($base) : '';
    $path = $path ? self::_fixSlashes($path) : '';
      
    if ($base && $base[strlen($base) - 1] !== '/')
    {
      $base .= '/';
    }
    
    if ($path && $path[0] === '/')
    {
      $path = substr($path, 1);
    }    
    
    return $base . $path;  
  
  } // Fx::buildPath
    
    
  public static function getProperty($name)
  {
  
    if (isset(self::$$name))
    {
      return self::$$name;
    }
          
  } // Fx::getProperty
    
  
  public static function setPath($name, $path, $setIncPath = false)
  {
  
    if (func_num_args() === 3 && is_string($setIncPath))
    {
      $s = 'String passed as last argument, boolean required. ';
      $s .= 'Perhaps you meant to use Fx::setPathEx()';
      throw new Exception($s);
    }
    
    self::_setPathWork($name, '', $path, $setIncPath);
          
  } // Fx::setPath
  
  
  public static function setPathEx($name, $baseName, $path, $setIncPath = false)
  {

    $basePath = self::getPath($baseName);
    self::_setPathWork($name, $basePath, $path, $setIncPath);
         
  } // Fx::setPathEx
  
  
  public static function setDb($params, &$var = null)
  {

    if (self::$Db)
    {
      return true;
    }
        
    if ($ini = self::varSet($params, self::ARG_INI, ''))
    {
      
      $path = self::varSet($params, self::ARG_PATH, '');
      self::iniParse($ini, $path);
      
      $host = self::ini(self::DB_HOST);
      $dbname = self::ini(self::DB_NAME);
      $user = self::ini(self::DB_USER);
      $pass = self::ini(self::DB_PASS);

    }
    else
    {
      $host = self::varSet($params, self::DB_HOST, '');
      $dbname = self::varSet($params, self::DB_NAME, '');
      $user = self::varSet($params, self::DB_USER, '');
      $pass = self::varSet($params, self::DB_PASS, '');
    }
        
    if ($host && $dbname && $user && $pass)
    {
      
      $dsn = 'mysql:host=' . $host . ';dbname=' . $dbname;
      $attribs = self::varSet($params, self::ARG_ATTRIBS, array());
      $errMode = self::varSet($attribs, constant('PDO::ATTR_ERRMODE'), null);
            
      if ($errMode === null)
      {
        $attribs[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
      }
      
      $Db = null;
            
      try
      {
                
        $Db = new PDO($dsn, $user, $pass);
        
        foreach ($attribs as $attr => $value)
        {
          $Db->setAttribute($attr, $value);
        }
        
        self::$Db =& $Db;
                
        
      }
      catch(PDOException $e)
      {
      }
      
    }
    
    if (!$Db)
    {
      self::$fatalError = true;
    }
    
    if (func_num_args() === 2)
    {
      self::_refDb($var);
    }
    
    return $Db;   
  
  } // Fx::setDb
  
  
  public static function setAutoload($callback, $path = '')
  {
    
    if (!$callback = self::_formatCallback($callback))
    {
      return;
    }
    
    self::_set(self::$callbacks, self::CB_AUTO, $callback);
    self::_autoloadSet();
            
    if ($path)
    {
    
      // path can be a path name
      if (!$incPath = self::getPath($path))
      {
        $incPath = $path;
      }
      
      if (!file_exists($incPath))
      {
        $incPath = self::getPath(self::PATH_APP, $incPath);
      }
      
      self::_includePathSet($incPath);  
           
    }
    
  } // Fx::setAutoload
  
  
  public static function setErrorHandler($callback)
  {

    if (!$callback = self::_formatCallback($callback))
    {
      return;
    }
        
    set_error_handler($callback);
    self::$usedError = true;
   
  } // Fx::setErrorHandler
  
  
  public static function setExceptionHandler($callback)
  {

    if (!$callback = self::_formatCallback($callback))
    {
      return;
    }
        
    set_exception_handler($callback);
    self::$usedException = true;
   
  } // Fx::setExceptionHandler
  
  
  public static function setErrorExceptionHandlers()
  {
  
    self::setErrorHandler(array(__CLASS__, 'handlerError'));
    self::setExceptionHandler(array(__CLASS__, 'handlerException'));
  
  } //setErrorHandlers
  
  
  public static function setExceptionHook($callback)
  {
    
    if ($callback)
    {
      $callback = self::_formatCallback($callback);
    }
    
    self::_set(self::$callbacks, self::CB_EXCEPTION, $callback);
      
  } // Fx::setExceptionHook
  
  
  public static function setPhpIni($mixed, $value = '')
  {
  
    if (!is_array($mixed))
    {
      $items = array($mixed => $value);
    }
    else
    {
      $items = $mixed;
    }
    
    self::_phpSet($items);  
  
  } // Fx::setPhpIni
  
  
  public static function fatalError()
  {
    
    return self::$fatalError;
    
  } // Fx::fatalError
  
  
  public static function isCli()
  {
    
    return self::$cli;
    
  } // Fx::isCli
  
  
  public static function isDeveloper()
  {
    
    return self::$dev;
    
  } // Fx::isDeveloper
    
  
  public static function ini($option, $section = '')
  {
  
    $value = '';
    
    foreach (self::$inis as $sec)
    {
    
      if (is_string($sec))
      {
        $value = self::varSet(self::$inis, $option, '');
        break;
      }
      else
      {
        
        if (!$section || $section === $sec)
        {
          
          if ($value = self::varSet($sec, $option, ''))
          {
            break;
          }
          
        }
        
      }
      
    }
    
    return $value;
      
  } // Fx::ini
  
  
  public static function iniParse($ini, $basePath = '')
  {

    if ($basePath)
    {
      $ini = self::getPath($basePath, $ini);
    }

    if (!self::$inis = @parse_ini_file($ini, true))
    {
      self::$inis = array();
    }
    
    return self::$inis;
      
  } // Fx::iniParse
  
  
  public static function restoreSettings()
  {
  
    set_include_path(self::$origs['includePath']);
    self::_phpRestore();
    
    if (self::$usedError)
    {
      restore_error_handler();
    }
    
    if (self::$usedException)
    {
      restore_exception_handler();
    }
    
    if (self::_get(self::$callbacks, self::CB_AUTO))
    {
      spl_autoload_unregister(array(__CLASS__, 'handlerAutoload'));
    }
         
  } // Fx::restoreSettings
  
    
  public static function outputError($code)
  {
    
    switch ($code)
    {

      case self::ERROR_UNAUTHORIZED :
        $msg = 'Unauthorized';
        break;
          
      case self::ERROR_NOT_FOUND :
        $msg = 'Not Found';
        break;
        
      case self::ERROR_SERVER :
        $msg = 'Internal Server Error';
        break;
        
      default:
        $code = self::ERROR_SERVER;
        $msg = 'Internal Server Error';
    
    }
    
    while (@ob_end_clean());
    header("HTTP/1.1 {$code} {$msg}", true, $code);
    exit();

  } // Fx::outputError        
  
 
  public static function redirect($url)
  {
    
    while (@ob_end_clean());
    
    if ($url)
    {
      header("Location: " . str_replace('&amp;', '&', $url));
    }
        
    exit; 
      
  } // Fx::redirect
  
  
  public static function callFunction($func)
  {

    if (!is_array($func))
    {
      return;
    }
    
    $arArgs = array();
    
    if (!is_array($func[0]))
    {
      $arFunc = $func[0];
    }
    else
    {
      
      if (count($func[0]) === 1)
      {
        $arFunc = $func[0][0];
      }
      else
      {
        $arFunc = $func[0];
      }
      
      if (isset($func[1]) && is_array($func[1]))
      {
        $arArgs = $func[1];
      }
      
    }
    
    if (is_callable($arFunc))
    {
      return call_user_func_array($arFunc, $arArgs);
    }  
  
  } // Fx::callFunction
  
  
  public static function objSet($base, $obj)
  {

    // copies the properties of obj to base. If the property is an object its reference is copied
    
    if (!is_object($base) || !is_object($obj))
    {
      trigger_error('Non-object passed', E_USER_ERROR);
    }
          
    foreach ($base as $key => $value)
    {
      
      if (property_exists($obj, $key))
      {
        $base->$key = $obj->$key;
      }
           
    }  
  
  } // Fx::objSet
      
  
  public static function varSet($var, $key, $default)
  {
  
    if (is_array($var))
    {
      return isset($var[$key]) ? $var[$key] : $default;
    }
    elseif (is_object($var))
    {
      return isset($var->$key) ? $var->$key : $default;
    }
    
    return null;
        
  } // Fx::varSet
  
  
  public static function outputFile($filename, $content, $type, $encoding)
  {

    @ob_end_clean();
    
    /* We do not set file size as this will download the
    file if the user has pressed cancel more than once

    ie header("Content-Length: $size");
    */

    header('Cache-control: private, no-cache, no-store, must-revalidate'); //IE 6 Fix
    header('Pragma: no-cache');
    header('Expires: Mon, 26 July 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
    header('Cache-Control: no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', false);

    header('Content-Type: ' .$type);
    header('Content-Disposition: attachment; filename=' .$filename);
    header('Content-Transfer-Encoding: ' . $encoding);

    print($content);
    exit;

  } // Fx::outputFile
  
  
  public static function println($s = '')
  {
    
    echo $s, PHP_EOL;
  
  } // Fx::println
  
  
  public static function write($s = '')
  {
    
    return $s . PHP_EOL;
  
  } // Fx::writeln
    
  
  public static function init()
  {
  
    if (!self::$instance)
    {
      self::_init();
    }
  
  } // Fx::init
  
  
  public static function handlerAutoload($className)
  {
    
    // we should always have a callback
    if ($callback = self::_get(self::$callbacks, self::CB_AUTO))
    {
      
      self::callFunction(array($callback, array($className)));
      
      if (class_exists($className, false))
      {
        return;
      }
           
    }
        
    require($className . '.php');
      
  } // Fx::handlerAutoload
  
  
  public static function handlerError($level, $message, $file, $line)
  {
    
    // determine if this error is one of the enabled ones
    if (!($level & error_reporting()))
    {
      return;
    }
    
    // all errors are exceptions, except E_USER_NOTICE which can be used for debugging
    if ($level === E_USER_NOTICE)
    {
      $s = 'PHP USER_NOTICE: ';
      $s .= $message;
      $s .= ' in ' . $file;
      $s .= ' on line ' . $line;
      error_log($s);    
      return true;
    }
    else
    {
      throw new ErrorException($message, 0, $level, $file, $line);
    }
                   
  } // Fx::handlerError
  

  public static function handlerException(Exception $Exception)
  {

    self::logException($Exception);
    
    try
    {
      
      if ($callback = self::_get(self::$callbacks, self::CB_EXCEPTION))
      {
        self::callFunction(array($callback, array($Exception)));
        exit;
      }
      
      if (!headers_sent())
      {
        self::outputError(self::ERROR_SERVER);
      }
                        
    }
    catch(Exception $e)
    {
      self::logException($Exception, true);
      exit;
    }    
    
  } // Fx::handlerException
  
  

   
  private static function logException(Exception $Exception, $inException = false)
  {

    if (!$inException)
    {
      $s = 'PHP Fatal error:  Uncaught ' . $Exception->__toString();
    }
    else
    {
      $s = 'PHP Exception handling error: ' . $Exception->__toString();
    }
    
    $s .= ' thrown in ' . $Exception->getFile();
    $s .= ' on line ' . $Exception->getLine();
    error_log($s);        
  
  } // Fx::logException
    
  
  private static function _get($container, $name)
  {
   
    $value = null;
    
    if (isset($container) && isset($container[$name]))
    {
      $value = $container[$name];
    }
    
    return $value;
  
  } // Fx::_get
  
  
  private static function _set(&$container, $name, $value)
  {
   
    $container[$name] = $value;
  
  } // Fx::_set
  
  
  private static function _refDb(&$var = null)
  {

    $tmp = self::$Db;
    self::$Db =& $var;
    self::$Db = $tmp;    
  
  } // Fx::_refDb
  
  
  private static function _includePathGet()
  {
  
    $path = self::_fixSlashes(get_include_path());
    return preg_replace('/;*$/', '', $path);
  
  } // Fx::_includePathGet
  
  
  private static function _includePathSet($path, $add = true)
  {

    if (!$path)
    {
      return;
    }
    
    $includes = self::_includePathGet();
        
    $path = preg_replace('/\/*$/', '', $path);
    
    $arOld = explode(PATH_SEPARATOR, $includes);
    $arNew = array();
    
    foreach ($arOld as $existing)
    {
      
      if ($existing)
      {
        
        $existing = preg_replace('/\/*$/', '', $existing);
        
        if ($existing !== $path)
        {
          $arNew[] = $existing;
        }
                 
      }
    
    }
    
    if ($add)
    {
      $arNew[] = $path;
    }
    
    set_include_path(implode(PATH_SEPARATOR, $arNew));
      
  } // Fx::_includePathSet
 
  
  private static function _fixSlashes($path)
  {
  
    return str_replace('\\', '/', $path);
  
  } // Fx::_fixSlashes
  
  
  private static function _setPathWork($name, $basePath, $path, $setIncPath)
  {
  
    if ($name === self::PATH_SYSTEM)
    {
      return;
    }
    else
    {
         
      $path = self::buildPath($basePath, $path);
                  
      if ($name === self::PATH_APP || $setIncPath)
      {
        
        $includes = self::_includePathGet();
        
        if ($orig = self::_get(self::$paths, $name))
        {
          self::_includePathSet($orig, false);
        }
               
        self::_includePathSet($path);
        
      }
      
      self::_set(self::$paths, $name, $path);
            
    }
          
  } // Fx::_setPathWork
  
  
  private static function _init()
  {
    
    self::$origs['ini'] = array();
    self::$origs['errorReporting'] = error_reporting();;
    self::$origs['includePath'] = self::_includePathGet();
    error_reporting(E_ALL);
    
    self::$cli = PHP_SAPI === 'cli';
    
    if (isset($_SERVER) && isset($_SERVER['REMOTE_ADDR']))
    {
      self::$dev = in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'));
    }
    
    self::_initPaths();
           
    $ar['display_errors'] = '0';
    $ar['default_charset'] = 'UTF-8';
    $ar['log_errors'] = '1';
    
    self::_phpSet($ar);
    
    self::$instance = true;
    new FxInstance();
      
  } // Fx::_init
  
  
  private static function _initPaths()
  {

    $sysPath = self::_fixSlashes(__DIR__);
    $appPath = '';
    $webPath = '/';
    $docRoot = '';
    $docPath = '';
    $docEntry = '';
    
    if (isset($_SERVER))
    {
    
      if (isset($_SERVER['DOCUMENT_ROOT']))
      {
        $docRoot = self::_fixSlashes($_SERVER['DOCUMENT_ROOT']); 
      }
      
      $webPath = self::_fixSlashes(dirname($_SERVER['SCRIPT_NAME']));
    
    }
    
    $inc = get_included_files();
    $count = count($inc);
    
    $docEntry = self::_fixSlashes($inc[0]);
    
    if ($count >= 2)
    {
      $appPath = self::_fixSlashes(dirname($inc[$count - 2]));
    }
    else
    {
      $appPath = $webPath ? $docRoot . substr($webPath, 1) : $docRoot;
    }
    
    if ($appPath)
    {
      self::_includePathSet($appPath);
    }
                   
    self::$paths = array(
      self::PATH_SYSTEM => $sysPath,
      self::PATH_WEB => $webPath,
      self::PATH_APP => $appPath,
      self::PATH_DOC_ROOT => $docRoot,
      self::PATH_DOC_ENTRY => $docEntry
    );
    
  } // Fx:_initPaths
  
  
  private static function _autoloadSet()
  {
      
    spl_autoload_register(array(__CLASS__, 'handlerAutoload'));

    // if framework already has an __autoload function we need to register it as well
    if (function_exists('__autoload'))
    {
      
      // check __autoload is not already registered  
      $ar = spl_autoload_functions();
      $found = false;
      
      foreach($ar as $funcName)
      {
      
        if (is_string($funcName) && $funcName === '__autoload')
        {
          $found = true;
          break;          
        }
      
      }
      
      if (!$found)
      {
        spl_autoload_register('__autoload');
      }
      
    }   
  
  } // Fx::_autoloadSet
  
  
  private static function _phpSet(array $items)
  {
                
    foreach ($items as $key => $value)
    {
      
      ini_set($key, $value);
      
      if (!isset($origs['ini'][$key]))
      {
        self::$origs['ini'][$key] = $value;
      }
      
    }
    
  } // Fx::_phpSet
  
  
  private function _phpRestore()
  {
  
    error_reporting(self::$origs['errorReporting']);
  
    foreach (self::$origs['ini'] as $key => $value)
    {
      ini_set($key, $value);
    }
    
  } // Fx::_phpRestore
  
  
  private static function _formatCallback($callback)
  {

    if (!$callback)
    {
      return;
    }
       
    if (!is_array($callback))
    {
      return array($callback);
    }
    else
    {

      if (is_array($callback[0]))
      {
        return array_slice($callback[0], 0, 2);
      }
      else
      {
        return array_slice($callback, 0, 2);
      }
            
    }
     
  } // Fx::_formatCallback
  

} // end class Fx


class FxInstance extends Fx
{


  public function __construct()
  {
  
    $props = get_class_vars(get_parent_class());
    
    foreach ($props as $key => $value)
    {
      $this->$key =& parent::$$key;
    }
    
    if (!isset($GLOBALS['Fx']))
    {
      $GLOBALS['Fx'] = $this;
    }
  
  } // constructor


} // end class FxInstance


class FxResolver
{
  
  public static function getWebPath($filename)
  {
  
    $webPath = '';
        
    $web = Fx::getPath(Fx::PATH_WEB);
    $docRoot = Fx::getPath(Fx::PATH_DOC_ROOT);
    
    if (!$web || !$docRoot)
    {
      return $webPath;
    }
    
    $filename = str_replace('\\', '/', $filename);
    $filename = preg_replace('/\/*$/', '', $filename);
    
    if (stripos($filename, $docRoot) !== 0)
    {
      return $webPath;
    }
      
    $src = substr($filename, strlen($docRoot));
    
    if ($web === '/')
    {
      return $src;
    }
    
    $targetDirs = self::getPathArray($web);
    $srcDirs = self::getPathArray($src);
    
    return self::work($targetDirs, $srcDirs);
      
  } // getWebPath
    
  
  private static function work($targetDirs, $srcDirs)
  {
    
    $len = count($targetDirs);
    $dLen = count($srcDirs);
      
    $start = 0;
    $matches = array();
    
    for ($i = 0; $i < $len; ++ $i)
    {
    
      if ($i === $dLen)
      {
        break;
      }
      
      self::matchDirs($targetDirs[$i], $srcDirs, $dLen, $start, $matches);
            
    }
    
    $res = $matches ? '/' . implode('/', array_reverse($matches)) : '';
    return $res;
      
  } // work
    
  
  private static function getPathArray($path, $reverse = true)
  {
    
    $path = strtolower($path);
    $path = str_replace('\\', '/', $path);
    
    $arRes = array();
    
    $ar = explode('/', $path);
    
    foreach ($ar as $dir)
    {
    
      if ($dir)
      {
        $arRes[] = $dir;
      }
      
    }
    
    return $reverse ? array_reverse($arRes) : $arRes;
  
  } // getPathArray
  
  
  private static function matchDirs($target, $dirs, $dLen, &$start, &$matches)
  {
  
    $found = false;
        
    for ($i = $start; $i < $dLen; ++ $i)
    {
    
      if ($dirs[$i] === $target)
      {
      
        for ($j = $start; $j <= $i; ++ $j)
        {
          $matches[] = $dirs[$j];
        }
        
        $start = $i + 1;
        $found = true;
        break;
        
      }
      
    }
  
    if (!$found && $matches)
    {
      $matches = array();
      $start = 0;
    }
    
  } // matchDirs
  
  
} // end class FxResolver

?>