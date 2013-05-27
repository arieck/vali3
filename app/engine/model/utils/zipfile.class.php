<?php

  // official ZIP file format: http://www.pkware.com/appnote.txt
  
  /*
    Example Usage of the Class 
    $zip = new Zipfile();  

    // add the subdirectory if you want
    $zip->addDir("dir/");

    // add a file
    $zip->addFile($filedata, "dir/filename.txt");  

  */

class Model_Utils_Zipfile  
{  

  private $arCompressed = array();
  private $arCentral = array();
  private $oldOffset = 0;

  
public function addDir($name)   
{

  // adds "directory" to archive - do this before putting any files in directory!
  // $name - name of directory... like this: "path/"
  // ...then you can add files using addFile with names like "path/file.txt"
  
  $name = str_replace("\\", "/", $name);  

  $fr = "\x50\x4b\x03\x04";
  $fr .= "\x0a\x00";    // ver needed to extract
  $fr .= "\x00\x00";    // gen purpose bit flag
  $fr .= "\x00\x00";    // compression method
  $fr .= "\x00\x00\x00\x00"; // last mod time and date

  $fr .= pack("V",0); // crc32
  $fr .= pack("V",0); // compressed filesize
  $fr .= pack("V",0); // uncompressed filesize
  $fr .= pack("v", strlen($name) ); // length of pathname
  $fr .= pack("v", 0 ); // extra field length
  $fr .= $name;  
  // end of "local file header" segment

  // no "file data" segment for path

  // "data descriptor" segment (optional but necessary if archive is not served as file)
  $fr .= pack("V",0); // crc32
  $fr .= pack("V",0); // compressed filesize
  $fr .= pack("V",0); // uncompressed filesize

  // add this entry to array
  $this->arCompressed[] = $fr;

  $newOffset = strlen(implode("", $this->arCompressed));
  
  // now add to central record
  $cdrec = "\x50\x4b\x01\x02";
  $cdrec .="\x00\x00";    // version made by
  $cdrec .="\x0a\x00";    // version needed to extract
  $cdrec .="\x00\x00";    // gen purpose bit flag
  $cdrec .="\x00\x00";    // compression method
  $cdrec .="\x00\x00\x00\x00"; // last mod time & date
  $cdrec .= pack("V", 0); // crc32
  $cdrec .= pack("V", 0); // compressed filesize
  $cdrec .= pack("V", 0); // uncompressed filesize
  $cdrec .= pack("v", strlen($name)); // length of filename
  $cdrec .= pack("v", 0); // extra field length   
  $cdrec .= pack("v", 0); // file comment length
  $cdrec .= pack("v", 0); // disk number start
  $cdrec .= pack("v", 0); // internal file attributes
  $ext = "\x00\x00\x10\x00";
  $ext = "\xff\xff\xff\xff";  
  $cdrec .= pack("V", 16); // external file attributes  - 'directory' bit set

  $cdrec .= pack("V", $this->oldOffset); // relative offset of local header
  $this->oldOffset = $newOffset;

  $cdrec .= $name;  
  
  // save to array
  $this->arCentral[] = $cdrec;  

} // addDir


public function addFile($data, $name)   
{

  // adds "file" to archive   
  // $data - file contents
  // $name - name of file in archive. Add path if you want
  
  $name = str_replace("\\", "/", $name);  
 
  $fr = "\x50\x4b\x03\x04";
  $fr .= "\x14\x00";    // ver needed to extract
  $fr .= "\x00\x00";    // gen purpose bit flag
  $fr .= "\x08\x00";    // compression method
  $fr .= "\x00\x00\x00\x00"; // last mod time and date

  $rawLength = strlen($data);  
  $crc = crc32($data);
  $zdata = gzcompress($data);  
  $zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug

  $compressedLength = strlen($zdata);  
  $fr .= pack("V", $crc); // crc32
  $fr .= pack("V", $compressedLength); // compressed filesize
  $fr .= pack("V", $rawLength); // uncompressed filesize
  $fr .= pack("v", strlen($name) ); // length of filename
  $fr .= pack("v", 0 ); // extra field length
  $fr .= $name;  
  // end of "local file header" segment
   
  // "file data" segment
  $fr .= $zdata;  

  // "data descriptor" segment (optional but necessary if archive is not served as file)
  $fr .= pack("V", $crc); //crc32
  $fr .= pack("V", $compressedLength); // compressed filesize
  $fr .= pack("V", $rawLength); // uncompressed filesize

  // add this entry to array
  $this->arCompressed[] = $fr;

  $newOffset = strlen(implode("", $this->arCompressed));

  // now add to central directory record
  $cdrec = "\x50\x4b\x01\x02";
  $cdrec .="\x00\x00";    // version made by
  $cdrec .="\x14\x00";    // version needed to extract
  $cdrec .="\x00\x00";    // gen purpose bit flag
  $cdrec .="\x08\x00";    // compression method
  $cdrec .="\x00\x00\x00\x00"; // last mod time & date
  $cdrec .= pack("V", $crc); // crc32
  $cdrec .= pack("V", $compressedLength); // compressed filesize
  $cdrec .= pack("V", $rawLength); // uncompressed filesize
  $cdrec .= pack("v", strlen($name)); // length of filename
  $cdrec .= pack("v", 0); // extra field length   
  $cdrec .= pack("v", 0); // file comment length
  $cdrec .= pack("v", 0); // disk number start
  $cdrec .= pack("v", 0); // internal file attributes
  $cdrec .= pack("V", 32); // external file attributes - 'archive' bit set

  $cdrec .= pack("V", $this->oldOffset); // relative offset of local header
  $this->oldOffset = $newOffset;

  $cdrec .= $name;  
  
  // save to central directory
  $this->arCentral[] = $cdrec;
    
}
    

public function getArchive()
{
    
  $data = implode("", $this->arCompressed);  
  $centralDir = implode("", $this->arCentral);  

  return   
    $data.  
    $centralDir.  
    "\x50\x4b\x05\x06\x00\x00\x00\x00".  
    pack("v", count($this->arCentral)).    // total # of entries "on this disk"
    pack("v", count($this->arCentral)).    // total # of entries overall
    pack("V", strlen($centralDir)).        // size of central dir
    pack("V", strlen($data)).              // offset to start of central dir
    "\x00\x00";
                                     
} // getArchive


public function outputZip($filename)
{

  @ob_end_clean();
  
  $content = $this->getArchive();
  
  /* We do not set file size as this will download the
  file if the user has pressed cancel more than once

  ie header("Content-Length: $size");
  */

  header('Cache-control: private, no-cache, no-store, must-revalidate'); //IE 6 Fix
  header('Pragma: no-cache');
  header('Expires: Mon, 26 July 1997 05:00:00 GMT');
  header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
  header('Cache-Control: no-cache, must-revalidate');
  header('Cache-Control: post-check=0, pre-check=0', false);

  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename=' . $filename);
  header('Content-Transfer-Encoding: binary');

  print($content);
  exit;
  
} // outputZip

    
} // end class Model_Utils_Zipfile
 

?>
