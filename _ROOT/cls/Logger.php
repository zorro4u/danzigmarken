<?php
namespace Dzg;

require_once __DIR__."/Database.php";

use Dzg\Database;
use PDO, PDOException;


/***********************
 * Summary of Logger
 */
class Logger
{
    /***********************
     * Summary of log
     * wird in Footer.php aufgerufen
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

        $ip = $_SERVER['REMOTE_ADDR'];
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

        $stmt= "INSERT INTO site_log (ip, browser, lang, accept, platform, mobile, ua, cache,
            url, referer, userid, login_id, identifier)
            SELECT :ip, :browser, :lang, :accept, :platform, :mobile, :ua, :cache,
            :url, :referer, :userid, :login, :ident
            WHERE :ip NOT IN (SELECT ip FROM site_login WHERE userid = 2 GROUP BY ip)
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
            ":ident"    => $server[22] ];
        Database::sendSQL($stmt, $data);

    }


    /***********************
     * Summary of show
     *
     * das Log-Protokoll anzeigen, ggf kürzen
     * wird als Button in admin.php aufgerufen
     * wurde in dzg/inc/logged.inc.php via functions/Footer.php angelegt
     */
    public static function show()
    {
        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!isset($_SESSION['userid'])) {
            header("location: /auth/login.php");
            exit;
        }

        $out = "";

        // nur bei Seitenaufruf über admin.php anzeigen
        //
        if (isset($_SERVER['HTTP_REFERER'])
            && basename($_SERVER['HTTP_REFERER']) === "admin"):

            // nur die letzten x Einträge zeigen
            $cut = 300;

            // Logs ohne userid=2 (admin)
            $stmt1 =
                "SELECT ip, date, url, referer
                FROM site_log WHERE

                -- admin logins finden
                ip NOT IN
                (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON log.ip = login.ip
                WHERE login.userid = 2 GROUP BY log.ip)

                -- nur manipulierte Zugriffe anzeigen
                -- AND url like '%\%%'

                -- den neuesten Eintrag als erstes zeigen
                ORDER BY id DESC
                LIMIT :cut";

            // IPs der registr. User im Log
            $stmt2 = "SELECT ip FROM site_log
                WHERE ip IN (SELECT ip FROM site_login)
                GROUP BY ip ORDER BY ip";

            $data1 = [":cut" => $cut,];  # int
            $results = Database::sendSQL($stmt1, $data1, 'fetchall');
            $results2 = Database::sendSQL($stmt2, [], 'fetchall', 'num');

            // wenn Log-Einträge
            if (!empty($results)):

                // Array-Anpassung [[],[]] -> [,]
                $login_ips = [];
                if (!empty($results)) {
                    foreach ($results2 AS $entry) {
                        $login_ips []= $entry[0];
                    }
                }

                // Anzahl der Log-Einträge
                $ct = count($results);

                // nur die letzten x Einträge zeigen
                #$cut = 10;
                if ($ct > $cut) {
                    for ($i=0; $i<$cut; $i++) {
                        $show []= $results[$i];
                    }
                } else {
                    $show = $results;
                }

                // Log-Protokoll anzeigen
                foreach($show AS $entry):
                    $out .= "<table>";
                    foreach($entry AS $key=>$value) {
                        if ($key === "id" or $key === "created") continue;
                        if ($key === "referer" && empty($value)) continue;

                        $style0 = $style1 = "";

                        // färbt die IPs der registr. User
                        if ($key === "ip" &&
                            in_array($value , $login_ips))
                        {
                            $style1 = "color:darkgreen;";
                        }

                        // manipulierter URL-Aufruf
                        if ($key === "url" &&
                            str_contains($value, "%"))
                        {
                            $style0 = $style1 = "color:red;";
                            $value = urldecode($value);
                        }

                        $out .= "<tr><td style='{$style0}'>{$key}: </td><td style='{$style1}'>{$value}</td></tr>";
                    }
                    #$out .= "<tr><td colspan=2 style=''></td></tr>";
                    $out .= "</table>";
                    $out .= "<hr>";
                endforeach;


                // abschließend: Einträge älter als 200 Tage löschen, oder userid=2 (admin)
                $time_cut = date("Y-m-d H:i:s", (time() - 3600*24*200));
                $stmt = "DELETE FROM site_log WHERE date < :cut";

                // 'admin' Abfrage dauert ca. 50sec !
                # OR ip IN (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON log.ip=login.ip WHERE login.userid = 2 GROUP BY log.ip)

                $data = [":cut" => $time_cut];  # string
                Database::sendSQL($stmt, $data);

            else:
                $out .= "nichts...";
            endif;

        else:
            $out .= "Du hast den 'rechten' Pfad verlassen und wirst hier nix sehen.";
        endif;


        /*
        // Logs ohne userid=2 (admin)
        SELECT * FROM site_log WHERE ip NOT IN (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON login.ip=log.ip WHERE login.userid=2 GROUP BY log.ip)

        // aus dem Log IPs von userid=2 (admin) löschen
        SELECT * FROM site_log WHERE ip IN (SELECT ip FROM site_login WHERE userid=2)
        DELETE FROM site_log WHERE ip IN (SELECT ip FROM site_login WHERE userid=2)

        // die IPs der registrierten User
        SELECT * FROM site_log WHERE ip IN (SELECT ip FROM site_login)
        SELECT * FROM site_log WHERE ip IN (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON login.ip=log.ip GROUP BY log.ip)

        // Log der Unbekannten IPs
        SELECT * FROM site_log WHERE ip NOT IN (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON login.ip=log.ip GROUP BY log.ip)

        // zählt die unbekannten IPs
        SELECT count(*) FROM (SELECT ip FROM site_log WHERE ip NOT IN (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON login.ip=log.ip GROUP BY log.ip) GROUP BY ip) as ct

        // zählt die unbekannten Anfragen
        SELECT count(*) FROM (SELECT ip FROM site_log WHERE ip NOT IN (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON login.ip=log.ip GROUP BY log.ip)) as ct
        */

        $output = "
        <!DOCTYPE html>
        <html lang=\"de\">
        <head>
            <meta charset=\"utf-8\">
            <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">

            <meta http-equiv=\"cache-control\" content=\"must-revalidate, no-store\" >
            <meta http-equiv=\"expires\" content=\"0\" >
            <meta http-equiv=\"language\" content=\"DE\">

            <meta name=\"robots\" content=\"noindex, nofollow, noimageindex, max-snippet:200, max-image-preview:standard, unavailable_after:2040-12-31\">
            <meta name=\"google\" content=\"nopagereadaloud\" >

            <title>log-File</title>

        <style>
        </style>

        </head>
        <body>

        {$out}

        </body>
        </html>
        ";

        echo $output;
    }

}