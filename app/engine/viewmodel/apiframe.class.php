<?php
  
class ViewModel_ApiFrame extends ViewModel_Base
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
    
    $this->clean['debug'] = false;
    
    if (isset($this->Route->input['debug']) && fn::loggedIn())
    {
      $this->clean['debug'] = Model_Session::getToken('level') >= 2;
    }
                
    return true;
  
  } // checkInput 
  
  
  protected function executeWork()
  {

    require Fx::getPath('API', 'vali-igc.php');
    
    if ($this->Route->cmd === 'api')
    {
      
      Fx::set('api', true);
      
      $params = array(
        'out' => 'vali',
        'debug' => $this->clean['debug']      
      );
      
      $Vali = new Vali();
      $Vali->execute($params);
      
      $this->processResponse($Vali);
      
      $baseUrl = Fx::getPath(Fx::PATH_WEB, cn::PGP_APP);
      $params['page'] = 'apiframe';
      $params['caption'] = 'Back to form';
      Fx::html('back', Fn::getHrefHtml($baseUrl, $params));
      
    }
    else
    {
      $arHidden = array();
      $arHidden[cn::PM_VIEW] = 'apiframe';
      $arHidden[cn::PM_CMD] = 'api';
      $arHidden['out'] = 'vali';
      Fx::html('hidden', $arHidden);    
    }
        
    Fx::html('header', ValiUtils::getServerName());    
    Fx::setPathEx('SCRIPTS', Fx::PATH_WEB, 'web/scripts');
    Fx::html('css', Fx::getPath('SCRIPTS', 'css/console.css'));
    Fx::html('js', Fx::getPath('SCRIPTS', 'js/console.js'));
            
  } // executeWork
  
  
  protected function output()
  {

    $file = $this->Route->page . '.inc.php';
    $path = 'view/includes/' . $file;
    $incFile = Fx::getPath('ENGINE', $path);
    include($incFile);
    
  } // output
  
  
  protected function doError()
  {
    
    /*
      If we do not handle this, the Base class calls Route->doError
    */
         
  } // doError


  private function processResponse($Vali)
  {

    $s = '';
    $sep = '<br />' . PHP_EOL;
    
    if ($this->clean['debug'] && $Vali->debug)
    {
      $s .= implode($sep, $Vali->debugData);
      $s .= $Vali->debugData ? $sep . $sep : '';
    }
    
    if ($Vali->out)
    {
    
      $output = array();        
      
      foreach ($Vali->response->output as $line)
      {
        $output[] = "&gt; " . $line;
      }
      
      $s .= implode($sep, $output);
      $s .= $output ? $sep . $sep : '';
    
    }
    
    $s .= $Vali->response->result;
    $s .= $sep;
    
    $s .= $Vali->response->msg;
    $s .= $sep;
    
    $s .= $Vali->response->igc;
    $s .= $sep;
    
    $s .= $sep;
    $s .= 'Thank you for using ' . VALI_SERVER_NAME;
    $s .= $sep . $sep;
    
    Fx::html('result', $s);
      
  } // processResponse
  
  
} // end class ViewModel_ApiFrame

?>
