<?php
namespace Dzg;

#require_once __DIR__.'/includes/login.inc.php';
#require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';
use Dzg\{Database, Auth, Tools, Header, Footer};
use PDO, PDOException;


class Logout
{
    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connectMyDB();
        }

        self::dataPreparation();

        Header::show();
        self::siteOutput();
        Footer::show("auth");

        // Datenbank schließen
        self::$pdo = Null;
    }


    /***********************
     * Klassenvariablen / Eigenschaften
     */
    public static $pdo;
    private static $show_form;
    private static $root_site;
    private static string $status_message;


    private static function dataPreparation()
    {
        $pdo = self::$pdo;
        $success_msg = "";
        $error_msg = "";

        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details', 'login', 'email'];
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


        # [$usrdata_X, $logindata_X, $error_msg] = Auth::checkUser();

        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::isCheckedIn()) {
            header("location: {$_SESSION['lastsite']}");
            exit;
        }


        $show_form = True;
        $root_site = $_SESSION['rootdir'].'/'.basename($_SESSION['main']);
        $userid = $_SESSION['userid'];
        $identifier = (!empty($_COOKIE['auto_identifier']))
            ? htmlspecialchars($_COOKIE['auto_identifier'], ENT_QUOTES)
            : "";

        // Logoutformular empfangen
        if (isset($_GET['logout'])
            && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST"):

            // alle Logins beenden
            if(isset($_POST['logout_all'])) {

                // alle anderen Autologins beenden, wenn mit Autologin auch angemeldet
                $stmt0 = "UPDATE site_login
                    SET login = NULL, autologin = NULL
                    WHERE userid = :userid AND (login = 1 && autologin = 1)
                    AND identifier != :ident";

                // alle anderen Logins beenden, wenn mit Autologin angemeldet
                $stmt = "UPDATE site_login
                    SET login = NULL, autologin = NULL
                    WHERE userid = :userid AND (login = 1)
                    AND identifier != :ident";

                // alle Autologins beenden
                $stmt1 = "UPDATE site_login
                    SET login = NULL, autologin = NULL
                    WHERE userid = :userid AND (login = 1 && autologin = 1)";

                // alle Logins beenden
                $stmt2 = "UPDATE site_login
                    SET login = NULL, autologin = NULL
                    WHERE userid = :userid AND (login = 1)";

                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                    $qry->bindParam(':ident', $identifier, PDO::PARAM_STR);
                    $qry->execute();

                } catch(PDOException $e) {die($e->getMessage().': settings.inc_del-autologin');}
                $success_msg = "Alle meine anderen Autologins beendet.";
            }

            // aktuelle Anmeldung beenden
            Auth::logout($_SESSION['lastsite']);

            $success_msg = "Du bist abgemeldet";
            $show_form = False;

            #header("location: {$_SESSION['lastsite']}");
            #exit;

        endif;


        self::$root_site = $root_site;
        #$error_msg = (!empty($error_arr))
        #    ? implode("<br>", $error_arr)
        #    : "";
        #self::$show_form = ($error_msg === "") ? True : False;
        self::$show_form = $show_form;
        self::$status_message = Tools::statusOut($success_msg, $error_msg);
    }



    private static function siteOutput()
    {
        $show_form = self::$show_form;
        $root_site = self::$root_site;
        $status_message = self::$status_message;

        $output = "
            <div class='container small-container-330 form-signin'>
            <h2 class='form-signin-heading'>Abmelden</h2>";

        #$output .= statusmeldungAusgeben();
        $output .= $status_message;

        // Seite anzeigen
        if ($show_form):
            $output .= "
                <form action='?logout' method='POST'>
                <br>";

            if (!empty($_SESSION['autologin'])):
                $output .= "
                    <div class='checkbox' style='padding-top: 15px;'>
                    <label>
                    <input type='checkbox' name='logout_all' value='1' autocomplete='off'> geräteübergreifend alle meine Anmeldungen beenden (Grand Logout)
                    </label>
                    </div>";
            endif;
            $output .= "
                <button class='btn btn-lg btn-primary btn-block' style='margin-top: 20px;' type='submit'>Logout</button>
                </form>";
        else:
            $output .= "
                <br><br><hr><br>
                <div><form action=".$root_site." method='POST'>
                <button class='btn btn-lg btn-primary btn-block' type='submit'>Startseite</button>
                </form></div>";
        endif;
        $output .= "</div>";

        echo $output;
    }
}