<?php
namespace Dzg\Cls;

require_once __DIR__.'/Auth.php';
use Dzg\Cls\Auth;

/***********************
 * Summary of Header
 * Webseiten-Header, Seiten-Navigation
 *
 * __public__
 * show()
 */
class Header
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private const HEAD_TEMPLATE = "/assets/inc/html-head-meta.php";

    private const
        PAGE_SETUP = [
            'cache_no' => "must-revalidate, no-store", #, no-cache, max-age=0, private",
            'cache_0'  => "no-cache, max-age=0, must-revalidate, private",
            'cache_1h' => "max-age=3600, stale-if-error=86400, private",    # 1h+1d
            'cache_1w' => "max-age=604800, stale-if-error=86400, private",  # 7+1Tage

            'expires_0'  => "0",                  # no-cache
            'expires_1h' => "3600",               # 1 Std Cache

            'robots_index'  => "index, nofollow,",      # indiziert
            'robots_no'     => "noindex, nofollow,",
            'robots_follow' => "noindex, follow,",      # Seitenlinks folgen

            'google0' => "",
            'google1' => "nopagereadaloud",

            'canonical0' => "",
            'canonical1' => '<link rel="canonical" href="https://www.danzigmarken.de/index">',
            'canonical2' => '<link rel="canonical" href="https://www.danzigmarken.de/details.php?id=10">',
        ];
    public const
        PAGE = [
            'index.php' => [
                'id' => 1,
                'meta' => [
                    'title' => "Briefmarken und Paketkarten der Stadt Danzig (1889-1920-1939-1945)",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_index'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical1'],
                ],
            ],
            'index2.php' => [
                'id' => 2,
                'meta' => [
                    'title' => "Briefmarken und Paketkarten der Stadt Danzig (1889-1920-1939-1945)",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_index'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical1'],
                ],
            ],
            'details.php' => [
                'id' => 3,
                'meta' => [
                    'title' => "Detailansicht - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google0'],
                    'canonical' => self::PAGE_SETUP['canonical2'],
                ],
            ],
            'change.php' => [
                'id' => 4,
                'meta' => [
                    'title' => "Bearbeiten - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_1h'],
                    'expires' => self::PAGE_SETUP['expires_1h'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical2'],
                ],
            ],
            'upload.php' => [
                'id' => 5,
                'meta' => [
                    'title' => "Upload - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_1w'],
                    'expires' => self::PAGE_SETUP['expires_1h'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'impressum.php' => [
                'id' => 6,
                'meta' => [
                    'title' => "Impressum - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_1w'],
                    'expires' => self::PAGE_SETUP['expires_1h'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'login.php' => [
                'id' => 7,
                'meta' => [
                    'title' => "Anmelden - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google0'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'pwforget.php' => [
                'id' => 8,
                'meta' => [
                    'title' => "PW-Vergessen - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'pwreset.php' => [
                'id' => 9,
                'meta' => [
                    'title' => "PW-Reset - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google0'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'register-info.php' => [
                'id' => 10,
                'meta' => [
                    'title' => "Registrieren-Info - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'register.php' => [
                'id' => 11,
                'meta' => [
                    'title' => "Registrieren - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google0'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'activate.php' => [
                'id' => 12,
                'meta' => [
                    'title' => "Aktivieren - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'kontakt.php' => [
                'id' => 13,
                'meta' => [
                    'title' => "Kontakt - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_1w'],
                    'expires' => self::PAGE_SETUP['expires_1h'],
                    'robots' => self::PAGE_SETUP['robots_follow'],
                    'google' => self::PAGE_SETUP['google0'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'logout.php' => [
                'id' => 14,
                'meta' => [
                    'title' => "Abmelden - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_1w'],
                    'expires' => self::PAGE_SETUP['expires_1h'],
                    'robots' => self::PAGE_SETUP['robots_follow'],
                    'google' => self::PAGE_SETUP['google0'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'download.php' => [
                'id' => 15,
                'meta' => [
                    'title' => "Download - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_1h'],
                    'expires' => self::PAGE_SETUP['expires_1h'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'about.php' => [
                'id' => 16,
                'meta' => [
                    'title' => "About - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_1w'],
                    'expires' => self::PAGE_SETUP['expires_1h'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'settings.php' => [
                'id' => 100,
                'meta' => [
                    'title' => "Konto - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'admin.php' => [
                'id' => 101,
                'meta' => [
                    'title' => "erweiterte Einstellungen - danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_no'],
                    'expires' => self::PAGE_SETUP['expires_0'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
            'dummy' => [
                'id' => 500,
                'meta' => [
                    'title' => "danzigmarken.de",
                    'cache' => self::PAGE_SETUP['cache_1h'],
                    'expires' => self::PAGE_SETUP['expires_1h'],
                    'robots' => self::PAGE_SETUP['robots_no'],
                    'google' => self::PAGE_SETUP['google1'],
                    'canonical' => self::PAGE_SETUP['canonical0'],
                ],
            ],
        ];


    private const ACC_PAGES = ['login.php','logout.php','admin.php','settings.php'];
    private const MAIN_PAGES = [1 => 'index.php', 2 => 'index2.php'];

    private static string $rootdir;
    private static $debug;
    private static $main;
    private static $stepout;
    private static string $site_name;
    private static int $siteid;


    /***********************
     * den obersten Teil der Webseite inclusive Seiten-Navigation ausgeben
     */
    public static function show(string $site_name='')
    {
        #self::$site_name = $site_name;

        self::data_preparation();
        self::html_meta_load();
        self::navigation_show();
    }


    /***********************
     * auf der Webseite die oberste, nicht sichtbare
     * Html <head> Section mit den meta-Anweisungen ausgeben
     */
    public static function html_meta_load()
    {
        # https://www.danzig.org/
        # https://arge.danzig.org/


        // die speziellen META-Anweisungen der Webseite laden
        $meta = (!empty(self::$site_name))
            ? self::PAGE[self::$site_name]['meta']
            : self::PAGE['dummy']['meta'];

        // und den Template-Variable zuordnen
        $title = $meta['title'];
        $cache = $meta['cache'];
        $expires = $meta['expires'];
        $robots = $meta['robots'];
        $google = $meta['google'];
        $canonical = $meta['canonical'];

        // lädt das <head> Template
        require $_SERVER['DOCUMENT_ROOT'].self::HEAD_TEMPLATE;
    }


    public static function get_siteid(string $site_name): int
    {
        if (empty(self::$siteid)) self::set_siteid($site_name);
        return self::$siteid;
    }
    public static function set_siteid(string $site_name)
    {
        # TODO
        #$site_name = basename($_SERVER['PHP_SELF']);

        $page = self::PAGE;
        $id = (!empty($page[$site_name]))
            ? $page[$site_name]['id']
            : $page['dummy']['id'];

        $_SESSION['siteid'] = self::$siteid = $id;

        if (empty($_SESSION['idx2']) && $id === 2) {
            $_SESSION['idx2'] = True;
        } elseif (!empty($_SESSION['idx2']) && $id === 1) {
            $_SESSION['idx2'] = False;
        }
    }


    /***********************
     * Summary of active
     * @param mixed $site_arr
     * @return string
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
     * Summary of data_preparation
     */
    private static function data_preparation()
    {
        Auth::is_checked_in();

        self::$site_name = basename($_SERVER['PHP_SELF']);
        $siteid = self::get_siteid(self::$site_name);

        // Stammverzeichnis festlegen, bei Aufruf aus Unterverzeichnis (wie auth/login.php)
        // sonst Probleme zB. mit css Aufruf
        // wird in auth.func.php gesetzt
        self::$rootdir = $rootdir = $_SESSION['rootdir'];

        // spezielle Menü-Formatierung debug-Mode und für Admin
        if (isset($_SESSION['su']) && $_SESSION['su'] === 1) {
            $color = " <span style='color:red;'>*</span>";
        } elseif (isset($_SESSION['loggedin'])) {
            $color = " <span style='color:yellow;'>*</span>";
        } else {
            $color = " *";
        }

        $debug = (strpos($rootdir, "/_prepare") !== False)
            ? $color : "";


        // Startseite festlegen, $_SESSION['main'], siehe auch: list-func
        $main_pages = self::MAIN_PAGES;
        if (in_array($siteid, array_keys($main_pages))) {
            $main_page = $rootdir.'/'.$main_pages[$siteid];
        } elseif (isset($_SESSION['main'])) {
            $main_page = $_SESSION['main'];
        } else {
            $main_page = $rootdir.'/'.$main_pages[1];
        }
        $_SESSION['main'] = $main_page;

        $main = ['site' => $main_page, 'name' => 'Übersicht'];
        $stepout = ['site' => $main_page, 'name' => '<i class="fas fa-home"></i>Übersicht'];


        if (empty($_SESSION['lastsite'])) $_SESSION['lastsite'] = $main_page;
        if (empty($_SESSION['fileid'])) $_SESSION['fileid'] = 0;
        if (empty($_SESSION['prev']))   $_SESSION['prev']   = -1;


        switch ($siteid):

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
                Tools::lastsite(["index", "index2", "details", "settings", "admin"]);
                $main = ['site' => $rootdir.'/impressum', 'name' => 'Impressum'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // login.php
            case 7:
                Tools::lastsite();
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

            // register-info.php
            case 10:
                $main = ['site' => $rootdir.'/auth/register-info.php', 'name' => 'Registrieren'];
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
                Tools::lastsite(["index", "index2", "details", "settings", "admin"]);
                $main = ['site' => $rootdir.'/kontakt/kontakt', 'name' => 'Kontakt'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // logout.php
            case 14:
                Tools::lastsite();
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
                Tools::lastsite(["index", "index2", "details", "settings", "admin"]);
                $main = ['site' => $rootdir.'/about.php', 'name' => 'About'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // setting.php
            case 100:
                Tools::lastsite(["index", "index2", "details"]);
                #$main = ['site' => $rootdir.'/download.php', 'name' => 'Download'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // admin.php
            case 101:
                Tools::lastsite(["index", "index2", "details", "settings", "admin"]);
                #$main = ['site' => $rootdir.'/download.php', 'name' => 'Download'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            default:
                Tools::lastsite();
                $stepout['site'] = $_SESSION['lastsite'];

        endswitch;

        self::$rootdir = $rootdir;
        self::$debug = $debug;
        self::$main = $main;
        self::$stepout = $stepout;
    }


    /***********************
     * Webseiten-Navigation ausgeben
     */
    private static function navigation_show()
    {
        $rootdir = self::$rootdir;
        $debug = self::$debug;
        $main = self::$main;
        $main_pages = self::MAIN_PAGES;
        $stepout = self::$stepout;
        $acc_pages = self::ACC_PAGES;
        $siteid = self::$siteid;

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
        }

        // links den Titel immer anzeigen
        $output .= "
            <a class='navbar-brand' style='color:#fff;' {$href}>
                Danzig Sammlung{$debug}{$symb}</a>
            </div>";    # ende -- navbar-header --


        // -- Menü: nicht angemeldet --
        if (!Auth::is_checked_in())
        {
            $output .= "
                <div id='navbar' class='navbar-collapse collapse'>
                <ul class='nav navbar-nav navbar-right'>";

            // aktiv: Login     < login >
            #<li><a class='active'><span class='login_self'><i class='fas fa-sign-in-alt'>
            if ($siteid === 7) {
                $output .= "
                </i></span></a></li>
                <li><a class='active'><i class='fas fa-sign-in-alt'></i>Login</a></a></li>";

            } else {
                switch ($siteid):

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
            }

            $output .= "</ul></div>";  # ende -- navbar-collapse --


        // -- Menü: angemeldet --
        } else {

            $output .= "
            <div id='navbar' class='navbar-collapse collapse'>
            <ul class='nav navbar-nav navbar-right'>";

            // -- nicht im Konto-Bereich --
            if (!in_array(basename($_SERVER['PHP_SELF']), $acc_pages)) {
                switch ($siteid):

                // aktiv: Einzelansicht     < einzel_gruppe_Konto >
                case 1:
                    $output .= "
                        <li><a class='active'>Einzelliste</a></li>
                        <li><a href='{$rootdir}/index2' title='Gruppenliste'>Markenliste</a></li>";
                        break;

                    /*
                    if (Auth::is_checked_in() && isset($_SESSION['su'])):    # || $_SESSION['userid']==3)
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
                    if (Auth::is_checked_in() && !empty($_SESSION['fileid'])) {
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
                if ($siteid === 14) {
                    $output .= "
                    <li><a class='active'><i class='fas fa-sign-out-alt'></i>Logout</a></a></li>";

                } else {

                    // aktiv: Einstellungen   < (admin_) setting_logout >
                    if ($siteid === 100) {
                        // wenn als Admin angemeldet, dann Admin-Menü zeigen
                        if (!empty($_SESSION['su']) &&
                            (in_array(basename($_SERVER['PHP_SELF']), $acc_pages)))
                        {
                            $output .= "
                                <li><a href='{$rootdir}/account/admin' title='Admin'>
                                <i class='fas fa-user-plus'></i></a></li>";
                        }
                        // Setting-Menü
                        $output .= "
                        <li><a class='active'><i class='fas fa-user-circle'></i>Konto</a></li>";

                    // aktiv: Admin     < admin_setting_logout >
                    } elseif ($siteid === 101) {
                        $output .= "
                            <li><a class='active'><i class='fas fa-user-plus'>
                                </i>Admin</a></a></li>
                            <li><a href='{$rootdir}/account/settings' title='Konto'>
                                <i class='fas fa-user-circle'></i></a></li>";
                    }

                    // Logout-Menü
                    $output .= "
                        <li><a href='{$rootdir}/auth/logout' title='Abmelden'>
                        <i class='fas fa-sign-out-alt'></i></a></li>";
                }
            }      # ende 'Konto-Bereich'

            $output .= "</ul></div>";  # ende -- navbar-collapse --


        }    # ende -- angemeldet --

        $output .= "</div>";  # -- container --
        $output .= "</nav>";


        echo $output;
    }

}