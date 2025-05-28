<?php
session_start();
date_default_timezone_set('Europe/Berlin');

#require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Starter.php";
#Starter::run("activate");

require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Activate.php";
Activate::show();