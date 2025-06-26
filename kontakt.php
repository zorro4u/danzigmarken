<?php
session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Starter.php";
use Dzg\Starter;

Starter::run("kontakt");
