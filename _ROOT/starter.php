<?php
namespace Dzg;

session_start();
date_default_timezone_set('Europe/Berlin');
#error_reporting(E_ERROR | E_PARSE);

require_once __DIR__.'/siteprep/siteconfig.php';


/**
 * zentraler Startpunkt für die Seitenausgabe
 */
class Starter
{
    /**
     * startet die entspr. Seite
     */
    public static function show(?string $site = null): void
    {
        // lädt die entspr. Klassendatei, wenn vorhanden
        !($classfile = self::loadSiteConfig($site))
            ?: require_once __DIR__.'/sites/'.ucfirst($classfile);

        // startet die Seitenklasse über den zugehörigen Dateinamen
        switch(strtolower(basename($classfile, '.php'))):

            case "admin":
                Admin::show();
                break;

            case "settings":
                Settings::show();
                break;

            case "table":
                Table::show();
                break;

            case "details":
                Details::show();
                break;

            case "change":
                Change::show();
                break;

            case "login":
                Login::show();
                break;

            case "logout":
                Logout::show();
                break;

            case "pwforget":
                PWforget::show();
                break;

            case "pwreset":
                PWreset::show();
                break;

            case "registerinfo":
                RegisterInfo::show();
                break;

            case "register":
                Register::show();
                break;

            case "activate":
                Activate::show();
                break;

            case "contact":
                Contact::show();
                break;

            case "upload":
                Upload::show();
                break;

            case "showlog":
                ShowLog::show();
                break;

            case "printview":
                Printview::show();
                break;

            case "impressum":
                Impressum::show();
                break;

            case "about":
                About::show();
                break;


            case "empty":
                Dummy::show("no page founded...");
                break;

            case "download":
            default:
                exit("exit: no page founded...");

        endswitch;
    }


    /**
     * setzt anhand der Startdatei globale Werte,
     * die aus der SiteConfig kommen.
     *
     * ggf. Seiten-Übergabe als Parameter
     * in Form: 'name.extension'
     */
    private static function loadSiteConfig(?string $site = null): string
    {
        # ??= steht für: if(!isset(x)) x=y else x=x;             - existiert nicht
        # ?:  steht für: if(isset(x) && empty(x)) z=y else z=x;  - existiert, aber leer

        $site ??= basename($_SERVER['PHP_SELF']);
        if (!pathinfo($site, PATHINFO_EXTENSION)) {
            $site .= '.php';
        };
        $page = SiteConfig::PAGE[$site] ?? SiteConfig::PAGE['dummy'];

        // globaler Wert für weiteren Seitenaufbau
        $_SESSION['siteid'] = $id = $page['site_id'];

        // Tabelle: Einzel- oder Gruppenmodus?
        $_SESSION['idx2'] = ($id === 2) ? true : false;

        return $page['class_file'];
    }
}


// EOF