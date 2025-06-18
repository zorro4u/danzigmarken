<?php
session_start();
date_default_timezone_set("Europe/Berlin");
error_reporting(E_ERROR | E_PARSE);

#header("Content-type: text/html; charset=utf-8");

// das Log-Protokoll anzeigen, ggf kürzen
// wird in admin.php aufgerufen
// wurde in dzg/inc/logged.inc.php via functions/Footer.php angelegt

#require_once __DIR__."/../auth/includes/auth.database.func.php";     // log-Database: $pdo
require_once $_SERVER["DOCUMENT_ROOT"]."/../data/dzg/cls/Database.php";     // log-Database
use Dzg\Cls\Database;

// Nutzer nicht angemeldet? Dann weg hier ...
if (!isset($_SESSION['userid'])) {
    header("location: /auth/login.php");
    exit;
}

$pdo = Database::connect_mariadb();

// nur bei Seitenaufruf über admin.php anzeigen
//
if (isset($_SERVER['HTTP_REFERER']) && basename($_SERVER['HTTP_REFERER']) === "admin") {
    $out = "";

    // nur die letzten x Einträge zeigen
    $cut = 300;

    // Logs ohne userid=2 (admin)
    $stmt =
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


    try {
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":cut", $cut, PDO::PARAM_INT);
        $qry->execute();
        $results = $qry->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {die($e->getMessage());}

    try {
        $qry = $pdo->query($stmt2);
        $results2 = $qry->fetchAll(PDO::FETCH_NUM);
    } catch(PDOException $e) {die($e->getMessage());}


    // wenn Log-Einträge
    if (!empty($results)) {

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
        foreach($show AS $entry) {
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
        }


        // abschließend: Einträge älter als 200 Tage löschen, oder userid=2 (admin)
        $time_cut = date("Y-m-d H:i:s", (time() - 3600*24*200));
        $stmt = "DELETE FROM site_log WHERE date < :cut";

        // 'admin' Abfrage dauert ca. 50sec !
        # OR ip IN (SELECT log.ip FROM site_log AS log JOIN site_login AS login ON log.ip=login.ip WHERE login.userid = 2 GROUP BY log.ip)

        try {
            $qry = $pdo->prepare($stmt);
            $qry->bindParam(":cut", $time_cut, PDO::PARAM_STR);
            $qry->execute();
        } catch(PDOException $e) {die($e->getMessage().": logged");}


    } else $out .= "nichts...";

} else {
    $out .= "Du hast den 'rechten' Pfad verlassen und wirst hier nix sehen.";
}

$pdo = null;


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

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta http-equiv="cache-control" content="must-revalidate, no-store" >
    <meta http-equiv="expires" content="0" >
    <meta http-equiv="language" content="DE">

    <meta name="robots" content="noindex, nofollow, noimageindex, max-snippet:200, max-image-preview:standard, unavailable_after:2040-12-31">
    <meta name="google" content="nopagereadaloud" >

    <title>log-File</title>

<style>
</style>

</head>
<body>

<?php echo $out; ?>

</body>
</html>