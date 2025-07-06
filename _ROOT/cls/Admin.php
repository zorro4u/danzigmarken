<?php
namespace Dzg;
require_once __DIR__.'/Settings.php';
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';


/****************************
 * Summary of Admin
 * class A extends B implements C
 */
class Admin
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    public static $status_message;
    public static $error_msg;
    public static $active;
    public static $usr_data;
    public static $user_list;
    public static $login_data;
    public static $identifier;
    public static $last_access;
    public static $reglinks;
    public static $count10;
    public static $count11;
    public static $count12;
    public static $count13;
    public static $count20;
    public static $count21;
    public static $count22;
    public static $count23;
    public static $count24;
    public static $count25;
    public static $userid;
    protected static $show_form;



    /****************************
     * Summary of show
     */
    public static function show()
    {
        self::siteEntryCheck();
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::siteOutput();
        Footer::show("account");

        self::lastScriptAusgeben();
    }


    /****************************
     * Summary of siteEntryCheck
     * CheckIn-Test
     * Plausi-Test: userid, identifier, token_hash
     * set identifier
     * set last_site
     * set showForm
     */
    public static function siteEntryCheck()
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

    }


    /****************************
     * Summary of getDBregistryLink
     */
    protected static function getDBregistryLink(): array
    {
        $reglinks = [];
        $stmt = "SELECT userid, email, username, vorname, nachname, notiz, pwcode_endtime
            FROM site_users WHERE email LIKE '%dummy%' ORDER BY pwcode_endtime";
        $reglinks = Database::sendSQL($stmt, [], 'fetchall');
        return $reglinks;
    }


    /****************************
     * Summary of getDBlastAccess
     */
    protected static function getDBlastAccess(): array
    {
        $last_access = [];

        // letzter 'Fremd'-Log
        $stmt = "SELECT
                    (SELECT COUNT(*) FROM site_log) as ct,
                    id,
                    ip,
                    date
                FROM site_log
                WHERE ip NOT IN
                    (SELECT log.ip
                    FROM site_log AS log
                        JOIN site_login AS login ON log.ip = login.ip
                    GROUP BY log.ip)
                ORDER BY date DESC, id DESC
                LIMIT 1";

        $last_access = Database::sendSQL($stmt, [], 'fetch');

        return $last_access;
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
                    SELECT *
                    FROM site_users
                    WHERE email NOT LIKE '%dummy%' AND status='activated' ),

                -- ** Nutzerübersicht **
                cte_login AS (
                    SELECT userid uid, COUNT(login) AS ct_login
                    FROM site_login
                    WHERE login IS NOT NULL
                    GROUP BY uid ),

                cte_autologin AS (
                    SELECT userid uid, COUNT(autologin) AS ct_autologin
                    FROM site_login
                    WHERE login IS NOT NULL AND autologin IS NOT NULL
                    GROUP BY uid ),

                -- der erste Login
                cte_first AS (
                    SELECT
                        userid uid,
                        MIN(created) AS first_seen
                    FROM site_login
                    GROUP BY uid ),

                -- ermittelt max_created_date/ip pro user
                cte1_maxcreated AS (
                    SELECT *
                    FROM (
                        SELECT
                            userid uid,
                            ip,
                            MAX(created) OVER (PARTITION BY userid ORDER BY created DESC) max_created
                        FROM site_login
                    ) AS t
                    GROUP BY uid ),

                -- ermittelt max_changed_date/ip pro user
                cte2_maxchanged AS (
                    SELECT *
                    FROM (
                        SELECT
                            userid uid,
                            ip,
                            MAX(`changed`) OVER (PARTITION BY userid ORDER BY `changed` DESC) max_changed
                        FROM site_login
                    ) AS t
                    GROUP BY uid ),

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
                    WHERE userid = :userid AND
                        (login IS NOT NULL && autologin IS NOT NULL) AND
                        identifier != :ident ),

                -- meine abgemeldeten log=0, auto=1
                cte_count10 AS (
                    SELECT userid uid, COUNT(*) AS count10
                    FROM site_login
                    WHERE userid = :userid AND
                        (login IS NULL && autologin IS NOT NULL) AND
                        identifier != :ident ),

                -- meine beendeten (tot) log=0, auto=0  #AND identifier IS NOT NULL
                cte_count12 AS (
                    SELECT userid uid, COUNT(*) AS count12
                    FROM site_login
                    WHERE userid = :userid AND
                        (login IS NULL && autologin IS NULL) ),

                -- meine abgelaufenen (tot)
                cte_count13 AS (
                    SELECT userid uid, COUNT(*) AS count13
                    FROM site_login
                    WHERE userid = :userid AND token_endtime < NOW() ),


                -- ** autologin, andere **
                -- aktive autologins
                cte_count21 AS (
                    SELECT userid uid, COUNT(*) AS count21
                    FROM site_login
                    WHERE userid != :userid AND
                        (login IS NOT NULL && autologin IS NOT NULL) ),

                -- abgemeldeten log=0, auto=1
                cte_count20 AS (
                    SELECT userid uid, COUNT(*) AS count20
                    FROM site_login
                    WHERE userid != :userid AND
                        (login IS NULL && autologin IS NOT NULL) ),

                -- beendetet (tot) log=0, auto=0    #AND identifier IS NOT NULL
                cte_count22 AS (
                    SELECT userid uid, COUNT(*) AS count22
                    FROM site_login
                    WHERE userid != :userid AND
                        (login IS NULL && autologin IS NULL) ),

                -- abgelaufene (tot)
                cte_count23 AS (
                    SELECT userid uid, COUNT(*) AS count23
                    FROM site_login
                    WHERE userid != :userid AND token_endtime < NOW() ),

                -- identifier existiert / alle anderen autologins
                cte_count24 AS (
                    SELECT userid uid, COUNT(*) AS count24
                    FROM site_login
                    WHERE userid != :userid AND identifier IS NOT NULL ),

                -- identifier existiert / alle anderen User
                cte_count25 AS (
                    SELECT userid uid, COUNT(*) AS count25
                    FROM (
                        SELECT *
                        FROM site_login
                        WHERE userid != :userid AND identifier IS NOT NULL
                        GROUP BY userid
                    ) AS t )


                -- Abfrage der Werte aus den virtuellen CTE-Tabellen
                SELECT
                    userid, username, email, created, changed, su, status,
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

                ORDER BY username
                ";
        $data = [':userid' => $userid, ':ident' => $identifier];
        $user_list = Database::sendSQL($stmt, $data, 'fetchall');

        return $user_list;
    }


    /****************************
     * Summary of dataPreparation
     */
    public static function dataPreparation()
    {
        Tools::lastSite();

        // Seiten-Check okay, Seite starten
        if (self::$show_form):

        $userid = self::$userid;
        $identifier = self::$identifier;

        // --- TAB: Registrierung ---
        //
        $reglinks = self::getDBregistryLink();


        // --- TAB: Nutzer ---
        //
        $user_list = self::getDBuserList($userid, $identifier);


        // --- TAB: Autologin / Info ---
        //
        $last_access = self::getDBlastAccess();

        $count10 = $count11 = $count12 = $count13 = $count21 = $count20 = $count22 = $count23 = $count24 = $count25 = 0;
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
                if ($count11 < $user['count11']) $count11 = $user['count11'];
                // meine ausgeloggten
                if ($count10 < $user['count10']) $count10 = $user['count10'];
                // meine beendeten (tot)
                if ($count12 < $user['count12']) $count12 = $user['count12'];
                // meine abgelaufenen (tot)
                if ($count13 < $user['count13']) $count13 = $user['count13'];

            } else {
                // alle anderen aktiven
                if ($count21 < $user['count21']) $count21 = $user['count21'];
                // alle anderen ausgeloggten
                if ($count20 < $user['count20']) $count20 = $user['count20'];
                // alle anderen beendeten (tot)
                if ($count22 < $user['count22']) $count22 = $user['count22'];
                // alle anderen abgelaufenen (tot)
                if ($count23 < $user['count23']) $count23 = $user['count23'];
                // alle anderen Anmeldungen
                if ($count24 < $user['count24']) $count24 = $user['count24'];
                // alle anderen Nutzer
                if ($count25 < $user['count25']) $count25 = $user['count25'];
            }
        }

        self::$user_list = $user_list;
        self::$last_access = $last_access;
        self::$reglinks = $reglinks;
        self::$count10 = $count10;
        self::$count11 = $count11;
        self::$count12 = $count12;
        self::$count13 = $count13;
        self::$count20 = $count20;
        self::$count21 = $count21;
        self::$count22 = $count22;
        self::$count23 = $count23;
        self::$count24 = $count24;
        self::$count25 = $count25;

        endif;      # Seiten-Check okay
    }


    /****************************
     * Summary of formEvaluation
     * Änderungsformular empfangen, Eingaben verarbeiten
     */
    public static function formEvaluation()
    {
        $error_msg = self::$error_msg;
        $identifier = self::$identifier;
        $reglinks = self::$reglinks;
        $user_list = self::$user_list;
        $userid = $_SESSION['userid'];
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
            $reg_url = "https://www.danzigmarken.de/auth/register.php?code=".$reg_code;
            $reg_link = "register.php?code=".$reg_code;     // intern
            $input_usr = $reg_code;
            $input_email = $reg_code."@dummy.de";
            $status = $reg_code;
            $notiz = $reg_url;

            $stmt = "INSERT
                INTO site_users (username, email, status, pwcode_endtime, notiz)
                VALUES (:username, :email, :status, :pwcode_endtime, :notiz)";
            $data = [
                ':username' => $input_usr,
                ':email' => $input_email,
                ':status' => $status,
                ':pwcode_endtime' => $pwcode_endtime,
                ':notiz' => $notiz ];
            Database::sendSQL($stmt, $data);
            $success_msg = "Registrierungs-Link erzeugt.";
        break;

        case "show_mail":
            if (isset($_POST['regchoise'])) {
                $i = (int)$_POST['regchoise'] - 1;
                $to       = str_replace("_dummy_", "", $reglinks[$i]['email']);
                $subject = "Registrierungs-Link für www.danzigmarken.de";
                $mailcontent  = "".
                    "An: ".$to."<br>".
                    "Betreff: ".$subject."<br>".
                    "----------------------------------------<br>".
                    "Hallo ".$reglinks[$i]['vorname'].",<br>".
                    "du kannst dich jetzt auf www.danzigmarken.de registrieren. ".
                    "Rufe dazu in den nächsten 4 Wochen (bis zum ".date('d.m.y', $reglinks[$i]['pwcode_endtime']).") ".
                    "den folgenden Link auf: <br><a href='".$reglinks[$i]['notiz']."'>".$reglinks[$i]['notiz']."</a><br>".
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
                }
                else $error_msg = "Kann mich nicht selbst löschen.";
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


    /****************************
     * Summary of siteOutput
     */
    public static function siteOutput()
    {
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $output = "<div class='container main-container'>";
        if (!$show_form):
            $output .= $status_message;
        else:

        // Seiten-Check okay, Seite starten
        $active = self::$active;
        $usr_data = self::$usr_data;
        $user_list = self::$user_list;
        $login_data = self::$login_data;
        $last_access = self::$last_access;
        $reglinks = self::$reglinks;
        $count10 = self::$count10;
        $count11 = self::$count11;
        $count12 = self::$count12;
        $count13 = self::$count13;
        $count20 = self::$count20;
        $count21 = self::$count21;
        $count22 = self::$count22;
        $count23 = self::$count23;
        $count24 = self::$count24;
        $count25 = self::$count25;

        $out_token = "";
        $last_seen = "";

// TODO:
// Link hinzufügen [mail-log löschen]:
// kontakt/rate_limiting/login.php
// log-admin

/*******************************************/

        $output .= "<h2>erweiterte Einstellungen</h2><br>";
        $output .= $status_message;

    // -- START --
    //
    // -- Nav tabs --
    $output .= "<div>
    <ul class='nav nav-tabs' role='tablist'>
        <li role='presentation' class='".$active['info']."'><a href='#info' aria-controls='info' role='tab' data-toggle='tab'>Info</a></l>
        <li role='presentation' class='".$active['user']."'><a href='#user' aria-controls='user' role='tab' data-toggle='tab'>Nutzer</a></l>
        <li role='presentation' class='".$active['autologin']."'><a href='#autologin' aria-controls='autologin' role='tab' data-toggle='tab'>Autologin</a></li>
        <li role='presentation' class='".$active['regis']."'><a href='#regis' aria-controls='regis' role='tab' data-toggle='tab'>Reg-Links</a></li>
        <li role='presentation' class='".$active['sonst']."'><a href='#sonst' aria-controls='sonst' role='tab' data-toggle='tab'>Sonstiges</a></li>
    </ul>

    <div class='tab-content'>";


    // -- TAB: Info --
    $output .= "
    <div role='tabpanel' class='tab-pane ".$active['info']."' id='info'>
        <p><br>weitere Informationen:</p>";

    #htmlspecialchars($usr_data['status'], ENT_QUOTES)
    if ($usr_data['status'] === "activated") {
        $act = "aktiv";
    } elseif (!empty($usr_data['status'])) {
        $act = "Aktivierung ausstehend";
    } else {
        $act = "";
    }

    $changed = (!empty($usr_data['changed']))
        ? date('d.m.y H:i', strtotime($usr_data['changed']))
        : "";
    $endtime = (!empty($login_data['token_endtime']))
        ? date('d.m.y H:i', strtotime($login_data['token_endtime']))
        : "";
    $out_created = (!empty($login_data['created']))
        ? date('d.m.y H:i', strtotime($login_data['created']))
        : "";
    $out_ip = (!empty($login_data['ip']))
        ? htmlspecialchars($login_data['ip'], ENT_QUOTES)
        : "";
    $out_date = (!empty($last_access['date']))
        ? "&nbsp;".$last_access['ip']."<br>&nbsp;".date('d.m.y H:i', strtotime($last_access['date']))."<br>&nbsp;#".$last_access['ct']
        : "";
    $out_ident = (!empty($login_data['identifier']))
        ? htmlspecialchars($login_data['identifier'], ENT_QUOTES)
        : "";
    $autologin = (!empty($login_data['autologin']))
        ? "<tr><td>a.login:</td><td>".$login_data['autologin']."</td></tr>"
        : "";
    $auto = (!empty($login_data['autologin']))
        ? "*"
        : "";
    /*
    $out_token = (!empty($login_data['token_hash'])) ? htmlspecialchars($login_data['token_hash'], ENT_QUOTES) : "";
    $last_seen = (!empty($login_data['changed'])) ? date('d.m.y H:i', strtotime($login_data['changed'])) : "";
    */
    $output .= "

    <table>
        <tr><td>Nutzer:</td><td>".htmlspecialchars($usr_data['username'], ENT_QUOTES)."</td></tr>
        <tr><td>Email:</td><td>".htmlspecialchars($usr_data['email'], ENT_QUOTES)."</td></tr>
        <tr><td>erstellt:</td><td>".date('d.m.y H:i', strtotime($usr_data['created']))."</td></tr>
        <tr><td>geändert:</td><td>".$changed."</td></tr>
        <tr><td>Login{$auto}:</td><td>".$out_created."</td></tr>
        <tr><td>last.ip:</td><td>".$out_ip."</td></tr>";

    #$output .= $autologin;
    $output .= "
        <!--<tr><td>gültig bis:</td><td>".$endtime."</td></tr>-->
        <!--<tr><td>AutoIdent:</td><td>".$out_ident."</td></tr>-->
        <!--<tr><td>Token:</td><td>".$out_token."</td></tr>-->
        <!--<tr><td>last.seen:</td><td>".$last_seen."</td></tr>-->
        <tr><td>&nbsp;</td><td></td></tr>
        <tr><td>last.unknown:</td><td>".$out_date."</td></tr>
    </table>

    <br><hr>
    <form action='../tools/show_log' method='POST'>
        <button class='btn btn-primary' type=''>Log-Protokoll</button>&emsp;&emsp;
        <button formaction='../tools/maillog_show' class='btn btn-primary' type='' value='' name=''>Mail-Log</button>&emsp;&emsp;
        <button formaction='../tools/excel_down' class='btn btn-primary' type='' value='' name=''>Excel_Download</button>&emsp;&emsp;
        <button formaction='../tools/pdf_down' class='btn btn-primary' type='' value='' name=''>PDF_Download</button>&emsp;&emsp;
        <button formaction='../tools/printview.php?sub2=100' class='btn btn-primary' type='' value='' name=''>PDF anzeigen</button>&emsp;&emsp;
        <button formaction='https://www.danzigmarken.de/yiisite/web/index.php' class='btn btn-primary' type='' value='' name=''>Yii_Site</button>
    </form>
</div>
";
unset($changed, $endtime, $out_created, $out_ip, $out_date, $act);
// -- ende: Info --


// -- TAB: Nutzer --
$output .= "

<div role='tabpanel' class='tab-pane ".$active['user']."' id='user'>

<p><br></p>
<form action='?save=delete_user&tab=user' method='POST' class='form-horizontal'>
<div class='panel panel-default'>

<table class='table'>
    <tr>
        <!--
        <th>#</th>
        <th></th>
        <th>User</th>
        <th>Email</th>
        <th>seit</th>
        <th>update</th>
        <th>Login</th>
        <th>Auto</th>
        <th>first.seen</th>
        <th>last.seen</th>
        <th>last.ip</th>
        -->

        <th>#</th>
        <th></th>
        <th>User</th>
        <th>Email</th>
        <th>Login</th>
        <th>Auto</th>
        <th>last.seen</th>
        <th>last.ip</th>
    </tr>

    ";
    $ct = 0;
    foreach ($user_list AS $user) :
        /*
        if ($user['max_changed'] > $user['max_created']) {
            $user['last_seen'] = $user['max_changed'];
            $user['last_ip'] = $user['ip_changed'];
        } else {
            $user['last_seen'] = $user['max_created'];
            $user['last_ip'] = $user['ip_created'];
        };
        $last_seen = ($user['last_seen']) ? date('d.m.y H:i', strtotime($user['last_seen'])) : "";
        */
        $changed = (!empty($user['changed'])) ? date('d.m.y H:i', strtotime($user['changed'])) : "";
        $first_seen = (!empty($user['first_seen'])) ? date('d.m.y H:i', strtotime($user['first_seen'])) : "";
        $last_seen = (!empty($user['last_seen'])) ? date('d.m.y H:i', strtotime($user['last_seen'])) : "";
        $ct++;

        $output .= "

    <tr><td>".$ct."</td>
        <td><input type='radio'
                id='usr".$ct."' name='usrchoise' value='".$ct."' autocomplete='off' />
            <label for='usr".$ct."'></label></td>
        <!--
        <td>".$user['username']."</td>
        <td>".$user['email']."</td>
        <td>".date('d.m.y H:i', strtotime($user['created']))."</td>
        <td>".$changed."</td>
        <td>".$user['ct_login']."</td>
        <td>".$user['ct_autologin']."</td>
        <td>".$first_seen."</td>
        <td>".$last_seen."</td>
        <td>".$user['last_ip']."</td>
        -->

        <td>".$user['username']."</td>
        <td>".$user['email']."</td>
        <td>".$user['ct_login']."</td>
        <td>".$user['ct_autologin']."</td>
        <td>".$last_seen."</td>
        <td>".$user['last_ip']."</td>
    </tr>

    ";
    endforeach;
    $output .= "

</table>
</div>
<button type='submit' class='btn btn-primary' onclick='return confirm('Wirklich den Nutzer  - L Ö S C H E N -  ?')'>Nutzer löschen</button>
</form>
</div>
";
unset($ct, $user, $changed, $first_seen, $last_seen);
// -- ende: Nutzer --


// -- TAB: Autologin --
$output .= "

<div role='tabpanel' class='tab-pane ".$active['autologin']."' id='autologin'>
    <br>

    ";
    if ($count10 || $count11 || $count12 || $count13 || $count20 || $count21 || $count22 || $count23 || $count25):
        $output .= "

        <p>Die automatische Anmeldung beenden für:</p>
        <form action='?save=autologin&tab=autologin' method='POST' class='form-horizontal'>

        ";
        if ($count10 || $count11 || $count12 || $count13):
            $output .= "

            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log11' name='logout' value='11' autocomplete='off'>
                <label for='log11'> meine anderen aktiven (".$count11."x)</label>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log10' name='logout' value='10' autocomplete='off'>
                <label for='log10'> meine ausgeloggten (".$count10."x)<label>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log12' name='logout' value='12' autocomplete='off'>
                <label for='log12'> meine beendeten (tot) (".$count12."x)<label>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log13' name='logout' value='13' autocomplete='off'>
                <label for='log13'> meine abgelaufenen (tot) (".$count13."x)<label>
            </div>

            ";
        endif;

        if ($count20 || $count21 || $count22 || $count23 || $count25):
            $output .= "

            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log21' name='logout' value='21' autocomplete='off'>
                <label for='log21'><hr>alle anderen aktiven (".$count21."x)</label>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log20' name='logout' value='20' autocomplete='off'>
                <label for='log20'>alle anderen ausgeloggten (".$count20."x)</label>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log22' name='logout' value='22' autocomplete='off'>
                <label for='log22'>alle anderen beendeten (tot) (".$count22."x)</label>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log23' name='logout' value='23' autocomplete='off'>
                <label for='log23'>alle anderen abgelaufenen (tot) (".$count23."x)</label>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log24' name='logout' value='24' autocomplete='off'>
                <label for='log24'>alle anderen Anmeldungen (".$count24."x)</label>
            </div>
            <div class='col-sm-offset-2 col-sm-10'>
                <input type='radio' id='log25' name='logout' value='25' autocomplete='off'>
                <label for='log25'>alle anderen Nutzer (".$count25."x)</label>
            </div>

            ";
        endif;
        $output .= "

        <div class='col-sm-offset-2 col-sm-10'>
            <div>
                <br></br><button class='btn btn-primary' type='submit'>Beenden</button>
            </div>
        </div>
        </form>

        ";
    else:
        $output .= "

        <p>Keine anderen Autologins vorhanden.</p>

        ";
    endif;
    $output .= "

</div>
";
// -- ende: Autologin --


// -- TAB: Registrierung --
$output .= "

<div role='tabpanel' class='tab-pane ".$active['regis']."' id='regis'>

<form action='?save=make_reglink&tab=regis' method='POST' style='margin-top: 30px;'>
    <button type='submit' class='btn btn-primary btn-lg'>Link erzeugen</button>
</form>

";
if($reglinks):
    $output .= "

<p><br></p>
<form action='?save=delete_reg&tab=regis' method='POST' class='form-horizontal'>
<div class='panel panel-default'>

<table class='table'>
    <tr>
        <th>#</th>
        <th></th>
        <th>gültig bis</th>
        <th>Email</th>
        <th>Registrierungslink</th>
    </tr>

    ";
    $ct_reg = 0;
    foreach ($reglinks AS $link) :
        if (count($link) > 2 ) {
            // wenn RegLink vorhanden
            $reglinks_vorhanden = true;     // genutzt bei Button Ausgabe
            $ct_reg++;
            $out_radio = "<input type='radio' id='".$ct_reg."' name='regchoise' value='".$ct_reg."' autocomplete='off'>
                <label for='reg".$ct_reg."'></label>";
            $endtime = date('d.m.Y H:i', $link['pwcode_endtime']);
            $out_mail = str_replace("_dummy_", "", $link['email']);
            $out_link = "<<a href='".$link['notiz']."' target='blank'> link öffnen </a>>";

        } else {
            $out_radio = $endtime = $out_mail = $link['notiz'] = $out_link = "";
            $reglinks_vorhanden = false;
        }
        $output .= "

        <tr><td>".$ct_reg."</td>
            <td>".$out_radio."</td>
            <td>".$endtime."</td>
            <td>".$out_mail."</td>
            <td>".$out_link."</td>
        </tr>

        ";
    endforeach;
    $output .= "

</table>
</div>

    ";
    #$dummy = (isset($_GET['dummy']) && $_GET['dummy'] == 0) ? 1 : 0;

    if ($reglinks_vorhanden):
        $output .= "

        <button formaction='?save=show_mail&tab=regis' class='btn btn-primary' type='' value='' name=''>als Email anzeigen</button>&nbsp;&emsp;&emsp;
        <button type='submit' class='btn btn-primary'>Link löschen</button>&nbsp;&emsp;&emsp;

        ";
        if ($ct_reg > 1):
            $output .= "

            <button formaction='?save=delete_allregs&tab=regis' class='btn Xbtn-primary' type='' value='' name=''>alle Links löschen</button>

            ";
        endif;
        $output .= "

        </form>

        ";
    endif;
endif;
$output .= "</div>";
unset($ct_reg, $link, $reglinks_vorhanden, $endtime, $out_radio, $out_mail, $out_link);
// -- ende: Registrierung --


// -- TAB: Sonstiges --
$output .= "
<div role='tabpanel' class='tab-pane ".$active['sonst']."' id='sonst'>
    <p></p>";

$output .= "
        <h3>Viewportabmessungen</h3>
        <h4>Breite</h4>
        <p><a href=\"https://wiki.selfhtml.org/wiki/ClientWidth\">Element.clientWidth</a>:
            <span id=\"clientW\"></span>px</p>
        <p><a href=\"https://wiki.selfhtml.org/wiki/InnerWidth\">Window.innerWidth</a>:
            <span id=\"innerW\"></span>px</p>
        <p><a href=\"https://wiki.selfhtml.org/wiki/OuterWidth\">Window.outerWidth</a>:
            <span id=\"outerW\"></span>px</p>
        <h4>Höhe</h4>
        <p><a href=\"https://wiki.selfhtml.org/wiki/ClientHeight\">Element.clientHeight</a>:
            <span id=\"clientH\"></span>px</p>
        <p><a href=\"https://wiki.selfhtml.org/wiki/InnerHeight\">Window.innerHeight</a>:
            <span id=\"innerH\"></span>px</p>
        <p><a href=\"https://wiki.selfhtml.org/wiki/OuterHeight\">Window.outerHeight</a>:
            <span id=\"outerH\"></span>px</p>
        <h3>Geräteabmessungen</h3>
        <h4>Breite</h4>
        <p><a href=\"https://wiki.selfhtml.org/wiki/JavaScript/Screen/width\">Screen.width</a>:
            <span id=\"screenW\"></span>px</p>
        <p><a href=\"https://wiki.selfhtml.org/wiki/availWidth\">Screen.availWidth</a>:
            <span id=\"availW\"></span>px</p>
        <h4>Höhe</h4>
        <p><a href=\"https://wiki.selfhtml.org/wiki/JavaScript/Screen/height\">Screen.height</a>:
            <span id=\"screenH\"></span>px</p>
        <p><a href=\"https://wiki.selfhtml.org/wiki/availHeight\">Screen.availHeight</a>:
            <span id=\"availH\"></span>px</p>
        <!--
        <p><a href=\"https://wiki.selfhtml.org/wiki/JavaScript/Window/matchMedia\">matchMedia</a>:
            <span id=\"matcMedia\"></span></p> -->
";

$output .= "</div>";  # -- Sonstiges.tab-pane --

$output .= "
<script>
'use strict';
document.addEventListener(\"DOMContentLoaded\", function () {
    document.addEventListener('resize', messen);
    messen();

    function messen() {
        document.getElementById('clientW')
            .textContent = document.querySelector('html')
            .clientWidth;
        document.getElementById('innerW')
            .textContent = window.innerWidth;
        document.getElementById('outerW')
            .textContent = window.outerWidth;
        document.getElementById('clientH')
            .textContent = document.querySelector('html')
            .clientHeight;
        document.getElementById('innerH')
            .textContent = window.innerHeight;
        document.getElementById('outerH')
            .textContent = window.outerHeight;
        document.getElementById('screenW')
            .textContent = screen.width;
        document.getElementById('availW')
            .textContent = screen.availWidth;
        document.getElementById('screenH')
            .textContent = screen.height;
        document.getElementById('availH')
            .textContent = screen.availHeight;

        document.getElementById('matchMedia')
            .textContent = window.matchMedia().media;
    }
});
</script>";

unset($changed, $endtime, $out_created, $out_ip, $out_date, $act);
// -- ende: Sonstiges --


$output .= "</div> ";  # -- tab-content --
$output .= "</div> ";  # -- ende: START --

/*
<div style='
    display: grid;
    justify-content: center;
    padding: 30px 30px;
    '>
<a href='https://www.worldflagcounter.com/details/iMx'><img src='https://www.worldflagcounter.com/iMx/' alt='Flag Counter'></a>
*//*
https://www.worldflagcounter.com/iMx/
https://www.worldflagcounter.com/details/iMx
https://www.worldflagcounter.com/regenerate/iMx
O1nJ8Z5bY
*//*
</div>
*/

#$output .= "</div>";  # -- main-container --

endif;                # showForm
$output .= "</div>";  # -- container main-container --


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }


    /**
     * Java Script zur Steuerung der Tab-Navigation
     * --> ans Ende der Webseite hängen
     */
    public static function lastScriptAusgeben()
    {
        $output = "

<script>

// href mit ?Parameter und #Sprungmarke
// -> class = \"anchor_extended\"
//
window.addEventListener(\"load\", function () {

// Falls der Browser nicht automatisch zum gewünschten Element springt
// erledigt das Javascript.
if (window.location.hash)
    window.location.href = window.location.hash;

// Die Steuerelemente, welche den Mechanismus auslösen sollen, werden selektiert,
// sie müssen via class=\"anchor_extended\" ausgezeichnet werden.
var anchors = document.getElementsByClassName(\"anchor_extended\");

for (var i = 0; i < anchors.length; i++) {
    anchors[i].addEventListener(\"click\", function (event) {
        // Prevent the anchor to perform its href-jump.
        event.preventDefault();
        // Variablen vordefinieren.
        var target = {},
        current = {}
        path = window.location.origin;

        // URL und Hash des Ziels extrahieren. Unterschieden wird zwischen a-Tag's dessen href
        // ausgelesen wird und anderen Elementen (wie z.B. div), bei denen auf das data-href=\"\"-Attribut
        // zugegriffen wird. Für den 2. Fall benötigen wir die eben definierte path-Variable
        // welche den absoluten Pfad enthält.
        target.href = this.href ? this.href.split(\"#\") : (path + this.dataset.href).split(\"#\");
        target.url = target.href.length > 2 ? target.href.slice(0, -1).join(\"#\") : target.href[0];
        target.hash = target.href.length > 1 ? target.href[target.href.length - 1] : \"\";

        // URL und Hash der aktuellen Datei.
        current.url = window.location.href.split(\"#\").slice(0, -1).join(\"#\");
        current.hash = window.location.hash;

        if (current.url == target.url)
            if (current.hash == target.hash)
                // Dateiname und Hash sind identisch, die Seite
                // wird lediglich neu geladen.
                window.location.reload();
            else {
                // Der Hash unterscheidet sich, dem location-Objekt
                // wird dieser zugeteilt, anschließend wird die Seite
                // neu geladen.
                window.location.hash = target.hash;
                window.location.reload();
            }
        else
            // Der Dateiname unterscheidet sich, _GET-Daten wurden geändert
            // oder eine andere Datei soll aufgerufen werden, es wird lediglich
            // auf diese Datei verwiesen.
            window.location.href = this.href;
    });
}

});
</script>

        ";     // ende von $output

        echo $output;
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