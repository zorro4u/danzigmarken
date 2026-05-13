<?php
namespace Dzg;
use Dzg\Tools\Auth;

require_once __DIR__.'/header_prep.php';
require_once __DIR__.'/../tools/auth.php';


/**
 * Summary of Header
 * Webseiten-Header, Seiten-Navigation
 */
class Header extends HeaderPrep
{
    /**
     * den obersten Teil der Webseite inclusive Seiten-Navigation ausgeben
     */
    public static function show(?string $site_name = null): void
    {
        self::antiflood();
        self::dataPreparation();
        self::loadHtmlHead();
        self::showNavigation();
    }



    /*
    <?= self::MSG[10] ?>
    ".self::MSG[10]."
     */
    protected const MSG = [
        10 => "",
        11 => "",
    ];


    /**
     * auf der Webseite die oberste, nicht sichtbare
     * Html <head> Section mit den meta-Anweisungen ausgeben
     */
    public static function loadHtmlHead(): void
    {
        # https://www.danzig.org/
        # https://arge.danzig.org/

        // wenn Funktion direkt von extern aufgerufen wird (printview),
        // dann allg. dummy-Angaben verwenden
        self::$site_id ??= 404;

        // die META-Anweisungen der Webseite aus dem Site-Array extrahieren
        foreach(self::PAGE as $k=>$v){
            if($v['site_id'] === self::$site_id)
                $meta = $v['meta'];
        }

        // und den Template-Variable zuordnen
        $title   = $meta['title'];
        $cache   = $meta['cache'];
        $expires = $meta['expires'];
        $robots  = $meta['robots'];
        $google  = $meta['google'];
        $canonical = $meta['canonical'];

        // gibt das HTML <head> Template aus,
        // inkl. den gerade gesetzen META-Werten
        require_once $_SERVER['DOCUMENT_ROOT'].self::HEAD_TEMPLATE;
    }


    /**
     * Webseiten-Navigation ausgeben
     */
    protected static function showNavigation(): void
    {
        $main_pages = self::MAIN_PAGES;
        $acc_pages  = self::ACC_PAGES;
        $rootdir = self::$rootdir;
        $main    = self::$main;
        $stepout = self::$stepout;
        $site_id = self::$site_id;

        $output = "
            <nav class='navbar navbar-inverse navbar-static-top'>
            <div class='container'>

            <div class='navbar-header'>
            <button type='button' class='navbar-toggle collapsed'
                    data-toggle='collapse' data-target='#navbar'
                    aria-expanded='false' aria-controls='navbar'>
                <span class='sr-only'>Menu</span>
                <span class='icon-bar'></span>
                <span class='icon-bar'></span>
                <span class='icon-bar'></span>
            </button>";


        if (!in_array(basename($_SERVER['PHP_SELF']), $main_pages)) {
            $href = "href='".$stepout['site']."' title='Hauptseite'";
            $symb = "&ensp;<i class='fas fa-circle-left' style='font-size:12px; color:khaki;'>
                </i>";
        } else {
            $href = "";
            $symb = "";
        };

        // links den Titel immer anzeigen
        $output .= "
            <a class='navbar-brand' style='color:#fff;' {$href}>
                Danzig Sammlung{$symb}</a>
            </div>";    # ende -- navbar-header --


        // -- Menü: nicht angemeldet --
        if (!Auth::isCheckedIn()) {
            $output .= "
                <div id='navbar' class='navbar-collapse collapse'>
                <ul class='nav navbar-nav navbar-right'>";

            // aktiv: Login     < login >
            #<li><a class='active'><span class='login_self'><i class='fas fa-sign-in-alt'>
            if ($site_id === 7) {
                $output .= "
                </i></span></a></li>
                <li><a class='active'><i class='fas fa-sign-in-alt'></i>Login</a></a></li>";
            }

            else {
                switch ($site_id):

                // aktiv: Einzelansicht     < einzel_gruppe_login >
                case 1:
                    $output .= "
                    <li><a class='active'>Einzelliste</a></li>
                    <li><a href='{$rootdir}/index2' title='Gruppenliste'>Markenliste</a></li>";
                    break;

                // aktiv: Markenansicht     < einzel_gruppe_login >
                case 2:
                    $output .= "
                    <li><a href='{$rootdir}/index' title='Einzelliste'>Einzelliste</a></li>
                    <li><a class='active'>Markenliste</a></li>";
                    break;

                // aktiv: Details     < detail_bearbeiten_login >
                case 3:
                    // Bearbeiten
                    //
                    # (nur für Admin und Heinz (id.3) zugänglich)
                    # <li><a href='{$rootdir}/auth/login' title='erst Anmelden dann Bearbeiten'><i class='fas fa-edit'></i>Bearbeiten **&emsp;</a></li>
                    $output .= "
                    <li><a class='active'>".$main['name']."</a></li>";
                    break;

                // aktiv: Kontakt     < kontakt_login >
                case 13:
                    $output .= "
                        <li><a class='active'><i class='fas fa-envelope'></i>".$main['name']."</a></li>";
                    break;

                // aktiv: Impressum     < impressum_login >
                case 6:
                    $output .= "
                        <li><a class='active'>".$main['name']."</a></li>";
                    break;

                // aktiv: About     < about_login >
                case 16:
                    $output .= "
                        <li><a class='active'>".$main['name']."</a></li>";
                    break;

                // aktiv: [was anderes]    < (Kontakt/Anmelden/Registrierung/...)_login >
                default:
                    $output .= "
                    <li><a class='active'>".$main['name']."</a></li>";

                endswitch;

                // ... und Login-Symbol als Letztes ranhängen
                $output .= "
                <li><a href='{$rootdir}/auth/login' title='Anmelden'>
                <i class='fas fa-sign-in-alt'></i><span class='login_link'></span></a></li>";
            };

            $output .= "</ul></div>";  # ende -- navbar-collapse --
        }


        // -- Menü: angemeldet --
        else {

            $output .= "
            <div id='navbar' class='navbar-collapse collapse'>
            <ul class='nav navbar-nav navbar-right'>";

            // -- nicht im Konto-Bereich --
            if (!in_array(basename($_SERVER['PHP_SELF']), $acc_pages)) {
                switch ($site_id):

                // aktiv: Einzelansicht     < einzel_gruppe_Konto >
                case 1:
                    $output .= "
                        <li><a class='active'>Einzelliste</a></li>
                        <li><a href='{$rootdir}/index2' title='Gruppenliste'>Markenliste</a></li>";
                        break;

                    /*
                    if (Auth::isCheckedIn() && isset($_SESSION['su'])):    # || $_SESSION['userid']==3)
                        $output .= "
                        <li><a class='change' href='{$rootdir}/download.php' title='Download'>
                        <i class='fas fa-download'></i></a></li>";
                    endif;*/
                    # fa-download, fa-arrow-circle-down, fa-arrow-circle-o-down, fa-arrow-down

                // aktiv: Markenansicht     < einzel_gruppe_Konto >
                case 2:
                    $output .= "
                        <li><a href='{$rootdir}/index' title='Einzelliste'>Einzelliste</a></li>
                        <li><a class='active'>Markenliste</a></li>";
                    break;

                // aktiv: Details     < detail_bearbeiten_Konto >
                case 3:
                    $output .= "
                        <li><a class='active'>".$main['name']."</a></li>";

                    // Bearbeiten
                    // für Admin und Heinz (id.3) anzeigen, (gelb markiert, class=change)
                    if (Auth::isCheckedIn()
                        && !empty($_SESSION['fileid']))
                    {
                        if ($_SESSION['userid'] == 3
                            || isset($_SESSION['su']))
                        {
                            /*$output .= "
                            <li><a class='change'>
                            <form method='POST' action='{$rootdir}/change.php' class='change'>
                            <input type='hidden' name='fid' value='".$_SESSION['fileid']."' />
                            <button class='lnk' title='Bearbeiten'>
                            <i class='change fas fa-edit'></i>Bearbeiten</button>
                            </form></a></li>";*/

                            $output .= "
                            <li><a class='change'
                                href='{$rootdir}/change.php?id=".$_SESSION['fileid']."'>
                                <i class='change fas fa-edit'></i>Bearbeiten&emsp;</a></li>";
                        };
                    };
                    break;

                // aktiv: Bearbeiten     < bearbeiten_beenden_Konto >
                // Seite wird nur bei Admin und Heinz (id.3) aufgerufen
                case 4:
                    /*$output .= "
                        <li><a class='active'>".$main['name']."</a></li>
                        <li><a class='change'>
                        <form method='POST' action='{$rootdir}/details.php' class='change'>
                        <input type='hidden' name='id' value='".$_SESSION['fileid']."' />
                        <button class='lnk' title='Beenden'><i class='change fas fa-edit'></i>Beenden&emsp;</button>
                        </form></a></li>";*/

                    $output .= "
                        <li><a class='active'>".$main['name']."</a></li>
                        <li><a class='change' href='".$stepout['site']."'>".
                            $stepout['name']."</a></li>";
                    break;


                // aktiv: Kontakt     < kontakt_Konto >
                case 13:
                    $output .= "
                        <li><a class='active'><i class='fas fa-envelope'></i>".$main['name']."</a></li>";
                break;

                // aktiv: Impressum     < impressum_Konto >
                case 6:
                    $output .= "
                        <li><a class='active'>".$main['name']."</a></li>";
                    break;

                // aktiv: About     < about_Konto >
                case 16:
                    $output .= "
                        <li><a class='active'>".$main['name']."</a></li>";
                    break;

                endswitch;

                // ... und Konto-Menü als letztes noch ranhängen    < (admin_) setting_logout >
                /*$output .= "
                    <li>
                    <div class='collapsible-menu'>
                    <input type='checkbox' id='menu'>
                    <label for='menu' title='Konto'><i class='fas fa-user-circle'></i></label>
                    <div class='menu-content'>
                    <ul>";*/

                // ggf Admin    <li><a href='{$rootdir}/account/admin'>Admin</a></li>";
                /*if (isset($_SESSION['su']) && $_SESSION['su'] === True)
                    $output .= "
                    <li><a href='{$rootdir}/account/admin' title='Admin'>
                    <i class='fas fa-user-plus'></i></a></li>";*/

                // Setting  <li><a href='{$rootdir}/account/settings' title='Konto'><span class='konto'></span></a></li>";  ... class='fas fa-cog'
                $output .= "
                    <li><a href='{$rootdir}/account/settings' title='Konto'>
                    <i class='fas fa-user-circle'></i></a></li>";

                // Logout   <li><a href='{$rootdir}/auth/logout' title='Abmelden'>Abmelden</a></li>";
                /*$output .= "
                    <li><a href='{$rootdir}/auth/logout' title='Abmelden'>
                    <i class='fas fa-sign-out-alt'></i></a></li>";*/

                #$output .= "</ul></div></div></li>";


                //TODO: das hier bitte als flyout-Menü
                /*  #fas fa-user-circle ... fas fa-user
                $output .= "
                <li><a href='{$rootdir}/account/settings.php' title='Konto / Abmelden'>
                <i class='fas fa-user-circle'></i></a></li>

                <li>
                <details style='Xcolor:#9d9d9d;'>
                    <summary><i class='fas fa-user'></i></summary>
                    <ul id='konto'>
                        <li><a href='{$rootdir}/account/settings.php'
                            style='Xcolor:#9d9d9d;'>Einstellungen</a></li>
                        <li><a href='{$rootdir}/auth/logout.php'
                            style='Xcolor:#9d9d9d;'>Abmelden</a></li>
                    </ul>
                </details>
                </li>

                <li>
                <button type='button' class='navbar-toggle collapsed'
                    data-toggle='collapse' data-target='#submenu'
                    aria-expanded='false' aria-controls='navbarX'>
                <span class='sr-only'>Sub-Menu</span>
                <a></a>
                </button>
                </li>

                <div id='submenu' class='navbar-collapse collapse'>
                    <ul class='nav navbar-nav navbar-right'>
                        <li><a href='{$rootdir}/account/settings.php'
                            style='Xcolor:#9d9d9d;'>Einstellungen</a></li>
                        <li><a href='{$rootdir}/auth/logout.php'
                            style='Xcolor:#9d9d9d;'>Abmelden</a></li>
                    </ul>
                </div>";
                */
            }


            // -- Konto-Bereich --
            else {

                // aktiv: Logout   < logout >
                if ($site_id === 14) {
                    $output .= "
                    <li><a class='active'><i class='fas fa-sign-out-alt'></i>Logout</a></a></li>";
                }
                else {

                    // aktiv: Einstellungen   < (admin_) setting_logout >
                    if ($site_id === 100) {
                        // wenn als Admin angemeldet, dann Admin-Menü zeigen
                        if (!empty($_SESSION['su'])
                            && (in_array(basename($_SERVER['PHP_SELF']), $acc_pages)))
                        {
                            $output .= "
                                <li><a href='{$rootdir}/account/admin' title='Admin'>
                                <i class='fas fa-user-plus'></i></a></li>";
                        };

                        // Setting-Menü
                        $output .= "
                        <li><a class='active'><i class='fas fa-user-circle'></i>Konto</a></li>";
                    }

                    // aktiv: Admin     < admin_setting_logout >
                    elseif ($site_id === 101) {
                        $output .= "
                            <li><a class='active'><i class='fas fa-user-plus'>
                                </i>Admin</a></a></li>
                            <li><a href='{$rootdir}/account/settings' title='Konto'>
                                <i class='fas fa-user-circle'></i></a></li>";
                    };

                    // Logout-Menü
                    $output .= "
                        <li><a href='{$rootdir}/auth/logout' title='Abmelden'>
                        <i class='fas fa-sign-out-alt'></i></a></li>";
                };
            };      # ende 'Konto-Bereich'

            $output .= "</ul></div>";  # ende -- navbar-collapse --


        };    # ende -- angemeldet --

        $output .= "</div>";  # -- container --
        $output .= "</nav>";


        echo $output;
    }
}


// EOF