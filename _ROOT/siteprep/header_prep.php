<?php
namespace Dzg\SitePrep;
use Dzg\SitePrep\SiteConfig;
use Dzg\Tools\{Auth, Tools, CheckIP, Logger};

require_once __DIR__.'/siteconfig.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';
require_once __DIR__.'/../tools/checkip.php';
require_once __DIR__.'/../tools/logger.php';


/**
 * Summary of Header
 * Webseiten-Header, Seiten-Navigation
 */
class HeaderPrep extends SiteConfig
{
    protected static string $rootdir;
    protected static array $main;
    protected static array $stepout;
    protected static int $site_id;
    protected static bool $ip_denied = false;

    // getter
    public static function site_id() {return self::$site_id;}
    public static function ip_denied() {return self::$ip_denied;}


    /**
     * Zeit & Häufigkeit abfangen
     *
     * kein Schutz vor DDOS oder HTTP-Flood,
     * aber bisschen vor permanenter DB-Abfragerei
     * Was ist bei ständig wechselnder IP?
     */
    public static function antiflood(): void
    {

        // -4-
        // Zugriff speichern
        Logger::log();

        // -1-
        CheckIP::block_ai_bots_by_rdns();

        // -2-
        // manipulierten URL-Aufruf blockieren
        $ip  = CheckIP::getIP();
        $url = $_SERVER["REQUEST_URI"] ?? '';
        if (str_contains($url, "%")
            || str_contains($url, "//"))
        {
            $ipc = new CheckIP("clear");
            $ipc->under_suspicion($ip, true);
            $ipc = null;
        };

        // -3-
        // IP mit Blockliste abgleichen,
        // wenn mehrfach dann (Bereich) blocken
        // und wegleiten.
        self::$ip_denied = CheckIP::antiflood();
    }


    /**
     * not in use
     */
    protected static function active(array $site_arr): string
    {
        // ist Seite gleich der aktuellen Seiten, css-Klasse 'active' setzen
        $class = (strpos($_SERVER['PHP_SELF'], basename($site_arr['site'])) !== False)
        ? 'class="active" style="color:#ccc;">'
        : $class = 'href="'.$site_arr['site'].'">';

        return $class;
    }


    /**
     * Summary of dataPreparation
     */
    protected static function dataPreparation(): void
    {
        Auth::isCheckedIn();

        // Site-ID wird in starter.php gesetzt
        $site_id = $_SESSION['siteid'] ?? 404;

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
                Tools::lastSite();
                $main = ['site' => $main_page, 'name' => 'Einzelliste'];
                break;

            // index2.php
            case 2:
                Tools::lastSite();
                $main = ['site' => $main_page, 'name' => 'Markenliste'];
                break;

            // details.php
            case 3:
                Tools::lastSite();
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
                Tools::lastSite(['settings', 'admin']);
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
                Tools::lastSite(['login']);
                $main = ['site' => $rootdir.'/auth/pwforget.php', 'name' => 'Passwort vergessen'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // pwreset.php
            case 9:
                Tools::lastSite(['login']);
                $main = ['site' => $rootdir.'/auth/pwreset.php', 'name' => 'Passwort reset'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // registerinfo.php
            case 10:
                Tools::lastSite(['login']);
                $main = ['site' => $rootdir.'/auth/registerinfo.php', 'name' => 'Registrieren'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // register.php
            case 11:
                Tools::lastSite(['login', 'registerinfo']);
                $main = ['site' => $rootdir.'/auth/register.php', 'name' => 'Registrieren'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // activate.php
            case 12:
                Tools::lastSite(['login']);
                $main = ['site' => $rootdir.'/auth/activate.php', 'name' => 'Aktivieren'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // contact.php
            case 13:
                Tools::lastSite(['about', 'settings', 'admin']);
                $main = ['site' => $rootdir.'/contact/contact', 'name' => 'Kontakt'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // logout.php
            case 14:
                Tools::lastSite(['login', 'email', 'settings', 'admin']);
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
                Tools::lastSite(['login', 'email', 'settings', 'admin']);
                $main = ['site' => $rootdir.'/about.php', 'name' => 'About'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // setting.php
            case 100:
                Tools::lastSite();
                #$main = ['site' => $rootdir.'/download.php', 'name' => 'Download'];
                $stepout['site'] = $_SESSION['lastsite'];
                break;

            // admin.php
            case 101:
                Tools::lastSite(['settings', 'admin']);
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
}


// EOF