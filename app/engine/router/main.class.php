<?php
   
class Router_Main
{
  
  /** @var Engine_Route */
  public $Route = null;
    
             
  public function __construct(Engine_Route $Route)
  {

    $this->Route = $Route;
    Fx::ref('Route', $this->Route);
                          
  } // constructor
  
  
  public function execute()
  {

    $this->setRoute();
    $this->Route->route();
  
  } // execute
  
  
  private function setRoute()
  {
         
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
      $this->Route->input = &$_POST;
      $inputType = INPUT_POST;
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET')
    {
      $this->Route->input = &$_GET;
      $inputType = INPUT_GET;
    }
    else
    {
      Fx::outputError(Fx::ERROR_UNAUTHORIZED);
    }
    
    $this->getBaseInput($inputType);
    $sess = $this->checkSessionStart(); 
    
    if ($inputType === INPUT_GET && !$this->Route->page)
    {
      $this->Route->page = cn::PG_HOME;
    }
           
  } // setRoute
  
  
  private function getBaseInput($inputType)
  {

    // page
    $value = filter_input($inputType, cn::PM_VIEW, FILTER_SANITIZE_STRING);
    $this->Route->page = $value ? $value : '';  
    
    // cmd
    $value = filter_input($inputType, cn::PM_CMD, FILTER_SANITIZE_STRING);
    $this->Route->cmd = $value ? $value : '';  
  
    // xhr
    $value = filter_input($inputType, cn::PM_XHR, FILTER_SANITIZE_STRING);
    $this->Route->xhr = $value ? true : false;
        
    if (!$this->Route->xhr)
    {
      Fx::setExceptionHook(array('AppBase', 'displayError'));
    }
    
    // json as params
    $value = filter_input($inputType, cn::PM_JSN);
  
    if ($value)
    {
      $value = htmlspecialchars_decode($value, ENT_QUOTES);
      $this->Route->input = json_decode($value, true);
    }
          
  } // getBaseInput
  
  
  private function checkSessionStart()
  {
  
    // add pages that do not require a session here
    $noSession = array();
  
    $start = !in_array($this->Route->page, $noSession);
    
    if ($start)
    {
      
      $cookiePath = Fx::getPath(Fx::PATH_WEB);
      
      $frames = array(
        'apiframe'      
      );
    
      Model_Session::startSession($this->Route, $cookiePath, $frames);
      Fx::set('sess', true);
      
      
    }
    
    return $start;
  
  } // checkSessionStart
                    

} // end Router_Main class
 
?>
