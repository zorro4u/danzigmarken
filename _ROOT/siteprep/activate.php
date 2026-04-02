<?php
namespace Dzg\SitePrep;
use Dzg\Tools\{Database, Tools};

session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/../tools/database.php';
require_once __DIR__.'/../tools/tools.php';


/***********************
 * Summary of Activate
 */
class ActivatePrep
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $show_form;
    protected static string $status_message;
    protected static $usr_data;


    /***********************
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
        $success_msg = "";
        $error_msg = "";

        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details'];
        if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']) === false)) {
            // wenn VorgängerSeite bekannt und nicht die aufgerufene Seite selbst ist, speichern
            $referer = str_replace("change", "details", $_SERVER['HTTP_REFERER']);
            $fn_referer = pathinfo($referer)['filename'];
            // wenn Herkunft von den target-Seiten, dann zu diesen, ansonsten Standardseite
            $_SESSION['lastsite'] =  (in_array($fn_referer, $return2))
                ? $referer
                : $_SESSION['main'];
        } elseif (empty($_SERVER['HTTP_REFERER']) && empty($_SESSION['lastsite'])) {
            // wenn nix gesetzt ist, auf Standard index.php verweisen
            $_SESSION['lastsite'] = (!empty($_SESSION['main'])) ? $_SESSION['main'] : "/";
        }
        unset($return2, $referer, $fn_referer);


        // Übergabewert auf Plausibilität prüfen
        if (isset($_GET['code'])) {
            $input_code = htmlspecialchars(Tools::cleanInput($_GET['code']));
            $error_msg = "";

            // Code prüfen (nur alphanumerisch)
            if($input_code && preg_match('/^[a-zA-Z0-9]+$/', $input_code) == 0) {
                $error_msg = 'Code enthält ungültige Zeichen';
            }
        } else $error_msg = 'Kein Code übermittelt.';

        // Code in Datenbank suchen
        if(isset($error_msg) && $error_msg === "") {

            // Nutzer mit Aktivierungscode suchen
            $stmt = 'SELECT userid, username, pwcode_endtime FROM site_users WHERE status = :code';
            $data = [':code' => $input_code];
            $usr_data = Database::sendSQL($stmt, $data, 'fetch');

            // Nutzer (code) gefunden
            if ($usr_data) {

                // Code noch nicht abgelaufen?
                if ($usr_data['pwcode_endtime'] < (string)time()) {

                    // Status auf 'activated' setzen -> zur späteren Auswertung
                    $stmt = "UPDATE site_users SET status = 'activated', pwcode_endtime = Null, notiz = Null WHERE userid = :userid";
                    $data = [':userid' => $usr_data['userid']];     # int
                    Database::sendSQL($stmt, $data);

                    $success_msg = 'Dein Konto ist aktiviert. Du kannst dich jetzt <a href="login.php?usr='.$usr_data['username'].'">anmelden</a>!';

                } else {
                    // veralteten Eintrag löschen
                    #$stmt0 = "UPDATE site_users SET status=NULL, pwcode_endtime=NULL, notiz=NULL, pwc=NULL WHERE userid=:userid";
                    $stmt = "DELETE FROM site_users WHERE userid = :userid";
                    $data = [':userid' => $usr_data['userid']];     # int
                    Database::sendSQL($stmt, $data);

                    $error_msg = 'Die Aktivierungsfrist von 4 Wochen ist am '.date("d.m.y", $usr_data['pwcode_endtime']).' abgelaufen.';
                }

            } else {
                $error_msg = 'Das Konto ist bereits aktiviert oder existiert nicht.';
            }
        }

        $show_form = ($success_msg !== "") ? True : False;
        $status_message = Tools::statusOut($success_msg, $error_msg);


        self::$show_form = $show_form;
        self::$status_message = $status_message;
        self::$usr_data = $usr_data;
    }

}