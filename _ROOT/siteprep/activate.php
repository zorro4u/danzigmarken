<?php
namespace Dzg;
use Dzg\Tools\Tools;

session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/../sitedata/activate.php';
require_once __DIR__.'/../sitemsg/activate.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Class Activate
 */
class ActivatePrep
{
    protected const MSG = ActivateMsg::MSG;

    protected static bool $show_form;
    protected static string $status_message;
    protected static array $usr_data;


    /**
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
        $msg = self::MSG;
        $input_code  = "";
        $success_msg = "";
        $error_msg = "";
        $usr_data  = [];

        // Übergabewert auf Plausibilität prüfen
        //
        if (isset($_GET['code'])) {
            $input_code = htmlspecialchars(Tools::cleanInput($_GET['code']));

            # Code prüfen (nur alphanumerisch)
            if ($input_code
                && preg_match('/^[a-zA-Z0-9]+$/', $input_code) == 0)
            {
                $error_msg = $msg[110];
            };
        }
        else {
            $error_msg = $msg[111];
        };


        // Code in Datenbank suchen
        //
        if (isset($error_msg) && $error_msg === "") {

            # Nutzer mit Aktivierungscode suchen
            $usr_data = ActivateData::getUser($input_code);

            # Nutzer (code) gefunden
            if ($usr_data) {

                # Code noch nicht abgelaufen?
                if ($usr_data['pwcode_endtime'] < (string)time()) {

                    # Status auf 'activated' setzen -> zur späteren Auswertung
                    ActivateData::setActivated($usr_data['userid']);

                    $success_msg = $msg[112] .
                        ' <a href="login.php?usr=' .
                        $usr_data['username'] . '">' .
                        $msg[113] . '</a>!';
                }

                # veralteten Eintrag löschen
                else {
                    ActivateData::deleteOld($usr_data['userid']);

                    $error_msg = $msg[114] .
                        date(" d.m.y ", $usr_data['pwcode_endtime']) .
                        $msg[115];
                };

            } else {
                $error_msg = $msg[116];
            };
        };

        $show_form = ($success_msg !== "") ? True : False;
        $status_message = Tools::statusOut($success_msg, $error_msg);


        self::$show_form = $show_form;
        self::$status_message = $status_message;
        self::$usr_data = $usr_data;
    }
}


// EOF