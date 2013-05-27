<h1>Sign In</h1>

<p> 
You only need to Sign In if you want to contact us.<br>
Or as a assigned CIVL Administrator, you have some extra functionality after logging in. <br>
We use Google for authentication and request the following information from your Google profile:
</p>

<ul>
<li>your <b>email</b> address</li>
<li>your <b>name</b></li>
</ul>

<p>
Please note that we do not store this information or use it for any other purpose than replying to your message. 
</p>


<form method="post" action="<?php echo Fx::get('baseUrl');?>">

<div class="sign-box">
<input type="image" src="web/images/signin-google.png" alt="google" />
</div>

<?php foreach (Fx::html('hidden') as $name => $value): ?>
<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" />
<?php endforeach; ?>

</form>
