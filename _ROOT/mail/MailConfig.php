<?php
namespace Dzg;

date_default_timezone_set('Europe/Berlin');

MailConfig::load();


/***********************
 * Summary of MailConfig
 */
class MailConfig
{

    public static $smtp;
    public static $smtp1;
    public static $smtp2;
    public static $cfg;
    public static $danke;
    public static $datenschutzerklaerung;
    public static $maximale_aufrufe;
    public static $Passwort_fuer_Login_Bereich;
    public static $zeichenlaenge_firma;
    public static $zeichenlaenge_vorname;
    public static $zeichenlaenge_name;
    public static $zeichenlaenge_email;
    public static $zeichenlaenge_telefon;
    public static $zeichenlaenge_betreff;
    public static $mail_logfile;


    public static function load()
    {
        require "mail_setup.php";
/*
        global
        $smtp,
        $smtp1,
        $smtp2,
        $cfg,
        $danke,
        $datenschutzerklaerung,
        $Maximale_Aufrufe,
        $Passwort_fuer_Login_Bereich,
        $zeichenlaenge_firma,
        $zeichenlaenge_vorname,
        $zeichenlaenge_name,
        $zeichenlaenge_email,
        $zeichenlaenge_telefon,
        $zeichenlaenge_betreff;
*/
        self::$smtp = $smtp;
        self::$smtp1 = $smtp1;
        self::$smtp2 = $smtp2;
        self::$cfg = $cfg;
        self::$danke = $danke;
        self::$datenschutzerklaerung = $datenschutzerklaerung;
        self::$maximale_aufrufe = $Maximale_Aufrufe;
        self::$Passwort_fuer_Login_Bereich = $Passwort_fuer_Login_Bereich;
        self::$zeichenlaenge_firma = $zeichenlaenge_firma;
        self::$zeichenlaenge_vorname = $zeichenlaenge_vorname;
        self::$zeichenlaenge_name = $zeichenlaenge_name;
        self::$zeichenlaenge_email = $zeichenlaenge_email;
        self::$zeichenlaenge_telefon = $zeichenlaenge_telefon;
        self::$zeichenlaenge_betreff = $zeichenlaenge_betreff;
        self::$mail_logfile = $mail_logfile;

    }
}