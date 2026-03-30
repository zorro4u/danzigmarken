<?php
namespace Dzg\Tools;

require_once __DIR__.'/SiteConfig.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/CheckIP.php';
require_once __DIR__.'/Logger.php';
require_once __DIR__.'/Tools.php';


/***********************
 * Summary of Header
 * Webseiten-Header, Seiten-Navigation
 *
 * __public__
 * show()
 */
class Header extends SiteConfig
{
    private static string $rootdir;
    private static $main;
    private static $stepout;
    private static string $site_name;
    private static int $site_id;


    /***********************
     * den obersten Teil der Webseite inclusive Seiten-Navigation ausgeben
     */
    public static function show(?string $site_name=null)
    {
        self::antiflood();
        self::dataPreparation();
        self::loadHtmlHead();
        self::showNavigation();
    }



    /**
     * Zeit & Häufigkeit abfangen
     *
     * kein Schutz vor DDOS oder HTTP-Flood,
     * aber bisschen vor permanenter DB-Abfragerei
     * Was ist bei ständig wechselnder IP?
     */
    public static function antiflood()
    {

        // -4-
        // Zugriff speichern
        Logger::log();

        // -1-
        CheckIP::block_ai_bots_by_rdns();

        // -2-
        // manipulierten URL-Aufruf blockieren
        $ip  = CheckIP::getIP();
        $url = isset($_SERVER["REQUEST_URI"])
        ? $_SERVER["REQUEST_URI"]
        : '';
        if (str_contains($url, "%")) {
            $ipc = new CheckIP("clear");
            $ipc->under_suspicion($ip, true);
            $ipc = null;
        };

        // -3-
        // IP mit Blockliste abgleichen,
        // wenn mehrfach dann (Bereich) blocken
        // und wegleiten.
        CheckIP::antiflood();

    }


    /**
     * not in use
     */
    public static function active(array $site_arr): string
    {
        // ist Seite gleich der aktuellen Seiten, css-Klasse 'active' setzen
        $class = (strpos($_SERVER['PHP_SELF'], basename($site_arr['site'])) !== False)
        ? 'class="active" style="color:#ccc;">'
        : $class = 'href="'.$site_arr['site'].'">';

        return $class;
    }


    /***********************
     * Summary of dataPreparation
     */
    private static function dataPreparation()
    {
        Auth::isCheckedIn();

        // Site-ID wird in Starter.php gesetzt
        $site_id = $_SESSION['siteid'];

        // Stammverzeichnis festlegen, bei Aufruf aus Unterverzeichnis (wie auth/login.php)
        // sonst Probleme zB. mit css Aufruf
        // wird in auth.func.php gesetzt
        self::$rootdir = $rootdir = $_SESSION['rootdir'];


        // Startseite festlegen, $_SESSION['main'], siehe auch: list-func
        $main_pages = self::MAIN_PAGES;
        if (in_array($site_id, array_keys($main_pages))) {
            $main_page = $rootdir.'/'.$main_pages[$site_id];
        }
        elseif (isset($_SESSION['main'])) {
            $main_page = $_SESSION['main'];
        }
        else {
            $main_page = $rootdir.'/'.$main_pages[1];
        };

        $_SESSION['main'] = $main_page;

        $main    = ['site' => $main_page, 'name' => 'Übersicht'];
        $stepout = ['site' => $main_page, 'name' => '<i class="fas fa-home"></i>Übersicht'];


        if (empty($_SESSION['lastsite'])) $_SESSION['lastsite'] = $main_page;
        if (empty($_SESSION['fileid'])) $_SESSION['fileid'] = 0;
        if (empty($_SESSION['prev']))   $_SESSION['prev']   = -1;


        switch ($site_id):

            // index.php
            case 1:
                $main = ['site' => $main_page, 'name' => 'Einzelliste'];
                break;

            // index2.php
            case 2:
                $main = ['site' => $main_page, 'name' => 'Markenliste'];
                break;

            // details.php
            case 3:
                $main = [
                    'site' => $rootdir.'/details.php?id='.$_SESSION['fileid'],
                    'name' => 'Detailansicht'
                ];
                $stepout['site'] = $main_page.'#'.$_SESSION['prev'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // change.php
            case 4:
                $main = ['site' => $rootdir.'/change.php', 'name' => 'Bearbeiten'];
                $stepout = [
                    'site' => $rootdir.'/details.php?id='.$_SESSION['fileid'],
                    'name' => '<i class="fas fa-circle-left"></i>Beenden'
                ];
                $stepout_X = [
                    'site' => $_SESSION['lastsite'],
                    'name' => '<i class="fas fa-circle-left"></i>Beenden'
                ];
                break;

            // upload.php
            case 5:
                $main = ['site' => $rootdir.'/upload.php', 'name' => 'Upload'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // impressum.php
            case 6:
                Tools::lastSite(["index", "index2", "details", "settings", "admin"]);
                $main = ['site' => $rootdir.'/impressum', 'name' => 'Impressum'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // login.php
            case 7:
                Tools::lastSite();
                $main = ['site' => $rootdir.'/auth/login', 'name' => 'Anmelden'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // pwforget.php
            case 8:
                $main = ['site' => $rootdir.'/auth/pwforget.php', 'name' => 'Passwort vergessen'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // pwreset.php
            case 9:
                $main = ['site' => $rootdir.'/auth/pwreset.php', 'name' => 'Passwort reset'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // registerinfo.php
            case 10:
                $main = ['site' => $rootdir.'/auth/registerinfo.php', 'name' => 'Registrieren'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // register.php
            case 11:
                $main = ['site' => $rootdir.'/auth/register.php', 'name' => 'Registrieren'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // activate.php
            case 12:
                $main = ['site' => $rootdir.'/auth/activate.php', 'name' => 'Aktivieren'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // kontakt.php
            case 13:
                Tools::lastSite(["index", "index2", "details", "settings", "admin"]);
                $main = ['site' => $rootdir.'/kontakt/kontakt', 'name' => 'Kontakt'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // logout.php
            case 14:
                Tools::lastSite();
                $main = ['site' => $rootdir.'/auth/logout', 'name' => 'Abmelden'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // download.php
            case 15:
                $main = ['site' => $rootdir.'/download.php', 'name' => 'Download'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // about.php
            case 16:
                Tools::lastSite(["index", "index2", "details", "settings", "admin"]);
                $main = ['site' => $rootdir.'/about.php', 'name' => 'About'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // setting.php
            case 100:
                Tools::lastSite(["index", "index2", "details"]);
                #$main = ['site' => $rootdir.'/download.php', 'name' => 'Download'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // admin.php
            case 101:
                Tools::lastSite(["index", "index2", "details", "settings", "admin"]);
                #$main = ['site' => $rootdir.'/download.php', 'name' => 'Download'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            default:
                Tools::lastSite();
                $stepout['site'] = $_SESSION['lastsite'];

        endswitch;

        self::$main = $main;
        self::$stepout = $stepout;
        self::$site_id = $site_id;
    }


    /***********************
     * auf der Webseite die oberste, nicht sichtbare
     * Html <head> Section mit den meta-Anweisungen ausgeben
     */
    public static function loadHtmlHead()
    {
        # https://www.danzig.org/
        # https://arge.danzig.org/

        // die META-Anweisungen der Webseite aus dem Site-Array extrahieren
        foreach(self::PAGE as $k=>$v){
            if($v['site_id'] === self::$site_id)
                $meta = $v['meta'];
        }
        # falls hier irgendwas mit der ID-Zuweisung schief lief
        $meta ??= self::PAGE['dummy']['meta'];

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


    /***********************
     * Webseiten-Navigation ausgeben
     */
    private static function showNavigation()
    {
        $rootdir = self::$rootdir;
        $main = self::$main;
        $main_pages = self::MAIN_PAGES;
        $stepout = self::$stepout;
        $acc_pages = self::ACC_PAGES;
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
        if (!Auth::isCheckedIn())
        {
            $output .= "
                <div id='navbar' class='navbar-collapse collapse'>
                <ul class='nav navbar-nav navbar-right'>";

            // aktiv: Login     < login >
            #<li><a class='active'><span class='login_self'><i class='fas fa-sign-in-alt'>
            if ($site_id === 7) {
                $output .= "
                </i></span></a></li>
                <li><a class='active'><i class='fas fa-sign-in-alt'></i>Login</a></a></li>";

            } else {
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


        // -- Menü: angemeldet --
        } else {

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
                    if (Auth::isCheckedIn() && !empty($_SESSION['fileid'])) {
                        if ($_SESSION['userid']==3 || isset($_SESSION['su'])) {
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
                        }
                    }
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


            // -- Konto-Bereich --
            } else {

                // aktiv: Logout   < logout >
                if ($site_id === 14) {
                    $output .= "
                    <li><a class='active'><i class='fas fa-sign-out-alt'></i>Logout</a></a></li>";

                } else {

                    // aktiv: Einstellungen   < (admin_) setting_logout >
                    if ($site_id === 100) {
                        // wenn als Admin angemeldet, dann Admin-Menü zeigen
                        if (!empty($_SESSION['su']) &&
                            (in_array(basename($_SERVER['PHP_SELF']), $acc_pages)))
                        {
                            $output .= "
                                <li><a href='{$rootdir}/account/admin' title='Admin'>
                                <i class='fas fa-user-plus'></i></a></li>";
                        };
                        // Setting-Menü
                        $output .= "
                        <li><a class='active'><i class='fas fa-user-circle'></i>Konto</a></li>";

                    // aktiv: Admin     < admin_setting_logout >
                    } elseif ($site_id === 101) {
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