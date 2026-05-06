<?php
namespace Dzg;
require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/starter.php";

function show(?string $site = null) {
    Starter::show($site);
}
