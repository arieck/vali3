<?php
  
class ViewModel_SignIn extends ViewModel_Base
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

    if (!$this->Route->cmd)
    {
      
      // we set hidden values for form
      $arHidden = array();
      $arHidden[cn::PM_VIEW] = cn::PG_SIGN_IN;
      $arHidden[cn::PM_CMD] = 'google';
      Fx::html('hidden', $arHidden); 
            
      VmBase::includeContent($this->Route, $this->Result);
      return;
      
    }
    
    $params[cn::PM_CMD] = 'return';
    $params[cn::PM_CID] = Model_Session::getFormId();
    $returnUrl = fn::getPageUrl(cn::PG_SIGN_IN, $params);
    
    $Login = new Model_Utils_GoogleLogin($returnUrl);
    
    if ($this->Route->cmd === 'google')
    {
                
      if (!$url = $Login->getLoginUrl())
      {
        throw new ErrorException($Login->error);
      }
      else
      {
        Fx::redirect($url);
      }
    
    }
    else if ($this->Route->cmd === 'return')
    {
    
      if (!$attributes = $Login->getLoginResponse($this->Route->input))
      {
      
        if ($Login->error)
        {
          throw new ErrorException($Login->error);
        }
        
        // the user has cancelled
        $this->Route->reRoute();
        
      }
      else
      {
      
        $url = fn::getLastPageUrl();
                
        $UserLogin = new Model_User_Login();
        $UserLogin->checkLogin($attributes);
                
        Fx::redirect($url);
        
      }
            
    }
    
    
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
  
  
} // end class ViewModel_SignIn

?>
