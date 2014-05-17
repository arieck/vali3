<?php

  $contact = fn::getPageHref(cn::PG_CONTACT, 'contact us');
  
?>

<h1>Supported Software FAQ</h1>
<p> 
<?php
if (Fx::html('userStatus')):
    $fileOut = file_get_contents(__DIR__.'/faq-software.html');
    if(strtolower(Fx::html('userStatus')) == "admin"):
        echo '<font color="red">Admin Editor Mode</font><br>';
		echo '<form method="post" action="/ckeditor/writeData.php">';
        echo '<textarea class="ckeditor" id="faq-software" name="faq-software">'.$fileOut.'</textarea>';
		echo '<input type="hidden" name="referer" id="referer" value="faq.html">';
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

