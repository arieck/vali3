<?php

  $contact = fn::getPageHref(cn::PG_CONTACT, 'contact us');
  
?>

<h1>Supported Software</h1>
<p> 
<?php
if (Fx::html('userStatus')):
    $fileOut = file_get_contents(__DIR__.'/supported-software.html');
    if(strtolower(Fx::html('userStatus')) == "admin"):
        echo '<font color="red">Admin Editor Mode</font><br>';
		echo '<form method="post" action="/ckeditor/writeData.php">';
        echo '<textarea class="ckeditor" id="supported-software" name="supported-software">'.$fileOut.'</textarea>';
		echo '<input type="hidden" name="referer" id="referer" value="supported.html">';
		echo '<p><br><input type="submit" value="Submit"></p>';
		echo '</form>';
		echo '<hr>';
		echo '<font color="red">preview:</font><br>';
		echo $fileOut;
    else:
        echo $fileOut; 
    endif;
endif;
?>
</p>

<p></p>

