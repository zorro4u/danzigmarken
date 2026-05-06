<?php
namespace Dzg\SiteForm;
use Dzg\SitePrep\Settings as Prep;
use Dzg\SiteData\Settings as Data;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../siteprep/settings.php';
require_once __DIR__.'/../sitedata/settings.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Settings
 */
class Settings extends Prep
{
    protected static $active;
    protected static $status_message;


    /**
     * Summary of siteEntryCheck
     * CheckIn-Test
     * Plausi-Test: userid, identifier, token_hash
     * set identifier
     * set last_site
     * set showForm
     */
    protected static function siteEntryCheck()
    {
        if (empty($_SESSION['main']))
            $_SESSION['main'] = "/";

        Tools::lastSite();

        [$usr_data, $securitytoken_row, $error_msg] = Auth::checkUser();

        // unberechtigter Seitenaufruf
        $status = (empty($error_msg)) ? true : false;

        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::isCheckedIn()) {
            #header("location: /auth/login.php");
            #exit;

            header('HTTP/1.0 403 Forbidden');
            echo "Forbidden";
            exit();
        }

        // globale Variablen setzen
        if ($status) {
            self::$identifier = $securitytoken_row['identifier'];
            self::$userid = $usr_data['userid'];
        }
        self::$error_msg = $error_msg;
        self::$show_form = $status;
    }


    /**
     * Summary of dataPreparation
     * set $usr_data, $userliste
     */
    protected static function dataPreparation()
    {
        // TODO:
        // Konto löschen per PW legitimieren
        // Konto löschen: logout nicht über die logout-Seite sondern per Funktion und Rücksprung zur Hauptseite
        //

        // globale Variablen holen
        $userid = self::$userid;
        $identifier = self::$identifier;
        $show_form  = self::$show_form;
        $usr_data   = [];
        $userliste  = [];

        // Seiten-Check okay, Seite starten
        if ($show_form):

        // Zählerangaben für Autologin-Anzeige des aktuellen Nutzers holen
        // alle aktiven Anmeldungen
        $data = [':userid' => $userid, ':ident' => $identifier];
        $results = Data::getUserCounts($data);

        // Daten separieren
        foreach ($results as $user) {

            // aktueller Nutzer (für Formular-Vorbelegung)
            if ($user['userid'] == $userid) $usr_data = $user;

            // die anderen (für Abgleich nach Änderung von name/email)
            else {
                $userliste []= [
                    'username' => $user['username'],
                    'email'    => $user['email']
                ];
            }
        }
        endif;      # Seiten-Check okay

        // globale Variablen setzen
        self::$usr_data  = $usr_data;
        self::$userliste = $userliste;
    }


    /**
     * Summary of formEvaluation
     * Formular-Eingabe verarbeiten
     */
    protected static function formEvaluation()
    {
        $identifier = self::$identifier;
        $usr_data   = self::$usr_data;
        $userliste  = self::$userliste;
        $userid = self::$userid;
        $show_form = self::$show_form;
        $error_msg = self::$error_msg;
        $success_msg = "";

        // Seiten-Check okay, Seite starten
        if ($show_form):

        // Änderungsformular empfangen
        if (isset($_GET['save']) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST"):

        $regex_usr = "/^[\wäüößÄÜÖ\-]{3,50}$/";
        $regex_email = "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix";
        $regex_pw = "/^[\w<>()?!,.:_=$%&#+*~^ @€µäüößÄÜÖ]{1,100}$/";
        // (Doppel-)Name mit Bindestrich/Leerzeichen, ohne damit zu beginnen oder zu enden und ohne doppelte Striche/Leerz., ist 0-50 Zeichen lang
        $regex_name = "/^[a-zA-ZäüößÄÜÖ]+([a-zA-ZäüößÄÜÖ]|[ -](?=[a-zA-ZäüößÄÜÖ])){0,50}$/";
        $regex_name_no = "/^[^a-zA-ZäüößÄÜÖ]+|[^a-zA-ZäüößÄÜÖ -]+|[- ]{2,}|[^a-zA-ZäüößÄÜÖ]+$/";

        $save = htmlspecialchars(Tools::cleanInput($_GET['save']));
        switch ($save):

        // Änderung Anmeldedaten
        case 'email':
            $update_username = False;
            $update_email = False;

            $input_usr = htmlspecialchars(Tools::cleanInput($_POST['username']));    # strtolower()
            $input_email  = htmlspecialchars(Tools::cleanInput($_POST['email']));
            $input_email2 = htmlspecialchars(Tools::cleanInput($_POST['email2']));
            $input_pw = $_POST['passwort'];


            // Eingabewerte auf Plausibilität prüfen

            // Passwort-Check
            if (!password_verify($input_pw, $usr_data['pw_hash']))
                $error_msg = "Bitte korrektes Passwort eingeben.";


            // Email-Check
            elseif ($input_email === "" && $input_usr === "")
                $error_msg = 'Name oder Email angeben.';

            elseif ($input_email !== "" && ($input_email !== $input_email2))
                $error_msg = 'Die Emailangaben müssen übereinstimmen.';

            elseif ($input_email !== "" &&
                (!filter_var($input_email, FILTER_VALIDATE_EMAIL)))
            {
                $error_msg = 'Keine gültige Email-Adresse.';

            } elseif ($input_email !== "" &&
                ($input_email !== $usr_data['email']))
            {
                foreach ($userliste AS $user_info) {
                    // ist email schon vorhanden?
                    if ($input_email == $user_info['email']) {
                        $error_msg = "Die E-Mail-Adresse ist bereits registriert.";
                        break;
                    } else {
                        $update_email = True;
                    }
                }

            } elseif ($input_email === $usr_data['email'])
                $success_msg = "E-Mail-Adressse unverändert.";


            // Username-Check
            elseif ($input_usr !== "" &&
                preg_match("/\W/", $input_usr, $matches))
            {
                $error_msg = 'nur Buchstaben/Zahlen im Anmeldenamen zulässig: '.
                    htmlspecialchars($matches[0]);

            } elseif ($input_usr !== "" &&
                ($input_usr !== $usr_data['username']))
            {
                foreach ($userliste AS $user_info) {
                    // ist username schon vorhanden?
                    if ($input_usr == $user_info['username']) {
                        $error_msg = "Der Benutzername ist schon vergeben.";
                        break;
                    } else {
                        $update_username = True;
                    }
                }
            } elseif ($input_usr == $usr_data['username'])
                $success_msg = "Benutzername unverändert.";

            else {}  // Namen-Email-Check okay


            // Daten in DB ändern
            if ($error_msg === "" && $success_msg === "") {
                if ($update_email && $update_username) {
                    $data = [
                        ':userid'   => $userid,
                        ':username' => $input_usr,
                        ':email'    => $input_email ];
                    Data::changeUser($data);
                    $usr_data['username'] = $input_usr;
                    $usr_data['email'] = $input_email;
                    $success_msg = "Benutzername und E-Mail-Adresse erfolgreich gespeichert.";

                } elseif ($update_email) {
                    $data = [':userid' => $userid, ':email' => $input_email];
                    Data::changeUserMail($data);
                    $usr_data['email'] = $input_email;
                    $success_msg = "E-Mail-Adresse erfolgreich gespeichert.";

                } elseif ($update_username) {
                    $data = [':userid' => $userid, ':username' => $input_usr];
                    Data::changeUserName($data);
                    $usr_data['username'] = $input_usr;
                    $success_msg = "Benutzername erfolgreich geändert.";
                }
                else {}   // keine Änderungen
            }
        break;


        // Änderung Passwort
        case 'passwort':
            $input_pwALT  = $_POST['passwortAlt'];
            $input_pwNEU1 = $_POST['passwortNeu'];
            $input_pwNEU2 = $_POST['passwortNeu2'];

            // Eingabewerte auf Plausibilität prüfen
            if ($input_pwNEU1 != $input_pwNEU2)
                $error_msg = "Die eingegebenen Passwörter stimmten nicht überein.";

            elseif (!password_verify($input_pwALT, $usr_data['pw_hash']))
                $error_msg = "Bitte korrektes Passwort eingeben.";

            elseif (strlen($input_pwNEU1) < 4 || strlen($input_pwNEU1) > 50)
                $error_msg = "Passwort muss zwischen 4 und 50 Zeichen lang sein!";

            elseif (!preg_match($regex_pw, $input_pwNEU1))
                $error_msg = "Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>";

            else {
                $passwort_hash = password_hash($input_pwNEU1, PASSWORD_DEFAULT);
                $data = [':userid' => $userid, ':pw_hash' => $passwort_hash];
                Data::storePW($data);
                $usr_data['pw_hash'] = $passwort_hash;
                $success_msg = "Passwort erfolgreich gespeichert.";
            }
        break;


        // Änderung Persönl. Daten
        case 'data':
            $error_msg = [];
            $input_vor = isset($_POST['vorname']) ? htmlspecialchars(Tools::cleanInput($_POST['vorname'])) : "";
            $input_nach = isset($_POST['nachname']) ? htmlspecialchars(Tools::cleanInput($_POST['nachname'])) : "";

            // Plausi-Check
            if ($input_vor !== "" && preg_match_all($regex_name_no, $input_vor, $match))
                $error_msg []= 'nur Buchstaben im Vornamen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen): "'.htmlentities(implode(" ", $match[0])).'"';

            if ($input_nach !== "" && preg_match_all($regex_name_no, $input_nach, $match))
                $error_msg []= 'nur Buchstaben im Nachnamen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen): "'.htmlentities(implode(" ", $match[0])).'"';

            // Eingabe okay
            if (empty($error_msg)){
                if ($input_vor != $usr_data['vorname'] || $input_nach != $usr_data['nachname']) {
                    $data = [
                        ':userid'   => $userid,
                        ':vorname'  => $input_vor,
                        ':nachname' => $input_nach ];
                    Data::changeUserData($data);
                    $usr_data['vorname'] = $input_vor;
                    $usr_data['nachname'] = $input_nach;
                    $success_msg = "Persönliche Daten geändert.";
                }
            }
            $error_msg = implode("", $error_msg);
        break;


        // Autologins abmelden, log=0
        case 'autologin':
            $data = [':userid' => $userid, ':ident' => $identifier];
            Data::deleteMyAutologin($data);
            $usr_data['count3'] = "";
            $success_msg = "alle meine anderen Autologins beendet.";
        break;


        // Konto löschen
        case 'delete':
            $input_pw3 = $_POST['pw_delete'];

            // Passwort-Check
            if (!password_verify($input_pw3, $usr_data['pw_hash']))
                $error_msg = "Bitte korrektes Passwort eingeben.";

            if ((isset($_SESSION['su']) && (int)$_SESSION['su'] === 1) )
                $error_msg = "Ein Admin kann sich hier nicht löschen.";

            if ($error_msg === "") {
                Data::deleteUser($userid);
                $usr_data = [];
                $success_msg = "Nutzer gelöscht.";

                Auth::logout();
                #header("location: /auth/logout.php");
                exit;
            }
        break;

        endswitch;  # Speichern-Taste gedrückt
        endif;      # Formular empfangen
        endif;      # Seiten-Check okay


        // Marker setzen, um wieder auf den letzten Tab-Reiter zu springen
        //
        // Liste der #Tab-ID's
        $site_tabs = ['email', 'passwort', 'data', 'autologin', 'delete', 'download'];

        $active = [];
        if (isset($_GET['tab']) && in_array($_GET['tab'], $site_tabs)) {
            foreach ($site_tabs as $tab) {
                if ($_GET['tab'] == $tab) {
                    $active[$tab] = "active";
                } else
                    $active[$tab] = "";
            }

        // irgendwie kein GET erhalten,
        // $active auf Standard (1.Tab = email) setzen
        } else {
            foreach ($site_tabs as $tab) {
                $active[$tab] = "";
            }
            $active[$site_tabs[0]] = "active";
        }

        $status_message = Tools::statusOut($success_msg, $error_msg);

        // globale Variablen setzen
        self::$usr_data = $usr_data;
        self::$active   = $active;
        self::$error_msg = $error_msg;
        self::$status_message = $status_message;

        unset($_REQUEST, $_POST, $_GET);
    }
}




/*

    <h2>Viewportabmessungen</h2>
    <h3>Breite</h3>
    <p><a href="https://wiki.selfhtml.org/wiki/ClientWidth">Element.clientWidth</a>:
        <span
        id="clientW"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/InnerWidth">Window.innerWidth</a>:
        <span
        id="innerW"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/OuterWidth">Window.outerWidth</a>:
        <span
        id="outerW"></span>px</p>
    <h3>Höhe</h3>
    <p><a href="https://wiki.selfhtml.org/wiki/ClientHeight">Element.clientHeight</a>:
        <span id="clientH"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/InnerHeight">Window.innerHeight</a>:
        <span
        id="innerH"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/OuterHeight">Window.outerHeight</a>:
        <span
        id="outerH"></span>px</p>
    <h2>Geräteabmessungen</h2>
    <h3>Breite</h3>
    <p><a href="https://wiki.selfhtml.org/wiki/JavaScript/Screen/width">Screen.width</a>:
        <span id="screenW"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/availWidth">Screen.availWidth</a>:
        <span
        id="availW"></span>px</p>
    <h3>Höhe</h3>
    <p><a href="https://wiki.selfhtml.org/wiki/JavaScript/Screen/height">Screen.height</a>:
        <span id="screenH"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/availHeight">Screen.availHeight</a>:
        <span
        id="availH"></span>px</p>



<script>
'use strict';
document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener('resize', messen);
    messen();

    function messen() {
        document.getElementById('clientW')
            .textContent = document.querySelector('html')
            .clientWidth;
        document.getElementById('innerW')
            .textContent = window.innerWidth;
        document.getElementById('outerW')
            .textContent = window.outerWidth;
        document.getElementById('clientH')
            .textContent = document.querySelector('html')
            .clientHeight;
        document.getElementById('innerH')
            .textContent = window.innerHeight;
        document.getElementById('outerH')
            .textContent = window.outerHeight;
        document.getElementById('screenW')
            .textContent = screen.width;
        document.getElementById('availW')
            .textContent = screen.availWidth;
        document.getElementById('screenH')
            .textContent = screen.height;
        document.getElementById('availH')
            .textContent = screen.availHeight;
    }
});
</script>

resizeObserver
Window.matchMedia()
*/