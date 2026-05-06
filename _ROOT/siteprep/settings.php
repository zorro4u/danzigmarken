<?php
namespace Dzg\SitePrep;
use Dzg\SiteData\Settings as Data;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../sitedata/settings.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Settings
 */
class Settings
{
    protected static $userid;
    protected static $usr_data;
    protected static $userliste;
    protected static $identifier;
    protected static $error_msg;
    protected static $show_form;


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
        $show_form = self::$show_form;
        $usr_data = [];
        $userliste = [];

        // Seiten-Check okay, Seite starten
        if ($show_form):

        // Zählerangaben für Autologin-Anzeige des aktuellen Nutzers holen
        // alle aktiven Anmeldungen
        $results = Data::getCounter($userid, $identifier);

        // Daten separieren
        foreach ($results as $user) {

            // aktueller Nutzer (für Formular-Vorbelegung)
            if ($user['userid'] == $userid) $usr_data = $user;

            // die anderen (für Abgleich nach Änderung von name/email)
            else {
                $userliste []= [
                    'username' => $user['username'],
                    'email' => $user['email']
                ];
            }
        }
        endif;      # Seiten-Check okay

        // globale Variablen setzen
        self::$usr_data = $usr_data;
        self::$userliste = $userliste;
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