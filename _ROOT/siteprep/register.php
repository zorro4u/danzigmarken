<?php
namespace Dzg\SitePrep;
use Dzg\SiteData\Register as Data;
use Dzg\Tools\Tools;

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__.'/../sitedata/register.php';
require_once __DIR__.'/../tools/tools.php';


/***********************
 * Summary of Register
 */
class Register
{
    protected static $usr_data;
    protected static $input_code;
    protected static $error_msg;


    /***********************
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details'];

        if (isset($_SERVER['HTTP_REFERER']) &&
            (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']) === false))
        {
            // wenn VorgängerSeite bekannt und nicht die aufgerufene Seite selbst ist, speichern
            $referer = str_replace("change", "details", $_SERVER['HTTP_REFERER']);
            $fn_referer = pathinfo($referer)['filename'];
            // wenn Herkunft von den target-Seiten, dann zu diesen, ansonsten Standardseite
            $_SESSION['lastsite'] =  (in_array($fn_referer, $return2))
                ? $referer
                : $_SESSION['main'];

        } elseif (empty($_SERVER['HTTP_REFERER']) &&
            empty($_SESSION['lastsite']))
        {
            // wenn nix gesetzt ist, auf Standard index.php verweisen
            $_SESSION['lastsite'] = (!empty($_SESSION['main'])) ? $_SESSION['main'] : "/";
        }
        unset($return2, $referer, $fn_referer);


        /*
        * Seitenaufruf mit Registrierungscode
        */

        $error_msg = "";

        // Registrierungs-Code checken
        if (!isset($_GET['code'])) {
            $error_msg = "Es fehlt ein gültiger Registrierungs-Link. <br>Wende dich dafür an den Seitenbetreiber.";
        } else {
            $input_code = htmlspecialchars(Tools::cleanInput($_GET['code']));

            // Plausi-Check
            if ($input_code === "")
                $error_msg = 'Es wurden kein Code zum Start der Registrierung übermittelt.';
            elseif (!preg_match('/^[a-zA-Z0-9]{1,1000}/', $input_code))
                $error_msg = 'Der Code enhält ungültige Zeichen.';
            else {}
        }

        // Link mit DB abgleichen
        if ($error_msg === "") {

            // Registrierungs-Link auf Gültigkeit prüfen
            $usr_data = Data::getUser($input_code);

            if (!$usr_data) {
                $error_msg = "Registrierungs-Link wurde verändert oder ist nicht gültig.";

            } elseif (($usr_data['pwcode_endtime'] + 3600*1) < time()) {  // +1 Std. Karenz
                // veralteten Eintrag löschen
                Data::deleteOldEntry($usr_data['userid']);

                $error_msg = "Registrierungs-Link ist nach 4 Wochen am ".date('d.m.Y', $usr_data['pwcode_endtime'])." abgelaufen.";

            } else {}
        }

        self::$usr_data   = $usr_data;
        self::$input_code = $input_code;
        self::$error_msg  = $error_msg;
    }

}