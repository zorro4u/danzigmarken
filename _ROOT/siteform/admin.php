<?php
namespace Dzg;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../siteprep/admin.php';
require_once __DIR__.'/../sitedata/admin.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Admin
 * class A extends B implements C
 */
class AdminForm extends AdminPrep
{
    protected static string $status_message;
    protected static array $active;


    /**
     * Summary of formEvaluation
     * Änderungsformular empfangen, Eingaben verarbeiten
     */
    protected static function formEvaluation(): void
    {
        $msg = self::MSG;
        $identifier  = self::$identifier;
        $reglinks    = self::$reglinks;
        $user_list   = self::$user_list;
        $userid      = $_SESSION['userid'];
        $success_msg = "";
        $error_msg   = "";

        // Seiten-Check okay, Seite starten
        if (self::$show_form):

            // Änderungsformular empfangen
            if (isset($_GET['save'])
                && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")
            {
                $save = htmlspecialchars(Tools::cleanInput($_GET['save']));
                switch ($save):

                    # Änderung TAB: Autologin
                    case "autologin":
                        $success_msg = self::tab_autologin($userid, $identifier);
                        break;

                    # Änderung TAB: Registrierung
                    case "make_reglink":
                        $success_msg = self::tab_reglinks();
                        break;

                    case "show_mail":
                        $success_msg = self::show_mail($reglinks);
                        break;

                    case "delete_reg":
                        $success_msg = self::delete_reg($reglinks);
                        break;

                    case "delete_allregs":
                        if (count($reglinks)) {
                            AdminData::deleteAllRegLink();
                            $success_msg = self::MSG[210];
                        };
                        break;

                    # Änderung TAB: Nutzer (löschen)
                    case "delete_user":
                        [$success_msg, $error_msg] = self::tab_delete_user($user_list);
                        break;

                    # Änderung TAB: Info
                    case "info":
                        break;

                endswitch;  # Speichern-Taste gedrückt

                // geänderte Daten für die Ausgabe neu laden
                self::dataPreparation();

            };      # Formular empfangen
        endif;      # Seiten-Check okay


        // Marker setzen, um wieder auf den letzten Tab-Reiter zu springen
        //
        // Liste der #Tab-ID's
        $site_tabs = ["info", "user", "autologin", "regis", "sonst", "tools"];

        $active = [];
        if (isset($_GET['tab'])
            && in_array($_GET['tab'], $site_tabs))
        {
            foreach ($site_tabs as $tab) {
                $active[$tab] = ($_GET['tab'] == $tab)
                    ? "active"
                    : "";
            };
        }

        // irgendwie kein GET erhalten,
        // $active auf Standard (1.Tab = email) setzen
        else {
            foreach ($site_tabs as $tab) {
                $active[$tab] = "";
            };
            $active[$site_tabs[0]] = "active";
        };

        if (!empty(self::$error_msg)) {
            $error_msg =  self::$error_msg . '<br>' . $error_msg;
        };

        $status_message = Tools::statusOut($success_msg, $error_msg);

        self::$status_message = $status_message;
        self::$active = $active;
        unset($_REQUEST, $_POST, $_GET);
    }



    private static function tab_autologin(int $userid, string $identifier): string
    {
        $success_msg = "";
        switch ((int)$_POST['logout']) {
            case 11:     // alle meine anderen aktiven Anmeldungen
                AdminData::deleteMyActive($userid, $identifier);
                $success_msg = self::MSG[211];
                break;

            case 10:     // alle meine ausgeloggten Anmeldungen
                AdminData::deleteMyNoActive($userid, $identifier);
                $success_msg = self::MSG[212];
                break;

            case 12:     // alle meine beendeten Anmeldungen (tot)
                AdminData::deleteMyClosed($userid);
                $success_msg = self::MSG[213];
                break;

            case 13:     // alle meine abgelaufenen Anmeldungen (tot)
                AdminData::deleteMyOld($userid);
                $success_msg = self::MSG[214];
                break;


            case 21:     // alle anderen aktiven
                AdminData::deleteActive($userid);
                $success_msg = self::MSG[215];
                break;

            case 20:     // alle anderen ausgeloggten Anmeldung
                AdminData::deleteNoActive($userid);
                $success_msg = self::MSG[216];
                break;

            case 22:     // alle anderen beendeten Anmeldung (tot)
                AdminData::deleteClosed($userid);
                $success_msg = self::MSG[217];
                break;

            case 23:     // alle anderen abgelaufenen (tot)
                AdminData::deleteOld($userid);
                $success_msg = self::MSG[218];
                break;

            case 24:     // alle Anmeldungen der anderen Nutzer ('break' weggelassen)
            case 25:     // alle anderen Nutzer
                AdminData::deleteOtherAutologin($userid);
                $success_msg = self::MSG[218];
                break;
        };  // $_POST['logout']

        return $success_msg;
    }


    private static function tab_reglinks(): string
    {
        $success_msg = "";

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
        AdminData::storeRegCode($data);
        $success_msg = self::MSG[220];

        return $success_msg;
    }


    private static function show_mail(array $reglinks): string
    {
        $success_msg = "";
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
        };
        return $success_msg;
    }


    private static function delete_reg(array $reglinks): string
    {
        $success_msg = "";
        if (isset($_POST['regchoise'])) {
            $i = (int)$_POST['regchoise'] - 1;
            AdminData::deleteRegLink($reglinks[$i]['userid']);
            $success_msg = self::MSG[221];
        };
        return $success_msg;
    }


    private static function tab_delete_user(array $user_list): array
    {
        $success_msg = "";
        $error_msg = "";
        if (isset($_POST['usrchoise'])) {
            $i = (int)$_POST['usrchoise'] - 1;
            if ($user_list[$i]['userid'] !== $_SESSION['userid']) {
                AdminData::deleteUser($user_list[$i]['userid']);
                $success_msg = self::MSG[222];
            }
            else {
                $error_msg = self::MSG[223];
            };
        };
        return [$success_msg, $error_msg];
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


// EOF