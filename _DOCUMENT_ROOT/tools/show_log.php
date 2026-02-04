<?php
date_default_timezone_set('Europe/Berlin');
session_start();

error_reporting(E_ERROR | E_PARSE);
#header("Content-type: text/html; charset=utf-8");

require_once $_SERVER['DOCUMENT_ROOT']."/assets/inc/start.php";
Dzg\start("logger");
