<?php
namespace Dzg\SitePrep;
use Dzg\SiteData\Activate as Data;
use Dzg\Tools\Tools;

session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/../sitedata/activate.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Class Activate
 */
class Activate
{
    protected static bool $show_form;
    protected static string $status_message;
    protected static array $usr_data;


    /**
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
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
                $error_msg = 'Code enthält ungültige Zeichen';
            };
        }
        else {
            $error_msg = 'Kein Code übermittelt.';
        };


        // Code in Datenbank suchen
        //
        if (isset($error_msg) && $error_msg === "") {

            # Nutzer mit Aktivierungscode suchen
            $usr_data = Data::getUser($input_code);

            # Nutzer (code) gefunden
            if ($usr_data) {

                # Code noch nicht abgelaufen?
                if ($usr_data['pwcode_endtime'] < (string)time()) {

                    # Status auf 'activated' setzen -> zur späteren Auswertung
                    Data::setActivated($usr_data['userid']);

                    $success_msg = 'Dein Konto ist aktiviert. Du kannst dich jetzt <a href="login.php?usr='.$usr_data['username'].'">anmelden</a>!';
                }

                # veralteten Eintrag löschen
                else {
                    Data::deleteOld($usr_data['userid']);

                    $error_msg = 'Die Aktivierungsfrist von 4 Wochen ist am '.date("d.m.y", $usr_data['pwcode_endtime']).' abgelaufen.';
                };

            } else {
                $error_msg = 'Das Konto ist bereits aktiviert oder existiert nicht.';
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