<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
 <head>
  <title>Index of /cgi-bin</title>
  <link rel="icon" type="image/png" href="/web/images/logo16.png" />
  <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="/web/images/favicon.ico" />
  <link type="text/css" rel="stylesheet" href="/web/scripts/css/engine.css" />
 </head>
 <body>
<h3>Index of /cgi-bin</h3>
  <table id="multiContainer">
   <tr>
   <th valign="top"><img src="/icons/blank.gif" alt="[ICO]"></th>
   <th>Name</th>
   <th align="right">Last modified</th>
   <th align="right">&nbsp;&nbsp;</th>
   <th align="right">Size</th>
   <th align="right">&nbsp;&nbsp;</th>
   <th align="right"> MD5</th>
   </tr>
   <tr><th colspan="7"><hr></th></tr>
<?php
  if ($handle = opendir('.')) {
    while (false !== ($file = readdir($handle))) {
	  // if ($file != "." && $file != ".." && (strpos($file, "exe") || strpos($file, "dll"))) {
	  if ($file != "." && $file != ".." && strpos($file, "exe")) {
	    echo '<tr>';
		echo '<td valign="top"><img src="/icons/binary.gif" alt="[binary vali exe]"></td>';
		echo '<td><a href="'.$file.'">'.$file.'</a></td>';
		echo '<td align="right">'.date("Y-m-d H:i", filemtime($file)).'</td>';
		echo '<td>&nbsp;</td>';
		echo '<td align="right">'.round((filesize($file)/1024),0).'k</td>';
		echo '<td>&nbsp;</td>';
		echo '<td align="right"><div id="md5">'.md5($file).'</div></td>';
		echo '</tr>';
	  }
	}
	closedir($handle);
  }
?>
</table>
<address>Apache Server at <?php echo $_SERVER['SERVER_NAME']; ?> Port <?php echo $_SERVER['SERVER_PORT']; ?></address>
</body></html>