#!c:\xampp\perl\bin\perl.exe

###################################################################################################
# Opensource under CDDL 1.0 license. 
# Do not remove or change this CDDL Header.
#
# The contents of this file are subject to the terms
# of the Common Development and Distribution License
# (the "License").  You may not use this file except
# in compliance with the License.
#
# You can obtain a copy of the license at
# http://opensource.org/licenses/cddl1.php
# See the License for the specific language governing
# permissions and limitations under the License.
# 
# Copyright John Stevenson, Andreas Rieck
# 1st release 1.6 01-12-2008, from original DHV-OLC vali by Andreas Rieck
# 2nd release 2.x Complete rewritten by John Stevenson 2010
###################################################################################################

###################################################################################################
# INSTALLATION:
#  1)simply modify shebang line of this script to point to your perl.exe
#  2)modify DIR_CONF at this script here below, to be able to find the config file
#  3)configure vali-igc.conf for your environment needs
#
# EXAMPLE CALL:
#   /usr/bin/curl -s -F igcfile=@sample-xgd.igc vali.fai-civl.org/cgi-bin/vali-igc.cgi
###################################################################################################

###################################################################################################
# Versions
# 2.0.0 initial release John
# 2.0.1 2010-04-13 arieck:
#   Input file copied to Windows temp directory and given a safe igc file name
#   Debug param introduced
#   Status Check checks modules in sorted order
#   Code review 
# 2.0.2 2010-06-03 arieck:
#	changed shebang first line to production environment perl.exe, xampp default package
#     using: "c:\xampp\perl\bin\perl.exe"
#	added html headers for sub help screen, otherwise we get cgi error, in case of wrong usage
#	  "The specified CGI application misbehaved by not returning a complete set of HTTP headers."
#	changed $ENV{SCRIPT_FILENAME} to: $ENV{SCRIPT_NAME}, otherswise issues at IIS6/perl combination
#     should be ok for Apache2 from xampp as well
#	  to verify env, use: http://vali.fai-civl.org/testbed/printentv.cgi
#   backport workaround vali-xgd bug from version 1.6
#	  Input files copied now into DIR_CONF (not %temp%) which must be script directory
#	  DIR_CONF setting is now required !
#	notice: at vali-igc.conf setting for ModuleDir is now required
#	  otherwise error 500 happen
#	notice: at vali-igc.conf setting for LogDir is required
#	  otherwise no log files written
# 2.0.3 2013-05-25 arieck:
#   ivshell.exe (backport from old vali1) is now required and used to execute any vali.exe
#   ivshell.exe must exist at ModuleDir, usual /cgi-bin
# 2.0.4 2013-05-26 arieck:
#   bugfix: if param nohtml is not set on URL caller, assume txt return type automatically
# 
###################################################################################################
# STATUS, BUGS, ToDo's: 
#   minor ToDo: igc upload fill up upload directory, in case of err 500 later on script
#		proposed solution: script should first autoerase *any* .igc and temp file if 
#		older then 15 minutes from upload directory
#   minor ToDo: igc upload directory should be read from value UploadDir at vali-igc.conf
#       current hardcoded here below as "DIR_IGCUPLOAD"
#
###################################################################################################

use warnings;
use strict;
use CGI;
use File::Basename;
use File::Spec;
use File::Temp;
use File::Copy;
                   
###################################################################################################
# conf file directory 
# must be an absolute path, for example "C:/server/htdocs/dir" 
use constant DIR_CONF => "C:/wwwroot/vali";

# upload dir 
# was the same as vali exe directory before, required workaround for vali-xgd bug
# actual the issue with vali-xgd seems not longer reproducible
# so we can use a more cleaner dedicated temp directory 
use constant DIR_IGCUPLOAD => "C:/wwwroot/vali/temp";
     
# SERVER constants - change to reflect current build
use constant SERVER_NAME => "Open Validation Server";
use constant SERVER_VERSION => "2.0.4";

# MAX_SIZE constant - maximum file upload form data size
use constant MAX_SIZE => 1024 * 3000;


###################################################################################################
# DO NOT CHANGE ANY CONSTANT VALUES BELOW HERE

# RESULT constants
use constant RESULT_PASS => "PASSED";
use constant RESULT_FAIL => "FAILED";
use constant RESULT_ERROR => "ERROR";

# STATUS constants
use constant STATUS_PASS => 0;
use constant STATUS_FAIL => 1;
use constant STATUS_BAD_UPLOAD => 2;
use constant STATUS_TOO_BIG => 3;
use constant STATUS_BAD_IGC => 4;
use constant STATUS_UNKNOWN_IGC => 5;
use constant STATUS_BAD_REQUEST => 400;
use constant STATUS_UNKNOWN => 500;
 
# OUTPUT constants
use constant OUTPUT_TXT => 0; 
use constant OUTPUT_JSON => 1;
use constant OUTPUT_XML => 2;
use constant OUTPUT_HTML => 3;
use constant OUTPUT_XVERBOSE => 4;
use constant OUTPUT_XHTML => 5;
use constant OUTPUT_XTXT => 6;

# MODE constants
use constant MODE_CGI => 0;
use constant MODE_CMD => 1;
use constant MODE_CHK => 2;

# ERROR FORMAT constant                                     
use constant ERR_FMT => "Configuration error at line %d : %s %s";
              
# we use 6 globals - debug, input, response, conf, log, cgi

my $debug = 0;

my %input = (
   "mode" => MODE_CGI,
   "filename" => "",
   "ccc" => ""
   );
   
my %response = (
  "result", "",
  "msg", "",
  "igc", "",
  "server", "",
  "status", STATUS_UNKNOWN,
  "output" => 0,
  "back" => "",
  "data" => "",
  "order" => [qw(result msg igc server status)]
  );
      
my %conf = (
  "Filename" => "",
  "LogDir" => "",
  "LogMode" => "",
  "HtmlWidth" => "",
  "HtmlBackground" => "",
  "HtmlTrackPw" => "",
  "ModuleDir" => ""
  );
  
my %log = (
  "pass" => "",
  "fail" => "",
  "check" => ""
  );  

my $cgi = CGI->new;

$CGI::POST_MAX = MAX_SIZE;
 
MAIN:
{
  init();
  execute(); 
  logResult();
  outputResult(); 
} # main

sub init
{
  # sets $response{"server"}, input{"mode"}
  
  my ($name, $path, $extension);
  $response{"server"} = join " " , SERVER_NAME, SERVER_VERSION;
      
  if ($ENV{SCRIPT_NAME})
  {
    $input{"mode"} = MODE_CGI;
    ($name, $path, $extension) = fileparse($ENV{SCRIPT_NAME}, qr/\.[^.]*/x);
  }
  else
  {
    $input{"mode"} = MODE_CMD;
    ($name, $path, $extension) = fileparse(__FILE__, qr/\.[^.]*/x);
    
    # path not guaranteed correct from __FILE__
    if ($0)
    {
      $path = dirname($0);
    }
    else
    {
      $path = File::Spec->rel2abs($0);
    }  
  }
  
  if (DIR_CONF)
  {
    $conf{"Filename"} = makeFilename(DIR_CONF, $name, "conf");
  }
  else
  {
    $conf{"Filename"} = makeFilename($path, $name, "conf");
  }
  
  $log{"pass"} = lc join("-", $name, RESULT_PASS);
  $log{"fail"} = lc join("-", $name, RESULT_FAIL);
  $log{"check"} = lc join("-", $name, "check"); 
  
  # set default configuration values    
  $conf{"LogDir"} = $path;
  $conf{"LogMode"} = "";
  $conf{"HtmlWidth"} = "500px";
  $conf{"HtmlBackground"} = "#fff";
  $conf{"HtmlTrackPw"} = "";
  $conf{"ModuleDir"} = $path;
  
  return;
       
} # init

sub execute
{
  my @modules;
  
  if ($input{"mode"} == MODE_CGI)
  {
    @modules = checkInputCgi();
  }
  else
  {
    @modules = checkInputStd();
  }
  
  validateIgc(@modules);
  
  return;
} # execute

sub checkInputCgi
{
  my ($igc, $inputFh, @result);
  
  $igc = $cgi->param("igcfile");
  
  # check we have a filename - 400 error sent on failure
  if (!$igc)
  {
    outputError(STATUS_BAD_REQUEST);
  }
    
  # set response values
  $response{"igc"} = getUserFilename($igc);
  
  my $output = $cgi->param("output");
  my $verbose = $cgi->param("verbose");
  my $nohtml = $cgi->param("nohtml");
 
  $response{"output"} = getOutput($output, $verbose, $nohtml); 
  $response{"back"} = $cgi->param("back");

  $inputFh = $cgi->upload("igcfile");
  
  # important to get conf values before we show any errors 
  @result = getConfValues();
  
  # check we have an uploaded file
  if (!$inputFh)
  {
    outputError(STATUS_BAD_UPLOAD);
  }
  
  checkIgcFile($response{"igc"}, $inputFh, $cgi->tmpFileName($igc));   
  
  return @result;
               
} # checkInputCgi

sub checkInputStd
{
  my (@args, $igc, $output, $verbose, $nohtml, $inputFh, @result);
  
  $igc = "";
  $output = "";
  $verbose = "";
  $nohtml = "";
  
  @args = @ARGV;
  
  if (!@args)
  {
    displayHelp();
  }
  
  while (@args)
  {
    if ($args[0] eq "-o")
    { 
      shift(@args);
      $output = shift(@args);
      next;
    }
        
    if ($args[0] eq "-n")
    { 
      shift(@args);
      $nohtml = shift(@args);
      next;
    }
        
    if ($args[0] eq "-v")
    {
      shift(@args); 
      $verbose = "yes";
      next;
    }
        
    if ($args[0] eq "-s")
    { 
      runCheck();
    }
    
    if ($args[0] eq "-sd")
    {
      $debug = 1; 
      runCheck();
    }
    
    if (length($args[0]) > 4)
    { 
      $igc = forwardSlashes($args[0]);
      last;
    }
    
    # anything else, show help
    displayHelp();
          
  }
  
  # check we have a filename - 400 error sent on failure
  if (!$igc)
  {
    outputError(STATUS_BAD_REQUEST);
  }
    
  # set response values
  $response{"igc"} = getUserFilename($igc);
  $response{"output"} = getOutput($output, $verbose, $nohtml);
  
  # important to get conf values before we show any errors 
  @result = getConfValues();
    
  if (!open($inputFh, q{<}, $igc))  
  {
    outputError(STATUS_BAD_UPLOAD);
  }
  
  checkIgcFile($response{"igc"}, $inputFh, $igc);
     
  return @result;
} # checkInputStd

sub validateIgc
{
  my(@module) = @_;
  
  my ($index, $debugStr);
  
  $index = -1;
  
  debug("Searching for module " . $input{"ccc"});
  
  for my $i (0 .. $#module)
  {
    
    if ($module[$i]{"ccc"} eq $input{"ccc"})
    {
      $index = $i;
      last;
    }
    
  }
    
  # see if we have not matched the ccc
  if ($index == -1)
  {
    outputError(STATUS_UNKNOWN_IGC);
  }
  
  debug("Executing module " . $input{"ccc"});
      
  if (validateIgcWork(\%{$module[$index]}))
  {
    $response{"result"} = RESULT_PASS;
    $response{"status"} = STATUS_PASS;
    $debugStr = "true";
  }
  else
  {
    $response{"result"} = RESULT_FAIL;
    $response{"status"} = STATUS_FAIL;
    $debugStr = "false";
  }

  $response{"msg"} = $module[$index]{"vali"};
  
  if ($input{"mode"} == MODE_CMD && $debug)
  {
    print $response{"data"};
  }
  
  debug("Validation function returned: ". $debugStr); 
  
  # delete temp igc file
  debug("Deleting temp igc: " . $input{"filename"});
  debug("");
  unlink($input{"filename"});
             
  return; 
} # validateIgc

sub validateIgcWork
{
  my $module = shift;
  my ($valid, @data, $sep, $valiFile, $fh, $error, $exitCode);
  my $ivShellFile = makeFilename($conf{"ModuleDir"}, "ivshell", "exe");
  
  $valid = 0;
  $sep = $input{"mode"} == MODE_CGI ? " <br />\n" : "\n";  
                     
  $valiFile = makeFilename($conf{"ModuleDir"}, $module->{"vali"}, "exe");
  
  unless (-e $valiFile)
  {
    $error = $input{"ccc"} . " does not exist:\n $valiFile";
    $error = sprintf(ERR_FMT, $module->{"line"}, "Module", $error);
    outputInternalError($error);    
  }
  
  unless (-e $ivShellFile)
  {
    $error = "ivShell does not exist:\n $ivShellFile";
    outputInternalError($error);    
  }  
  
  if (!open($fh, q{-|}, "$ivShellFile $valiFile " . $input{"filename"}))
  {
    $error = $input{"ccc"} . " cannot be opened:\n $valiFile - " . $!;
    $error = sprintf(ERR_FMT, $module->{"line"}, "Module", $error);
    outputInternalError($error);
  }
    
  while (my $line = <$fh>)
  {
    
    chomp($line);
    push(@data, "> " . $line);
               
    if ($module->{"regex"} && $line =~ /$module->{"pass"}/)
    {
      $valid = 1;
    }
          
  }
    
  close($fh);
  $exitCode = unpack("c", pack "C", $? >> 8);
  
  push(@data, "> Exit code: $exitCode");  
      
  if ($module->{"regex"})
  {    
  
    # make sure the program has responded
    if (!@data)
    {
      $error = $input{"ccc"} . " has not responded: \n $valiFile - No output";
      $error = sprintf(ERR_FMT, $module->{"line"}, "Module", $error);
      outputInternalError($error);    
    }
    
  }
  elsif ($exitCode eq $module->{"pass"})
  {
    $valid = 1;   
  }
  
  # add command line to start of data array
  unshift(@data, join(" ", ">", $module->{"vali"}, $response{"igc"}));
  $response{"data"} .= join($sep, @data) . $sep;
      
  return $valid;
} # validateIgcWork

sub getOutput
{
  my ($output, $verbose, $nohtml) = @_;

  if ($output eq "json")
  {
    return OUTPUT_JSON;
  }
  
  if ($output eq "xml")
  {
    return OUTPUT_XML;
  }
  
  if ($output eq "txt")
  {
    return OUTPUT_TXT;
  }
  
  if ($output eq "html")
  {
    return OUTPUT_HTML;
  }
  
  if ($output eq "debug")
  {
    
    $debug = 1;
        
    if ($input{"mode"} == MODE_CGI)
    {
      return OUTPUT_HTML;
    }
    else
    {
      return OUTPUT_TXT;
    }
    
  }
  
  if ($verbose)
  {
    return OUTPUT_XVERBOSE;
  }  
  else
  {
                
    if (lc $nohtml eq "no")
    {
      return OUTPUT_XHTML;
    }
	else 
	{
	  return OUTPUT_XTXT;
	}
    
  }
  
  outputError(STATUS_BAD_REQUEST);
  return;
} # getOutput

sub checkIgcFile
{
  # A wrapper for checkIgcFileWork, enabling us to close inputFh
  
  my ($igc, $inputFh, $inputFilename) = @_;
  
  my $error = checkIgcFileWork($igc, $inputFh, $inputFilename);
  
  close($inputFh);
  
  if ($error)
  {
    outputError($error);
  }
  
  return;
} # checkIgcFile

sub checkIgcFileWork
{
  # Checks for igc extension from user igc file name,
  # checks file size and gets CCC from first (A record) line,
  # copies input file to temp directory with a safe igc file name  
  #
  # Inputs
  #   igc - the user igc file name
  #   inputFh - file handle of input file
  #   inputFilename - file name of input file (will be a temp name for CGI).
  # Outputs
  #   sets $input{"ccc"} - from A record
  #   sets $input{"filename"} - from new temp file name 
  # Returns 0 or a status code
  
  my ($igc, $inputFh, $inputFilename) = @_;
  my ($buffer, $tempFh);
         
  # Check for igc extension
  debug("Checking file extension: $igc");
  
  if ($igc !~ /\.igc$/ix)
  {
    return STATUS_BAD_IGC;
  }
  
  debug("Checking file data: $inputFilename");
         
  # Check file size
  if (-s $inputFh > MAX_SIZE)
  {
    return STATUS_TOO_BIG;
  }
         
  # Check A record
  read($inputFh, $buffer, 4);
  
  if (length($buffer) != 4 || substr($buffer, 0, 1) ne "A" || $buffer =~ /[^A-Z]/x)
  {
    return STATUS_BAD_IGC;
  }
  else
  {
    $input{"ccc"} = substr($buffer, 1, 3);
  }
  
  # create new temp file  
  ($tempFh, $input{"filename"}) = createTempIgc(0);
  close $tempFh;
  
  debug("Copying to temp igc: " . $input{"filename"});
        
  # copy data to temp file
  copy($inputFilename, $input{"filename"});
              
  return 0;  
} # checkIgcFileWork

sub getUserFilename
{
  my $userFilename = shift;
  my ($name, $path, $extension) = fileparse($userFilename, qr/\.[^.]*/x);
  
  return $name . $extension;
} # getUserFilename

sub getConfValues
{
  my ($confFile, $index, $error, @result);
  
  debug("Reading configuration file: " . $conf{"Filename"});
             
  if (!open($confFile, q{<}, $conf{"Filename"}))
  {
    $error = "Unable to open conf file:\n " . $conf{"Filename"} . " - " . $!;
    outputInternalError($error);
  }
  
  $index = 0;
  
  while (my $line = <$confFile>)
  {
    
    $line = trim($line);
    $index += 1;
            
    if (!$line || substr($line, 0, 1) eq "#")
    {
      next;
    }
        
    my @values = split(/ /, $line, 2);

    if (@values == 2)
    {
      $values[1] = trim($values[1]);
    }
    
    if (@values == 1 || length($values[1]) == 0)
    {
      $error = sprintf(ERR_FMT, $index, "$values[0] has no value", "");
      outputInternalError($error);
    }
  
    if ($values[0] eq "Module")
    {

      my @data = checkConfModule($values[1], sprintf(ERR_FMT, $index, "Modules", ""));
      
      my %hash = (
        "ccc" => $data[0],
        "vali" => $data[1],
        "pass" => $data[2],
        "regex" => $data[3],
        "line" => $index
      );
                 
      if ($input{"mode"} == MODE_CHK)
      {
        $hash{"name"} = $values[0];
      }
          
      push(@result, {%hash});
            
    }
    elsif (exists($conf{$values[0]}))
    {

      if ($input{"mode"} == MODE_CHK)
      {
        
        my %hash = (
          "name" => $values[0],
          "value" => $values[1],
          "line" => $index
          );
          
        push(@result, {%hash});
        
      }
      
      $conf{$values[0]} = $values[1];
                        
    }
    else
    {
      $error = sprintf(ERR_FMT, $index, "$values[0] is not recognized", "");
      outputInternalError($error);    
    } 
                  
  }
      
  close($confFile);

  return @result;
} # getConfValues

sub checkConfModule
{
  my ($value, $errorStart) = @_;
  my ($error, $ccc, $vali, $pass, $regex);
    
  # value is trimed: ccc vali pass
      
  my @data = split(/ /, $value, 2);
 
  $ccc = $data[0];
 
  if (@data == 2)
  {
    $value = trim($data[1]);
  }
    
  if (@data == 1 || length($data[1]) == 0)
  {
    $error = $errorStart . "$ccc does not have enough values";
    outputInternalError($error);
  }
         
  # value is trimmed: vali pass
  
  @data = split(/ /, $value, 2);
  
  $vali = $data[0];
  if (@data == 2)
  {
    $pass = trim($data[1]);
  }
    
  if (@data == 1 || length($data[1]) == 0)
  {
    $error = $errorStart . "$ccc does not have enough values";
    outputInternalError($error);
  }
                            
  # check ccc length
  if (length($ccc) != 3 || $ccc =~ /[^A-Z]/x)
  {
    $error = $errorStart . "$ccc must be 3 uppercase characters A-Z";
    outputInternalError($error);
  }
                   
  # check .exe 
  if ($vali !~ /.exe$/x)
  {
    $error = $errorStart . "$ccc $vali must have an exe extension";
    outputInternalError($error);
  }
      
  # check pass - see if we are a signed integer
  if ($pass =~ /^-{0,1}\d+$/x)
  {
    $regex = 0;
  }
  else
  {
    
    # we are a string - must be enclosed in slashes    
    if (substr($pass, 0, 1) ne "/" || substr($pass, -1, 1) ne "/")
    {
      $error = $errorStart . "$ccc pass value must be enclosed in forward-slashes";
      outputInternalError($error);      
    }
    
    $regex = 1;
    $pass = substr($pass, 1, length($pass) - 2);
  
  }
       
  return ($ccc, $vali, $pass, $regex); 
} # checkConfModule

sub debug
{
  my $line = shift;
  
  if (!$debug)
  {
    return;
  }
    
  $line = $line ? "Debug:: " . $line : "";
  
  if ($input{"mode"} == MODE_CGI) 
  {
    $response{"data"} .= $line . "<br />\n";
  }
  else 
  {
    print $line . "\n";
  }
} # debug

sub logResult
{
  my ($data, $filename, $log);
  
  if (!$conf{"LogMode"})
  {
    return;
  }
  
  my ($sec, $min, $hour, $day, $mon, $year) = (localtime)[0,1,2,3,4,5]; 
  
  $data = sprintf("[%4d-%02d-%02d %02d:%02d:%02d]", $year + 1900, $mon + 1, $day, $hour, $min, $sec);
  $data = sprintf("%s [%s] %s: %s \n", $data, $response{"result"}, $response{"msg"}, $response{"igc"});
  
  if ($response{"status"} == STATUS_PASS)
  {
    
    if ($conf{"LogMode"} ne "both" && $conf{"LogMode"} ne "pass")
    {
      return;
    }
    
    $filename = makeFilename($conf{"LogDir"}, $log{"pass"}, "log");
    
  }
  else
  {

    if ($conf{"LogMode"} ne "both" && $conf{"LogMode"} ne "fail")
    {
      return;
    }
        
    $filename = makeFilename($conf{"LogDir"}, $log{"fail"}, "log");
    
  }

  return writeToLog($filename, $data);
} # logResult

sub outputInternalError
{
  my $error = shift;
  
  if ($input{"mode"} == MODE_CGI)
  {
    print $cgi->header(-status=>"500 Internal Server Error");
  }
  else
  {
    
    if ($input{"mode"} == MODE_CHK)
    {
      print "\n$error \n\n";
      print "Status Check: " . RESULT_FAIL . "\n\n";
    }
    else
    {
      print "Error: $error\n";
      print "[HTTP Header: 500 Internal Server Error]\n\n"; 
    }
   
  }
  
  exit 1;
} # outputInternalError

sub outputError
{
  my $status = shift;
  
  # check for STATUS_BAD_REQUEST
  if ($status == STATUS_BAD_REQUEST)
  {
    
    if ($input{"mode"} == MODE_CGI)
    {
      print $cgi->header(-status=>"400 Bad Request");
    }
    else
    {
      print "Error: Bad input\n";
      print "[HTTP Header: 400 Bad Request]\n\n";
    }
    
    exit 0;
  
  }
  
  $response{"result"} = RESULT_ERROR;
  $response{"msg"} = getStatusErrorMsg($status);
  $response{"status"} = $status;
  
  outputResult();
  return;
} # outputError

sub outputResult
{
  # catch all
  if ($response{"status"} == STATUS_UNKNOWN)
  {
    outputInternalError("Error: Unknown error");
  }
  
  if ($input{"mode"} == MODE_CGI)
  {
    outputHeader(); 
  }
    
  if ($response{"output"} == OUTPUT_TXT)
  {
    print getTxt();
  }
  elsif ($response{"output"} == OUTPUT_JSON)
  {
    print getJson();
  }
  elsif ($response{"output"} == OUTPUT_XML)
  {
    print getXml();
  }
  elsif ($response{"output"} == OUTPUT_HTML)
  {
    print getHtml();
  }
  else
  {
    print getVersion1();
  }
  
  exit 0;
} # outputResult

sub outputHeader
{
  my $type = "text/html";
    
  if ($response{"output"} == OUTPUT_JSON)
  {
    $type = "text/javascript";
  }
  elsif ($response{"output"} == OUTPUT_XML)
  {
    $type = "text/xml";
  }
  
  print $cgi->header(
    -type => $type,
    -expires=>"-1d",
    -charset=>"utf-8",
    );
} # outputHeader

sub getJson
{
  my $format = "\"%s\":\"%s\"";
  my @ar;
  
  foreach my $field (@{$response{"order"}})
  {
    push(@ar, sprintf($format, $field, $response{$field})); 
  }
  
  return sprintf("{%s}", join(",", @ar)); 
} # getJson

sub getXml
{
  my $format = "<%s>%s</%s>";
  my @ar;
  
  foreach my $field (@{$response{"order"}})
  {
    push(@ar, sprintf($format, $field, $response{$field}, $field)); 
  }
  
  my $xml = qq{<?xml version="1.0" encoding="UTF-8" ?>};
  return sprintf("%s<ValiResponse>%s</ValiResponse>", $xml, join("", @ar));
} # getXml

sub getTxt
{
  my $format = "%s\r\n";
  my @ar;
  
  foreach my $field (@{$response{"order"}})
  {
    push(@ar, sprintf($format, $response{$field})); 
  }      
  
  return join("", @ar);
} # getTxt

sub getHtml
{
  my $s = "";
  
  $s .= getHtmlStart();
  $s .= getHtmlContent(); 
  $s .= qq{</body></html>};
         
 return $s;  
} # getHtml

sub getHtmlStart
{
  return qq(<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Open Validation Server</title>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<style type="text/css">
  .vc-container
  {
    width: $conf{"HtmlWidth"};
    height: auto;
    background: $conf{"HtmlBackground"};
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-weight: bold;
    font-size: 10px;
  }
  .vc-header {color: #fff; padding: 5px; background-color: #336699;}
  .vc-body
  {
    height: 200px;
    overflow: auto;
    font-family: monospace;
    font-size: 11px;
    font-weight: normal;
    color: #fff;
    padding: 7px;
    background-color: #000;
  }
  .vc-back {padding: 5px; font-size: 11px;}
  .vc-back a:link {color: navy}
  .vc-back a:visited {color: navy}
  .vc-back a:hover {color: red}
  .vc-back a:focus {color: red}
  .vc-back a:active {color: red;}  
</style>
</head>
<body>);
} # getHtmlStart

sub getHtmlContent
{
  if ($response{"data"})
  {
    $response{"data"} = join("", $response{"data"});
    $response{"data"} .= $debug ? "<br />" : "<br /><br />";
  }
  
  my $s = <<"EOM";
<div class="vc-container">
<div class="vc-header">
$response{"server"}
</div>
<div id="console" class="vc-body">
$response{"data"}
$response{"result"}
<br />
$response{"msg"}
<br />
$response{"igc"}
<br />
<br /> 
Thank you for using Open Validation Server
</div>
EOM
  
  if ($response{"back"})
  {
    $s .= qq{<div class="vc-back"><a href="$response{"back"}">Back to form</a></div>};
  }

  $s .= qq{</div>};
  $s .= getHtmlTracking();
        
  return $s;  
 
} # getHtmlContent


sub getHtmlTracking
{
    
  if (!$conf{"HtmlTrackPw"})
  {
    return "";
  }
  
  my $s = trimExtra($conf{"HtmlTrackPw"}); 
  my @values = split(/ /, $s, 2);
  $values[0] = trimQuotes($values[0]);
      
  $s = <<"EOM";
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://$values[0]/" : "http://$values[0]/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", $values[1]);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
</script>
EOM

  return $s;
} # getHtmlTracking

sub getVersion1
{
  if ($response{"result"} eq RESULT_ERROR)
  {
    print "ERROR: " . $response{"msg"} . "\n";
    exit 0;
  }
  
  if ($response{"status"} == STATUS_PASS)
  {
    $response{"result"} = "ValiGPS: IGC file OK";
    $response{"msg"} = sprintf("(%s)", $response{"msg"});
  }
  elsif ($response{"status"} == STATUS_FAIL)
  {
    $response{"result"} = "ValiGPS: IGC file wrong";
    $response{"msg"} = sprintf("(%s)", $response{"msg"});  
  }
  
  if ($response{"output"} == OUTPUT_XVERBOSE)
  {
    return getHtml();
  }
  else
  { 
    my $sep = $response{"output"} == OUTPUT_XHTML ? "<br />" : "\n";
    return join($sep, $response{"result"}, $response{"msg"});
  }
} # getVersion1

sub getStatusErrorMsg
{
  my $status = shift;
      
  if ($status == STATUS_BAD_UPLOAD)
  {
    return "File upload failed";
  }
       
  if ($status == STATUS_TOO_BIG)
  {
    my $max = (MAX_SIZE) / 1024000;
    return "File too large - max $max mb";    
  }
  
  if ($status == STATUS_BAD_IGC)
  {
    return "Not an IGC file";
  }
  
  if ($status == STATUS_UNKNOWN_IGC)
  {
    return "IGC program not supported: " . $input{"ccc"};
  }
  
  return "Unspecified error";
} # getStatusErrorMsg

sub writeToLog
{
  my ($filename, $msg) = @_;
  my $log;
  
  if (!open($log, q{>>}, $filename))
  {
   return $!;
  }
  
  if ($msg)
  {
    print $log $msg;
  }
  
  close($log);
  
  return;
} # writeToLog

sub runCheck
{
  my @confValues;
    
  print uc $response{"server"};
  print " - Status Check\n\n";   
  print "Check:: Checking configuration file: " . $conf{"Filename"} . "\n";
  
  $input{"mode"} = MODE_CHK;
    
  @confValues = getConfValues();
  
  checkLogging(@confValues);           
  checkModules(@confValues);
          
  print "\nStatus Check: " . RESULT_PASS . "\n\n";
    
  exit 0;
} # runCheck

sub checkLogging
{
  my @confValues = @_;
  
  my ($line, $value, $error, $log1, $log2);
  
  $line = getConfLine(\@confValues, "LogMode");
  
  if ($line == -1)
  {
    print "Check:: Logging: none\n";
    return;
  }
  
  $value = $conf{"LogMode"};
  
  debug("Checking LogMode: $value");
  
  if ($value ne "pass" &&
      $value ne "fail" &&
      $value ne "both")
  {
    $error = "value is invalid - $value";
    $error = sprintf(ERR_FMT, $line, "LogMode", $error);
    outputInternalError($error);    
  }
  
  $line = getConfLine(\@confValues, "LogDir");
  
  if ($line == -1)
  {
    $line = "?";
  }
  
  $value = $conf{"LogDir"};
  
  # clean LogDir value and check directory exists
  $value = sanitizeFilePath($value);
  
  debug("Checking LogDir: $value");
  
  unless (-d $value)
  {
    $error = "directory does not exist:\n  $value";
    $error = sprintf(ERR_FMT, $line, "LogDir", $error);
    outputInternalError($error);    
  }
        
  $log1 = makeFilename($value, $log{"pass"}, "log");
  $log2 = makeFilename($value, $log{"fail"}, "log");
  
  if (-e $log1)
  {
    
    debug("Checking write to Pass log: $log1");
    
    $error = writeToLog($log1, "");
    if ($error)
    {
      $error = "unable to write to log file:\n  $log1 - $error";
      $error = sprintf(ERR_FMT, $line, "LogDir", $error);
      outputInternalError($error);    
    }
    
  }
  elsif (-e $log2)
  {

    debug("Checking write to Fail log: $log2");
    
    $error = writeToLog($log2, "");
    if ($error)
    {
      $error = "unable to write to log file:\n  $log2 - $error";
      $error = sprintf(ERR_FMT, $line, "LogDir", $error);
      outputInternalError($error);    
    }    
  
  }
  else
  {

    # write a temporary log file at LogDir location
    $log1 = makeFilename($value, $log{"check"}, "log");
    
    debug("Checking write to LogDir: $value");
    debug("Creating temporary log file: $log1");
    $error = writeToLog($log1, "");
    
    if ($error)
    {
      $error = "unable to write to directory:\n  $value - $error";
      $error = sprintf(ERR_FMT, $line, "LogDir", $error);
      outputInternalError($error);    
    }
    else
    {
      unlink($log1);
      debug("Deleted temporary log file: $log1"); 
    }    
  
  }
  
  print "Check:: Logging: " . $conf{"LogMode"} . "\n"; 
} # checkLogging

sub getConfLine
{
  my ($values, $name) = @_;
  
  my @confValues = @$values;
  
  for my $i (0 .. $#confValues)
  {
  
    if ($confValues[$i]{"name"} eq $name)
    {
      return $confValues[$i]{"line"};
    }
  
  }  

  return -1;
} # getConfLine

sub checkModules
{
  my @confValues = @_;
  
  my ($ccc, $error, @modOrder, %modules);
  
  # check confValues
  for my $i (0 .. $#confValues)
  {
     
    if ($confValues[$i]{"name"} eq "Module")
    {
      
      $ccc = $confValues[$i]{"ccc"};
      
      if (exists($modules{$ccc}))
      {
        $error = "$ccc is a duplicate, introduced at line " . $modules{$ccc}{"line"};
        $error = sprintf (ERR_FMT, $confValues[$i]{"line"}, "Module", $error);
        outputInternalError($error);      
      }
      
      $modules{$ccc} = $confValues[$i];
      push(@modOrder, $ccc);
      
    }
                
  }
  
  if (!@modOrder)
  {
    $error = "Error: No Modules found";
    outputInternalError($error);      
  }
  
  @modOrder = sort @modOrder;
  checkModulesWork(\@modOrder, \%modules);
 
  print "\nCheck:: Modules available: " . @modOrder . "\n";
  
  foreach my $ccc (@modOrder)
  {
    print join("   ", $ccc, $modules{$ccc}{"vali"}, $modules{$ccc}{"pass"}) . "\n";
  }
} # checkModules

sub checkModulesWork
{
  # Checks all vali modules
  # Inputs
  #   modOrder - an array of modules in their sorted order
  #   modules - a hash of module hashes
    
  my ($modOrder, $modules) = @_;
  my ($filename, $error, $dir, $tmpFh, $result, $debugStr);
    
  if (!@$modOrder)
  {
    $error = "Error: No Modules found";
    outputInternalError($error);      
  }
       
  # first pass - check vali files exist
  foreach my $ccc (@$modOrder)
  {

    $filename = makeFilename($conf{"ModuleDir"}, $modules->{$ccc}{"vali"}, "exe");
    debug("Checking module $ccc: " . $modules->{$ccc}{"vali"});
    
    unless (-e $filename)
    {
      $error = "$ccc does not exist:\n $filename";
      $error = sprintf(ERR_FMT, $modules->{$ccc}{"line"}, "Module", $error);
      outputInternalError($error);    
    }
    
    unless (-x $filename)
    {
      $error = "$ccc cannot be executed:\n $filename";
      $error = sprintf(ERR_FMT, $modules->{$ccc}{"line"}, "Module", $error);
      outputInternalError($error);    
    }
    
    $modules->{$ccc}{"file"} = $filename;    
    
  }
  
  # second pass - create temp invalid igc and test vali files with it
  
  ($tmpFh, $input{"filename"}) = createTempInvalidIgc();
  debug("Created temporary invalid igc: " . $input{"filename"});
  my ($name, $path, $extension) = fileparse($input{"filename"});
  $response{"igc"} = $name . $extension;
       
  print "Check:: Checking modules with invalid IGC file: " . $response{"igc"} . "\n";
      
  foreach my $ccc (@$modOrder)
  {

    $response{"data"} = "";
    $input{"ccc"} = $ccc;
    print join(" ", "\nCheck::", "Executing module", $input{"ccc"}, "\n");
    $result = validateIgcWork($modules->{$ccc});
    
    print $response{"data"};
    
    $debugStr = $result ? "true" : "false";
    debug("Validation function returned: $debugStr");
             
    if ($result)
    {
    
      $filename = makeFilename($conf{"ModuleDir"}, $modules->{$ccc}{"vali"}, "exe");
      my $pass = $modules->{$ccc}{"pass"};
      
      if ($modules->{$ccc}{"regex"})
      {
        $pass = "Pass string: $pass";
      }
      else
      {
        $pass = "Pass exit code: $pass";
      }
      
      $error = "$ccc has incorrectly validated the file: \n $filename - $pass";
      $error = sprintf(ERR_FMT, $modules->{$ccc}{"line"}, "Module", $error);
      outputInternalError($error);        
    
    }
                        
  }
  
  close($tmpFh);
  
  return;
} # checkModulesWork

sub createTempInvalidIgc
{
  # Returns a file handle and safe filename of a small invalid igc file
  my ($fh, $filename) = createTempIgc(1);
     
  # add dummy igc content
  print $fh getDummyIgc("XXX");
  
  return ($fh, $filename);
} # createTempInvalidIgc

sub getDummyIgc
{
  # Returns simple dumy igc text
  # Inputs
  #   ccc - ccc for A record

  my $ccc = shift;
   
  return join(
    "\r\n",
    "A$ccc",
    "HFDTE010199",
    "HFPLTPILOT: Not Set",
    "B1306175535282N00341995WA0057700577",
    "B1306265535281N00341994WA0055500555",
    "B1306365535280N00341995WA0052900529",
    "GXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
    ""
    ); 
} # getDummyIgc
     
sub displayHelp
{
  my $format = "%-1s %-12s: %-40s\n";

  if ($input{"mode"} == MODE_CGI)
  {
	print "Content-type: text/html\n\n";
	print "<html><head><title>vali2 usage</title></head><body>";
	print "<pre>";
  }
  
  print uc $response{"server"} . " (" . $input{"mode"} . ")\n\n";   
  print "Usage: [options] [filename]\n\n";
  
  print "IGC VALIDATION\n\n";
  printf $format, "", "-o value", "output - json, xml, html, txt (default) or debug";
  printf $format, "", "filename", "IGC file to validate"; 
  print "\n"; 
  printf $format, "", "-n value", "version 1 nohtml output - yes or no";
  printf $format, "", "-v", "version 1 verbose output";
  
  print "\n";
    
  print "INFO\n\n";
  printf $format, "", "-h", "this help screen";
  printf $format, "", "-s", "internal status check";
  printf $format, "", "-sd", "internal status check with debug data";
  print "\n";

  if ($input{"mode"} == MODE_CGI)
  {  
	print "</pre></body></html>";
  }
  exit 0;
} # displayHelp


############################
# Helper functions
############################


sub createTempIgc
{
  # Returns a file handle and safe filename of a temporary igc file
  # Input - autoDelete: whether to delete the file when the handle is closed
  
  my $autoDelete = shift;
  
  # get template for creating file
  my ($min, $hour, $day, $mon, $year) = (localtime)[1,2,3,4,5];
  my $template = sprintf("%4d-%02d-%02d-%02d%02d-XXXX", $year + 1900, $mon + 1, $day, $hour, $min);
  
  # create file
  #my $dir = File::Spec->tmpdir();  
  #vali-xgd bug workaround 
  my $dir = DIR_IGCUPLOAD;
  my $fh = File::Temp->new(TEMPLATE => $template, DIR => $dir, UNLINK => $autoDelete, SUFFIX => ".igc");
     
  return ($fh, quoteFilename($fh->filename));
} # createTempIgc
   
   
sub makeFilename
{
  # Creates a filename, using forward slashes, from
  # path, name and ext arguments.
  # name can include the extension
  # ext is either with or without the period, or empty
  
  my ($path, $name, $ext) = @_;
  
  # remove quotes and make slashes forward
  $path = sanitizeFilePath($path); 
    
  # add trailing slash
  if (substr($path, length($path) - 1, 1) ne "/")
  {
    $path .= "/";
  }
  
  # add period to extension if required
  if ($ext && substr($ext, 0, 1) ne ".")
  {
    $ext = "." . $ext;
  }
  
  # remove extension from name if required
  $name =~ s/$ext//gx;
           
  return $path . $name . $ext; 
} # makeFilename


sub sanitizeFilePath
{
  # Removes quotes and converts slashes
  
  my $string = shift;
  $string = trimQuotes($string);
  $string = forwardSlashes($string);
  return $string;
} # sanitizeFilePath


sub trim
{
  # Trims whitespace from both ends of input string
  
  my $string = shift;
  $string =~ s/^\s+//x;
  $string =~ s/\s+$//x;
  return $string;
} # trim

sub trimExtra
{
  # Trims extra whitespace from a string
  
  my $string = shift;
  $string =~ s/\s+/ /gx;
  return $string;
} # trimExtra

sub trimAll
{
  # Trims start, end and extra whitespace from a string
  
  my $string = shift;
  $string = trim($string);
  return trimExtra($string);
} # trimAll

sub trimQuotes
{
  # Trims single and double quotes from a string
  
  my $string = shift;
  $string =~ s/[\"|\']//gx;
  return $string;
} # trimQuotes

sub forwardSlashes
{
  # Replaces backslashes with forwardslashes
  
  my $string = shift;
  $string =~ s/\\/\//gx;
  return $string;
} # forwardSlashes


sub quoteFilename
{
  # Adds enclosing double quotes if there are spaces in file name
  
  my $string = shift;
  if ($string =~ /\s/x)
  {
    $string = "\"" . $string . "\"";
  }
  
  return $string;
} # quoteFilename
