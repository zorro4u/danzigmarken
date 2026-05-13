<?php
namespace Dzg;
use Dzg\Tools\Tools;

require_once __DIR__.'/../sitedata/register.php';
require_once __DIR__.'/../sitemsg/register.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Class Register
 */
class RegisterPrep
{
    protected const MSG = RegisterMsg::MSG;

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
            $error_msg = self::MSG[110];
        };

        if ($error_msg === "") {
            $input_code = htmlspecialchars(Tools::cleanInput($_GET['code']));

            # keinen Code übergeben
            if ($input_code === "") {
                $error_msg = self::MSG[111];
            }

            # verwendete Zeichen im Code nicht okay
            elseif (!preg_match('/^[a-zA-Z0-9]{1,100}$/', $input_code)) {
                $error_msg = self::MSG[112];
            };
        };

        # Code mit DB abgleichen
        if ($error_msg === "") {
            $usr_data = RegisterData::getUser($input_code);

            # Code nicht in DB gefunden
            if (!$usr_data) {
                $error_msg = self::MSG[113];
            }

            # Zeitfenster (+1 Std. Karenz) überschritten,
            # veralteten Eintrag löschen
            elseif (($usr_data['pwcode_endtime'] + 3600*1) < time()) {
                RegisterData::deleteOldEntry($usr_data['userid']);
                $error_msg = self::MSG[114].date(' d.m.Y', $usr_data['pwcode_endtime']);
            };
        };

        self::$usr_data   = $usr_data;
        self::$input_code = $input_code;
        self::$error_msg  = $error_msg;
    }
}


// EOF