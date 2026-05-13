<?php
namespace Dzg;
use Dzg\Tools\Auth;

require_once __DIR__.'/../sitemsg/login.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


class LoginPrep
{
    protected const MSG = LoginMsg::MSG;
    protected static string $success_msg;

    protected static function siteEntryCheck(): void
    {
        // Nutzer schon angemeldet? Dann weg hier ...
        self::$success_msg = (Auth::isCheckedIn())
            ? self::MSG[110]
            : "";
    }


    protected static function dataPreparation(): void
    { }

}


// EOF