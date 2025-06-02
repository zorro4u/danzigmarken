<?php
/* Prozess: RegInfoSeite-->email(Admin)-->email(RegCode)-->RegSeite-->email(AktLink)-->dieseSeite:ActivateSeite-->Login */
namespace Dzg\Cls;

session_start();
date_default_timezone_set('Europe/Berlin');

// Datenbank- & Auth-Funktionen laden
#require_once __DIR__.'/Database.php';
#require_once __DIR__.'/Auth.php';
#require_once __DIR__.'/Tools.php';
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';

use PDO, PDOException;
use Dzg\Cls\{Database, Auth, Tools, Header, Footer};

/***********************
 * Summary of Activate
 */
class Activate
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private static $pdo;
    private static $showForm;
    private static string $status_message;
    private static $usr_data;


    /****************************
     * Summary of show
     */
    public static function show()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connect_mariadb();
        }

        self::data_preparation();

        Header::show();
        self::site_output();
        Footer::show("auth");

        // Datenbank schließen
        self::$pdo = Null;
    }


    /***********************
     * Summary of data_preparation
     */
    private static function data_preparation()
    {
        $pdo = self::$pdo;

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
            $input_code = htmlspecialchars(Tools::clean_input($_GET['code']));
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
            try {
                $qry = $pdo->prepare($stmt);
                $qry->execute($data);
                $usr_data = $qry->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {die($e->getMessage().': activate_code-suchen');}

            // Nutzer (code) gefunden
            if ($usr_data) {

                // Code noch nicht abgelaufen?
                if ($usr_data['pwcode_endtime'] < (string)time()) {

                    // Status auf 'activated' setzen -> zur späteren Auswertung
                    $stmt = "UPDATE site_users SET status = 'activated', pwcode_endtime = Null, notiz = Null WHERE userid = :userid";
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->bindParam(':userid', $usr_data['userid'], PDO::PARAM_INT);
                        $qry->execute();
                    } catch(PDOException $e) {die($e->getMessage().': activate_set-status');}

                    $success_msg = 'Dein Konto ist aktiviert. Du kannst dich jetzt <a href="login.php?usr='.$usr_data['username'].'">anmelden</a>!';

                } else {
                    // veralteten Eintrag löschen
                    #$stmt0 = "UPDATE site_users SET status=NULL, pwcode_endtime=NULL, notiz=NULL, pwc=NULL WHERE userid=:userid";
                    $stmt = "DELETE FROM site_users WHERE userid = :userid";
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->bindParam(':userid', $usr_data['userid'], PDO::PARAM_INT);
                        $qry->execute();
                    } catch(PDOException $e) {die($e->getMessage().': activate_del-old');}

                    $error_msg = 'Die Aktivierungsfrist von 4 Wochen ist am '.date("d.m.y", $usr_data['pwcode_endtime']).' abgelaufen.';
                }

            } else {
                $error_msg = 'Das Konto ist bereits aktiviert oder existiert nicht.';
            }
        }

        $showForm = ($success_msg !== "") ? True : False;
        $status_message = Tools::status_out($success_msg, $error_msg);


        self::$showForm = $showForm;
        self::$status_message = $status_message;
        self::$usr_data = $usr_data;
    }


    /****************************
     * Summary of site_output
     */
    public static function site_output()
    {
        $showForm = self::$showForm;
        $status_message = self::$status_message;
        $usr_data = self::$usr_data;

        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='small-container-330 form-signin'>";
        $output .= "
            <h2 class='form-signin-heading'>Aktivierung</h2>
            <br>";

        // Seite anzeigen
        if ($showForm):
        $output .= "
        <form action='./login.php?usr=".$usr_data['username']."' method='POST' style='margin-top: 30px;'>
            <button class='btn btn-lg btn-primary btn-block' type='submit'>Anmelden</button>
        </form>";

        endif;

        $output .= "</div>";
        $output .= "</div>";


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }

}