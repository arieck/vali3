<?php

/*
  A simple way of getting html content into our page.
  All html is either found in Result->content, or is created by
  combining Result->includes, and is put in Fx::html[pageContent] 

*/

class View_Content extends View_BaseStd
{


  protected function executeWork()
  {

    if ($this->Result->content)
    {
      Fx::html('pageContent', $this->Result->content);
    }
    elseif ($this->Result->includes)
    {
          
      ob_start();
      
      foreach ($this->Result->includes as $include)
      {
        include $include;
      }
      
      Fx::html('pageContent', ob_get_contents());
      
      ob_end_clean();
            
    }
         
  } // executeWork


} // end View_Content class

?>
