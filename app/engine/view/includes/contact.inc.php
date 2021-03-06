<?php

  $noaccess = !fn::loggedIn();
  $signin = fn::getPageHref(cn::PG_SIGN_IN, 'Sign In');
  $sent = Fx::get('msg-sent');  
  
      $arHidden = array();
      $arHidden[cn::PM_VIEW] = cn::PG_CONTACT;
      $arHidden[cn::PM_CMD] = 'msg';
      Fx::html('hidden', $arHidden); 
      
?>

<h1>Contact Us</h1>

<?php if ($sent) : ?>

<p>
Thank-you for your message. We will get back to you as soon as possible.
</p>

<?php elseif ($noaccess) : ?>

<p> 
You will use the Ticket System at <a href="http://support.fai-civl.org/" target="_blank">http://support.fai-civl.org/</a> to get in contact with us.
</p>

<?php else : ?>

<p> 
You will use the Ticket System at <a href="http://support.fai-civl.org/" target="_blank">http://support.fai-civl.org/</a> to get in contact with us.
</p>

<!--
<p> The Mail function is not implemented yet. </p>
<p>
Please type your message then click <b>Send</b>. A copy will be sent to you email address.
</p>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<textarea class="textarea" maxlength="1000" name="message"></textarea>
<div style="margin:10px 0;">
<div style="float:left;">
<input type="submit" name="btn" value="Send" />
</div>
<div style="float:left;font-size:85%;color:#666;margin: 5px 0 0 25px;">
Max: 1000 characters
</div>
<div class="clear"></div>
</div>
<?php foreach (Fx::html('hidden') as $name => $value): ?>
<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
<?php endforeach; ?>
</form>
-->

<?php endif; ?>
