<?php
namespace Dzg;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../sitedata/logout.php';
require_once __DIR__.'/../sitemsg/logout.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


class LogoutPrep
{
    protected const MSG = LogoutMsg::MSG;
    protected static bool $show_form;
    protected static string $root_site;
    protected static string $status_message;


    protected static function dataPreparation(): void
    {
        $success_msg = "";
        $error_msg = "";

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
                LogoutData::setLogout($userid, $identifier);
                $success_msg = self::MSG[110];
            }

            // aktuelle Anmeldung beenden
            Tools::lastSite(['login', 'email']);
            Auth::logout();

            $success_msg = self::MSG[111];
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


// EOF