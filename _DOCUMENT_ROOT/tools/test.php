<?php
namespace Dzg\Tools;

require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/tools/Database.php";
require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/tools/Tools.php";
require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/tools/CheckIP.php";
require_once $_SERVER['DOCUMENT_ROOT']."/tools/ip_range/ip_in_range2.php";
require_once $_SERVER['DOCUMENT_ROOT']."/tools/CIDRmatch.php";
#require_once $_SERVER['DOCUMENT_ROOT']."/../data/dzg/tools/Logger.php";
#Logger::delete_logs();


#$ip = new CheckIP('2003:cc:a72b:6b01:cca3:fc23:5bec:389c');
#$ip = new CheckIP('17.246.19.249');
#$ip = new CheckIP('clear');
#$ip = new CheckIP();
#$ip->antiflood();
#$ip->under_suspicion('17.246.19.249');
#if($ip->denied()) {echo $ip->message().'<br>';} else echo $ip->ip().'<br>';
#var_dump($ip->denied);echo'<br>';
#var_dump($ip);echo'<br>';
#echo'<br>';

/*
CheckIP::antiflood();
var_dump($ip->antiflood());echo'<br>';
var_dump($ip);echo'<br>';
*/
#ip_long();
#ip_test();

#$ip = '2003:cc:a72b:6b01:cca3:fc23:5bec:389c';
#$ipc = new CheckIP("clear");
#$ipc->under_suspicion($ip);
#echo $ipc->status().'<br>';


#var_dump($dbh);echo'<br>';
#var_dump($qry);echo'<br>';
#var_dump($sql);echo'<br>***<br>';
#var_dump($query);echo'<br>***<br>';
#$dbh = null;
#$dbconnect = null;

/*
function my_reg_ip()
{
    $stmt = "SELECT ip FROM site_login WHERE userid=2 GROUP BY ip";
    $result = Database::sendSQL($stmt, [], 'fetchall', 'num');

    $myIPs = [];
    foreach($result as $i){
        $myIPs []= $i[0];
    }

    return $myIPs;
}
*/
function write_errorlog_into_DB()
{
    $myIPs = CheckIP::my_reg_ips();

    $file = $_SERVER['DOCUMENT_ROOT']."/../data/dzg/error_log.1";
    #$file = $_SERVER['DOCUMENT_ROOT']."/../data/dzg/error_log.2";
    $file_arr = file($file);
    #var_dump('file_arr: ',count($file_arr));echo'<br><br>';

    $data = [];
    $ips  = [];
    $ct_ips = [];
    $ct   = 0;
    foreach($file_arr as $line){

        $line = str_replace('[', '', $line);
        $row = explode('] ', $line);
        #var_dump($row);echo'<br><br>';

        # 'Wed Feb 11 11:22:46.419818 2026' Problem mit Sek.bruchteil
        $d = explode(' ',$row[0]);
        $d = $d[2].'.'.$d[1].'.'.$d[4].' '.$d[3];

        # 'client 20.238.36.39:0',
        $c0 = strpos($row[3], 'client');
        $c  = substr($row[3], $c0 + 7);           # cut 'client '
        $ip = substr($c, 0, strrpos($c, ':'));    # cut ':0'

        # ip4 --> ip6
        $ip6 = strpos($ip,':') === false
        ? false     # '::ffff:'.$ip
        : $ip;

        #var_dump(ip2long($ip));echo'<br>';
        #var_dump(inet_ntop(inet_pton($ip)));echo'<br>';
        #var_dump(inet_ntop(inet_pton($ip6)));echo'<br><br>';

        #if($ip6){
        if(!in_array($ip, $myIPs)){
            $data[$ct] = [
                'date'   => date("d.m.Y H:i:s", strtotime($d)),
            #    'status' => $row[1],
            #    'pid'    => $row[2],
                'client' => $ip,
            #    'msg'    => htmlentities($row[4], ENT_QUOTES, "UTF-8"),
            ];
            $ips []= $ip;
        };
        $ct++;
    };

    # zählt gleiche IPs, erstellt Array
    $ct_ips = array_count_values($ips);

    # sortiert es absteigend
    arsort($ct_ips);

    $block = [];
    $ip4 = $ip6 = '';
    foreach($ct_ips as $ip=>$v){

        # nur ab 80 Vorkommen für DB-Speicherung berücksichtigen
        if($v>=50 && $v<700){
            (strpos($ip,':') === false)
            ? $ip4 = $ip     # '::ffff:'.$ip
            : $ip6 = $ip;
            #$block []= [$ip4, $ip6];
            $block []= [$ip,];
            echo "{$ip} : {$v}<br>";
        };
    };

    $stmt = "INSERT INTO site_blacklist
        (ip, net, `block`, notiz)
        VALUES (?, 1, 1, 'from error_log')
        ON DUPLICATE KEY UPDATE id=id";
    #Database::sendSQL($stmt, $block, false, 'num', true);

    echo "<br>=".count($block)."/".count($ct_ips)."=<br>";
    return $data;
}

#CheckIP::write_errorlog_into_DB();

$test = new \CIDRmatch\CIDRmatch();
$ip4 = "1.2.3.4";
$range4 = "1.2.5.4/16";
#$res4 = ipv4_in_range($ip4, $range4);
$res4 = $test->match($ip4, $range4);
var_dump($res4);echo'<br>';

$ip6 = "1::4";
$range6 = "1::/32";
#$res6 = ip_in_range($ip6, $range6);
$res6 = $test->match($ip6, $range6);

#$r6 = $res6 ?: 'NIX'; # res6 existiert, aber leer
#$r7 = $res7 ?? 'NIX'; # res7 existiert nicht
var_dump($res6);echo'<br>';





echo '<br>ready<br>';
?>

<!DOCTYPE html>
<HTML lang="de">
<head>
  <meta name="robots" content="noindex, nofollow, noimageindex, noarchive, nosnippet, notranslate" >
  <title></title>
</head><body></body></html>
