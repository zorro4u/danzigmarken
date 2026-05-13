<?php
namespace Dzg;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../siteprep/settings.php';
require_once __DIR__.'/../sitedata/settings.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Settings
 */
class SettingsForm extends SettingsPrep
{
    protected const REGEX_USR = "/^[\wäüößÄÜÖ\-]{3,50}$/";
    protected const REGEX_EMAIL = "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix";
    protected const REGEX_PW = "/^[\w<>()?!,.:_=$%&#+*~^ @€µäüößÄÜÖ]{1,100}$/";
    // (Doppel-)Name mit Bindestrich/Leerzeichen, ohne damit zu beginnen oder zu enden und ohne doppelte Striche/Leerz., ist 0-50 Zeichen lang
    protected const REGEX_NAME = "/^[a-zA-ZäüößÄÜÖ]+([a-zA-ZäüößÄÜÖ]|[ -](?=[a-zA-ZäüößÄÜÖ])){0,50}$/";
    protected const REGEX_NAME_NO = "/^[^a-zA-ZäüößÄÜÖ]+|[^a-zA-ZäüößÄÜÖ -]+|[- ]{2,}|[^a-zA-ZäüößÄÜÖ]+$/";


    protected static array $active;
    protected static string $status_message;


    /**
     * Summary of formEvaluation
     * Formular-Eingabe verarbeiten
     */
    protected static function formEvaluation()
    {
        $show_form = self::$show_form;
        $error_msg = self::$error_msg;
        $success_msg = "";

        # Seiten-Check okay, Seite starten
        if ($show_form):

        // Änderungsformular empfangen
        //
        if (isset($_GET['save'])
            && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")
        {
            switch (htmlspecialchars(Tools::cleanInput($_GET['save']))) {

                # Änderung Anmeldedaten
                case 'email':
                    [$success_msg, $error_msg] = self::chg_login_data();
                    break;

                # Änderung Passwort
                case 'passwort':
                    [$success_msg, $error_msg] = self::chg_pwd();
                    break;

                # Änderung Persönl. Daten
                case 'data':
                    [$success_msg, $error_msg] = self::chg_usr_data();
                    break;

                # Autologins abmelden, log=0
                case 'autologin':
                    [$success_msg, $error_msg] = self::del_login();
                    break;

                # Konto löschen
                case 'delete':
                    [$success_msg, $error_msg] = self::del_account();
                    break;

            };  // Speichern-Taste gedrückt
        };      // Formular empfangen
        endif;  // Seiten-Check okay


        // Marker setzen, um wieder auf den letzten Tab-Reiter zu springen
        //
        # Liste der #Tab-ID's
        $site_tabs = ['email', 'passwort', 'data', 'autologin', 'delete', 'download'];

        $active = [];
        if (isset($_GET['tab']) && in_array($_GET['tab'], $site_tabs)) {
            foreach ($site_tabs as $tab) {
                $active[$tab] = ($_GET['tab'] == $tab)
                    ? "active"
                    : "";
            };
        }

        # irgendwie kein GET erhalten,
        # $active auf Standard (1.Tab = email) setzen
        else {
            foreach ($site_tabs as $tab) {
                $active[$tab] = "";
            };
            $active[$site_tabs[0]] = "active";
        };

        $status_message = Tools::statusOut($success_msg, $error_msg);

        # globale Variablen setzen
        self::$active   = $active;
        self::$error_msg = $error_msg;
        self::$status_message = $status_message;

        unset($_REQUEST, $_POST, $_GET);
    }



    private static function chg_login_data(): array
    {
        $usr_data   = self::$usr_data;
        $userliste  = self::$userliste;
        $userid = self::$userid;

        $update_username = False;
        $update_email = False;
        $success_msg = $error_msg = "";

        $input_usr = htmlspecialchars(Tools::cleanInput($_POST['username']));    # strtolower()
        $input_email  = htmlspecialchars(Tools::cleanInput($_POST['email']));
        $input_email2 = htmlspecialchars(Tools::cleanInput($_POST['email2']));
        $input_pw = $_POST['passwort'];


        // Eingabewerte auf Plausibilität prüfen
        //
        # Passwort-Check
        if (!password_verify($input_pw, $usr_data['pw_hash'])) {
            $error_msg = self::MSG[210];
        }

        # Email-Check
        elseif ($input_email === "" && $input_usr === "") {
            $error_msg = self::MSG[211];
        }

        elseif ($input_email !== ""
            && ($input_email !== $input_email2))
        {
            $error_msg = self::MSG[212];
        }

        elseif ($input_email !== ""
            && (!filter_var($input_email, FILTER_VALIDATE_EMAIL)))
        {
            $error_msg = self::MSG[213];
        }

        elseif ($input_email !== ""
            && ($input_email !== $usr_data['email']))
        {
            foreach ($userliste AS $user_info) {
                # ist email schon vorhanden?
                if ($input_email == $user_info['email']) {
                    $error_msg = self::MSG[214];
                    break;
                }
                else {
                    $update_email = True;
                };
            };
        }

        elseif ($input_email === $usr_data['email']) {
            $success_msg = self::MSG[215];
        }

        # Username-Check
        elseif ($input_usr !== ""
            && preg_match("/\W/", $input_usr, $matches))
        {
            $error_msg = self::MSG[216] . ': '. htmlspecialchars($matches[0]);
        }

        elseif ($input_usr !== ""
            && ($input_usr !== $usr_data['username']))
        {
            foreach ($userliste AS $user_info) {
                # ist username schon vorhanden?
                if ($input_usr == $user_info['username']) {
                    $error_msg = self::MSG[217];
                    break;
                } else {
                    $update_username = True;
                };
            };
        }

        elseif ($input_usr == $usr_data['username']) {
            $success_msg = self::MSG[218];
        }

        else {};  // Namen-Email-Check okay


        // Daten in DB ändern
        //
        if ($error_msg === ""
            && $success_msg === "")
        {
            if ($update_email && $update_username) {
                $data = [
                    ':userid'   => $userid,
                    ':username' => $input_usr,
                    ':email'    => $input_email ];
                SettingsData::changeUser($data);
                $usr_data['username'] = $input_usr;
                $usr_data['email'] = $input_email;
                $success_msg = self::MSG[219];
            }
            elseif ($update_email) {
                $data = [':userid' => $userid, ':email' => $input_email];
                SettingsData::changeUserMail($data);
                $usr_data['email'] = $input_email;
                $success_msg = self::MSG[220];
            }
            elseif ($update_username) {
                $data = [':userid' => $userid, ':username' => $input_usr];
                SettingsData::changeUserName($data);
                $usr_data['username'] = $input_usr;
                $success_msg = self::MSG[221];
            }
            else {};   // keine Änderungen
        };

        self::$usr_data = $usr_data;
        return [$success_msg, $error_msg];
    }


    private static function chg_pwd(): array
    {
        $usr_data = self::$usr_data;
        $userid = self::$userid;

        $input_pwALT  = $_POST['passwortAlt'];
        $input_pwNEU1 = $_POST['passwortNeu'];
        $input_pwNEU2 = $_POST['passwortNeu2'];

        $success_msg  = $error_msg = "";

        # Eingabewerte auf Plausibilität prüfen
        if ($input_pwNEU1 != $input_pwNEU2)
            $error_msg = self::MSG[222];

        elseif (!password_verify($input_pwALT, $usr_data['pw_hash']))
            $error_msg = self::MSG[210];

        elseif (strlen($input_pwNEU1) < 4 || strlen($input_pwNEU1) > 50)
            $error_msg = self::MSG[210];

        elseif (!preg_match(self::REGEX_PW, $input_pwNEU1))
            $error_msg = self::MSG[223] . " !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)" . self::MSG[224];

        else {
            $passwort_hash = password_hash($input_pwNEU1, PASSWORD_DEFAULT);
            $data = [':userid' => $userid, ':pw_hash' => $passwort_hash];
            SettingsData::storePW($data);
            $usr_data['pw_hash'] = $passwort_hash;
            $success_msg = self::MSG[225];
        };

        self::$usr_data = $usr_data;
        return [$success_msg, $error_msg];
    }


    private static function chg_usr_data(): array
    {
        $usr_data = self::$usr_data;
        $userid = self::$userid;

        $success_msg  = $error_msg = "";
        $error_msg = [];

        $input_vor = isset($_POST['vorname'])
            ? htmlspecialchars(Tools::cleanInput($_POST['vorname']))
            : "";
        $input_nach = isset($_POST['nachname'])
            ? htmlspecialchars(Tools::cleanInput($_POST['nachname']))
            : "";

        # Plausi-Check
        if ($input_vor !== ""
            && preg_match_all(self::REGEX_NAME_NO, $input_vor, $match))
        {
            $error_msg []= self::MSG[226] . ' '. self::MSG[228] . ': "'.htmlentities(implode(" ", $match[0])).'"';
        };

        if ($input_nach !== ""
            && preg_match_all(self::REGEX_NAME_NO, $input_nach, $match))
        {
            $error_msg []= self::MSG[227] . ' '. self::MSG[228] . ': "'.htmlentities(implode(" ", $match[0])).'"';
        };

        # Eingabe okay
        if (empty($error_msg)
            && ($input_vor != $usr_data['vorname']
                || $input_nach != $usr_data['nachname']))
        {
            $data = [
                ':userid'   => $userid,
                ':vorname'  => $input_vor,
                ':nachname' => $input_nach ];
            SettingsData::changeUserData($data);
            $usr_data['vorname'] = $input_vor;
            $usr_data['nachname'] = $input_nach;
            $success_msg = self::MSG[229];
        };
        $error_msg = implode("", $error_msg);

        self::$usr_data = $usr_data;
        return [$success_msg, $error_msg];
    }


    private static function del_login(): array
    {
        $usr_data = self::$usr_data;
        $userid = self::$userid;
        $identifier = self::$identifier;
        $success_msg  = $error_msg = "";

        $data = [':userid' => $userid, ':ident' => $identifier];
        SettingsData::deleteMyAutologin($data);
        $usr_data['count3'] = "";
        $success_msg = self::MSG[230];

        self::$usr_data = $usr_data;
        return [$success_msg, $error_msg];
    }


    private static function del_account(): array
    {
        $usr_data = self::$usr_data;
        $userid = self::$userid;
        $input_pw3 = $_POST['pw_delete'];
        $success_msg  = $error_msg = "";

        # Passwort-Check
        if (!password_verify($input_pw3, $usr_data['pw_hash'])) {
            $error_msg = self::MSG[210];
        };

        if ((isset($_SESSION['su']) && (int)$_SESSION['su'] === 1)) {
            $error_msg = self::MSG[231];
        };

        if ($error_msg === "") {
            SettingsData::deleteUser($userid);
            $usr_data = [];
            $success_msg = self::MSG[232];

            Auth::logout();
            #header("location: /auth/logout.php");
            exit;
        };

        self::$usr_data = $usr_data;
        return [$success_msg, $error_msg];
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


// EOF