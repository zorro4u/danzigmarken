<?php
/**
 * erzeugt CAPTCHA Bild,
 * benötigt dafür in Apache Config, php.ini die GD-extension
 */

require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/mail/Captcha.php";
use Dzg\Mail\Captcha;

session_start();

$text = Captcha::getText();
$_SESSION['captcha_spam'] = Captcha::encrypt($text);

header('Content-type: image/png');
$img = imagecreatefrompng('../pic/captcha.png'); 	# Hintergrundbild
$color = imagecolorallocate($img, 0, 0, 0); 	# Farbe
$ttf = "../fonts/Imperator.ttf";
$ttfsize = 25; 	# Schriftgrösse
$angle = rand(0,5);
$t_x = rand(5,50);
$t_y = 35;
imagettftext($img, $ttfsize, $angle, $t_x, $t_y, $color, $ttf, $text);
imagepng($img);
imagedestroy($img);
