<?php
session_start();
date_default_timezone_set('Europe/Berlin');

#require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Starter.php";
#Starter::run("register");

require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Register.php";
use Dzg\Register;

Register::show();