<div class='portlet-content portlet-container' style='<?=$style?>'>
<?php
$f=$dashDir.$src.".php";
$fCss=$dashDir.$src.".css";
if(file_exists($fCss)) {
	echo "<style>";
	readfile($fCss);
	echo "</style>";
}
if(file_exists($f)) {
	include $f;
}
?>
</div>