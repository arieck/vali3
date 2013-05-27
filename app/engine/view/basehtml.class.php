<?php

  
abstract class View_BaseHtml
{
    
  /** @var Engine_Route */
  public $Route = null;
  
  /** @var Engine_Result */
  public $Result = null;
  
  protected $baseUrl = '';
  protected $done = false;
  
  abstract protected function init();    
  abstract protected function pageOpen(&$include);
  abstract protected function pageContent(&$include);
  abstract protected function pageClose(&$include);

  public function __construct()
  {
    
    Fx::refEx($this, array('Route', 'Result'));
        
    $this->baseUrl = Fx::getPath(Fx::PATH_WEB, cn::PGP_APP);
    Fx::set('baseUrl', $this->baseUrl);
    
    $url = fn::getPageUrl(cn::PG_HOME);
    Fx::set('homeUrl', $url);
    
    $href = fn::getHrefHtml($this->baseUrl);
    Fx::set('homeHref', $href);
       
  } // constructor
  
  
  public function execute()
  {
    
    @ob_end_clean();
    
    ob_start();
           
    Fx::setExceptionHook(null);
    
    $this->init();
    
    if (!$this->done)
    {    
      $this->displayContent('pageOpen');
    }
    
    if (!$this->done)
    {
      $this->displayContent('pageContent');
    }
    
    if (!$this->done)
    {
      $this->displayContent('pageClose');
    }
    
    ob_end_flush();                
    flush();
    exit();
          
  } // execute
  
  
  private function displayContent($name)
  {

    $include = '';
    
    $callback = array($this, $name);
    $args = array(&$include);
    
    $content = call_user_func_array($callback, $args);
    
    if ($include)
    {
      require($include);
    }
    else
    {
      echo $content;
    }  
  
  } // displayContent
  
  
} // end View_BaseHtml class

?>
