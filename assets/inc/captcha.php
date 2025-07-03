<?php
/***********************
 * erzeugt CAPTCHA Bild,
 * wird im Kontakt-Formular gestartet
 */
session_start();
require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/mail/AntiSpam.php";
use Dzg\Mail\AntiSpam;

AntiSpam::loadCaptchaPic();
