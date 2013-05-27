<!DOCTYPE html>
<head>
<meta charset="utf-8">
<title>Open Validation Server</title>
<link type="text/css" rel="stylesheet" href="<?php Fx::out('css'); ?>" />
</head>
<body>
<div class="vc-container">

<div class="vc-header">
<?php Fx::out('header'); ?>
</div>

<?php if (Fx::get('api')) : ?>

<div id="console" class="vc-body">
<div class="vc-data">
<?php Fx::out('data'); ?>
</div>
<?php Fx::out('result'); ?>
</div>

<div class="vc-back">
<?php Fx::out('back'); ?>
</div>
    
<?php else : ?>

  <div id="console" class="vc-body" style="display:none;"></div>
  <div id="console-form" class="vc-body">
  
  <div class="vc-data">
  Enter an IGC file to check then click the Validate button.<br />
  Compressed content (zip, gzip) is also accepted.
  </div>

  <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" name="igcform">
  
  <input type="file" id="igcfile" name="igcfile" class="vc-file" />
  <div style="margin-top: 20px;">
  <input type="submit" name="go" value="Validate" class="vc-input" />
  <input type="button" name="cancel" value="Cancel" class="vc-input" style="display:none;" />
  
  <div id="wait" class="vc-wait"></div>
  
  </div>
  
  <?php foreach (Fx::html('hidden') as $name => $value): ?>
  <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
  <?php endforeach; ?>
  
  </form>
  </div>
  
<?php endif; ?>

<div class="vc-back">
<a id="back" href="" style="display:none;">Back to form</a>
</div>

</div>

<script type="text/javascript" src="<?php Fx::out('js'); ?>" ></script> 
</body>
</html>
