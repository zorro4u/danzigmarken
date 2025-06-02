<?php
session_start();
date_default_timezone_set('Europe/Berlin');

#require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Starter.php";
#Starter::run("pw_reset");

require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Pw_reset.php";
use Dzg\Cls\Pw_reset;

Pw_reset::show();