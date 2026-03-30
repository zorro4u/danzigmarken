<?php
namespace Dzg\Tools;

require_once __DIR__.'/Database.php';


/****************************
 * Class CheckIP
 *
 * sucht BesucherIP in Blackliste
 *
 * public:
 * ip()         : IP/Bereich
 * denied()     : true/false
 * status()     : denied/allowed
 * message()    : blocked IP message
 * error_msg()  : error message
 * under_suspicion($ip)          : schreibt aktuelle IP in Blockliste
 * get_IP($ip)                   : aktuelle BesucherIP ermitteln, oder IP validieren
 *
 * public.static:
 * antiflood($loads, $time_gap)  : Hauptalgorithmus, ruft dann CheckIP als Instanz auf
 *
 *
 * $ip = new CheckIP('2003:cc:a72b:6b01:cca3:fc23:5bec:389c');
 * $ip = new CheckIP('17.246.19.249');
 * $ip = new CheckIP('clear');
 * $ip = new CheckIP();
 * ... $ip->antiflood();
 * ... $ip->under_suspicion('17.246.19.249');
 * if ($ip->denied) {echo $ip->message;}
 * else echo $ip->ip();
 *
 * CheckIP::antiflood();
 */
class CheckIP
{
    // instance attribute
    public static $net4_arr = [
         8 => 3,
        16 => 2,
        24 => 1,
        32 => 0
    ];
    public static $net6_arr = [
        16  => 7,
        32  => 6,
        48  => 5,
        64  => 4,
        80  => 3,
        96  => 2,
        112 => 1,
        128 => 0
    ];

    private ?string $ip=null;    # (?) - kann Null oder String sein
    private bool $denied=false;
    private ?string $status=null;
    private ?string $message=null;
    private ?string $error=null;

    // getter
    public function ip() {return $this->ip;}
    public function denied() {return $this->denied;}
    public function status() {return $this->status;}
    public function message() {return $this->message;}
    public function error_msg() {return $this->error;}

    // setter
    private function set_ip(string $value) {$this->ip=$value;}
    private function set_denied(bool $value) {$this->denied=$value;}
    private function set_status(string $value) {$this->status=$value;}
    private function set_message(?string $value) {$this->message=$value;}
    private function set_error_msg(string $value) {
        $this->error=$value;
        $this->set_status("error");
    }

    private function setter(string $ip, ?string $message)
    {
        $this->set_ip($ip);
        $this->set_message($message);
        $this->set_denied($message ? true : false);
        $this->set_status($message ? "denied" : "allowed");
    }


    // Instanzen Start
    public function __construct(?string $ip=null)
    {
        if ($ip !== "clear")
            $this->check_IP($ip);
    }



    /*---------------------------*/
    // Public Part

    /**
     * -- Hauptmethode --
     * Zeit & Häufigkeit abfangen
     *
     * kein Schutz vor DDOS oder HTTP-Flood,
     * aber bisschen vor permanenter DB-Abfragerei
     * Was ist bei ständig wechselnder IP?
     *
     * wird in Header.php aufgerufen
     */
    public static function antiflood(int $loads=5, int $time_gap=3, ?string $ip=null) :string
    {
        // IP mit Blockliste abgleichen,
        // wenn mehrfach dann (Bereich) blocken
        // und wegleiten.
        // $loads     : Häufigkeit der Seitenaufrufe im Zeitfenster
        // $time_gap  : sec, Zeitfenster

        #$ip_address    = new CheckIP();
        $ip_address    = new static($ip);

        $now   = time();
        $delay = $now + $time_gap;
        $redirection = "/timeout.html";     # Warteseite

        // IP ist gesperrt, dann weg hier
        # https://theuselessweb.com/
        $sites = [
            "https://puginarug.com/",
            "https://cat-bounce.com/",
            "http://www.republiquedesmangues.fr/"];
        $useless = array_rand($sites, 1);

        if ($ip_address->denied()) {
            header("location: {$useless}");
            exit;
        };

        // alles okay
        if (empty($_SESSION['f_delay']) || $now >= $_SESSION['f_delay']) {
            # reset
            $_SESSION['f_delay'] = $delay;
            $_SESSION['f_loads'] = 1;
        }

        // auch noch okay
        elseif ($now < $_SESSION['f_delay'] && $_SESSION['f_loads'] <= $loads) {
            $_SESSION['f_loads']++;
        }

        // zu häufig, zu schnell -> ab in die Blockliste, (aber noch nicht sperren)
        elseif ($now < $_SESSION['f_delay'] && $_SESSION['f_loads'] > $loads) {
            # reset
            $_SESSION['f_delay'] = $delay;
            $_SESSION['f_loads'] = 1;

            $ip_address->under_suspicion();

            header("location: {$redirection}");
            exit;
        };
        return $ip_address->status();
    }


    /**
     * IP in Blackliste speichern
     */
    public function under_suspicion(?string $ipinput=null, bool $block=false) :void
    {
        $ip = !$ipinput
        ? $this->ip
        : self::valid_IP($ipinput);

        if ($ip) {
            self::write_into_blacklist($ip, $block);
            $this->check_IP($ip);
            if (!$this->denied()) {
                $this->set_status("suspected");
            };
        }

        else {
            $this->set_error_msg("Got a wrong input for IP ({$ip}).");
            echo $this->error_msg().'<br>';
        };
    }


    /**
     * Besucher IP ermitteln
     */
    public function get_IP(?string $ip=null) :string
    {
        $userip = self::getIP($ip);

        if (!$userip) {
            $this->set_error_msg("Got a wrong IP Address ({$ip})");
            return $this->status();
        }

        else
            return $userip;
    }

    public static function getIP(?string $ip=null) :string
    {
        $userip = !$ip
        ? self::get_user_IP()
        : self::valid_IP($ip);
        return $userip;
    }


    /*---------------------------*/
    // Private Part

    /**
     * -- Instanzen-Start --
     * überprüft, ob Besucher IP gesperrt ist
     */
    private function check_IP(?string $ip=null) :void
    {
        $userip = $this->get_IP($ip);

        if (filter_var($userip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->check_IP4($userip);

        } elseif (filter_var($userip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->check_IP6($userip);
        }

        else {
            echo $this->error_msg().'<br>';
        };
    }


    /*---------------------------*/
    // Private Helper Function

    private static function get_user_IP() :mixed
    {
        //check ip from share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }

        //to check ip is pass from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        };

        $ip = $_SERVER['REMOTE_ADDR'];

        return $ip;
    }


    /**
     * IP validieren
     */
    private static function valid_IP(string $ip_input) :mixed
    {
        $ip = null;
        if (filter_var($ip_input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip = $ip_input;

        } elseif (filter_var($ip_input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = $ip_input;
        };
        return $ip;
    }


    private function check_IP4(string $userip) :void
    {
        // Netzbereiche bestimmen
        $ip_arr = self::netmask4($userip);

        // IP im Bereich finden, ggf Bereich erweitern
        $message = null;

        for ($net=0; $net<4; $net++) {

            // gespeicherte IP-Adressen entspr. Netzbereich aus DB lesen
            $ip_arr[$net] += self::DB_get_data($ip_arr[$net]);
            $count  = $ip_arr[$net]['count'];
            $denied = $ip_arr[$net]['denied'];
            $mask   = $ip_arr[$net]['mask'];

            // im Bereich gefunden...
            if (($ip_arr[$net]['count'] > 0) && ($net<=$mask)) {

                // mehrfach im Bereich gefunden/gesperrt -> auffällig, IP/Bereich sperren
                $max = 10**($net+1);
                if (($count > $max) || ($denied > intdiv($max, 4))) {
                    self::DB_block_ip4($ip_arr[$net], $net);
                    $message = "Bereich ({$ip_arr[$net]['cut']}) jetzt geblockt...<br>";
                }

                // IP/Bereich gesperrt? EXIT
                elseif ($count == $denied+15) {
                    $message = "IP/Bereich ({$ip_arr[$net]['cut']}) gesperrt....";
                }

                // IP gesperrt? EXIT
                elseif ($denied > 0) {
                    $message = "IP ({$ip_arr[$net]['cut']}) gesperrt....";
                };

                // Schleife/Bereichserweiterung beenden
                if ($message) break;
            }
        };
        $this->setter($userip, $message);
    }


    private static function netmask4(string $userip) :array
    {
        // netmask/CIDR: /32, /24, /16, /8
        // 0: (/32) a.b.c.d - IP
        // 1: (/24) a.b.c.
        // 2: (/16) a.b.
        // 3: (/8)  a.
        $ip_parts = explode('.', $userip);
        $ip_arr   = [];
        for ($net=0; $net<4; $net++) {
            $pt = $net>0 ? '.' : '';
            $ip_arr[$net] = [];
            $ip_arr[$net]['cut'] = implode('.', array_slice($ip_parts, 0, 4-$net)).$pt;
        };
        return $ip_arr;
    }


    private function check_IP6(string $userip) :void
    {
        // Netzbereiche bestimmen
        $ip_arr = self::netmask6($userip);

        // IP im Bereich finden, ggf Bereich erweitern
        $message = null;
        $ct = 0;
        $arr_len = count($ip_arr);

        for ($net=0; $net<$arr_len; $net++) {
            #$addr = inet_ntop(inet_pton($ip_arr[$net]['cut']));

            // gespeicherte IP-Adressen entspr. Netzbereich aus DB lesen
            $ip_arr[$net] += self::DB_get_data($ip_arr[$net]);
            $count  = $ip_arr[$net]['count'];
            $denied = $ip_arr[$net]['denied'];

            // im Bereich gefunden...
            if (($ip_arr[$net]['count'] > 0) && ($ct<2)) {
                // mehrfach im Bereich gefunden/gesperrt -> auffällig, IP/Bereich sperren
                $max = 10**($net+1);
                if (($count > $max) || ($denied > intdiv($max, 4))) {
                    self::DB_block_ip4($ip_arr[$net], $net);
                    $message = "Bereich ({$ip_arr[$net]['cut']}) jetzt geblockt...<br>";
                    $ct++;
                }

                // IP/Bereich gesperrt? EXIT
                elseif ($count == $denied) {
                    $message = "IP/Bereich ({$ip_arr[$net]['cut']}) gesperrt....";
                }

                // IP gesperrt? EXIT
                elseif (isset($ip_arr[$net]['result']['block']) &&
                    $ip_arr[$net]['result']['block'] == 1) {
                    $message = "IP ({$ip_arr[$net]['cut']}) gesperrt....";
                };

                // Schleife/Bereichserweiterung beenden
                if ($message) break;
            }

            # bei unbek. IP wird nur der nächste Bereich getestet
            else {
                $ct++;
                if ($ct > 1) break;
            };
        };
        $this->setter($userip, $message);
    }

    private static function netmask6(string $userip)
    {
        // netmask/CIDR:
        // 0: (/128) a1:a2:a3:a4:a5:a6:a7:a8 - IP
        // 1: (/112) a1:a2:a3:a4:a5:a6:a7::
        // 2: (/96)  a1:a2:a3:a4:a5:a6::
        // 3: (/80)  a1:a2:a3:a4:a5::
        // ---
        // 4: (/64)  a1:a2:a3:a4::  - Enduser
        // 5: (/48)  a1:a2:a3::
        // 6: (/32)  a1:a2::        - Provider
        // 7: (/16)  a1::

        ## fe80::/64 LinkLocal, 169.254.0.0/16
        ## fec0::/10 SiteLocal, 192.168.x.x - veraltet
        ## fd00::/7  UniqueLocal, fd+40bitSiteID+16bitSubNetID + 64bitInterfaceID
        ## ff00::/8  MultiCast
        ## ...       GlobalUnicast
        $skip_netmask = [1,2,3];
        $ip6_long = self::ip6_long($userip);
        $ip_parts = explode(':', $ip6_long);
        $ip_arr   = [];
        for ($net=0; $net<8; $net++) {
            if (in_array($net, $skip_netmask)) continue;
            $pt = $net>0 ?'::' :'';
            $ip_arr[$net] = [];
            $ip_arr[$net]['cut'] = implode(':', array_slice($ip_parts, 0, 8-$net)).$pt; # cut
            $ip_arr[$net]['cut'] = inet_ntop(inet_pton($ip_arr[$net]['cut']));  # short
        };
        $arr = [];
        foreach ($ip_arr as $v) {
            $arr []= $v;
        };
        return $arr;
    }


    private static function ip6_long(string $ip) :string
    {
        // Convert address to packed format
        $addr = inet_pton($ip);

        // Convert address to long hexadecimal format
        $long = '';
        foreach (str_split($addr) as $char)
            $long .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);

        // seperated with ':'
        return implode(':', str_split($long, 4));
    }


    private static function DB_get_data(array $ip_arr) :array
    {
        $ip = $ip_arr['cut'];
        if (substr($ip, -2, 2) === "::")
            $ip = substr($ip, 0, -1);

        # wenn IP mit Pkt endet, dann Bereichsuche mit %, sonst suche Einzel-IP
        if (substr($ip, -1, 1) === "." ||
            substr($ip, -1, 1) === ":") {
            $x = '%';
            $g = '';
        } else {
            $x = '';
            $g = '-- ';
        };
        $like  = "'{$ip}{$x}'";
        $group = "\n{$g}GROUP BY ip\n";   # ggf als Kommentare, dann aber nur auf seperater Zeile

        $stmt = "WITH
        black_list AS (
        SELECT * FROM site_blacklist
        WHERE ip LIKE {$like} {$group}
        ORDER BY net, ip),

        ct_black AS (
        SELECT count(*) ct_black FROM site_blacklist
        WHERE ip LIKE {$like}),

        ct_block AS (
        SELECT count(*) ct_block FROM site_blacklist
        WHERE ip LIKE {$like} AND `block`=1),

        netcode AS (
        SELECT max(net) mask FROM site_blacklist
        WHERE ip LIKE {$like} AND `block`=1)

        SELECT * FROM black_list, ct_black, ct_block, netcode
        ";

        $result = Database::sendSQL($stmt, [], 'fetchall');
        #$count = count($result);
        #$ct_black = !empty($result) ? $result[0]['ct_black'] : 0;
        $ct_black = $result[0]['ct_black'] ?? 0;
        $ct_block = $result[0]['ct_block'] ?? 0;
        $mask     = $result[0]['mask'] ?? 0;

        #echo$stmt.'<br>';
        $res = [];
        foreach ($result AS $i) {
            foreach ($i AS $k=>$v) {
        #        echo"{$k}: {$v}<br>";
            };
        #    echo'<br>';
        };


        $arr = [];
        $arr['result'] = $result;
        $arr['count']  = $ct_black;
        $arr['denied'] = $ct_block;
        $arr['mask']   = $mask;
        #$arr['stmt']   = $stmt;

        return $arr;
    }


    private static function DB_block_ip4(array $ip_arr, int $net) :void
    {
        $ip = $ip_arr['cut'];
        # wenn IP mit Pkt endet, dann Bereichsuche mit %, sonst suche Einzel-IP
        $x = (substr($ip, -1, 1) === "." || substr($ip, -1, 1) === ":")
            ? '%' : '';
        $like = "'{$ip}{$x}'";

        $stmt = "UPDATE site_blacklist SET `block`=1, net={$net} WHERE ip LIKE {$like}";
        Database::sendSQL($stmt);
    }


    private static function write_into_blacklist($input, bool $block=false) :void
    {
        $data = (!is_array($input))
        ? [ ':ip'    => $input,
            ':block' => (int)$block,]
        : $input;


        $stmt1 =
            "INSERT INTO site_blacklist (ip, `block`)
            SELECT :ip, :block
            WHERE :ip NOT IN
                (SELECT ip FROM site_login
                WHERE userid=2 GROUP BY ip)
            ";

        # wird noch nicht verwendet
        $stmt2 =
            "INSERT INTO site_blacklist (ip, net)
            SELECT :ip, :net
            WHERE :ip NOT IN
                (SELECT ip FROM site_login
                WHERE userid=2 GROUP BY ip)
            ";

        $stmt = count($data) == 1 ? $stmt1 : $stmt2;
        Database::sendSQL($stmt, $data);
    }



    private static function info($ip_arr, $userip)
    {
        $ip_cut = $ip_arr['cut'];
        #$ip_lo  = $ip_arr['lo'];
        #$ip_hi  = $ip_arr['hi'];
        $stmt   = $ip_arr['stmt'];
        $count  = $ip_arr['count'];
        $denied = $ip_arr['denied'];
        $result = $ip_arr['result'];
        $block  = (!empty($ip_arr['result'])) ? $ip_arr['result'][0]['block'] : 0;

        echo $userip.' | '.$ip_cut.' | '.$count.' | '.$denied.' | '.$block.'<br>';
        #var_dump($stmt); echo'<br>';

        /*
        foreach ($result AS $i) {
            foreach ($i AS $k=>$v) {
                echo $k, ": ", $v, "<br>";
            };
            #echo"<br>";
        };
        */
        #echo'<br>';
        #var_dump($result); echo'<br>';
    }



    /**
     * Blockiert KI-Bots (z.B. GPTBot, ClaudeBot, PerplexityBot),
     * wenn sie sich nicht korrekt über Reverse DNS verifizieren lassen.
     * https://webwerkstatt-berlin.de/ki-crawler-blockieren-inhalte-schuetzen/
     */
    public static function block_ai_bots_by_rdns()
    {
        if (empty($_SERVER['REMOTE_ADDR']) || empty($_SERVER['HTTP_USER_AGENT'])) {
            return; // Kein valider Request
        }

        $ip  = $_SERVER['REMOTE_ADDR'];
        $ua  = $_SERVER['HTTP_USER_AGENT'];

        // Bekannte KI-Bot-Domains und erwartete User-Agents
        $bots = [
            [
                'name'   => 'GPTBot',
                'ua'     => '/GPTBot/i',
                'domain' => '/openai\.com$/'
            ],
            [
                'name'   => 'ClaudeBot',
                'ua'     => '/ClaudeBot/i',
                'domain' => '/anthropic\.com$/'
            ],
            [
                'name'   => 'PerplexityBot',
                'ua'     => '/PerplexityBot/i',
                'domain' => '/perplexity\.ai$/'
            ],
        ];

        foreach ($bots as $bot) {
            if (preg_match($bot['ua'], $ua)) {
                // 1. Reverse DNS: IP -> Hostname
                $hostname = @gethostbyaddr($ip);

                // 2. Forward DNS: Hostname -> IP
                $forward_ip = $hostname ? @gethostbyname($hostname) : '';

                // Prüfen: Domain-Endung und Forward-IP muss identisch sein
                if (
                    !$hostname ||
                    !$forward_ip ||
                    $forward_ip !== $ip ||
                    !preg_match($bot['domain'], $hostname)
                ) {
                    // Spoofing-Verdacht -> Blockieren
                    header('HTTP/1.1 403 Forbidden');
                    header('Content-Type: text/plain');
                    echo "Access denied.";
                    exit;
                }
            }
        }
    }


    /* ============= */

    public static function write_errorlog_into_DB()
    {
        $myIPs = self::my_reg_ips();

        $file = $_SERVER['DOCUMENT_ROOT']."/../logs/error_log";
        #$file = $_SERVER['DOCUMENT_ROOT']."/../data/dzg/error_log.1";
        #$file = $_SERVER['DOCUMENT_ROOT']."/../data/dzg/error_log.2";
        $file_arr = file($file);

        $data = [];
        $ips  = [];
        foreach($file_arr as $line_num => $line){

            $line = str_replace('[', '', $line);
            $row  = explode('] ', $line);
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

            if(!in_array($ip, $myIPs)){
                $ips []= $ip;
                /*
                $data[$line_num] = [
                    'date'   => date("d.m.Y H:i:s", strtotime($d)),
                #    'status' => $row[1],
                #    'pid'    => $row[2],
                    'client' => $ip,
                #    'msg'    => htmlentities($row[4], ENT_QUOTES, "UTF-8"),
                ];
                */
            };
        };

        # zählt gleiche IPs, erstellt Array
        $ip_count = array_count_values($ips);

        # sortiert es absteigend
        arsort($ip_count);

        $block = [];
        $ip4 = $ip6 = '';
        foreach($ip_count as $ip => $ct){
            #echo "{$ip} : {$ct}<br>";

            # nur ab 80 Vorkommen für DB-Speicherung berücksichtigen
            if($ct>=50 && $ct<700){
                (strpos($ip, ':') === false)
                ? $ip4 = $ip     # '::ffff:'.$ip
                : $ip6 = $ip;
                #$block []= [$ip4, $ip6];
                $block []= [$ip];
                #echo "{$ip} : {$v}<br>";
            };
        };

        // in DB schreiben
        $insert = $update = [];
        if($block){

            // IP vorhanden?
            $stmt = "WITH
                input AS (SELECT ? `address`, 1 netcode),

                -- Insert, =0
                found AS (
                SELECT COUNT(*) found FROM site_blacklist, input
                WHERE ip=`address`),

                -- Update, >0
                upd AS (
                SELECT COUNT(*) upd FROM site_blacklist, input
                WHERE ip=`address` and net<netcode)

                SELECT `address`, found, upd FROM input, found, upd";
            $qry = Database::sendSQL($stmt, $block, true, 'assoc', true);

            // Insert oder Update?
            foreach($qry as $ip){
                !$ip['found']
                ? $insert []= $ip['address']
                : ($ip['upd']>0 ? $update []= $ip['address'] : 0);
            }

            // IP blocken
            if($insert){
                $stmt = "INSERT INTO site_blacklist
                    (ip, net, `block`, notiz)
                    VALUES (?, 1, 1, 'from error_log')
                    ON DUPLICATE KEY UPDATE id=id ";
                $data = $insert;
            }
            elseif($update){
                $stmt = "UPDATE site_blacklist
                    SET net=1, `block`=1, notiz='from error_log'
                    WHERE ip=? ";
                $data = $update;
            }
            Database::sendSQL($stmt, $data, false, '', true);
            #$stmt = "ALTER TABLE `site_blacklist` AUTO_INCREMENT=0";
            #Database::sendSQL($stmt, []);

            #echo "= errorlog: +".count($insert)."/".count($ip_count)." =<br><br>";
        }
        return count($insert);
    }


    public static function my_reg_ips()
    {
        $stmt = "SELECT ip FROM site_login WHERE userid=2 GROUP BY ip";
        $result = Database::sendSQL($stmt, [], 'fetchall', 'num');

        $myIPs = [];
        foreach($result as $i){
            $myIPs []= $i[0];
        }
        return $myIPs;
    }


    public static function write_htaccess_into_DB()
    {
        $file = $_SERVER['DOCUMENT_ROOT']."/.htaccess";
        $file_arr = file($file);

        $net4_arr = self::$net4_arr;
        $net6_arr = self::$net6_arr;

        $block  = [];
        foreach($file_arr as $line){

            // IP Zeile finden
            $cut = 'Require not ip ';
            $lcut = strlen($cut);
            if(strpos($line, $cut) !== false){

                // Zeile ohne führenden Text
                $row = trim(substr($line, $lcut));

                // IPv6 Adresse?
                $ip6 = (strpos($row, ':') !== false) ? true : false;

                // Position netmask im String
                $netpos = (strrpos($row, '/') !== false) ? strrpos($row, '/') : null;

                // netmask Zahl
                $netm = $netpos
                ? (int)substr($row, strrpos($row, '/') + 1)
                : ($ip6 ? 128 : 32);

                // interner Code dafür
                $netcode = $ip6
                ? $net6_arr[$netm]
                : $net4_arr[$netm];

                // IP Adresse ohne netmask-string
                $ip  = substr($row, 0, $netpos);

                $block []= [$ip, $netcode];
            };
        };

        $stmt = "INSERT INTO site_blacklist
            (ip, net, `block`, notiz)
            VALUES (?, ?, 1, 'from htaccess')
            ON DUPLICATE KEY UPDATE id=id";
        Database::sendSQL($stmt, $block, false, 'num', true);

        return $block;
    }


    public static function write_DB_into_htaccess()
    {
        #$file = $_SERVER['DOCUMENT_ROOT']."/tools/.htaccess.txt";
        $file = $_SERVER['DOCUMENT_ROOT']."/.htaccess";

        $net4_rev = array_flip(self::$net4_arr);
        $net6_rev = array_flip(self::$net6_arr);


        // load file
        $file_input = file($file);

        // IPs aus File lesen
        $file_str = [];
        foreach($file_input as $line_num => $line){

            // IP Zeile finden
            $cut = 'Require not ip ';
            $lcut = strlen($cut);
            if(strpos($line, $cut) !== false){

                // Zeile ohne führenden Text
                $file_str []= trim(substr($line, $lcut));
            }

            elseif(strpos($line, '</RequireAll>') !== false){
                $ip_section_end = $line_num;
            };
        };


        // IP aus DB lesen
        $stmt =
            "SELECT ip, net FROM  site_blacklist
            WHERE `block`=1
            ORDER BY ip";
        $db_input = Database::sendSQL($stmt, [], 'all');

        // zum besseren Sortieren Array mit IP als Zahl bilden
        $db_str  = [];
        $ip4_arr = [];
        $ip6_arr = [];
        foreach($db_input as $db){
            $ip6 = (strpos($db['ip'], ':') !== false) ? true : false;

            $netm = $db['net'] !== 0
            ? ($ip6 ? $net6_rev[$db['net']] : $net4_rev[$db['net']])
            : false;
            $netm_str = $netm ? '/'.$netm : '';

            ($ip6)
            ? $ip6_arr += [$db['ip'] => $netm_str]
            : $ip4_arr += [ip2long($db['ip']) =>  $netm_str];
        };

        // Array sortieren
        ksort($ip4_arr);
        ksort($ip6_arr);

        // Ausgabe-Array inkl Netmaske als String bilden
        foreach($ip4_arr as $k=>$netm_str){
            $ip = long2ip($k);
            $db_str []= $ip.$netm_str;
        }
        foreach($ip6_arr as $k=>$netm_str){
            $ip = $k;
            $db_str []= $ip.$netm_str;
        }


        // Abgleich: DB-IP nicht in File enthalten
        $write = [];
        foreach($db_str as $ip){
            if(in_array($ip, $file_str) === false){
                $write []= $ip;
                #echo $ip.'<br>';
            }
        }


        // File neu schreiben
        if($write){

            // Datei-Inhalt erstellen (alt+neu)
            $new_file_arr = [];
            foreach($file_input as $line_num => $line){

                if($line_num != $ip_section_end){
                    $new_file_arr []= $line;
                }

                // vor Ende IP-Section neue IPs einfügen
                else{
                    $date = date("d.m.Y H:i:s");
                    $new_file_arr []= "# added at {$date}\n";
                    foreach($write as $ip_str){
                        $new_file_arr []= 'Require not ip '.$ip_str."\n";
                    }
                    $new_file_arr []= "\n";
                    $new_file_arr []= $line;
                };
            };

            // in Datei schreiben
            $output_str = implode('', $new_file_arr);
            #$output_file = $_SERVER['DOCUMENT_ROOT']."/tools/.htaccess.txt";
            $output_file = $file;
            file_put_contents($output_file, $output_str);


            #$out = htmlentities($file_input[$ip_section_end]);
            #var_dump(htmlspecialchars($file_input[2]));echo'<br>';

            echo "= htaccess: +".count($new_file_arr)-2-count($file_input)." =<br><br>";
        };

    }


    public static function clear_htaccess_ip()
    {
        #$file = $_SERVER['DOCUMENT_ROOT']."/tools/.htaccess.txt";
        $file = $_SERVER['DOCUMENT_ROOT']."/.htaccess";
        $file_input = file($file);

        $skip = 'Require not ip ';
        $new_file_arr = [];
        foreach($file_input as $line){
            if(strpos($line, $skip) === false){
                $new_file_arr []= $line;
            };
        };

        // in Datei schreiben
        $output_str = implode('', $new_file_arr);
        #$output_file = $_SERVER['DOCUMENT_ROOT']."/tools/.htaccess.txt";
        $output_file = $file;
        file_put_contents($output_file, $output_str);
    }

}



#################################################################

function ip_test() {
    // Our Example IP's
    $ip4= "10.22.99.129";
    $ip6= "fe80:1:2:3:a:bad:1dea:dad";

    // ip2long examples
    var_dump(ip2long($ip4));echo'<br>'; // int(169239425)
    var_dump(ip2long($ip6));echo'<br>'; // bool(false)

    // inet_pton examples
    var_dump(inet_pton($ip4));echo'<br>'; // string(4)
    var_dump(inet_pton($ip6));echo'<br>'; // string(16)

    // Unpacking and Packing
    $_u4 = current(unpack("A4", inet_pton($ip4)));
    var_dump(inet_ntop(pack("A4", $_u4)));echo'<br>'; // string(12) "10.22.99.129"

    $_u6 = current(unpack("A16", inet_pton($ip6)));
    var_dump(inet_ntop(pack("A16", $_u6)));echo'<br>'; //string(25) "fe80:1:2:3:a:bad:1dea:dad"

    // Finally use
    #if ($ip <= $high_ip && $low_ip <= $ip) {
    #    echo "in range";
    #}
}





/**
* ip_in_range.php - Function to determine if an IP is located in a
*                   specific range as specified via several alternative
*                   formats.
*
* Network ranges can be specified as:
* 1. Wildcard format:     1.2.3.*
* 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
* 3. Start-End IP format: 1.2.3.0-1.2.3.255
*
* Return value BOOLEAN : ip_in_range($ip, $range);
*
* Source website: http://www.pgregg.com/projects/php/ip_in_range/
*/

// decbin32
// In order to simplify working with IP addresses (in binary) and their
// netmasks, it is easier to ensure that the binary strings are padded
// with zeros out to 32 characters - IP addresses are 32 bit numbers
function decbin32 ($dec) {
    return str_pad(decbin($dec), 32, '0', STR_PAD_LEFT);
}


// ip_in_range
// This function takes 2 arguments, an IP address and a "range" in several
// different formats.
// Network ranges can be specified as:
// 1. Wildcard format:     1.2.3.*
// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
// 3. Start-End IP format: 1.2.3.0-1.2.3.255
// The function will return true if the supplied IP is within the range.
// Note little validation is done on the range inputs - it expects you to
// use one of the above 3 formats.
function ip_in_range($ip, $range) {
    if (strpos($range, '/') !== false) {
        // $range is in IP/NETMASK format
        list($range, $netmask) = explode('/', $range, 2);

        if (strpos($netmask, '.') !== false) {
            // $netmask is a 255.255.0.0 format
            $netmask = str_replace('*', '0', $netmask);
            $netmask_dec = ip2long($netmask);
            return ( (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec) );
        }

        else {
            // $netmask is a CIDR size block
            // fix the range argument
            $x = explode('.', $range);
            while(count($x)<4) $x[] = '0';
            list($a,$b,$c,$d) = $x;
            $range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
            $range_dec = ip2long($range);
            $ip_dec = ip2long($ip);

            # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
            #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

            # Strategy 2 - Use math to create it
            $wildcard_dec = pow(2, (32-$netmask)) - 1;
            $netmask_dec = ~ $wildcard_dec;

            return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
        }
    }

    else {
        // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
        if (strpos($range, '*') !==false) { // a.b.*.* format
            // Just convert to A-B format by setting * to 0 for A and 255 for B
            $lower = str_replace('*', '0', $range);
            $upper = str_replace('*', '255', $range);
            $range = "$lower-$upper";
        }

        if (strpos($range, '-')!==false) { // A-B format
            list($lower, $upper) = explode('-', $range, 2);
            $lower_dec = (float)sprintf("%u",ip2long($lower));
            $upper_dec = (float)sprintf("%u",ip2long($upper));
            $ip_dec = (float)sprintf("%u",ip2long($ip));
            return ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
        }

        echo 'Range argument is not in 1.2.3.4/24 or 1.2.3.4/255.255.255.0 format';
        return false;
    }
}

/**
 * Check if a given ip is in a network
 * @param  string $ip    IP to check in IPV4 format eg. 127.0.0.1
 * @param  string $range IP/CIDR netmask eg. 127.0.0.0/24, also 127.0.0.1 is accepted and /32 assumed
 * @return boolean true if the ip is in this range / false if not.
 */
function ip_in_range_1( $ip, $range ) {
    if ( strpos( $range, '/' ) === false ) {
        $range .= '/32';
    }
    // $range is in IP/CIDR format eg 127.0.0.1/24
    list( $range, $netmask ) = explode( '/', $range, 2 );
    $range_decimal = ip2long( $range );
    $ip_decimal = ip2long( $ip );
    $wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return ( ( $ip_decimal & $netmask_decimal ) == ( $range_decimal & $netmask_decimal ) );
}

// Use the excellent rlanvin/php-ip which supports IPv4 and IPv6 (via the GMP extension):
// https://github.com/rlanvin/php-ip/tree/master
// composer require rlanvin/php-ip
/*
require 'vendor/autoload.php';
use PhpIP\IPBlock;

$block = IPBlock::create('10.0.0.0/24');
$block->contains('10.0.0.42'); // true
*/



/**
 * Zeit abfangen
 */
function antiflood0()
{
    $wait = 2;    # sec
    $now  = time();

    if (isset($_SESSION['request_delay'])) {
        if ($now < $_SESSION['request_delay']) {

            // users will be redirected to this page
            // if it makes requests faster than delay
            header("location: /flood.html");
            exit;
        }
    }
    $_SESSION['request_delay'] = $now + $wait;
}



function ip_long()
{
    // Sample IP addresses
    #$ipaddr = '1.2.3.4/24'; // IPv4 with /24 netmask
    $ipaddr = 'A:2::3:4/64'; // IPv6 with /64 netmask

    // Strip out the netmask, if there is one.
    $cx = strpos($ipaddr, '/');
    if ($cx) {
        $subnet = (int)(substr($ipaddr, $cx+1));
        $ipaddr = substr($ipaddr, 0, $cx);
    }
    else $subnet = null; // No netmask present

    // Convert address to packed format
    $addr = inet_pton($ipaddr);

    // Let's display it as hexadecimal format
    #$long = '';
    foreach (str_split($addr) as $char)
        #echo str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
        $long .= str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);
    $ip6_long = implode(':', str_split($long, 4));

    echo $long."<br />\n";
    echo $ip6_long."<br />\n";
    echo "<br />\n";

    $skip_netmask = [1,2,3];
    $ip_parts = explode(':', $ip6_long);
    for ($net=0; $net<8; $net++) {
        if (in_array($net, $skip_netmask)) continue;
        $pt = $net>0 ?'::' :'';
        $ip_arr[$net] = [];
        $ip_arr[$net]['cut'] = implode(':', array_slice($ip_parts, 0, 8-$net)).$pt; # cut
        $ip_arr[$net]['cut'] = inet_ntop(inet_pton($ip_arr[$net]['cut']));  # short

        if (substr($ip_arr[$net]['cut'], -2, 2) === "::") {
            $ip_arr[$net]['cut'] = substr($ip_arr[$net]['cut'], 0, -1);
            #var_dump($ip_arr[$net]['cut']);echo'<br>';
        };
    }
    var_dump($ip_arr);echo'<br>';
    #echo inet_ntop($addr)."<br />\n";

    // Convert the netmask
    if (is_integer($subnet)) {
        // Maximum netmask length = same as packed address
        $len = 8*strlen($addr);

        if ($subnet > $len)
            $subnet = $len;

        // Create a hex expression of the subnet mask
        $mask  = str_repeat('f', $subnet>>2);
        $mask .= match ($subnet & 3) {
            3 => 'e',
            2 => 'c',
            1 => '8',
            default => 'unknown status',
        };

        $mask = str_pad($mask, $len>>2, '0');

        // Packed representation of netmask
        $mask = pack('H*', $mask);
    }

    // Display the netmask as hexadecimal
    foreach (str_split($mask) as $char)
        echo str_pad(dechex(ord($char)), 2, '0', STR_PAD_LEFT);






}

