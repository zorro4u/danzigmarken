<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class Admin
{
    /**
     * Summary of getDBregistryLink
     */
    public static function getDBregistryLink(): array
    {
        $stmt = "SELECT userid, email, username, vorname, nachname, notiz, pwcode_endtime
            FROM site_users
            WHERE email LIKE '%dummy%'
            ORDER BY pwcode_endtime";

        return Database::sendSQL($stmt, [], 'fetchall');
    }


    /**
     * Summary of getDBuserList
     */
    public static function getDBuserList($userid, $identifier): array
    {
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

        return Database::sendSQL($stmt, $data, 'fetchall');
    }


    /**
     *
     */
    public static function get_log_data(): array
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




    /**
     * 11, alle meine anderen aktiven Anmeldungen
     */
    public static function deleteMyActive($userid, $identifier)
    {
        $data = [':userid' => $userid, ':ident' => $identifier];
        $stmt = "UPDATE site_login
            SET `login`=NULL, autologin=NULL
            WHERE userid=:userid
                AND (login IS NOT NULL && autologin IS NOT NULL)
                AND identifier != :ident";
        #$stmt = "DELETE FROM site_login WHERE userid = :userid AND (login IS NOT NULL && autologin IS NOT NULL) AND identifier != :ident";
        Database::sendSQL($stmt, $data);
    }


    /**
     * 10, alle meine ausgeloggten Anmeldungen
     */
    public static function deleteMyNoActive($userid, $identifier)
    {
        $data = [':userid' => $userid, ':ident' => $identifier];
        $stmt = "UPDATE site_login
            SET `login`=NULL, autologin=NULL
            WHERE userid=:userid
                AND (login IS NULL && autologin IS NOT NULL)
                AND identifier != :ident";
        Database::sendSQL($stmt, $data);
    }


    /**
     * 12, alle meine beendeten Anmeldungen (tot)
     */
    public static function deleteMyClosed($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "DELETE FROM site_login
            WHERE userid=:userid
            AND (login IS NULL && autologin IS NULL)";
        Database::sendSQL($stmt, $data);
    }


    /**
     * 13, alle meine abgelaufenen Anmeldungen (tot)
     */
    public static function deleteMyOld($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "DELETE FROM site_login
            WHERE userid=:userid
            AND token_endtime < NOW()";
        Database::sendSQL($stmt, $data);
    }


    /**
     * 21, alle anderen aktiven
     */
    public static function deleteActive($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "UPDATE site_login
            SET `login`=NULL
            WHERE userid != :userid
                AND (login IS NOT NULL && autologin IS NOT NULL)";
        Database::sendSQL($stmt, $data);
    }


    /**
     * 20, alle anderen ausgeloggten Anmeldung
     */
    public static function deleteNoActive($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "UPDATE site_login
            SET `login`=NULL, autologin=NULL
            WHERE userid != :userid
                AND (login IS NULL && autologin IS NOT NULL)";
        Database::sendSQL($stmt, $data);
    }


    /**
     * 22, alle anderen beendeten Anmeldung (tot)
     */
    public static function deleteClosed($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "DELETE FROM site_login
            WHERE userid != :userid
                AND (login IS NULL && autologin IS NULL)";
        #AND identifier IS NOT NULL
        Database::sendSQL($stmt, $data);
    }


    /**
     * 23, alle anderen abgelaufenen (tot)
     */
    public static function deleteOld($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "DELETE FROM site_login
            WHERE userid != :userid
                AND token_endtime < NOW()";
        Database::sendSQL($stmt, $data);
    }


    /**
     * 24/25, alle anderen Nutzer
     */
    public static function deleteOtherAutologin($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "UPDATE site_login
            SET `login`=NULL, autologin=NULL
            WHERE userid != :userid
                AND identifier IS NOT NULL";
        Database::sendSQL($stmt, $data);
    }


    public static function storeRegCode($data)
    {
        $stmt =
            "INSERT INTO site_users
            (username, email, `status`, pwcode_endtime, notiz)
            VALUES
            (:username, :email, :status, :pwcode_endtime, :notiz) ";
        Database::sendSQL($stmt, $data);
    }


    public static function deleteRegLink($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "DELETE FROM site_users WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    public static function deleteAllRegLink()
    {
        // DB aufräumen, VACUUM;
        // DB Integritätsprüfung: PRAGMA integrity_check;
        // Zähler zurücksetzen:
        // "DELETE FROM sqlite_sequence WHERE name = '{tab_name}'"  # autoincrement zurücksetzen
        $stmt = "DELETE FROM site_users WHERE email LIKE '%dummy%'";
        Database::sendSQL($stmt, []);
    }


    public static function deleteUser($userid)
    {
        $data = [':userid' => $userid];        # int
        $stmt = "UPDATE site_users
            SET `status`='deaktiv'
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);

        // wenn Variante 'deaktiv setzen', dann auch alle Anmeldungen löschen,
        // (sonst bei DELETE automatisch per Verknüpfung gelöscht).
        self::deleteUserLogin($userid);
    }


    public static function deleteUserLogin($userid)
    {
        $data = [':userid' => $userid];        # int
        $stmt = "DELETE FROM site_login WHERE userid = :userid";
        Database::sendSQL($stmt, $data);
    }
}


// EOF