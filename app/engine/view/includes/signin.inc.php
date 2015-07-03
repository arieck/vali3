<h1>Sign In</h1>

<p> 
The login into this system is for FAI/CIVL Administration purpose only.<br>
To get in contact with us, you will use the <b>contact</b> link above.
</p>

<form method="post" action="<?php echo Fx::get('baseUrl');?>">

<div class="sign-box">
<input type="image" src="web/images/signin-google.png" alt="google" />
</div>

<?php foreach (Fx::html('hidden') as $name => $value): ?>
<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
<?php endforeach; ?>

</form>
