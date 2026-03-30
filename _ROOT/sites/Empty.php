<?php
namespace Dzg\Sites;
use Dzg\Tools\{Header, Footer};

require_once __DIR__.'/../tools/Header.php';
require_once __DIR__.'/../tools/Footer.php';


class Dummy
{
    public static function show(?string $msg=null)
    {
        Header::show();
        self::siteOutput($msg);
        Footer::show("empty");
    }


    private static function siteOutput($msg)
    {
        echo "
            <div class='container main-container registration-form'>
            <br><br><p>{$msg}</p></div>";
    }
}