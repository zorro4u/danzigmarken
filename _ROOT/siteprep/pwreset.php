<?php
/* Prozess: ForgetSeite-->email(Admin)/email(Code)-->dieseSeite:ResetSeite-->Login */

namespace Dzg\SitePrep;
use Dzg\SiteData\PWreset as Data;
use Dzg\Tools\Tools;

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);
session_start();

require_once __DIR__.'/../sitedata/pwreset.php';
require_once __DIR__.'/../tools/tools.php';


// TODO: alle Autologins beenden

/***********************
 * Summary of Pw_reset
 */
class PWreset
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $show_form;
    protected static string $status_message;
    protected static $success_msg;
    protected static $name;
    protected static $input_code;
    protected static $usr_data;


    /***********************
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details'];
        if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']) === false)) {
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
        }
        unset($return2, $referer, $fn_referer);


        /*
        * Seitenaufruf mit passwortcode
        */

        $error_msg = "";
        $success_msg = "";
        $name = "";
        $input_code = "";
        $show_form = True;

        if (!isset($_GET['pwcode'])) {
            $error_msg = "Ohne Legitimations-Code kann das Passwort nicht zurückgesetzt werden.";

        // Passcode prüfen
        } else {
            $input_code = htmlspecialchars(Tools::cleanInput($_GET['pwcode']));

            // Werte auf Plausibilität prüfen
            if ($input_code === "")
                $error_msg = 'Es wurde kein Legitimations-Code zum Zurücksetzen des Passworts übermittelt.';
            elseif (!preg_match('/^[a-zA-Z0-9]{1,1000}/', $input_code))
                $error_msg = 'Der Passcode enhält ungültige Zeichen.';
        }

        // Link mit DB abgleichen
        if ($error_msg === "") {

            // Passcode auf Gültigkeit prüfen
            $pwcode_hash = sha1($input_code);
            $usr_data = Data::getPassCode($pwcode_hash);

            if (!$usr_data)
                $error_msg = "Der Benutzer wurde nicht gefunden oder hat kein neues Passwort angefordert bzw. der übergebene Code war ungültig. ".
                    "<hr>Stell sicher, dass du den genauen Link in der URL aufgerufen hast. ".
                    "Solltest du mehrmals die Passwortvergessen-Funktion genutzt haben, so ruf den Link in der neuesten E-Mail auf.";

            elseif (($usr_data['pwcode_endtime'] + 3600*1) < time()) {  // +1 Std. Karenz
                // Passcode abgelaufen, veralteten Eintrag löschen
                Data::deletePassCode($usr_data['userid']);

                $error_msg = "Dein Code ist leider am ".date('d.m.y H:i', $usr_data['pwcode_endtime']).
                    " abgelaufen. Benutze die <a href='pwforget'>Passwortvergessen-Funktion</a> erneut.";
            }
            else {
                // Anrede
                if (!empty($usr_data['vorname']))
                    $name = $usr_data['vorname'];
                elseif ($usr_data['username'])
                    $name = $usr_data['username'];
                else
                    $name = $usr_data['email'];
            }
        }

        // Passcode okay, Seite starten
        if ($error_msg === ""):

            // Formularwerte empfangen
            if (isset($_GET['send']) && (strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")) {
                // Eingabewerte auf Plausibilität prüfen
                if (isset($_POST['passwort'], $_POST['passwort2'])) {
                    $input_pwNEU1 = $_POST['passwort'];
                    $input_pw2 = $_POST['passwort2'];
                    $regex_pw = "/^[\w<>()?!,.:_=$%&#+*~^ @€µÄÜÖäüöß]{1,100}$/";  // attention: add a slash at the begin and the end

                    // Passwortlänge prüfen
                    if (strlen($input_pwNEU1) < 4 || strlen($input_pwNEU1) > 50)
                        $error_msg = 'Passwort muss zwischen 4 und 50 Zeichen lang sein!';

                    // Passwort-Zeichen prüfen (nur alphanumerisch + ein paar Sonderzeichen (keine sql kritischen), Länge <100 Zeichen
                    elseif (!preg_match($regex_pw, $input_pwNEU1))
                        $error_msg = 'Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>';

                    // Doublette prüfen
                    elseif ($input_pwNEU1 !== $input_pw2)
                        $error_msg = "Bitte identische Passwörter eingeben";

                    else {}
                } else
                    $error_msg = 'Passwort angeben.';

                // Plausi-Check okay, speichere neues Passwort und lösche den Code
                // TODO: alle Autologins beenden
                if ($error_msg === "") {
                    $passwort_hash = password_hash($input_pwNEU1, PASSWORD_DEFAULT);
                    Data::storeNewPassword($usr_data['userid'], $passwort_hash);

                    $success_msg = "Dein Passwort wurde geändert";
                    $show_form = False;

                }  // Eingabewerte in Datenbank schreiben
            }  // Formularwerte empfangen

        endif;  // Passcode okay, Seite starten


        $show_form = ($error_msg === "") ? True : False;
        $status_message = Tools::statusOut($success_msg, $error_msg);


        self::$show_form = $show_form;
        self::$status_message = $status_message;
        self::$success_msg = $success_msg;
        self::$name = $name;
        self::$input_code = $input_code;
        self::$usr_data = $usr_data;
    }
}

