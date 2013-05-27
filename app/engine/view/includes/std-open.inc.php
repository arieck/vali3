<!DOCTYPE html>
<head>
<meta charset="UTF-8">
<title><?php Fx::out('title'); ?></title>
<link rel="icon" type="image/png" href="<?php Fx::out('icon'); ?>" />
<link rel="shortcut icon" type="image/vnd.microsoft.icon" href="<?php Fx::out('favicon'); ?>" />
<?php foreach (Fx::html('css') as $css): ?>
<link type="text/css" rel="stylesheet" href="<?php echo $css; ?>" />
<?php endforeach; ?>
<?php
if (Fx::html('userStatus')):
    if(strtolower(Fx::html('userStatus')) == "admin"):
        echo '<script type="text/javascript" src="/ckeditor/ckeditor.js"></script>';
        echo "\n";
    endif;
endif;
?>
</head>

<body>

<div id="content">

<div id="header">

  <div id="header-bar">
  
    <div id="fai-placer">
    </div>
    
    <div id="fai-image">
    </div>
    
    <div id="logo">
      <?php Fx::out('appName'); ?>
    </div>
        
    <div id="topMenu" class="topMenu"><?php Fx::out('topMenu'); ?></div>
    <div class="clear"></div>

  </div>

  <?php if (Fx::html('userStatus')): ?>

  <div id="userHeader">
  <span class="userStatus"><?php Fx::out('userStatus'); ?>:</span>
  <?php Fx::out('userName'); ?>
  
  </div>

  <?php endif; ?>

</div>

<div id="page-container">
