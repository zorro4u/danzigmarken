<?php
namespace Dzg\Sites;
use Dzg\SitePrep\{Header, Footer};

require_once __DIR__.'/../siteprep/loader_default.php';


class Dummy
{
    public static function show(?string $msg=null)
    {
        Header::show();
        self::view($msg);
        Footer::show("empty");
    }


    private static function view($msg)
    {
        echo "
            <div class='container main-container registration-form'>
            <br><br><p>{$msg}</p></div>";
    }
}