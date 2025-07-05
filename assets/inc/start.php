<?php
require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/Starter.php";

function run($site='table') {
    Dzg\Starter::run($site);
}
