<?php
date_default_timezone_set('Europe/Berlin');
session_start();

require_once $_SERVER['DOCUMENT_ROOT']."/assets/inc/show.php";
Dzg\show("register");
