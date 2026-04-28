<?php

  $contact = fn::getPageHref(cn::PG_CONTACT, 'contact us');
  
?>

<h1>Web Service</h1>
<p>
The available Web Service is designed to use for XC Onlinecontest providers.<br>
<i>This is not a enduser service for the pilot.</i>
</p>
<p> 
Please <?php echo $contact;?> if you have questions.<br>
When implementing on your side, please don't run multiple connections in parallel<br>
and don't run validation tests faster then 30 tests per minute.
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
<p>
For LeonardoXC based installations we offer wrapper which can be deployed at your own LeonardoXC Server.<br>
The wrapper script will generate the required return values for remote IGC file validation.<br>
See more details at GitHub <a href="https://github.com/arieck/leonardoVali" target="_blank">Link</a>.<br>
</p>
<p></p>


