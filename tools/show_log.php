<?php
session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);
#header("Content-type: text/html; charset=utf-8");

require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Logger.php";
use Dzg\Logger;

Logger::show();
