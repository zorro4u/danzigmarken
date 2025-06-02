<?php
// wird in Footer.php aufgerufen
date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/../cls/Database.php';
use Dzg\Cls\Database;

// Test auf Heimnetz
$remaddr = $_SERVER['REMOTE_ADDR'];
$addr = substr($remaddr, 0, 11);
$home = ['X-192.168.10.', '192.168.11.'];
foreach ($home AS $chk)
    $homechk = ($addr == $chk) ? True : False;

// Test auf index.php
$mainpages = ['index.php', 'index2.php'];
$mainpagechk = (in_array(basename($_SERVER['PHP_SELF']), $mainpages)) ? True : False;

// Test auf debug/probe-Modus
$workingpath = __DIR__;
$debug = (strpos($workingpath, "/_prepare") !== False) ? True : False;


// logge wenn: auswärts und nicht admin
// und nicht angemeldet: && !isset($_SESSION['loggedin'])
// und nur index-Seiten: && $mainpagechk===true
#if ($homechk === False  && !isset($_SESSION['su'])) {

// SQL-Abfrage geht nicht bei MariaDB.NAS
if ($debug === False) {

    $pdo = Database::connect_mariadb();

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
    $server[21] = isset($_SESSION['autologin']) ? $_SESSION['autologin'] : NULL;

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
    $stmt= "INSERT INTO site_log (ip, browser, lang, accept, platform, mobile, ua, cache, url, referer)
        SELECT :ip, :browser, :lang, :accept, :platform, :mobile, :ua, :cache, :url, :referer
        WHERE :ip NOT IN (SELECT ip FROM site_login WHERE userid = 2 GROUP BY ip)
        ";

    $data = [
        ":ip" => $ip, ":browser" => $server[0], ":lang" => $server[1], ":accept" => $server[2], ":platform" => $server[3],
        ":mobile" => $server[4], ":ua" => $server[5], ":cache" => $server[6], ":url" => $server[7], ":referer" => $server[8]
    ];

    try {
        $qry = $pdo->prepare($stmt);

        #$qry->bindParam(":ip", $ip, PDO::PARAM_STR);
        #$qry->bindParam(":browser", $server[0], PDO::PARAM_STR);
        #$qry->bindParam(":lang", $server[1], PDO::PARAM_STR);
        #$qry->bindParam(":accept", $server[2], PDO::PARAM_STR);
        #$qry->bindParam(":platform", $server[3], PDO::PARAM_STR);
        #$qry->bindParam(":mobile", $server[4], PDO::PARAM_STR);
        #$qry->bindParam(":ua", $server[5], PDO::PARAM_STR);
        #$qry->bindParam(":cache", $server[6], PDO::PARAM_STR);
        #$qry->bindParam(":url", $server[7], PDO::PARAM_STR);
        #$qry->bindParam(":referer", $server[8], PDO::PARAM_STR);

        #$qry->bindParam(":userid", $server[20], PDO::PARAM_INT);
        #$qry->bindParam(":autoident", $server[21], PDO::PARAM_STR);

        $qry->execute($data);
    } catch(PDOException $e) {die($e->getMessage().': logged.inc');}

    $pdo = Null;
}
