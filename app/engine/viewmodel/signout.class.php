<?php
  
class ViewModel_SignOut extends ViewModel_Base
{

  protected function init()
  {

    /*
      Base class calls doError on failure
    */
    
    return true;
  
  } // init
  
  
  protected function checkAccess()
  {

    /*
      Base class sets Route-> error to ERR_ACCESS and calls doError on failure
    */
    
    return true;
  
  } // checkAccess
  
  
  protected function checkInput()
  {

    /*
      Base class calls doError on failure
    */
            
    return true;
  
  } // checkInput 
  
  
  protected function executeWork()
  {

    $url = fn::getLastPageUrl();
    fn::logout($url);
    
  } // executeWork
  
  
  protected function output()
  {

    
  } // output
  
  
  protected function doError()
  {
    
    /*
      If we do not handle this, the Base class calls Route->doError
    */
         
  } // doError
  
  
} // end class ViewModel_SignOut

?>
