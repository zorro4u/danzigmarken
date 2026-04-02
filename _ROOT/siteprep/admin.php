<?php
namespace Dzg\SitePrep;
use Dzg\Tools\{Database, Auth, Tools, CheckIP};

require_once __DIR__.'/../tools/loader_tools.php';
require_once __DIR__.'/../tools/checkip.php';


/****************************
 * Summary of Admin
 * class A extends B implements C
 */
class AdminPrep
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $status_message;
    protected static $error_msg;
    protected static $active;
    protected static $usr_data;
    protected static $user_list;
    protected static $login_data;
    protected static $identifier;
    protected static $log_data;
    protected static $reglinks;
    protected static $counter;
    protected static $userid;
    protected static $show_form;
    protected static $new_blocked;


    /****************************
     * Summary of siteEntryCheck
     * CheckIn-Test
     * Plausi-Test: userid, identifier, token_hash
     * set identifier
     * set last_site
     * set showForm
     */
    protected static function siteEntryCheck()
    {
        if (empty($_SESSION['main']))
            $_SESSION['main'] = "/";

        $return2 = ["index", "index2", "details"];
        Tools::lastSite($return2);

        [$usr_data, $login_data, $error_msg] = Auth::checkUser();

        // unberechtigter Seitenaufruf
        $status = (empty($error_msg)) ? true : false;

        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::isCheckedIn()) {
            header("location: /auth/login.php");
            exit;
        }

        // Nutzer kein Admin? Dann auch weg hier ...
        if ($_SESSION['su'] != 1) {
            header("location: {$_SESSION['lastsite']}");
            exit;
        }

        // globale Variablen setzen
        if ($status) {
            self::$identifier = $login_data['identifier'];
            self::$userid = $usr_data['userid'];
            self::$login_data = $login_data;
            self::$usr_data = $usr_data;
        }
        self::$error_msg = $error_msg;
        self::$show_form = $status;

        self::$new_blocked = CheckIP::write_errorlog_into_DB();
    }


    /****************************
     * Summary of getDBregistryLink
     */
    protected static function getDBregistryLink(): array
    {
        $reglinks = [];
        $stmt = "SELECT userid, email, username, vorname, nachname, notiz, pwcode_endtime
            FROM site_users
            WHERE email LIKE '%dummy%'
            ORDER BY pwcode_endtime";
        $reglinks = Database::sendSQL($stmt, [], 'fetchall');
        return $reglinks;
    }


    /****************************
     * Summary of getDBuserList
     */
    protected static function getDBuserList($userid, $identifier): array
    {
        $user_list = [];

        // --- TAB: Nutzer/Autologin (/Info) ---
        //
        // plus zusätzliche Abfragen:
        // Abfrage Nutzer Autologin
        // Abfrage and.Nutzer Autologin (ct4=ct8)
        //
        // mit 'WITH' werden erst virtuell die nötigen Tabellen gebildet,
        // dann daraus die Abfrege generiert.
        // ist klarer als verschachtelte JOIN/SELECT Konstrukte

        $stmt = "WITH
        cte_users AS (
        SELECT * FROM site_users
        WHERE email NOT LIKE '%dummy%'
            AND `status`='activated'),

        -- ** Nutzerübersicht **
        cte_login AS (
        SELECT userid `uid`, COUNT(`login`) AS ct_login
        FROM site_login
        WHERE `login` IS NOT NULL
        GROUP BY `uid`),

        cte_autologin AS (
        SELECT userid `uid`, COUNT(autologin) AS ct_autologin
        FROM site_login
        WHERE `login` IS NOT NULL
            AND autologin IS NOT NULL
        GROUP BY `uid`),

        -- der erste Login
        cte_first AS (
        SELECT userid `uid`, MIN(created) AS first_seen
        FROM site_login
        GROUP BY `uid`),

        -- ermittelt max_created_date/ip pro user
        tmp1 AS (
        SELECT userid `uid`, ip,
            MAX(created) OVER
            (PARTITION BY userid ORDER BY created DESC) max_created
        FROM site_login),

        cte1_maxcreated AS (
        SELECT * FROM tmp1
        GROUP BY `uid`),

        -- ermittelt max_changed_date/ip pro user
        tmp2 AS (
        SELECT userid `uid`, ip,
            MAX(`changed`) OVER
            (PARTITION BY userid ORDER BY `changed` DESC) max_changed
        FROM site_login),

        cte2_maxchanged AS (
        SELECT * FROM tmp2
        GROUP BY `uid`),

        -- Vgl: max_created vs. max_changed und wählt größten (neuesten) aus
        cte_last AS (
        SELECT cte1.uid,
            IF(cte2.max_changed > cte1.max_created, cte2.max_changed, cte1.max_created) AS last_seen,
            IF(cte2.max_changed > cte1.max_created, cte2.ip, cte1.ip) AS last_ip
        FROM cte1_maxcreated AS cte1
        LEFT JOIN cte2_maxchanged AS cte2 ON cte1.uid = cte2.uid ),

        cte_toketime AS (
        SELECT userid uid, token_endtime
        FROM site_login
        WHERE identifier = :ident ),


        -- ** autologin, selbst **
        -- meine anderen aktiven
        cte_count11 AS (
        SELECT userid uid, COUNT(*) AS count11
        FROM site_login
        WHERE userid = :userid
            AND (login IS NOT NULL && autologin IS NOT NULL)
            AND identifier != :ident ),

        -- meine abgemeldeten log=0, auto=1
        cte_count10 AS (
        SELECT userid `uid`, COUNT(*) AS count10
        FROM site_login
        WHERE userid = :userid
            AND (`login` IS NULL && autologin IS NOT NULL)
            AND identifier != :ident ),

        -- meine beendeten (tot) log=0, auto=0  #AND identifier IS NOT NULL
        cte_count12 AS (
        SELECT userid `uid`, COUNT(*) AS count12
        FROM site_login
        WHERE userid = :userid
            AND (`login` IS NULL && autologin IS NULL) ),

        -- meine abgelaufenen (tot)
        cte_count13 AS (
        SELECT userid `uid`, COUNT(*) AS count13
        FROM site_login
        WHERE userid = :userid
            AND token_endtime < NOW() ),


        -- ** autologin, andere **
        -- aktive autologins
        cte_count21 AS (
        SELECT userid `uid`, COUNT(*) AS count21
        FROM site_login
        WHERE userid != :userid
            AND (`login` IS NOT NULL && autologin IS NOT NULL) ),

        -- abgemeldeten log=0, auto=1
        cte_count20 AS (
        SELECT userid `uid`, COUNT(*) AS count20
        FROM site_login
        WHERE userid != :userid
            AND (`login` IS NULL && autologin IS NOT NULL) ),

        -- beendetet (tot) log=0, auto=0    #AND identifier IS NOT NULL
        cte_count22 AS (
        SELECT userid `uid`, COUNT(*) AS count22
        FROM site_login
        WHERE userid != :userid
            AND (`login` IS NULL && autologin IS NULL) ),

        -- abgelaufene (tot)
        cte_count23 AS (
        SELECT userid `uid`, COUNT(*) AS count23
        FROM site_login
        WHERE userid != :userid
            AND token_endtime < NOW() ),

        -- identifier existiert / alle anderen autologins
        cte_count24 AS (
        SELECT userid `uid`, COUNT(*) AS count24
        FROM site_login
        WHERE userid != :userid
            AND identifier IS NOT NULL ),

        -- identifier existiert / alle anderen User
        tmp25 AS (
        SELECT * FROM site_login
        WHERE userid != :userid
            AND identifier IS NOT NULL
        GROUP BY userid),

        cte_count25 AS (
        SELECT userid `uid`, COUNT(*) AS count25
        FROM tmp25)


        -- Abfrage der Werte aus den virtuellen CTE-Tabellen
        SELECT
            userid, username, email, created, changed, su, `status`,
            ct_login, ct_autologin, last_seen, last_ip, first_seen, token_endtime,
            count11, count10, count12, count13, count21, count20, count22, count23, count24, count25

        FROM cte_users AS usr

        -- Nutzerübersicht
        LEFT JOIN cte_login     AS ct1 ON usr.userid = ct1.uid
        LEFT JOIN cte_autologin AS ct2 ON usr.userid = ct2.uid
        LEFT JOIN cte_first     AS ct3 ON usr.userid = ct3.uid
        LEFT JOIN cte_last      AS ct4 ON usr.userid = ct4.uid
        LEFT JOIN cte_toketime  AS ct5 ON usr.userid = ct5.uid

        -- autologin, selbst
        LEFT JOIN cte_count11 AS ct11 ON usr.userid = ct11.uid
        LEFT JOIN cte_count10 AS ct10 ON usr.userid = ct10.uid
        LEFT JOIN cte_count12 AS ct12 ON usr.userid = ct12.uid
        LEFT JOIN cte_count13 AS ct13 ON usr.userid = ct13.uid

        -- autologin, andere
        LEFT JOIN cte_count21 AS ct21 ON usr.userid = ct21.uid
        LEFT JOIN cte_count20 AS ct20 ON usr.userid = ct20.uid
        LEFT JOIN cte_count22 AS ct22 ON usr.userid = ct22.uid
        LEFT JOIN cte_count23 AS ct23 ON usr.userid = ct23.uid
        LEFT JOIN cte_count24 AS ct24 ON usr.userid = ct24.uid
        LEFT JOIN cte_count25 AS ct25 ON usr.userid = ct25.uid

        ORDER BY username ";

        $data = [':userid' => $userid, ':ident' => $identifier];
        $user_list = Database::sendSQL($stmt, $data, 'fetchall');

        return $user_list;
    }


    /**
     *
     */
    protected static function get_log_data(): array
    {
        $log_data = [];

        $stmt = "WITH
        -- Hilfsabfragen
        known_ip AS (
            SELECT log.ip
            FROM site_log AS `log`
            JOIN site_login AS `login` ON log.ip=login.ip
            GROUP BY log.ip),

        unknown_single AS (
            SELECT * FROM site_log
            WHERE ip NOT IN (SELECT * FROM known_ip)),

        unknown_group AS (
            SELECT * FROM unknown_single
            GROUP BY ip),

        black_group AS (
            SELECT * FROM site_blacklist
            GROUP BY ip),

        block_ip AS (
            SELECT * FROM site_blacklist
            WHERE `block`=1
            GROUP BY ip),

        -- Hauptabfragen
        last_ip AS (
            SELECT id, ip, `date` FROM site_log
            WHERE ip NOT IN (SELECT * FROM known_ip)
            ORDER BY `date` DESC, id DESC
            LIMIT 1),

        ct_last AS (
            SELECT COUNT(*) AS ct_last FROM site_log
            JOIN last_ip ON site_log.ip=last_ip.ip),

        ct_group AS (SELECT COUNT(*) ct_group FROM unknown_group),
        ct_singl AS (SELECT COUNT(*) ct_singl FROM unknown_single),
        ct_black AS (SELECT COUNT(*) ct_black FROM black_group),
        ct_block AS (SELECT COUNT(*) ct_block FROM block_ip)

        -- einzeilige Abfrage
        SELECT *
        FROM last_ip, ct_last, ct_group, ct_singl, ct_black, ct_block
        ";

        $log_data = Database::sendSQL($stmt, [], 'fetch');


        // Anzahl der Tageszugriffe
        $date = date('Y-m-d', strtotime($log_data['date']));
        $stmt =
            "SELECT COUNT(*) ct_date FROM site_log
            WHERE `date` LIKE '{$date}%' ";

        $log_data += Database::sendSQL($stmt, [], 'fetch');

        return $log_data;
    }


    /****************************
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
        Tools::lastSite();

        // Seiten-Check okay, Seite starten
        if (self::$show_form):

            $userid     = self::$userid;
            $identifier = self::$identifier;

            // --- TAB: Registrierung ---
            //
            $reglinks = self::getDBregistryLink();


            // --- TAB: Nutzer ---
            //
            $user_list = self::getDBuserList($userid, $identifier);


            // --- TAB: Autologin / Info ---
            //
            $log_data = self::get_log_data();


            $ct10 = $ct11 = $ct12 = $ct13 = $ct21 = $ct20 = $ct22 = $ct23 = $ct24 = $ct25 = 0;
            foreach ($user_list as $user) {

                // --- TAB: Info ---
                if ($user['userid'] == $userid) {
                    #$usr_data['username'] = $user['username'];
                    #$usr_data['email'] = $user['email'];
                    #$usr_data['created'] = $user['created'];
                    #$usr_data['changed'] = $user['changed'];
                    #$login_data['created'] = $user['created'];
                    #$login_data['last_seen'] = $user['last_seen'];
                    #$login_data['last_seen'] = $login_data['changed'];
                    #$login_data['ip'] = $user['last_ip'];
                    #$login_data['identifier'] = $identifier;
                    #$login_data['token_hash'] = $login_data['token_hash'];
                    #$login_data['token_endtime'] = $user['token_endtime'];

                    // --- TAB: Autologin ---
                    // meine anderen aktiven
                    if ($ct11 < $user['count11']) $ct11 = $user['count11'];
                    // meine ausgeloggten
                    if ($ct10 < $user['count10']) $ct10 = $user['count10'];
                    // meine beendeten (tot)
                    if ($ct12 < $user['count12']) $ct12 = $user['count12'];
                    // meine abgelaufenen (tot)
                    if ($ct13 < $user['count13']) $ct13 = $user['count13'];
                } else {
                    // alle anderen aktiven
                    if ($ct21 < $user['count21']) $ct21 = $user['count21'];
                    // alle anderen ausgeloggten
                    if ($ct20 < $user['count20']) $ct20 = $user['count20'];
                    // alle anderen beendeten (tot)
                    if ($ct22 < $user['count22']) $ct22 = $user['count22'];
                    // alle anderen abgelaufenen (tot)
                    if ($ct23 < $user['count23']) $ct23 = $user['count23'];
                    // alle anderen Anmeldungen
                    if ($ct24 < $user['count24']) $ct24 = $user['count24'];
                    // alle anderen Nutzer
                    if ($ct25 < $user['count25']) $ct25 = $user['count25'];
                };
            };

            self::$user_list = $user_list;
            self::$log_data = $log_data;
            self::$reglinks = $reglinks;
            self::$counter = [
                10 => $ct10,
                11 => $ct11,
                12 => $ct12,
                13 => $ct13,
                20 => $ct20,
                21 => $ct21,
                22 => $ct22,
                23 => $ct23,
                24 => $ct24,
                25 => $ct25,
            ];

        endif;      # Seiten-Check okay
    }


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