<?php
$room=$_POST['room'];
if (strstr($room,"/") || strstr($room,"..")) exit;

$dir = "uploads/$room";
if (!file_exists($dir)) @mkdir($dir);
@chmod($dir, 0755);
$dir .= "/slides";
if (!file_exists($dir)) @mkdir($dir);
@chmod($dir, 0755);

if (file_exists($dir . "/slideshow.xml")) include($dir . "/slideshow.xml");
else echo "<SLIDES></SLIDES>";
?>