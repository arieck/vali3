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
    
	// array for allowed OpenID admin emails 
    $admins = array(
      'user@example.com',
      'whatever@example.com'
    );
  
    if (in_array(strtolower($Rec->email), $admins))
    {
      $Rec->level = 2;
    }
    
    Model_Session::login($Rec);   
    
  } // checkLogin


} // enc class Model_User_Login

?>
