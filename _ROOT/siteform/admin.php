<?php
namespace Dzg\SiteForm;
use Dzg\SitePrep\Admin as Prep;
use Dzg\Tools\{Database, Auth, Tools};

require_once __DIR__.'/../siteprep/admin.php';
require_once __DIR__.'/../tools/loader_tools.php';


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
                            case 11:        // alle meine anderen aktiven Anmeldungen
                                $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
                        WHERE userid = :userid AND (login IS NOT NULL && autologin IS NOT NULL) AND identifier != :ident";
                                #$stmt = "DELETE FROM site_login WHERE userid = :userid AND (login IS NOT NULL && autologin IS NOT NULL) AND identifier != :ident";
                                $data = [':userid' => $userid, ':ident' => $identifier];
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Alle meine anderen aktiven Autologins beendet.";
                                break;

                            case 10:        // alle meine ausgeloggten Anmeldungen
                                $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
                        WHERE userid = :userid AND (login IS NULL && autologin IS NOT NULL) AND identifier != :ident";
                                $data = [':userid' => $userid, ':ident' => $identifier];
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Alle meine ausgeloggten Logins beendet.";
                                break;

                            case 12:     // alle meine beendeten Anmeldungen (tot)  #AND identifier IS NOT NULL
                                $stmt = "DELETE FROM site_login
                        WHERE userid = :userid AND (login IS NULL && autologin IS NULL)";
                                $data = [':userid' => $userid];     # int
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Alle meine beendeten Logins gelöscht.";
                                break;

                            case 13:        // alle meine abgelaufenen Anmeldungen (tot)
                                $stmt = "DELETE FROM site_login
                        WHERE userid = :userid AND token_endtime < NOW()";
                                $data = [':userid' => $userid];     # int
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Alle meine abgelaufenen Logins gelöscht.";
                                break;


                            case 21:        // alle anderen aktiven
                                $stmt = "UPDATE site_login SET login = NULL
                        WHERE userid != :userid AND (login IS NOT NULL && autologin IS NOT NULL)";
                                $data = [':userid' => $userid];     # int
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Alle aktiven Autologins der anderen Nutzer beendet.";
                                break;

                            case 20:        // alle anderen ausgeloggten Anmeldung
                                $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
                        WHERE userid != :userid AND (login IS NULL && autologin IS NOT NULL)";
                                $data = [':userid' => $userid];     # int
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Alle ausgeloggten Autologins der anderen Nutzer beendet.";
                                break;

                            case 22:        // alle anderen beendeten Anmeldung (tot)  #AND identifier IS NOT NULL
                                $stmt = "DELETE FROM site_login
                        WHERE userid != :userid AND (login IS NULL && autologin IS NULL)";
                                $data = [':userid' => $userid];     # int
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Alle anderen toten Logins gelöscht.";
                                break;

                            case 23:        // alle anderen abgelaufenen (tot)
                                $stmt = "DELETE FROM site_login
                        WHERE userid != :userid AND token_endtime < NOW()";
                                $data = [':userid' => $userid];     # int
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Alle anderen abgelaufenen Logins gelöscht.";
                                break;

                            case 24:        // alle Anmeldungen der anderen Nutzer ('break' weggelassen)
                            case 25:        // alle anderen Nutzer
                                $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
                        WHERE userid != :userid AND identifier IS NOT NULL";
                                $data = [':userid' => $userid];     # int
                                Database::sendSQL($stmt, $data);
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

                        $stmt =
                            "INSERT INTO site_users
                    (username, email, `status`, pwcode_endtime, notiz)
                VALUES (:username, :email, :status, :pwcode_endtime, :notiz) ";
                        $data = [
                            ':username' => $input_usr,
                            ':email'    => $input_email,
                            ':status'   => $status,
                            ':pwcode_endtime' => $pwcode_endtime,
                            ':notiz'    => $notiz
                        ];
                        Database::sendSQL($stmt, $data);
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
                            $stmt = "DELETE FROM site_users WHERE userid = :userid";
                            $data = [':userid' => $reglinks[$i]['userid']];     # int
                            Database::sendSQL($stmt, $data);
                            $success_msg = "Registrierungslink gelöscht.";
                        }
                        break;

                    case "delete_allregs":
                        if (count($reglinks)) {
                            // DB aufräumen, VACUUM;
                            // DB Integritätsprüfung: PRAGMA integrity_check;
                            // Zähler zurücksetzen: "DELETE FROM sqlite_sequence WHERE name = '{tab_name}'"  # autoincrement zurücksetzen
                            $stmt = "DELETE FROM site_users WHERE email LIKE '%dummy%'";
                            Database::sendSQL($stmt, []);
                            $success_msg = "alle Registrierungslinks gelöscht.";
                        }
                        break;


                    // --- Änderung TAB: Nutzer (löschen) ---
                    //
                    case "delete_user":
                        if (isset($_POST['usrchoise'])) {
                            $i = (int)$_POST['usrchoise'] - 1;
                            if ($user_list[$i]['userid'] !== $_SESSION['userid']) {
                                $stmt = "UPDATE site_users SET status = 'deaktiv' WHERE userid = :userid";
                                #$stmt = "DELETE FROM site_users WHERE userid = :userid";
                                $data = [':userid' => $user_list[$i]['userid']];        # int
                                Database::sendSQL($stmt, $data);
                                $success_msg = "Nutzer gelöscht.";

                                // wenn Variante 'deaktiv setzen', dann auch alle Anmeldungen löschen,
                                // (sonst bei DELETE automatisch per Verknüpfung gelöscht).
                                $stmt = "DELETE FROM site_login WHERE userid = :userid";
                                $data = [':userid' => $user_list[$i]['userid']];        # int
                                Database::sendSQL($stmt, $data);
                            } else $error_msg = "Kann mich nicht selbst löschen.";
                        }
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