<?php
namespace Dzg;
use Dzg\Sites;
use Dzg\SitePrep\SiteConfig as Init;

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__.'/siteprep/siteconfig.php';


/**
 * zentraler Startpunkt für Seitenausgabe
 */
class Starter
{
    /**
     * setzt anhand der Startdatei globale Werte,
     * die aus der SiteConfig kommen.
     *
     * ggf. Seiten-Übergabe als Parameter
     * in Form: 'name.extension'
     */
    public static function loadSiteConfig(?string $site=null)
    {
        # ??= steht für: if(!isset(x)) x=y else x=x;             - existiert nicht
        # ?:  steht für: if(isset(x) && empty(x)) z=y else z=x;  - existiert, aber leer

        $site ??= basename($_SERVER['PHP_SELF']);
        if (!pathinfo($site, PATHINFO_EXTENSION)) {
            $site .= '.php';
        }
        $page = Init::PAGE[$site] ?? Init::PAGE['dummy'];

        // globaler Wert für weiteren Seitenaufbau
        $_SESSION['siteid'] = $id = $page['site_id'];

        // Tabelle: Einzel- oder Gruppenmodus?
        $_SESSION['idx2'] = ($id === 2) ? true : false;

        return $page['class_file'];
    }


    /**
     * startet die entspr. Seite
     */
    public static function show($site=null)
    {
        // lädt die entspr. Klassendatei, wenn vorhanden
        !($classfile = self::loadSiteConfig($site))
            ?: require_once __DIR__."/sites/".ucfirst($classfile);

        // startet die Seitenklasse über den zugehörigen Dateinamen
        switch(strtolower(basename($classfile, '.php'))):

            case "admin":
                Sites\Admin::show();
                break;

            case "settings":
                Sites\Settings::show();
                break;

            case "table":
                Sites\Table::show();
                break;

            case "details":
                Sites\Details::show();
                break;

            case "change":
                Sites\Change::show();
                break;

            case "login":
                Sites\Login::show();
                break;

            case "logout":
                Sites\Logout::show();
                break;

            case "pwforget":
                Sites\PWforget::show();
                break;

            case "pwreset":
                Sites\PWreset::show();
                break;

            case "registerinfo":
                Sites\RegisterInfo::show();
                break;

            case "register":
                Sites\Register::show();
                break;

            case "activate":
                Sites\Activate::show();
                break;

            case "contact":
                Sites\Contact::show();
                break;

            case "upload":
                Sites\Upload::show();
                break;

            case "showlog":
                Sites\ShowLog::show();
                break;

            case "printview":
                Sites\Printview::show();
                break;

            case "impressum":
                Sites\Impressum::show();
                break;

            case "about":
                Sites\About::show();
                break;


            case "empty":
                Sites\Dummy::show("no website found... <br>");
                break;

            case "download":
            default:
                exit("exit: no website found... <br>");

        endswitch;
    }
}

// EOF