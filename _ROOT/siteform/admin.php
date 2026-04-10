<?php
namespace Dzg\SiteForm;
use Dzg\SitePrep\Admin as Prep;
use Dzg\SiteData\AdminData as Data;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../siteprep/admin.php';
require_once __DIR__.'/../sitedata/admin.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


/****************************
 * Summary of Admin
 * class A extends B implements C
 */
class Admin extends Prep
{
    protected static $status_message;
    protected static $active;


    /****************************
     * Summary of formEvaluation
     * Änderungsformular empfangen, Eingaben verarbeiten
     */
    protected static function formEvaluation()
    {
        $error_msg   = self::$error_msg;
        $identifier  = self::$identifier;
        $reglinks    = self::$reglinks;
        $user_list   = self::$user_list;
        $userid      = $_SESSION['userid'];
        $success_msg = "";

        // Seiten-Check okay, Seite starten
        if (self::$show_form):


            // Änderungsformular empfangen
            if (isset($_GET['save']) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST"):

                $save = htmlspecialchars(Tools::cleanInput($_GET['save']));
                switch ($save):


                        // --- Änderung TAB: Autologin ---
                        //
                    case "autologin":

                        switch ((int)$_POST['logout']) {
                            case 11:     // alle meine anderen aktiven Anmeldungen
                                Data::deleteMyActive($userid, $identifier);
                                $success_msg = "Alle meine anderen aktiven Autologins beendet.";
                                break;

                            case 10:     // alle meine ausgeloggten Anmeldungen
                                Data::deleteMyNoActive($userid, $identifier);
                                $success_msg = "Alle meine ausgeloggten Logins beendet.";
                                break;

                            case 12:     // alle meine beendeten Anmeldungen (tot)
                                Data::deleteMyClosed($userid);
                                $success_msg = "Alle meine beendeten Logins gelöscht.";
                                break;

                            case 13:     // alle meine abgelaufenen Anmeldungen (tot)
                                Data::deleteMyOld($userid);
                                $success_msg = "Alle meine abgelaufenen Logins gelöscht.";
                                break;


                            case 21:     // alle anderen aktiven
                                Data::deleteActive($userid);
                                $success_msg = "Alle aktiven Autologins der anderen Nutzer beendet.";
                                break;

                            case 20:     // alle anderen ausgeloggten Anmeldung
                                Data::deleteNoActive($userid);
                                $success_msg = "Alle ausgeloggten Autologins der anderen Nutzer beendet.";
                                break;

                            case 22:     // alle anderen beendeten Anmeldung (tot)
                                Data::deleteClosed($userid);
                                $success_msg = "Alle anderen toten Logins gelöscht.";
                                break;

                            case 23:     // alle anderen abgelaufenen (tot)
                                Data::deleteOld($userid);
                                $success_msg = "Alle anderen abgelaufenen Logins gelöscht.";
                                break;

                            case 24:     // alle Anmeldungen der anderen Nutzer ('break' weggelassen)
                            case 25:     // alle anderen Nutzer
                                Data::deleteOtherAutologin($userid);
                                $success_msg = "Alle Autologins der anderen Nutzer beendet.";
                                break;
                        }  // $_POST['logout']
                        break;


                    // --- Änderung TAB: Registrierung ---
                    //
                    case "make_reglink":

                        // Code für Zugang zur Registrierungsseite, 30 Tage gültig
                        $reg_code = uniqid();
                        $pwcode_endtime = Auth::getPWcodeTimer();

                        #$reg_url = getSiteURL().'register.php?code='.$reg_code;
                        $reg_url = "https://www.danzigmarken.de/auth/register.php?code=" . $reg_code;
                        $reg_link  = "register.php?code=" . $reg_code;     // intern
                        $input_usr = $reg_code;
                        $input_email = $reg_code . "@dummy.de";
                        $status = $reg_code;
                        $notiz  = $reg_url;

                        $data = [
                            ':username' => $input_usr,
                            ':email'    => $input_email,
                            ':status'   => $status,
                            ':pwcode_endtime' => $pwcode_endtime,
                            ':notiz'    => $notiz ];
                        Data::storeRegCode($data);
                        $success_msg = "Registrierungs-Link erzeugt.";
                        break;

                    case "show_mail":
                        if (isset($_POST['regchoise'])) {
                            $i = (int)$_POST['regchoise'] - 1;
                            $to       = str_replace("_dummy_", "", $reglinks[$i]['email']);
                            $subject = "Registrierungs-Link für www.danzigmarken.de";
                            $mailcontent  = "" .
                                "An: " . $to . "<br>" .
                                "Betreff: " . $subject . "<br>" .
                                "----------------------------------------<br>" .
                                "Hallo " . $reglinks[$i]['vorname'] . ",<br>" .
                                "du kannst dich jetzt auf www.danzigmarken.de registrieren. " .
                                "Rufe dazu in den nächsten 4 Wochen (bis zum " . date('d.m.y', $reglinks[$i]['pwcode_endtime']) . ") " .
                                "den folgenden Link auf: <br><a href='" . $reglinks[$i]['notiz'] . "'>" . $reglinks[$i]['notiz'] . "</a><br>" .
                                "Herzliche Grüße";

                            $success_msg = $mailcontent;
                        }
                        break;

                    case "delete_reg":
                        if (isset($_POST['regchoise'])) {
                            $i = (int)$_POST['regchoise'] - 1;
                            Data::deleteRegLink($reglinks[$i]['userid']);
                            $success_msg = "Registrierungslink gelöscht.";
                        }
                        break;

                    case "delete_allregs":
                        if (count($reglinks)) {
                            Data::deleteAllRegLink();
                            $success_msg = "alle Registrierungslinks gelöscht.";
                        }
                        break;


                    // --- Änderung TAB: Nutzer (löschen) ---
                    //
                    case "delete_user":
                        if (isset($_POST['usrchoise'])) {
                            $i = (int)$_POST['usrchoise'] - 1;
                            if ($user_list[$i]['userid'] !== $_SESSION['userid']) {
                                Data::deleteUser($user_list[$i]['userid']);
                                $success_msg = "Nutzer gelöscht.";
                            }
                            else {
                                $error_msg = "Kann mich nicht selbst löschen.";
                            };
                        };
                        break;


                    // --- Änderung TAB: Info ---
                    //
                    case "info":
                        break;

                endswitch;  # Speichern-Taste gedrückt

                // geänderte Daten für die Ausgabe neu laden
                self::dataPreparation();

            endif;      # Formular empfangen
        endif;      # Seiten-Check okay


        // Marker setzen, um wieder auf den letzten Tab-Reiter zu springen
        //
        // Liste der #Tab-ID's
        $site_tabs = ["info", "user", "autologin", "regis", "sonst"];

        $active = [];
        if (isset($_GET['tab']) && in_array($_GET['tab'], $site_tabs)) {
            foreach ($site_tabs as $tab) {
                if ($_GET['tab'] == $tab) {
                    $active[$tab] = "active";
                } else
                    $active[$tab] = "";
            }

            // irgendwie kein GET erhalten,
            // $active auf Standard (1.Tab = email) setzen
        } else {
            foreach ($site_tabs as $tab) {
                $active[$tab] = "";
            }
            $active[$site_tabs[0]] = "active";
        }


        $status_message = Tools::statusOut($success_msg, $error_msg);

        self::$status_message = $status_message;
        self::$active = $active;
        unset($_REQUEST, $_POST, $_GET);
    }
}

#foreach ($_COOKIE AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_SESSION AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
/*
foreach ($_SESSION AS $k=>$v) {
    $typ=["integer", "boolean"];
    $typ1=["string"];
    if (in_array(gettype($v), $typ)) {
        echo gettype($v), "_", $k, ": ", $v, "<br>";}};echo"<br>";*/
#var_dump($_SESSION);
/*
ident: xxxx
autologin: -
userid: zz
loggedin: -
su: z
status: --v

rootdir:
main: /index.php
lastsite: /index.php#674
siteid: 3

sort: the.id DESC, sta.kat10, sta.datum
dir: ASC
col: sta.kat10
filter: the.id IS NOT NULL AND sta.deakt=0 AND dat.deakt=0 AND ort.deakt=0
version: 250617
proseite: 10
start: 0
groupid: 500
fileid: 674
prev: 674
next: 673
*/