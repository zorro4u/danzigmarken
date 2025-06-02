<?php
session_start();
date_default_timezone_set('Europe/Berlin');

#require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Starter.php";
#Starter::run("register-info");

require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Register_info.php";
use Dzg\Cls\Register_info;

Register_info::show();
