<?php
// 
// ToDo:
//   This is just a temporary hardcoded solution to update 
//   the supported-software.html without the need to use a database.
//   When it make sense, we full implement later ckeditor + mysql 
//   or similar for any "Rubbish cms 1.0" page. ;-)
// 
if (!empty($_POST)) {
	$referer = "index.php";
	if(isset($_POST['referer'])) { $referer = $_POST['referer']; } else { $referer = "index.php"; }
	if(isset($_POST['supported-software'])) { 
		$file = "C:/wwwroot/vali/app/engine/view/includes/supported-software.html";
		$value = $_POST['supported-software'];
		file_put_contents($file, $value);
	}
	header('Location: /'.$referer);
}
?>