<?php
  
class Model_User_Login
{


  public function checkLogin($axAttributes)
  {
  
    $Rec = new Record_Login();
    $Rec->firstName = $axAttributes['firstname'];
    $Rec->lastName = $axAttributes['lastname'];
    $Rec->email = $axAttributes['email'];
    $Rec->level = 1;
    
    $admins = array(
      'johngstevenson@gmail.com',
      '4rieck@gmail.com'
    );
  
    if (in_array(strtolower($Rec->email), $admins))
    {
      $Rec->level = 2;
    }
    
    Model_Session::login($Rec);   
    
  } // checkLogin


} // enc class Model_User_Login

?>
