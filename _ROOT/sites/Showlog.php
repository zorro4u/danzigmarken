<?php
namespace Dzg\Sites;
use Dzg\Tools\{Database, CheckIP, Logger};

require_once __DIR__."/../tools/database.php";
require_once __DIR__."/../tools/checkip.php";
require_once __DIR__."/../tools/logger.php";


class ShowLog
{
    /**
     * das Log-Protokoll anzeigen, ggf kürzen
     * wird als Button in admin.php aufgerufen
     * wurde in footer.php angelegt
     */
    public static function show(): void
    {
       // Nutzer nicht angemeldet? Dann weg hier ...
        if (!isset($_SESSION['userid'])) {
            header("location: /auth/login.php");
            exit;
        };

        // Error-Log IPs in Blackliste eintragen
        #CheckIP::write_errorlog_into_DB();
        CheckIP::write_DB_into_htaccess();

        // nur einmalig zu Service-Zwecke
        #CheckIP::write_htaccess_into_DB();
        #CheckIP::clear_htaccess_ip();


        $out = "";
        $show = [];
        $suspect = [];

        // nur bei Seitenaufruf über admin.php anzeigen
        //
        if (isset($_SERVER['HTTP_REFERER'])
            && basename($_SERVER['HTTP_REFERER']) === "admin"):

            // nur die letzten x Einträge zeigen
            $cut = 2000;

            // Logs ohne userid=2 (admin)
            $stmt1 = "WITH
            -- admin logins finden
            admin_ip AS (
                SELECT log.ip
                FROM site_log AS log
                JOIN site_login AS login ON log.ip = login.ip
                WHERE login.userid=2
                GROUP BY log.ip)

            SELECT ip, `date`, `url`, referer
            FROM site_log
            WHERE ip NOT IN (SELECT * FROM admin_ip)

            -- nur manipulierte Zugriffe anzeigen
            -- AND url like '%\%%'

            -- den neuesten Eintrag als erstes zeigen
            ORDER BY id DESC
            LIMIT :cut ";

            // IPs der registr. User im Log
            $stmt2 =
                "SELECT ip FROM site_log
                WHERE ip IN (SELECT ip FROM site_login)
                GROUP BY ip
                ORDER BY ip ";

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
                    };
                };

                // Anzahl der Log-Einträge
                $ct = count($results);

                // nur die letzten x Einträge zeigen
                #$cut = 10;
                if ($ct > $cut) {
                    for ($i=0; $i<$cut; $i++) {
                        $show []= $results[$i];
                    };
                } else {
                    $show = $results;
                };

                // Log-Protokoll anzeigen
                foreach($show AS $entry):
                    $out .= "<table>";
                    $ip = '';
                    foreach($entry AS $key=>$value) {
                        if ($key === "id") continue;
                        if ($key === "created") continue;
                        if ($key === "referer") continue;
                        #if ($key === "referer" && empty($value)) continue;

                        $style0 = $style1 = "";

                        if($key === "ip") {
                            $ip = $value;
                        };

                        // färbt die IPs der registr. User
                        if ($key === "ip" &&
                            in_array($value , $login_ips))
                        {
                            $style1 = "color:darkgreen;";
                        };

                        // manipulierter URL-Aufruf
                        if ($key === "url"
                            && (str_contains($value, "%") ||
                                str_contains($value, "//")))
                        {
                            $style0 = $style1 = "color:red;";
                            $value = urldecode($value);

                            $suspect []= $ip;
                        };

                        $out .= "<tr><td style='{$style0}'>{$key}: </td><td style='{$style1}'>{$value}</td></tr>";
                    };
                    #$out .= "<tr><td colspan=2 style=''></td></tr>";
                    $out .= "</table>";
                    $out .= "<hr>";
                endforeach;

                // abschließend alte DB-Einträge löschen
                Logger::delete_logs();

            else:
                $out .= "nichts...";
            endif;

        else:
            $out .= "Du hast den 'rechten' Pfad verlassen und wirst hier nix sehen.";
        endif;

        // wird schon im Header/antiflood gemacht
        if($suspect) {
            $ipc = new CheckIP("clear");
            foreach($suspect as $ip) {
            #    $ipc->under_suspicion($ip);
            };
        };


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


        // HTML Ausgabe
        //
        echo $output;
    }
}


// EOF