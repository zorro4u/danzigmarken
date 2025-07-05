<?php
date_default_timezone_set('Europe/Berlin');
session_start();

require_once $_SERVER['DOCUMENT_ROOT']."/assets/inc/start.php";
run("logout");
