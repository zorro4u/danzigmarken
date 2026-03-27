<?php
namespace Dzg;
require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/Starter.php";

function start($site='table') {
    Starter::run($site);
}
