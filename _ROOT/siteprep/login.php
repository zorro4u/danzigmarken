<?php
namespace Dzg\SitePrep;
use Dzg\Tools\Auth;
use Dzg\Tools\Tools;

require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


class Login
{
    protected static string $success_msg;

    protected static function siteEntryCheck(): void
    {
        // Herkunftsseite speichern
        Tools::lastSite();

        // Nutzer schon angemeldet? Dann weg hier ...
        self::$success_msg = (Auth::isCheckedIn())
            ? "Du bist schon angemeldet. Was machst du dann hier? ..."
            : "";
    }


    protected static function dataPreparation(): void
    { }

}


// EOF