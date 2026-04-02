<?php
namespace Dzg\Tools;

require_once __DIR__."/database.php";
require_once __DIR__."/checkip.php";


/**
 * Log Daten in DB schreiben
 */
class Logger
{
    /**
     * in Log schreiben
     * wird in footer.php aufgerufen
     */
    public static function log()
    {
        // Test auf Heimnetz
        $remaddr = $_SERVER['REMOTE_ADDR'];
        $addr = substr($remaddr, 0, 11);
        $home = ['X-192.168.10.', '192.168.11.'];
        foreach ($home AS $chk)
            $homechk = ($addr == $chk) ? True : False;

        // Test auf index.php
        $mainpages = ['index.php', 'index2.php'];
        $mainpagechk = (in_array(basename($_SERVER['PHP_SELF']), $mainpages)) ? True : False;

        // logge wenn: auswärts und nicht admin
        // und nicht angemeldet: && !isset($_SESSION['loggedin'])
        // und nur index-Seiten: && $mainpagechk===true
        #if ($homechk === False  && !isset($_SESSION['su'])) {

        $ip = CheckIP::getIP();
        #$ip = $_SERVER['REMOTE_ADDR'];
        #$datum = date('Y-m-d H:i:s');  // wird in der DB gesetzt
        #$remote = getenv("REMOTE_ADDR");
        #$host = getHostByAddr($remote);

        $server[0] = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : NULL;
        $server[1] = isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? $_SERVER["HTTP_ACCEPT_LANGUAGE"] : NULL;
        $server[2] = isset($_SERVER["HTTP_ACCEPT"]) ? $_SERVER["HTTP_ACCEPT"] : NULL;
        $server[3] = isset($_SERVER["HTTP_SEC_CH_UA_PLATFORM"]) ? $_SERVER["HTTP_SEC_CH_UA_PLATFORM"] : NULL;
        $server[4] = isset($_SERVER["HTTP_SEC_CH_UA_MOBILE"]) ? $_SERVER["HTTP_SEC_CH_UA_MOBILE"] : NULL;
        $server[5] = isset($_SERVER["HTTP_SEC_CH_UA"]) ? $_SERVER["HTTP_SEC_CH_UA"] : NULL;
        $server[6] = isset($_SERVER["HTTP_CACHE_CONTROL"]) ? $_SERVER["HTTP_CACHE_CONTROL"] : NULL;
        $server[7] = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : NULL;
        $server[8] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL;

        $server[20] = isset($_SESSION['userid']) ? $_SESSION['userid'] : NULL;
        $server[21] = isset($_SESSION['login_id']) ? $_SESSION['login_id'] : NULL;
        $server[22] = isset($_SESSION['ident']) ? $_SESSION['ident'] : NULL;

        /*
        $stmt = "INSERT INTO site_log
            (ip, browser, lang, accept, platform, mobile, ua, cache, url, userid, autoident)
            VALUES (:ip, :browser, :lang, :accept, :platform, :mobile, :ua, :cache, :url, :userid, :autoident)";*/

        # "INSERT INTO site_log (browser) SELECT 'TEST'
        # WHERE 0 IN (SELECT COUNT(*) FROM (SELECT ip FROM site_login WHERE userid = 2 AND ip = :ip GROUP BY ip) AS t);";

        // einfügen, wenn ip nicht Admin (userid=2) gehört
        // "insert into .. select .. where .."
        // bei MariaDB.rainbow (10.11.6) geht's
        // bei MariaDB.NAS (10.3.29) geht's nicht, da nur "-> .. select .. from .. where" (was hier nicht hilft)

        $stmt=
        "INSERT INTO site_log
            (ip, browser, lang, accept, platform, mobile, ua, cache,
            `url`, referer, userid, login_id, identifier)
        SELECT :ip, :browser, :lang, :accept, :platform, :mobile,
            :ua, :cache, :url, :referer, :userid, :login, :ident
        WHERE :ip NOT IN
            (SELECT ip FROM site_login WHERE userid=2 GROUP BY ip)
        ";

        $data = [
            ":ip"       => $ip,
            ":browser"  => $server[0],
            ":lang"     => $server[1],
            ":accept"   => $server[2],
            ":platform" => $server[3],
            ":mobile"   => $server[4],
            ":ua"       => $server[5],
            ":cache"    => $server[6],
            ":url"      => $server[7],
            ":referer"  => $server[8],
            ":userid"   => $server[20],   # int
            ":login"    => $server[21],   # int
            ":ident"    => $server[22]
            ];
        Database::sendSQL($stmt, $data);


        /*
        // manipulierter URL-Aufruf, schon im Header/antiflood
        if (str_contains($data[':url'], "%")) {
            $ipc = new CheckIP("clear");
            $ipc->under_suspicion($data[':ip']);
            $ipc = null;
        };*/
    }


    /**
     * log-Einträge löschen
     */
    public static function delete_logs()
    {
        // erst 'admin' Einträge löschen, dauert länger :-/
        $stmt =
        "DELETE FROM site_log
        WHERE ip IN
            (SELECT log.ip
            FROM site_log AS `log`
            JOIN site_login AS `login` ON log.ip=login.ip
            WHERE login.userid=2
            GROUP BY log.ip) ";
        #Database::sendSQL($stmt, []);

        // behält nur die x-neuesten Log-Einträge
        $keep = 10**4;  # 10-tausend
        $stmt = "SELECT count(*)-{$keep} FROM site_log";
        $del  = Database::sendSQL($stmt,[],'fetch','num')[0];

        if($del > 0) {
            $stmt = "DELETE FROM site_log ORDER BY id LIMIT {$del}";
            Database::sendSQL($stmt, []);
        };

        // Einträge älter als 300 Tage löschen
        #$time_cut = date("Y-m-d H:i:s", (time() - 3600*24*300));
        #$data = [":cut" => $time_cut];  # string
        #$stmt = "DELETE FROM site_log WHERE date < :cut";
        #Database::sendSQL($stmt, $data);
    }
}