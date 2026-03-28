<?php
namespace Dzg\Mail;

date_default_timezone_set('Europe/Berlin');


// Bei Aufruf der Datei (über include) wird hier die mail_setup.php Datei geladen
// und die Werte in die Klasse zum Abruf übertragen
MailConfig::load();


/***********************
 * Summary of MailConfig
 */
class MailConfig
{
    public static $smtp;
    public static $cfg;
    public static $danke;
    public static $datenschutzerklaerung;
    public static $zeichenlaenge;
    public static $mail_logfile;

    /**
     * Setup Datei laden und Werte übernehmen
     */
    public static function load()
    {
        require_once "mail_setup.php";

        self::$smtp = $smtp;
        self::$cfg  = $cfg;
        self::$zeichenlaenge = $zeichenlaenge;
        self::$datenschutzerklaerung = $datenschutzerklaerung;
        self::$danke = $danke;
        self::$mail_logfile = $mail_logfile;
    }
}