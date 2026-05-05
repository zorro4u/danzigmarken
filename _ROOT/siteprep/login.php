<?php
namespace Dzg\SitePrep;
use Dzg\Tools\Auth;
use Dzg\Tools\Tools;

require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


class Login
{
    protected const MSG = [
        0 => "Du bist schon angemeldet. Was machst du dann hier? ...",
    ];

    protected static string $success_msg;

    protected static function siteEntryCheck(): void
    {
        // Herkunftsseite speichern
        Tools::lastSite();

        // Nutzer schon angemeldet? Dann weg hier ...
        self::$success_msg = (Auth::isCheckedIn())
            ? self::MSG[0]
            : "";
    }


    protected static function dataPreparation(): void
    { }

}


// EOF