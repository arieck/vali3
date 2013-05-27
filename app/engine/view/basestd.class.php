<?php

  
abstract class View_BaseStd extends View_BaseHtml
{
    
  /** @var Engine_Route */
  public $Route = null;
  
  /** @var Engine_Result */
  public $Result = null;
  
  public $vanilla = false;
  private $jsScripts = array();
  private $js = array();
        
  abstract protected function executeWork();

  
  public function init()
  {

    Fx::setPathEx('IMAGES', Fx::PATH_WEB, 'web/images');
    Fx::setPathEx('SCRIPTS', Fx::PATH_WEB, 'web/scripts');
    
    $this->setBaseHtmlValues();  
  
  
  } // init
  
  public function pageOpen(&$include)
  {
  
    $this->setHeaderValues();
    $include = 'includes/std-open.inc.php';
    
  }
  
  
  public function pageContent(&$include)
  {
  
    $this->callExecuteWork();
    $include = 'includes/std-page.inc.php';
    return '';
    
  }
  
  
  public function pageClose(&$include)
  {

    $s = '';
    
    $s .= Fx::write('</div>');
    $s .= Fx::write('</div>');
   
    foreach($this->jsScripts as $script)
    {
      $s .= Fx::write("<script src='{$script}' type='text/javascript'></script>");
    }
    
    if ($this->js)
    {
      
      $s .= Fx::write('<script type="text/javascript">');
      $s .= implode(PHP_EOL, $this->js);
      $s .= Fx::write('</script>');
    
    }
   
    $s .= Fx::write('</body>');
    $s .= Fx::write('</html>');

    return $s;
    
  }
  
  public function addJsScript($script)
  {
  
    $this->jsScripts[] = Fx::getPath('SCRIPTS', 'js/' . $script);
  
  
  } // addJsScript
  
  
  public function addJs($js)
  {
  
    $this->js[] = $js;
  
  
  } // addJsScript
 
  
  private function setBaseHtmlValues()
  {

    /*
     head
    */
    
    $ar = array('title', 'icon', 'favicon', 'css');
    
    foreach ($ar as $html)
    {
      Fx::html($html, '');
    }
    
    // title
    Fx::html('title', 'Open Validation Server');
        
    // icons
    Fx::html('icon', Fx::getPath('IMAGES', 'logo16.png'));
    Fx::html('favicon', Fx::getPath('IMAGES', 'favicon.ico'));
    
    // css    
        
    $arCss = array();
    $arCss[] = Fx::getPath('SCRIPTS', 'css/engine.css');
    
    Fx::html('css', $arCss);
    
    /*
      header
    */
    
    $ar = array('appName', 'topMenu', 'userStatus', 'userName');
    
    foreach ($ar as $html)
    {
      Fx::html($html, '');
    }
    
    // appName
    $params = array('caption' => strtolower(Fx::get('appName')));
    $href = fn::getHrefHtml($this->baseUrl, $params);
    Fx::html('appName', $href);
    
    
    /*
      side navigation and content
    */
    $ar = array('pageNav', 'pageContent');
    
    foreach ($ar as $html)
    {
      Fx::html($html, '');
    }
  
  } // setBaseHtmlValues
  
  
  private function callExecuteWork()
  {

    if ($this->vanilla)
    {
      
      if (!Fx::fatalError())
      {
        $this->executeWork();
      }
      
      return;
      
    }
    
    $this->setNavigation();
    
    
    $this->executeWork();
    
  
  } // callExecuteWork
  
  
  private function setAppParams()
  {
  
    if ($this->vanilla)
    {
      return;
    }
    
    Model_Session::formIdUpdate();
    
    $this->appParams['name'] = session_name();
    //$this->appParams['path'] = sfn::getBasePath();
    $this->appParams['level'] = App_Model_Session::getToken('level');
    $this->appParams['id'] = App_Model_Session::getFormId();
    $this->appParams['appName'] = $this->Agent->appName;
    $this->appParams['appHostUrl'] = $this->Agent->appHostUrl;
    $this->appParams['errorPage'] = cn::PG_ERR_ACCESS . '.html';
    $this->appParams['jsPath'] = $this->scriptJsPath;
    $this->appParams['version'] = $this->scriptJsVersion;
    $this->appParams['page'] = $this->Route->page;
    $this->appParams['jsPage'] = $this->jsPage ? $this->Route->page : '';
    $this->appParams['json'] = 'json2.js';
    
    // js files
    $ar = array();
    $this->appParams['js'] = $this->scriptsJs;
           
    // pages
    $ar = array();
    $ar['PG_HOME'] = cn::PG_HOME;
    $ar['PG_MAIN'] = cn::PG_MAIN;
    $this->appParams['pages'] = $ar;
    
    // constants (cn)
    $ar = array();
    $ar['PM_VIEW'] = 'page';
    $ar['PM_CID'] = cn::PM_CID;
    $ar['PM_CMD'] = cn::PM_CMD;
    $ar['PM_XHR'] = cn::PM_XHR;
    $ar['PM_JSN'] = cn::PM_JSN;
    $this->appParams['cn'] = $ar;
     
  } // setAppParams
              
  
  private function getTopMenu()
  {

    $html = '';
    
    if ($this->vanilla)
    {
      return $html;
    }
    
    $arMenu = array();
    $idBase = 'menu';
           
    if ($this->Route->page !== cn::PG_HOME)
    {        
      // home
      $item['page'] = cn::PG_HOME;
      $item['caption'] = 'home';
      $item['title'] = 'Go to the main page';
      $item['id'] = "{$idBase}-{$item['page']}";
      $arMenu[] = $item;
    }

    if ($this->Route->page !== cn::PG_CONTACT)
    {    
      // contact
      $item['page'] = cn::PG_CONTACT;
      $item['caption'] = 'contact';
      $item['title'] = 'Send us an email';
      $item['id'] = "{$idBase}-{$item['page']}";
      $arMenu[] = $item;
    }
    
    if ($this->Route->page !== cn::PG_SIGN_IN)
    {
    
    // signout
      if (fn::loggedIn())
      {
        $item['page'] = cn::PG_SIGN_OUT;
        $item['caption'] = 'sign out';
        $item['title'] = 'Sign Out of your account';
        $item['id'] = "{$idBase}-{$item['page']}";
        $arMenu[] = $item;    
      }
      else
      {
        $item['page'] = cn::PG_SIGN_IN;
        $item['caption'] = 'sign in';
        $item['title'] = 'Sign In';
        $item['id'] = "{$idBase}-{$item['page']}";
        $arMenu[] = $item;        
      }
    
    }
    
    foreach ($arMenu as $item)
    {
          
      if ($html)
      {
        $html .= '&nbsp;&nbsp;|&nbsp;&nbsp;'; 
      }
             
      $html .= fn::getHrefHtml($this->baseUrl, $item);
          
    }          
    
    return $html;
    
  } // getTopMenu
  
  
  private function setHeaderValues()
  {
    
    /*
      topMenu
      userStatus
      userName
    */
    
    Fx::html('topMenu', $this->getTopMenu());
    
    if ($this->vanilla)
    {
      return;
    }
    
    if (fn::loggedIn())
    {
      $status = Model_Session::getToken('level') > 1 ? 'Admin' : 'User';
      $user = Model_Session::getToken('firstName') . ' ' . Model_Session::getToken('lastName');
    }
    else
    {
      $status = 'User';
      $user = 'Guest';
    }
    
    Fx::html('userStatus', $status);
    Fx::html('userName', $user);
                   
  } // setHeaderValues

     
  private function setNavigation()
  {
   
    $s = '';
    
    $s .= Fx::write('<ul>');
    $s .= $this->getNavList();
    $s .= Fx::write('</ul>');
    
    Fx::html('pageNav', $s);
      
  } // setNavigation

  
  private function getNavList()
  {
  
    //$showDetails = fn::loggedIn() && Model_Session::getToken('memberId');
        
    $ini = Fx::getPath('CONFIG', 'menu.ini');    
    $arIni = Fx::iniParse($ini);
    
    $page = $this->Route->page;
    $arMenu = $this->getNavInfo($arIni, $page);
        
    $s = '';
    
    foreach ($arMenu as $dummy => $item)
    {
       
      $li = '<li';
      
      if ($item['active'])
      {
        $li .= ' class="active">';
        $li .= $item['caption'];
      }
      else
      {
        $li .= '>' . fn::getHrefHtml($this->baseUrl, $item);
      }
      
      $li .= '</li>' . PHP_EOL;
      $s .= $li;       
    }
    
    return $s;
  
  } // getNavList
  
  
  private function getNavInfo($arIni, $page)
  {
  
    $arInfo = array();
    
    if (!isset($arIni['home']))
    {
      return;
    }
    
    if (!$home = Fx::varSet($arIni['home'], 'items', false))
    {
      return;
    }
    
    $mpage = cn::PG_HOME;
    $mcaption = 'Home';
    $mactive = $page === cn::PG_HOME;
    $msection = true;
    
    $section = '';
    $arTarget = array();
    $arSections = array();
     
    if (!$mactive)
    {
      $arTarget = $this->getNavTarget($arIni, $page, $section);
    }
        
    $arInfo[] = array(
      'page' => $mpage,
      'caption' => $mcaption,
      'active' => $mactive,
      'section' => $msection
    );
        
    foreach ($home as $homeSection)
    {
    
      if ($arTarget && $homeSection === $section)
      {
        $arSections = $arTarget;
        break;
      }
      
      if ($ar = $this->getNavSection($arIni, $page, $homeSection, true))
      {
        $arSections = array_merge($arSections, $ar);
      }
          
    }
    
    if ($arSections)
    {
      $arInfo = array_merge($arInfo, $arSections);
    }
    
    return $arInfo;   
  
  } // getNavInfo
  
  
  private function getNavTarget($arIni, $page, &$section)
  {

    $section = '';
    
    foreach ($arIni as $sec => $data)
    {
    
      if (isset($data['page']) && $data['page'] === $page)
      {
        $ar = explode('-', $sec);
        $section = $ar[0];
        break;        
      }
    
    }

    if (!$section || !isset($arIni[$section]))
    {
      return;
    }
        
    if ($arSection = $this->getNavSection($arIni, $page, $section))
    {
      return $arSection;
    }
      
  } // getNavTarget
  
  
  private function getNavSection($arIni, $page, $section, $first = false)
  {

    if (!$section || !isset($arIni[$section]))
    {
      return;
    }
    
    if (!$items = Fx::varSet($arIni[$section], 'items', false))
    {
      return;
    }
    
    $arSection = array();
    $msection = count($items) > 1;
    $count = 0;
    
    
    foreach ($items as $item)
    {
    
      $count += 1;
            
      if ($first && $count > 1)
      {
        break;
      }
      
      if ($first && count($arSection) === 1)
      {
        
      }
      
      $itemSection = $section . '-' . $item;
      
      $details = Fx::varSet($arIni, $itemSection, '');
      
      if (!$details)
      {
        return;
      }
      
      $mpage = Fx::varSet($details, 'page', '');
      $mcaption = Fx::varSet($details, 'caption', '');
      $mactive = $mpage === $page;
            
      if (!$mpage || !$mcaption)
      {
        return;
      }
      
      $arSection[] = array(
        'page' => $mpage,
        'caption' => $mcaption,
        'active' => $mactive,
        'section' => $msection
      );
     
    }
    
    return $arSection;  
  
  } // getNavSection
  
  
  private function isNavSection($section)
  {
  
    return strpos($this->Route->page, $section) === 0;
  
  } // isNavSection
  
  
} // end View_BaseStd class

?>
