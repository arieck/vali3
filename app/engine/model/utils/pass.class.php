<?php

class Model_Utils_Pass  
{

  private function __construct()
  {
    // private so cannot be created through new call
  } // constructor


  public static function checkPassword($password, $passwordHash)
  {

    $hash = self::getPasswordHash($password, $passwordHash);
    return (strcasecmp($hash, $passwordHash) === 0);
    
  } // checkPassword
  
  
  public static function getPasswordHash($plainText, $salt = null)
  {

    /*
      DO NOT CHANGE
    */
    
    if ($salt === null)
    {
      $unique = uniqid(rand(), true);
      $salt = substr(hash("sha256", $unique), 0, 32);
    }
    else
    {
      $salt = substr($salt, 0, 32);
    }

    return $salt . hash("sha256", $salt . $plainText);
    
  } // getPasswordHash


  } // end class Model_Utils_Pass  
      
?>
