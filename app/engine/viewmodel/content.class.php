<?php
  
class ViewModel_Content extends ViewModel_Base
{

  protected function init()
  {

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
            
    return true;
  
  } // checkInput 
  
  
  protected function executeWork()
  {

    VmBase::includeContent($this->Route, $this->Result);
           
  } // executeWork
  
  
  protected function output()
  {

    VmBase::outputContent();
    
  } // output
  
  
  protected function doError()
  {
    
    /*
      If we do not handle this, the Base class calls Route->doError
    */
    
         
  } // doError
  
  
} // end class ViewModel_Content

?>
