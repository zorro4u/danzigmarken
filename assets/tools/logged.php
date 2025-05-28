<?php
session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

header('Content-type: text/html; charset=utf-8');

// das Log-Protokoll anzeigen, ggf kürzen
// wird in admin.php aufgerufen
// wurde in assets/inc/logged.inc.php via functions/Footer.php angelegt

#require_once __DIR__.'/../auth/includes/auth.database.func.php';     // log-Database: $pdo
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Database.php';     // log-Database

// Nutzer nicht angemeldet? Dann weg hier ...
if (!isset($_SESSION['userid'])) {
    header("location: /auth/login.php");
    exit;
}

// nur bei Seitenaufruf über admin.php anzeigen
//
if (isset($_SERVER['HTTP_REFERER']) && basename($_SERVER['HTTP_REFERER']) === "admin") {
    // nur die letzten x Einträge zeigen
    $cut = 300;

    // Log-Protokoll holen
    #$stmt = "SELECT * FROM site_log ORDER BY id DESC";

    // Logs ohne userid=2 (admin)
    $stmt = "SELECT ip, date, url, referer FROM site_log WHERE ip NOT IN

        -- admin logins finden
        (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON log.ip = login.ip WHERE login.userid = 2 GROUP BY log.ip)

        -- den neuesten Eintrag als erstes zeigen
        ORDER BY id DESC
        LIMIT :cut";

    // IPs der reg. User im Log
    $stmt2 = "SELECT ip FROM site_log WHERE ip IN (SELECT ip FROM site_login) GROUP BY ip ORDER BY ip";

    try {
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":cut", $cut, PDO::PARAM_INT);
        $qry->execute();
        $results = $qry->fetchAll(PDO::FETCH_ASSOC);

        $qry = $pdo->query($stmt2);
        $results2 = $qry->fetchAll(PDO::FETCH_NUM);
    } catch(PDOException $e) {die($e->getMessage());}

    // wenn Log-Einträge
    if (!empty($results)) {

        // Array-Anpassung [[],[]] -> [,]
        if (!empty($results)) {
            $login_ips = [];
            foreach ($results2 AS $entry) {$login_ips []= $entry[0];
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
            $cut = $ct;
        }

        // Log-Protokoll anzeigen
        foreach($show AS $entry) {
            echo '<table>';
            foreach($entry AS $key=>$value) {
                if ($key === 'id' or $key === 'created') continue;
                if ($key === 'referer' && empty($value)) continue;
                #var_dump($key, $value);echo'<br>';

                // färbt die IPs der registr. User
                $value_formated ='<td>'.$value.'</td>';
                if ($key === 'ip'){
                    if (in_array($value , $login_ips))
                        $value_formated = '<td style="color:darkgreen;">'.$value.'</td>';
                }
                echo '<tr><td>'.$key.': </td>'.$value_formated.'</tr>';
            }
            echo '</table>';
            echo '<hr>';
        }

        // abschließend: Einträge älter als 180 Tage löschen, oder userid=2 (admin)
        $time_cut = date('Y-m-d H:i:s', (time() - 3600*24*180));
        $stmt = "DELETE FROM site_log WHERE
            date < :cut OR
            ip IN (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON log.ip=login.ip WHERE login.userid = 2 GROUP BY log.ip)";

        try {
            $qry = $pdo->prepare($stmt);
            $qry->bindParam(":cut", $time_cut, PDO::PARAM_STR);
            $qry->execute();
        } catch(PDOException $e) {die($e->getMessage().': logged');}

    } else echo 'nichts...';
} else {
    echo "Du hast den 'rechten' Pfad verlassen und wirst hier nix sehen.";
}


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

