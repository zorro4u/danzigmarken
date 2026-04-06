<?php
namespace Dzg\SitePrep;
use Dzg\Tools\{Database, Auth, Tools, CheckIP};

require_once __DIR__.'/../tools/loader_tools.php';
require_once __DIR__.'/../tools/checkip.php';


/****************************
 * Summary of Admin
 * class A extends B implements C
 */
class Admin
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $error_msg;
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