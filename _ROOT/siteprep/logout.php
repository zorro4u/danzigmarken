<?php
namespace Dzg\SitePrep;
use Dzg\SiteData\Logout as Data;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../sitedata/logout.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


class Logout
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $show_form;
    protected static $root_site;
    protected static string $status_message;


    protected static function dataPreparation()
    {
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
                Data::setLogout($userid, $identifier);
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
}

