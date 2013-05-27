<?php

######################################################################################
# Versions
# 3.01 initial release
#
# ToDo:
#   read value UploadDir from config file to store the uploaded IGC non into system temp
######################################################################################
 
# conf file directory - if empty will use this script directory
# must be an absolute path, for example "C:/server/htdocs/dir" 
define('VALI_DIR_CONF', 'c:/wwwroot/vali');
     
# SERVER constants - change to reflect current build
define('VALI_SERVER_NAME', 'Open Validation Server');
define('VALI_SERVER_VERSION', '3.03');

# MAX_SIZE constant - maximum file upload form data size
define('VALI_MAX_SIZE', 3000000);

# DO NOT CHANGE ANY CONSTANT VALUES BELOW HERE

# RESULT constants
define('IV_RESULT_PASS', 'PASSED');
define('IV_RESULT_FAIL', 'FAILED');
define('IV_RESULT_ERROR', 'ERROR');

# STATUS IGC constants
define('IV_STATUS_IGC_PASSED', 1);
define('IV_STATUS_IGC_FAILED', 2);
define('IV_STATUS_IGC_FAILED_NC', 3);


class Vali
{

  /** @var ValiConfig */
  public $config;
  
  /** @var ValiInput */
  public $input;
  
  /** @var ValiLog */
  public $log;
  
  /** @var ValiResponse */
  public $response;
  
  public $debugData = array();
    
  public $mode = 0;
  public $error = 0;
  public $debug = false;
  public $out = '';
  public $verbose = false;
  
  private $internal = false;
  private $ivshell = '';
  private $ivStatus = 0;
  
  
  const IVSHELL_ID = 'IVSHELL:';
  const MODE_CGI = 0;
  const MODE_CMD = 1;
  const OUT_VALI = 'vali';
  const OUT_NCFAIL = 'ncfail';
  
  const IGC_PASSED = 'IGC_PASSED';
  const IGC_FAILED = 'IGC_FAILED';
  const IGC_FAILED_NC = 'IGC_FAILED_NC';

  const ERR_REQUEST = 'ERR_REQUEST'; 
  const ERR_UPLOAD = 'ERR_UPLOAD';
  const ERR_UPLOAD_HASH = 'ERR_UPLOAD_HASH';
  const ERR_SIZE = 'ERR_SIZE';
  const ERR_FORMAT = 'ERR_FORMAT';
  const ERR_FORMAT_GZIP = 'ERR_FORMAT_GZIP';
  const ERR_FORMAT_ZIP = 'ERR_FORMAT_ZIP';
  const ERR_UNSUPPORTED = 'ERR_UNSUPPORTED';
  const ERR_SERVICE = 'ERR_SERVICE';
  
    
  public function execute($params = null)
  {
    
    $this->init($params);
    
    $this->executeWork();
    
    $output = $this->outputGet();
    
    if ($this->internal)
    {
      return;
    }
    else
    {
      $this->outputSend($output);
    }      
        
  } # Vali::execute
  
  
  private function executeWork()
  {

    # input
    if (!$this->getInput())
    {
      return;
    }    
    
    # config    
    if (!$this->getConfig())
    {
      return;
    }
    
    # validate    
    if (!$this->validateIgc())
    {
      return;
    }
    
    # check that status has been set
    if ($this->response->status === self::ERR_SERVICE)
    {
      $this->error = 'Unknown error at end of process - status not set';
    }
 
  } # Vali::executeWork
  
  
  private function init($params)
  {

    # just in case this has not been set
    ini_set('display_errors', 0);
    
    # set ValiUtils::$Vali
    ValiUtils::$Vali = $this;    
    
    # check params
    if (is_array($params))
    {
      $this->internal = true;
      $this->debug = isset($params['debug']) && $params['debug'];
      $this->out = isset($params['out']) && $params['out'];
    }
        
    # set mode
    $this->mode = PHP_SAPI === 'cli' ? Vali::MODE_CMD : Vali::MODE_CGI;
    
    # set 500 header if cgi and not internal
    if ($this->mode === Vali::MODE_CGI && !$this->internal)
    {
      ob_start();
      header("HTTP/1.1 500 Internal Server Error", true, 500);     
    }
            
    # get the directory we are in to use as default
    $path = ValiUtils::backSlashes(dirname(__FILE__));
    
    $this->ivshell = ValiUtils::makeFilename($path, 'ivshell.exe');
    
    # get the basename of this file to use for conf and log files
    $basename = basename(__FILE__, '.php');
    
    $this->config = new ValiConfig($this, $path, $basename);
    $this->log = new ValiLog($basename); 
        
    $this->response = new ValiResponse();
    $this->response->server = ValiUtils::getServerName();
                
    # create ValiInput last
    $this->input = new ValiInput($this);
          
  } # Vali::init
  
  
  private function getInput()
  {

    $this->input->execute($this->mode);
            
    return empty($this->error);
      
  } # Vali::getInput
  
  
  private function getConfig()
  {
  
    $this->config->getValues($this->input->ccc);
    
    if ($this->error)
    {
      return;
    }
    
    if (!$this->config->module)
    {
      $this->error = self::ERR_UNSUPPORTED;
    }
    
    return empty($this->error);
    
  } # Vali::getConfig
  
  
  private function validateIgc()
  {

    $this->ivStatus = 0;
    
    $this->validateIgcWork();
    
    # errors will be ivshell output format errors
    if ($this->error)
    {
      return;
    }
    
    switch ($this->ivStatus)
    {
    
      case IV_STATUS_IGC_PASSED :
        $this->response->status = self::IGC_PASSED;
        break;
        
      case IV_STATUS_IGC_FAILED :
        $this->response->status = self::IGC_FAILED;
        break;
        
      case IV_STATUS_IGC_FAILED_NC :
        $this->response->status = self::IGC_FAILED_NC;
        break;
        
      default :
      
        $s = 'invalid status ('. $this->ivStatus .')  from ' . $this->config->module->valiName;
        $s .= ' for: ' . $this->response->igc;
        $this->error = $s;      
    
    } 
        
    return empty($this->error);             
                     
  } # Vali::validateIgc
  
  
  private function validateIgcWork()
  {

    ValiUtils::debug("Check module {$this->input->ccc} at: {$this->config->module->vali}");
    
    if (!file_exists($this->config->module->vali))
    {
      $this->error = "Module {$this->input->ccc} not found at: {$this->config->module->vali}";
      return;
    }
    
    ValiUtils::debug('Check ivshell at: ' . $this->ivshell);
    
    if (!file_exists($this->ivshell))
    {
      $this->error = 'ivshell not found at: ' . $this->ivshell;
      return;
    }
         
    ValiUtils::debug('Execute ivshell with module ' . $this->input->ccc);
    
    $params = array();
    $params[] = $this->ivshell;
    $params[] = '-s';
    $params[] = $this->config->module->vali;
    $params[] = $this->config->module->params;
    $params[] = $this->input->igcfile;
        
    $cmd = ValiUtils::buildCommand($params);
            
    $lastLine = exec($cmd, $output);
    
    if (!$lastLine)
    {
      $this->error = 'no output from ivshell for ' . $this->config->module->valiName;
      return;    
    }
    
    if (!$this->getIgcResponse($lastLine, $output))
    {
      $this->error = 'invalid output from ivshell for ' . $this->config->module->valiName;  
      return;
    }
                        
  } # Vali::validateIgcWork
  
  
  private function getIgcResponse($lastLine, $output)
  {
           
    ValiUtils::debug('Checking response: ' . $lastLine);
    
    # tidy output first
    $this->response->output = array();
    
    foreach ($output as $line)
    {
    
      $line = trim($line);
      
      if ($line || $this->response->output)
      {
        $this->response->output[] = ValiUtils::toUtf8($line);
      }
      
    }
        
    $res = '';
    
    if (strpos($lastLine, self::IVSHELL_ID) === 0)
    {
      $res = substr($lastLine, strlen(self::IVSHELL_ID));
      array_pop($this->response->output);
    }
        
    if (!$res)
    {
      return;
    }
        
    list($result, $status, $msg) = explode(',', $res, 3);
    $this->ivStatus = intval($status);
    
    if (!$result || !$this->ivStatus || !$msg)
    {
      return;                        
    }
    
    switch ($result)
    {
    
      case IV_RESULT_PASS:
        
        $this->response->result = IV_RESULT_PASS;
        
        if ($this->ivStatus !== IV_STATUS_IGC_PASSED)
        {
          return;
        }
        
        break;
        
      case IV_RESULT_FAIL:
        
        $this->response->result = IV_RESULT_FAIL;

        if ($this->ivStatus !== IV_STATUS_IGC_FAILED && $this->ivStatus !== IV_STATUS_IGC_FAILED_NC)
        {
          return;
        }
                
        break;
        
      case IV_RESULT_ERROR:
        
        $this->response->result = IV_RESULT_ERROR;
        break;
      
      default:
        
        return;                    
    
    }
        
    $this->response->msg = $msg;
    
    return true;  
  
  } # Vali::getIgcResponse
  
  
  private function outputGet()
  {
  
    if ($this->error)
    {
      $this->errorProcess();
    }
    
    # delete tmpfile if we have one
    ValiUtils::deleteTmpFile($this->input);  
    
    if ($this->verbose)
    {
      $this->outputVerbose();
      exit;
    }
    else if (!$this->internal)
    {
      return $this->outputToJson();
    }      
  
  } # Vali::outputGet

    
  
  private function outputVerbose()
  {

    if ($this->debug)
    {
      print PHP_EOL . PHP_EOL;
    }
    
    $s = '';
    
    if ($this->out)
    {

      $output = array();        
      foreach ($this->response->output as $line)
      {
        $output[] = '> ' . $line;
      }
      
      $s .= implode(PHP_EOL, $output);
      $s .= $output ? PHP_EOL . PHP_EOL . PHP_EOL : '';
          
    }
    
    $s .= $this->response->result;
    
    if ($this->response->status !== self::IGC_PASSED)
    {
      $s .= ' [' . $this->response->status . ']'; 
    }
    
    $s .= PHP_EOL;
    
    $s .= $this->response->msg . PHP_EOL;
    $s .= $this->response->igc . PHP_EOL;
    $s .= PHP_EOL;
    
    print $s;
         
  } # Vali::outputVerbose
  
  
  private function outputToJson()
  {

    $arOutput = $this->outputFormat();
    
    if (defined('JSON_UNESCAPED_UNICODE'))
    {
      return json_encode($arOutput, JSON_UNESCAPED_UNICODE);
    }
    else
    {
      return json_encode($arOutput);
    }
      
  } # Vali::outputToJson
  
  
  private function outputFormat()
  {
  
    $ar = array();
    $ar['result'] = $this->response->result;
    $ar['status'] = $this->response->status;
    $ar['msg'] = $this->response->msg;
    $ar['igc'] = $this->response->igc;
    $ar['ref'] = $this->response->ref;
    $ar['server'] = $this->response->server;
    
    if ($this->out === self::OUT_VALI ||
      ($this->out === self::OUT_NCFAIL && 
      $this->response->status === self::IGC_FAILED_NC))
    {
      $ar['output'] = $this->response->output;
    }    
                  
    return $ar;
          
  } # Vali::outputFormat
 
  
  private function outputToUtf8(array $arValues)
  {

    foreach ($arValues as $key => $value)
    {
    
      if (ValiUtils::nonAscii($value))
      {
          
        if (!mb_detect_encoding($value, 'UTF-8', true))
        {
          $arValues[$key] = utf8_encode($value);
        }
        
      }
      
    }
    
    return $arValues;
      
  } # Vali::outputToUtf8
  
  
  private function outputSend($output)
  {
    
    while (@ob_end_clean());
                                   
    if ($this->mode === Vali::MODE_CGI)
    {
      header("HTTP/1.1 200 Okay", true, 200);
      header("Content-type: application/json; charset=utf-8");
      header("Cache-Control: no-cache, must-revalidate");
      header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('-1 day')) . ' GMT');
    }
    
    print $output;
    exit; 
  
  } # Vali::outputSend
  
    
  private function errorProcess()
  {

    # we are a fatal error if we do not start ERR_
    if (substr($this->error, 0, 4) !== 'ERR_')
    {
      error_log($this->error);
      ValiUtils::debug($this->error);
      $status = self::ERR_SERVICE;
    }
    else
    {
      $status = $this->error;
    } 
            
    $this->response->result = IV_RESULT_ERROR;
    $this->response->status = $status;
    $this->response->msg = $this->errorGetMsg($status);
  
  } # Vali::errorProcess
  
  
  private function errorGetMsg($status)
  {
 
    switch ($status)
    {

      case self::ERR_REQUEST:
        return 'Bad request';
            
      case self::ERR_UPLOAD:
        return 'File upload failed';
    
      case self::ERR_UPLOAD_HASH:
        return 'File hashes do not match';
        
      case self::ERR_SIZE:
        $max = (VALI_MAX_SIZE) / 1000000;
        return "File too large - max {$max}mb";
        
      case self::ERR_FORMAT:
        return 'Not an IGC file';
        
      case self::ERR_FORMAT_GZIP:
        return 'Not a valid gzip file';
        
      case self::ERR_FORMAT_ZIP:
        return 'Not a valid zip file';
        
      case self::ERR_UNSUPPORTED:
        return 'IGC program not supported - ' . $this->input->ccc;
        
      case self::ERR_SERVICE:
        return 'Service error';
        
      default:
        return 'Unspecified error';      
    }
  
  } # Vali::errorGetMsg
    

} # end class Vali


class ValiResponse
{

  public $result = IV_RESULT_ERROR;
  public $status = Vali::ERR_SERVICE;
  public $msg = '';
  public $igc = '';
  public $ref = '';
  public $server = '';
  public $output = array();
  
        
} # end class ValiResponse


class ValiInput
{
  
  public $igcfile = '';
  public $tmpfile = '';
  public $ccc = '';
  
  /** @var Vali */  
  private $base;
    
  /** @var ValiResponse */  
  private $response;
 
  private $error = '';
  private $mode = 0;
  private $md5 = null;
  private $fileContent = '';
    
  const FMT_IGC = 'igc';
  const FMT_GZIP = 'gzip';
  const FMT_ZIP = 'zip';
             
  
  public function __construct(Vali $base)
  {
  
    $this->base = $base;
    $this->response = $base->response;
    $this->error = &$base->error;
                  
                          
  } # ValiInput::constructor
  
  
  public function execute($mode)
  {

    $this->mode = $mode;
    
    if ($this->mode === Vali::MODE_CGI)
    {
      
      ValiUtils::rawInputCheck();
      $this->checkInputCgi();
    
    }
    else
    {
      $this->checkInputCmd();
      
      if ($this->base->verbose)
      {
        while (@ob_end_clean());
      }
      
    }
    
    ValiUtils::debug('Checking input');
    
    if ($this->error)
    {
      return;
    }
                         
    # check the file - sets this->ccc
    if (!$this->checkIgcFile($this->igcfile, $this->response->igc, $this->md5))
    {
      return;
    }
    
    # move the file if needed - sets this->igcfile and this->tmpfile
    if (!$this->moveIgcFile())
    {
      return;
    }
           
  } # ValiInput::execute
             
  
  private function checkInputCgi()
  {
             
    # check uploaded igc file
    if (!isset($_FILES['igcfile']))
    {
      $this->error = Vali::ERR_REQUEST;
      return;    
    }

    if (!$_FILES['igcfile']['name'])
    {
      $this->error = Vali::ERR_REQUEST;
      return;        
    }
    
    $this->igcfile = ValiUtils::sanitizeFilePath($_FILES['igcfile']['tmp_name']);
    $basename = basename(ValiUtils::sanitizeFilePath($_FILES['igcfile']['name']));
    $this->response->igc = ValiUtils::toUtf8($basename);
    
    if ($_FILES['igcfile']['error'] != UPLOAD_ERR_OK)
    {
      $this->error = Vali::ERR_UPLOAD;
      return;    
    }
    
    if (!is_uploaded_file($_FILES['igcfile']['tmp_name']))
    {
      $this->error = Vali::ERR_UPLOAD;
      return;    
    }
    
    # check ref      
    if (isset($_POST['ref']))
    {
      $this->response->ref = $_POST['ref'];
    }
    
    # check output - currently only vali      
    if (isset($_POST['out']))
    {
      
      $ar = array(Vali::OUT_VALI, Vali::OUT_NCFAIL);
      
      if (in_array($_POST['out'], $ar))
      {
        $this->base->out = $_POST['out'];
      }
      
    }
            
    # check md5      
    if (isset($_POST['md5']))
    {
      $this->md5 = $_POST['md5'];
    }
      
  } # ValiInput::checkInputCgi
  
  
  private function checkInputCmd()
  {

    # arguments are [-v verbose | -vx verbose/debug] filename 
    $args = $GLOBALS['argv'];
    array_shift($args);
            
    while ($args)
    {
            
      # verbose or verbose/debug
      if ($args[0] === '-v' || $args[0] === '-vx')
      {
        
        # make sure verbose is not already set
        if ($this->base->verbose)
        {
          $this->error = Vali::ERR_REQUEST;
          return;              
        }
        
        $this->base->verbose = true;
        $this->base->out = Vali::OUT_VALI;
        $this->base->debug = $args[0] === '-vx'; 
        array_shift($args);
        
        continue;
        
      }
                        
      # igc file name
      $this->igcfile = ValiUtils::sanitizeFilePath($args[0]);
      $this->response->igc = ValiUtils::toUtf8(basename($this->igcfile));
      break;
                   
    }
           
    # check input file
    if (!$this->igcfile)
    {
      $this->error = Vali::ERR_REQUEST;
      return;      
    }
             
  } # ValiInput::checkInputCmd
    
  
  private function checkIgcFile($igcfile, $igcname, $md5)
  {

    /*
      Checks for igc extension from user igc file name,
      checks file size, checks md5 if passed in, gets CCC from first (A record) line   
      
      Inputs
        igcfile - the temp file name of the uploaded igc file, or
        the system file name 
        igcname  - the user igc base file name (same as igcfile for cmd mode)
        md5 - hash of igcfile
      
      Outputs
      Sets this->ccc
          
      Returns
        True or false
                    
    */  
    
    # check size
    ValiUtils::debug("Checking file: {$igcfile}");
    
    $fileSize = @filesize($igcfile);
    
    if ($fileSize === false)
    {
      $this->error = $this->mode === Vali::MODE_CGI ? Vali::ERR_UPLOAD : Vali::ERR_REQUEST;
      return;
    }
    elseif ($fileSize < 0 || $fileSize > VALI_MAX_SIZE)
    {
      # note: < 0 to trap greater than 2gb on 32 bit
      $this->error = Vali::ERR_SIZE;
      return;    
    }
    
    $fp = false;
    
    # open the file
    if (!$fp = @fopen($igcfile, 'rb'))
    {
      $this->error = $this->mode === Vali::MODE_CGI ? Vali::ERR_UPLOAD : Vali::ERR_REQUEST;
      return;    
    }
    
    # either check hash and create buf4, or just create buf4
    if ($this->mode === Vali::MODE_CGI && !is_null($this->md5))
    {
      
      ValiUtils::debug("Check file md5");
      
      # get file contents
      $this->fileContent = fread($fp, $fileSize);
            
      # compare hashes
      if (strcasecmp($md5, md5($this->fileContent)) !== 0)
      {
        error_log('hashes: ' . $md5 . ' ' . md5($this->fileContent));
        
        $this->error = Vali::ERR_UPLOAD_HASH;
        return;
      }
      else
      {
        $buf4 = substr($this->fileContent, 0, 4);
      }
          
    }
    else
    {
      $buf4 = fread($fp, 4);
      rewind($fp);
    }
    
    if (!$format = $this->checkFileFormat($buf4, false))
    {
      $this->error = Vali::ERR_FORMAT;
      fclose($fp);
      return;    
    }
        
    if ($format === self::FMT_IGC)
    {
      $res = true;  
    }
    else
    {
      
      if ($format === self::FMT_GZIP && !$this->fileContent)
      {
        $this->fileContent = fread($fp, $fileSize);
      }
            
      $res = $this->inflate($format, $igcfile);
      
    }
    
    fclose($fp);
            
    return $res;
  
  } # ValiInput::checkIgcFile
  
  
  private function checkFileFormat($buf4, $igc)
  {

    $format = false;
    
    if (preg_match('/^A[A-Z]{1}[A-Z,0-9]{2}/', $buf4))
    {
      $format = self::FMT_IGC;
      $this->ccc = substr($buf4, 1, 3);
      ValiUtils::debug("Module required: {$this->ccc}");
    }
    else if (preg_match('/^\\x50\\x4B\\x03\\x04/', $buf4))
    {
      $format = self::FMT_ZIP;
    }
    else if (preg_match('/^\\x1f\\x8b\\x08[\\x00-\\xff]/', $buf4))
    {
      $format = self::FMT_GZIP;
    }
    
    if ($igc)
    {
      return $format === self::FMT_IGC;
    }
    else 
    {
      return $format;
    }
      
  } # ValiInput::checkFileFormat
  
  
  private function inflate($format, $filename)
  {
  
    $unpacked = false;
    
    ValiUtils::debug("Checking compression: {$format}");
    
    if ($format === self::FMT_GZIP)
    {
      $unpacked = $this->gzDecodeWrapper($this->fileContent);
    }
    else 
    {
      $unpacked = $this->unzip($filename);
    }
    
    if (!$unpacked)
    {
      $this->error = $format === self::FMT_GZIP ? Vali::ERR_FORMAT_GZIP : Vali::ERR_FORMAT_ZIP;
    }
    else if (!$this->checkFileFormat(substr($unpacked, 0, 4), true))
    {
      $this->error = Vali::ERR_FORMAT;
    }
    else
    {
      $this->fileContent = $unpacked;
      return true;   
    }
      
  } # ValiInput::inflate
  
  
  private function gzDecodeWrapper($data)
  {
    
    if (function_exists("gzdecode"))
    {
      return gzdecode($data);
    }
    
    $flags = ord(substr($data, 3, 1));
    $headerlen = 10;
    $extralen = 0;
    $filenamelen = 0;
    
    if ($flags & 4)
    {
      $extralen = unpack('v' ,substr($data, 10, 2));
      $extralen = $extralen[1];
      $headerlen += 2 + $extralen;
    }
    
    if ($flags & 8) // filename
        $headerlen += strpos($data, chr(0), $headerlen) + 1;
        
    if ($flags & 16) // comment
        $headerlen += strpos($data, chr(0), $headerlen) + 1;
        
    if ($flags & 2) // CRC at end of headers
        $headerlen += 2;
        
    return gzinflate(substr($data, $headerlen));
    
  } # ValiInput::gzDecodeWrapper
  
  
  private function unzip($filename)
  {

    $zip = @zip_open($filename);
    
    if (is_resource($zip) && $zip_entry = zip_read($zip))
    {
      
      if (is_resource($zip_entry) && zip_entry_open($zip, $zip_entry))
      {
        return zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
      }
      
    }
          
  } # ValiInput::unzip
    
  
  private function moveIgcFile()
  {

    /*
      Checks for non-ascii filename and creates a safe temp file, or moves uploaded
      file to safe temp file with an igc extension
                     
      Outputs
        Sets this->igcfile - the name of the igcfile to check, might now be a temp file
        Sets this->tmpfile - the name of the temp file (ie the same as this->igcfile) if created
      Returns
        True or false
    */
         
    $newfile = $this->igcfile;
    
    $hasContent = (bool) $this->fileContent;
    
    # check for non-ascii characters
    
    if ($hasContent || ValiUtils::nonAscii($this->igcfile) || $this->mode === Vali::MODE_CGI)
    {
            
      # get temp file name, in newfile
      if (!$fp = ValiUtils::createTmpIgcFile($newfile, $hasContent))
      {
        $this->error = 'Unable to create temp file';
        return;    
      }
               
      # set tmpfile, it will be deleted later
      $this->tmpfile = $newfile;
      
      ValiUtils::debug('Move to temp file: ' . $newfile);
      
      $ok = false;
      
      if ($hasContent)
      {
        
        if (@fwrite($fp, $this->fileContent))
        {
          $ok = true;
        }
        
      }
      else
      {
      
        if (@copy($this->igcfile, $newfile))
        {
          $ok = true;        
        }
      
      }
      
      if (!$ok)
      {
        $this->error = 'Unable to move ' . $this->igcfile . ' to ' . $newfile;
        return;              
      }
      
      if ($this->mode === Vali::MODE_CGI)
      {
        @unlink($this->igcfile);
      }
                                   
    }
    
    # set this->igcfile
    $this->igcfile = $newfile;
    
    # clear this->fileContent
    $this->fileContent = '';
    
    return true;
       
  } # ValiInput::moveIgcFile
  
  
} # end class ValiInput


class ValiModule
{

  public $ccc = '';
  public $vali = '';
  public $valiName = '';
  public $params = '';
  public $dll = false;

} # end class ValiModule


class ValiConfig
{

  public $Filename = '';
  public $LogDir = '';
  public $LogMode = '';
  public $ModuleDir = '';
  
  /** @var ValiModule */
  public $module;
  
  private $mode;
  private $error;
  private $modules = array();

  const FMT_ERR = 'Config error at line %d: %s %s';
  
  public function __construct(Vali $base, $defaultDir, $basename)
  {

    $this->mode = &$base->mode;
    $this->error = &$base->error;
        
    if (VALI_DIR_CONF)
    {
      $this->filename = ValiUtils::makeFilename(VALI_DIR_CONF, $basename, 'conf');
    }
    else
    {
      $this->filename = ValiUtils::makeFilename($defaultDir, $basename, 'conf');
    }
    
    $this->logDir = $defaultDir;
    $this->moduleDir = $defaultDir;
                                        
  } # ValiConfig::constructor
  
  
  public function getValues($cccSearch = '')
  {
  
    $this->module = null;
    $this->getValuesFromFile($cccSearch);
    
    ValiUtils::debug('Module directory: ' . $this->ModuleDir);
          
  } # ValiConfig::getValues
  
  
  private function getValuesFromFile($cccSearch)
  {
  
    ValiUtils::debug('Read config file: ' . $this->filename);
    
    if (!$arFile = file($this->filename))
    {
      $this->error = 'Unable to open config file: ' . $this->filename;
      return;
    }
  
    if ($cccSearch)
    {
      ValiUtils::debug('Search for module ' . $cccSearch);
    }
    
    $this->getValuesFromFileWork($arFile, $cccSearch);
    
    if ($this->module)
    {
      $this->module->vali = ValiUtils::makeFilename($this->ModuleDir, $this->module->valiName);
    }
   
  } # ValiConfig::getValuesFromFile 
  
  
  private function getValuesFromFileWork($arFile, $cccSearch)
  {
       
    $count = count($arFile);
    $index = 0;
    $class = get_class($this);
    
    $splitRegex = '/[\s]+/';
    
    for ($i = 0; $i < $count; ++ $i)
    {
    
      $index += 1;
      $line = trim($arFile[$i]);
      
      # skip empty lines or comment lines (prefixed #)
      if (!$line || $line[0] === '#')
      {
        continue;
      }
    
      # split the line into 2 - directive and value
      if ($parts = preg_split($splitRegex, $line, 2))
      {
        $directive = $parts[0];
        $value = isset($parts[1]) ? $parts[1] : '';  
      }
      else
      {
        continue;
      }
    
      if (!$value)
      {
        continue;
      }
        
      if ($directive === 'Module')
      {

        if (!$data = $this->checkModuleFromFile($value, $splitRegex, $cccSearch))
        {
          $this->error = sprintf(self::FMT_ERR, $index, 'Module', $this->error);
          return;
        }
        
        $module = new ValiModule();
        $module->ccc = $data[0];
        $module->valiName = $data[1];
        $module->params = $data[2];
                
        if ($cccSearch && $module->ccc === $cccSearch)
        {
          
          if (!$this->module)
          {
            $this->module = $module;
            ValiUtils::debug("Found module {$cccSearch}: {$module->valiName}");
          }
          else
          {
            $this->error = sprintf(self::FMT_ERR, $index, 'Module', "{$module->ccc} duplicate entry");
            return;
          }
          
        }
              
      }
      elseif (property_exists($class, $directive))
      {
        
        # set the value
        $this->$directive = $value;
      
      }
      else
      {
        continue;
      } 
     
    }
          
  } # ValiConfig::getValuesFromFileWork
  
  
  private function checkModuleFromFile($value, $splitRegex, $cccSearch)
  {
           
    # value is: ccc vali params
    $parts = preg_split($splitRegex, $value, 2);
    $ccc = $parts[0];
    $value = isset($parts[1]) ? $parts[1] : '';  
               
    $parts = preg_split($splitRegex, $value, 2);
    $vali = $parts[0];
    $params = isset($parts[1]) ? $parts[1] : '';  
    
    if (!$this->checkModuleWork($ccc, $vali, $params, $data))
    {
    
      if (!$ccc || $ccc === $cccSearch)
      {
        return;
      }
      else
      {
        $this->error = '';
      }      
              
    }
    
    return $data;
          
  } # ValiConfig::checkModuleFromFile
  
  
  private function checkModuleWork($ccc, $vali, $params, &$data)
  {

    $data = array($ccc, $vali, $params);
        
    if (!$ccc || !$vali || !$params)
    {
      $this->error = $ccc ? "{$ccc} does not have enough values" : 'not enough values';
      return;    
    }
    
    # check ccc length and valid characters
    if (strlen($ccc) != 3 || preg_match('/[^A-Z]/', $ccc))
    {
      $this->error = "{$ccc} must be 3 uppercase characters A-Z";
      return;
    }
    
    if (!preg_match('/\.(dll|exe)$/i', $vali))
    {
      $this->error = "{$ccc} {$vali} must have a .dll or .exe file extension";
      return;
    }
    
    if (preg_match('/\.dll$/i', $vali))
    {
             
      if (!preg_match('/(cdc|std)/', $params))
      {
        $this->error = "{$ccc} call ({$params}) is incorrectly formatted";
        return;
      }      
    
    }
    else
    {
      
      $len = strlen($params);
      
      if ($params[0] !== '/' && $params[strlen($params)] !== '/')
      {
        $this->error = "{$ccc} match ({$params}) is incorrectly formatted";
        return;
      }
         
    }
    
    return true;
  
  } # ValiConfig::checkModuleWork
  

} # end class ValiConfig


class ValiLog
{

  public $pass = '';
  public $fail = '';
  public $check = '';
  
  
  public function __construct($basename)
  {
    
    $this->pass = strtolower($basename . '-php-' . IV_RESULT_PASS);
    $this->fail = strtolower($basename . '-php-' . IV_RESULT_FAIL);
    $this->check = strtolower($basename . '-php-' . 'check');
                                          
  } # ValiLog::constructor
  

} # end class ValiLog


class ValiUtils
{

  /** @var Vali */
  public static $Vali = null;
  
  
  public static function createTmpIgcFile(&$filename, $returnHandle, $dir = '')
  {
  
    if (!$dir || !file_exists($dir))
    {
      $dir = realpath(sys_get_temp_dir());
	  ValiUtils::debug('SysTempDir is: ' . $dir);
    }
          
    $extra = $filename ? $filename : uniqid(mt_rand());
    $count = 0;
    
    do
    {
      $name = sprintf("iv-%u", crc32(uniqid(mt_rand(), true) . $extra));
      $tmpName = ValiUtils::makeFilename($dir, $name, 'igc');
      $fp = @fopen($tmpName, 'x');
      ++ $count;
    }
    while (!$fp && $count < 20);
    
    if ($fp)
    {
      
      $filename = $tmpName;
      
      if (!$returnHandle)
      {
        fclose($fp);
        $fp = true;
      }
      
    }
    
    return $fp;
          
  } # ValiUtils::createTmpIgcFile
  

  public static function debug($line)
  {

    if (!self::$Vali->debug)
    {
      return;
    }
    
    $line = $line ? 'Dbg:: ' . self::toUtf8($line) : '';
            
    if (self::$Vali->verbose)
    {
      print $line . PHP_EOL;
    }
    else
    {
      self::$Vali->debugData[] = $line;
    }
          
  } # ValiUtils::debug
  
  
  public static function deleteTmpFile(ValiInput $input)
  {
  
    if ($input->tmpfile)
    {
      self::debug('Delete temp file: ' . $input->tmpfile);
      @unlink($input->tmpfile);
      $input->tmpfile  = '';
    }
      
  } # ValiUtils::deleteTmpFile
  
  
  public static function nonAscii($value)
  {
  
    return preg_match('/[^(\x20-\x7F)]/', $value);
    
  } # ValiUtils::nonAscii
  
  
  public static function toUtf8($value)
  {

    if (self::nonAscii($value))
    {
        
      if (!mb_detect_encoding($value, 'UTF-8', true))
      {
        $value = utf8_encode($value);
      }
      
    }
        
    return $value;
      
  } # ValiUtils::toUtf8
  
  
  public static function backSlashes($value)
  {
    # Replaces forward slashes with backslashes
    
    return str_replace('/', '\\', $value);
     
  } # ValiUtils::backSlashes 

  
  public static function buildCommand($params)
  {
    # Builds a command line, quoting values with spaces
    # and double-quoting the entire cmd if required   
    
    $cmd = '';
    
    foreach ($params as $value)
    {
    
      if (strpos($value, ' '))
      {
        $value = self::quoteFilename($value);
      }
      
      $cmd .= $cmd ? ' ' . $value : $value;
      
    }
    
    $format = PHP_VERSION_ID < 50300 ? '"%s"' : '%s';
    return sprintf($format, $cmd);
     
  } # ValiUtils::buildCommand 
  
  
  public static function makeFilename($path, $name, $ext = '')
  {
    # Creates a filename, using back slashes, from
    # path, name and ext arguments.
    # name can include the extension
    # ext is either with or without the period, or empty
    
              
    # remove quotes and make slashes forward
    $path = self::sanitizeFilePath($path); 
      
    # add trailing slash
    if (substr($path, strlen($path) - 1, 1) !== '\\')
    {
      $path .= '\\';
    }
    
    # add period to extension if required
    if ($ext && substr($ext, 0, 1) !== '.')
    {
      $ext = '.' . $ext;
    }
    
    # remove extension from name if found 
    if ($ext && strpos($name, $ext))
    {
      $name = str_replace($ext, '', $name);
    }
                       
    return $path . $name . $ext;
    
  } # ValiUtils::makeFilename
    
  
  public static function getServerName()
  {
  
    return VALI_SERVER_NAME . ' ' . VALI_SERVER_VERSION;
    
  } # ValiUtils::getServerName
   
  
  public static function quoteFilename($value)
  {
    # Adds enclosing double quotes if there are spaces in file name
    
    if (strpos($value, ' '))
    {
      $value = '"' . $value . '"';
    }
    
    return $value;

  } # ValiUtils::quoteFilename
  
  
  public static function rawInputCheck()
  {
  
    $_GET = array_map("ValiUtils::rawInputProcess", $_GET);
    $_POST = array_map("ValiUtils::rawInputProcess", $_POST);
    $_COOKIE = array_map("ValiUtils::rawInputProcess", $_COOKIE);
    
  } # ValiUtils::rawInputCheck
  
  
  public static function rawInputProcess($value)
  {

    if (get_magic_quotes_gpc())
    {
      $value = stripslashes($value);
    }
    
    return trim($value);
    
  } # ValiUtils::rawInputProcess

        
  public static function sanitizeFilePath($path)
  {
    # Removes quotes and converts slashes
    
    $path = self::trimQuotes($path);
    $path = self::backSlashes($path);
    return $path;

  } # ValiUtils::sanitizeFilePath
  
  
  public static function trimQuotes($value)
  {
    # Trims single and double quotes from a string
    
     return preg_replace('/[\"|\']/', '', $value);
     
  } # ValiUtils::trimQuotes
  
  
} # end class ValiUtils

?>
