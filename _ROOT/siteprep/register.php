<?php
namespace Dzg\SitePrep;
use Dzg\SiteData\Register as Database;
use Dzg\Tools\Tools;

require_once __DIR__.'/../sitedata/register.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Class Register
 */
class Register
{
    protected const MSG = [
        10 => "Die Registrierung funktioniert nur mit dem per Email zugesandten Link. <br>Überprüfe nochmal deinen Posteingang und den Spam-Ordner. Wiederhole ggf. die Registrierung. <br>",
        11 => "Der Registrierungs-Code fehlt. Überprüfe nochmal den Link in deiner Email.",
        12 => "<b>Manipulationsverdacht: </b><br>Es wurden ungültige Zeichen im Registrierungs-Code erkannt.",
        13 => "Der Registrierungs-Link ist nicht gültig. Wiederhole die Registrierung.",
        14 => "Der Registrierungs-Link ist nach 4 Wochen abgelaufen."
    ];

    protected static array $usr_data;
    protected static string $input_code;
    protected static string $error_msg;


    /**
     * Summary of dataPreparation
     */
    protected static function dataPreparation(): void
    {
        // Registrierungscode überprüfen
        //
        $usr_data = [];
        $error_msg = "";
        $input_code = "";

        # Seitenaufruf ohne Code
        if (!isset($_GET['code'])) {
            $error_msg = self::MSG[10];
        };

        if ($error_msg === "") {
            $input_code = htmlspecialchars(Tools::cleanInput($_GET['code']));

            # keinen Code übergeben
            if ($input_code === "") {
                $error_msg = self::MSG[11];
            }

            # verwendete Zeichen im Code nicht okay
            elseif (!preg_match('/^[a-zA-Z0-9]{1,100}$/', $input_code)) {
                $error_msg = self::MSG[12];
            };
        };

        # Code mit DB abgleichen
        if ($error_msg === "") {
            $usr_data = Database::getUser($input_code);

            # Code nicht in DB gefunden
            if (!$usr_data) {
                $error_msg = self::MSG[13];
            }

            # Zeitfenster (+1 Std. Karenz) überschritten,
            # veralteten Eintrag löschen
            elseif (($usr_data['pwcode_endtime'] + 3600*1) < time()) {
                Database::deleteOldEntry($usr_data['userid']);
                $error_msg = self::MSG[14].date(' d.m.Y', $usr_data['pwcode_endtime']);
            };
        };

        self::$usr_data   = $usr_data;
        self::$input_code = $input_code;
        self::$error_msg  = $error_msg;
    }
}


// EOF