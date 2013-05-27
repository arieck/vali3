<?php

// a static class for manipulating $_SESSION variables
  
class Model_Session
{
  
  private static $cookiePath = '';
  private static $xhr = false;
  private static $framePages = array();
  
    
  public static function captcha($value, $check)
  {
    
    // check value is set
    $captcha = self::getToken('captcha');
     
    if ($check)
    {
      return $captcha === md5($value);  
    }
    else
    {
      $_SESSION['token']['captcha'] = md5($value);
    }
          
  } // captcha
  
  
  public static function formIdCheck($value)
  {
    
    return ($value === self::getFormId());
          
  } // formIdCheck
  
  
  public static function formIdUpdate()
  {
    
    if (!self::$xhr)
    {
      $_SESSION['token']['unique'] = fn::getUniqueId();
    }
            
    return self::getFormId();
          
  } // formIdUpdate
  
  
  public static function getCookiePath()
  {
  
    return self::$cookiePath;
    
  } // getCookiePath 

  
  public static function getFormId()
  {
    
    $id = md5(self::getToken('start') . self::getToken('unique'));
        
    return substr($id, 6, 10);
          
  } // getFormId
  
  
  public static function getName()
  {
  
    return self::getToken('firstName') . ' ' . self::getToken('lastName');
  
  } // getName
              
  
  public static function getToken($name)
  {

    if (!isset($_SESSION) || !isset($_SESSION['token'][$name]))
    {
      // we throw an error to get a stack trace
      throw new Exception("_SESSION[token][{$name}] is not set");
    }
    
    return $_SESSION['token'][$name];
          
  } // getToken
       
  
  public static function login(Record_Login $Rec)
  {
    
    // note: we cannot regenerate id for xhr logins, in case the request times out client side    
    if (!self::$xhr && $_SESSION['token']['pages'] > 1)
    {
      $page = $_SESSION['token']['thisPage'];
      session_regenerate_id(true);
      $_SESSION = array();
      self::initSessionToken($page);
    }
         
    $_SESSION['token']['firstName'] = $Rec->firstName;
    $_SESSION['token']['lastName'] = $Rec->lastName;
    $_SESSION['token']['email'] = $Rec->email;
    $_SESSION['token']['userId'] = $Rec->userId;
    $_SESSION['token']['level'] = $Rec->level;
    $_SESSION['token']['regenerated'] = !self::$xhr;
    $_SESSION['token']['impersonator'] = $Rec->impersonator;
    $_SESSION['token']['created'] = time();
    $_SESSION['token']['verified'] = 0;
   
    // set verified
    if ($Rec->level)
    {
      $_SESSION['token']['verified'] = $_SESSION['token']['created'];
    }
    
  } // login
  
  
  public static function loginUpdate(Record_Login_Update $UpdateRec)
  {
    
    $LoginRec = new Record_Login();
    self::getLoginRec($LoginRec);
    
    if ($UpdateRec->level)
    {
      $LoginRec->level = $UpdateRec->level;
    }
    
    self::login($LoginRec);
   
  } // loginUpdate
  
                      
  public static function logout()
  {

    if (isset($_SESSION)) // important
    {
    
      $_SESSION = array();
      
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]);
     
      session_destroy();
    
    }   
            
  } // logout
  
  
  public static function startSession(Engine_Route $Route, $cookiePath, $frames = array())
  {
          
    self::$xhr = $Route->xhr;
    self::$cookiePath = $cookiePath;
    self::$framePages = $frames;
            
    ini_set('session.cookie_path', self::$cookiePath);
    session_name(cn::CK_SESS_NAME);
    session_cache_limiter('nocache');
    session_start();
    self::initSessionToken($Route->page);  
  
  } // startSession
  
  
  private static function getLoginRec(Record_Login $Rec)
  {

    $Rec->firstName = self::getToken('firstName');
    $Rec->lastName = self::getToken('lastName');
    $Rec->email = self::getToken('email');
    $Rec->userId = self::getToken('userId');
    $Rec->level = self::getToken('level');
    $Rec->impersonator = self::getToken('impersonator');
    
    return $Rec;
         
  } // getLoginRec
    
  
  private static function initSessionToken($page)
  {
    
    if (!isset($_SESSION['token']))
    {
      $_SESSION['token'] = array();
      $_SESSION['token']['firstName'] = '';
      $_SESSION['token']['lastName'] = '';
      $_SESSION['token']['email'] = '';
      $_SESSION['token']['userId'] = 0;
      $_SESSION['token']['level'] = 0;
      $_SESSION['token']['regenerated'] = false;
      $_SESSION['token']['impersonator'] = 0;
      $_SESSION['token']['created'] = 0;
      $_SESSION['token']['verified'] = 0;
      $_SESSION['token']['start'] = time();
      $_SESSION['token']['lastPage'] = '';
      $_SESSION['token']['thisPage'] = '';
      $_SESSION['token']['pages'] = 1;
      $_SESSION['token']['unique'] = fn::getUniqueId();
      $_SESSION['token']['captcha'] = '';
    }
    else
    {
                  
      $_SESSION['token']['pages'] += 1;
      
      if ($_SESSION['token']['level'] > 0 && !$_SESSION['token']['regenerated'])
      {
        session_regenerate_id(true);
        $_SESSION['token']['regenerated'] = true;
      }
      
    }
    
    if (!in_array($page, self::$framePages) && $page !== $_SESSION['token']['thisPage'])
    {
      $_SESSION['token']['lastPage'] = $_SESSION['token']['thisPage'];
      $_SESSION['token']['thisPage'] = $page;
    }
          
  } // initSessionToken
      

} // end Model_Session class

?>
