<?php
  
abstract class ViewModel_Base
{
    
  /** @var Engine_Route */
  public $Route = null;
  
  /** @var Engine_Result */
  public $Result = null;

  /** @var object */  
  public $Rec = null;
  
  public $Db = null;
  public $clean = array();
  public $html = array();
    
  protected $Model = null;
    
  abstract protected function init();
  abstract protected function checkAccess();
  abstract protected function checkInput();
  abstract protected function executeWork();
  abstract protected function output();
  abstract protected function doError();
         
  
  public function __construct()
  {
    
    $this->Result = new Engine_Result();
    Fx::refEx($this, array('Route', 'Result', 'clean'));
    $this->Rec =& $this->Result->Rec;
                    
  } // constructor
  
  
  public function execute()
  {

    if (!$this->init())
    {
      $this->onError();
    }
    
    if (!$this->checkAccess())
    {
      $this->Route->error = cn::ERR_ACCESS;
      $this->onError();
    }
    
    if (!$this->checkInput())
    {  
      $this->onError();
    }
    
    if (!$this->Result->errors)
    {
      $this->executeWork();
    }
    
    $this->output();
        
    exit;
          
  } // execute
  
  
  protected function callError()
  {
  
    $this->onError(true);
  
  } // callError
  
  
  private function onError($called = false)
  {
  
    // called means the child class has triggered us
    if (!$called)
    {
      $this->doError();
    }
    
    // if we get here then the child class has not handled the error
    $this->Route->doError();
    
  } // onError
  
  
  protected function checkInputValue($key, $filter, $errorMsg, $filterEmpty = false)
  {
    
    if (!is_int($filter))
    {
      throw new InvalidArgumentException('checkInputValue');
    }
                 
    if (!$this->getInputValue($key, $baseValue))
    {
      $this->Result->addError($key, $errorMsg, $baseValue);
      return false;
    }
    
    $error = false;
          
    if ($filter)
    {
      
      if ($filter === FILTER_VALIDATE_BOOLEAN)
      {
        $value = filter_var($baseValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $error = is_null($value);
      }
      else
      {
        $value = filter_var($baseValue, $filter); 
        $error = $value === false;
      }
    
    }
    else
    {
      $value = $baseValue;
    }
    
    if (!$error && $filterEmpty)
    {
      $error = empty($value);    
    }  
          
    if ($error)
    {
      $this->Result->addError($key, $errorMsg, $baseValue);
    }
    else
    {
      $this->clean[$key] = $value;
    }
            
    return true;
       
  } // checkInputValue
  
  
  protected function getInputValue($key, &$value)
  {
    
    if (!isset($this->Route->input[$key]))
    {
      return false; 
    }
    else
    {
      $value = trim($this->Route->input[$key]);
      return true;
    }
  
  } // getInputValue
 
 
  protected function cleanToRecord($Record)
  {
  
    if (!is_object($Record))
    {
      throw new InvalidArgumentException('cleanToRecord');
    }
    
    foreach($Record as $key => $value)
    {
    
      if (isset($this->clean[$key]))
      {
        $Record->$key = $this->clean[$key];
      }
          
    }
      
  } // cleanToRecord
  

} // end class ViewModel_Base


class VmBase
{

  // a class for common viewmodel functions

  
  public static function includeContent(Engine_Route $Route, Engine_Result $Result)
  {
  
    $file = $Route->page . '.inc.php';
    $path = 'view/includes/' . $file;
    $incFile = Fx::getPath('ENGINE', $path);
    
    if (!file_exists($incFile))
    {
      $Route->error = cn::ERR_ROUTE;
      $Route->doError();
    }
    else
    {
      $Result->includes[] = $incFile;
    }  
    
  } // includeContent
  
  
  public static function outputContent()
  {

    $View = new View_Content();
    $View->execute();  
  
  } // outputContent


} // end class VmBase

?>
