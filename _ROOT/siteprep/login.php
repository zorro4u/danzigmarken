<?php
namespace Dzg\SitePrep;
use Dzg\Tools\Auth;

require_once __DIR__.'/../tools/auth.php';


class Login
{
    protected static string $success_msg;

    protected static function siteEntryCheck()
    {
        // Nutzer schon angemeldet? Dann weg hier ...
        self::$success_msg = (Auth::isCheckedIn())
            ? "Du bist schon angemeldet. Was machst du dann hier? ..."
            : "";


        if (empty($_SESSION['main'])) $_SESSION['main'] = "/";

        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details'];
        if (isset($_SERVER['HTTP_REFERER'])
            && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']) === false))
        {
            // wenn VorgängerSeite bekannt und nicht die aufgerufene Seite selbst ist, speichern
            $referer = str_replace("change", "details", $_SERVER['HTTP_REFERER']);
            $fn_referer = pathinfo($referer)['filename'];

            // wenn Herkunft von den target-Seiten, dann zu diesen, ansonsten Standardseite
            $_SESSION['lastsite'] =  (in_array($fn_referer, $return2))
                ? $referer
                : $_SESSION['main'];

        } elseif (empty($_SERVER['HTTP_REFERER']) && empty($_SESSION['lastsite'])) {

            // wenn nix gesetzt ist, auf Standard index.php verweisen
            $_SESSION['lastsite'] = (!empty($_SESSION['main'])) ? $_SESSION['main'] : "/";

        } else {
            $_SESSION['lastsite'] = $_SESSION['main'];
        }
    }


    protected static function dataPreparation()
    { }

}

