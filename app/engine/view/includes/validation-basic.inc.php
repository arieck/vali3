<?php
  
  $online = fn::getPageHref('validation', 'Online Validation');
  if(strtolower(Fx::html('userStatus')) == 'admin'):
    $frame = fn::getPageUrl('apiframe-admin');
  else:
    $frame = fn::getPageUrl('apiframe');
  endif;
  
  
?>

<h1>Basic Service</h1>
<p> 
Use this form to validate single IGC files. 
If you have more than one file to validate you can use the
<?php echo $online; ?> service. 
</p>
 
<div id="web-form" style="margin: 15px 0;height: 280px; width: 530px;">
<iframe src="<?php echo $frame; ?>" class="iframe-form" frameborder="0"></iframe>

</div>

<h1>Output</h1>
<p>
If there is any output from the validation program it is prefixed with the <b>&gt;</b> character. Below this the result
is reported in a block of three lines, the first of which starts with the word PASSED, FAILED or ERROR.
The second line reports the validation program that was used (or a brief description in the case of an ERROR),
while the third line reports the name of the IGC file that was checked.
</p>

<?php
 /* 
  $item['page'] = cn::PG_WEB_SERVICE;
  $item['text'] = 'Web Service API';
  $href = fn::getHrefHtml($this->Agent->urlApp, $item);
  
  fn::println('<p>');
  fn::println('More information about service output can be found in the ' . $href . ' section.');
  fn::println('</p>');
 */ 
?>
