<?php

  $contact = fn::getPageHref(cn::PG_CONTACT, 'contact us');
  
?>

<h1>Web Service</h1>
<p>
The Web Service is designed to use for XC Onlinecontest providers.<br>
<i>This is not a enduser service for the pilot.</i>
</p>
<p> 
We are currently developing and testing the Web Service (JSON, HTML and TXT results).<br>
Please <?php echo $contact;?> if you would like to be notified on updates and changes<br>
or if you need further information.
</p>
<p>
Example demonstration. When using Linux curl command in a loop<br>
<br>
1) for TXT return results call:<br>
$ /usr/bin/curl -s -F igcfile=@YourSample.igc vali.fai-civl.org/cgi-bin/vali-igc.cgi<br>
<br>
<br>
2) for JSON return results call:<br>
$ /usr/bin/curl -F igcfile=@YourSample.igc vali.fai-civl.org/api/vali/json<br>
<br>
</p>

<p></p>

