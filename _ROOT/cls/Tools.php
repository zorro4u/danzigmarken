<?php
namespace Dzg\Cls;

require_once __DIR__.'/Database.php';
use Dzg\Cls\Database;
use PDO, PDOException, Exception;

function vardump(mixed ...$params)
{
    if (!empty($_SESSION['su'])) {
        var_dump($params);
        echo'<br>';
        /*
        switch (count($params)) {
            case 1:
                var_dump($params[0]);
                break;
            case 2:
                var_dump($params[0], $params[1]);
                break;
            case 3:
                var_dump($params[0], $params[1], $params[2]);
                break;
            case 4:
                var_dump($params[0], $params[1], $params[2], $params[3]);
                break;
            case 0:
                break;
            default:
                var_dump($params);
        }*/
    }
}

/***********************
 * ================================================================
 * Dump variable number of arguments
 * -
 * Usage:
 * echo xdump("hello", 123, ["a" => "b"], .......);
 * ================================================================
 *
 * @param mixed ...$data Whatever data
 *
 * @return string
 */
function xdump(...$data): string {
    static $counter = 1;

    # Result output
    $output = "";

    if (!empty($_SESSION['su'])):

    # Different nice and decent colors for different file types
    static $colormap = ["integer" => "60afdc", "double" => "49c0b6", "float" => "49c0b6", "string" => "fd9f3e", "null" => "2c2c2c", "boolean" => "ff6c5f", "object" => "a26eea", "array" => "4257b2", "resource" => "d20962",];

    # If this is a CLI request, output will be plain, without HTML to also have a nicely formatted output in console
    static $cli = null;
    if ($cli === null) {
        $cli = (defined("STDIN") || array_key_exists("SHELL", $_ENV) || (defined("PHP_SAPI") && strtolower(PHP_SAPI) == "cli") || (function_exists("php_sapi_name") && strtolower((string)php_sapi_name()) == "cli"));
    }

    # Exception for trace string
    $exception   = new \Exception();
    $print_trace = htmlentities($exception->getTraceAsString(), ENT_QUOTES, "UTF-8");


    # Iterate through passed data
    foreach ($data as $value) {
        $bool  = (bool)$value;
        $type  = strtolower(gettype($value));
        $color = (isset($colormap[$type]) ? $colormap[$type] : "f85a40");
        $color = ($value === true ? "47cf73" : ($value === false ? "ee4f4f" : $color));
        # For arrays, show values count, for strings show the length
        $type_info = (is_array($value) ? count($value) : (is_string($value) ? strlen($value) : ""));

        # CLI mode (for dumping values in console)
        if ($cli) {
            $output .= "\n============================ x dump ============================\n";
            $output .= "{$type}" . ($type_info !== "" ? "({$type_info})" : "") . " (~" . ($bool ? "true" : "false") . ") (call-no. {$counter})\n";
            if ($value !== null && $value !== true && $value !== false) {
                $output .= "----------------------------------------------------------------\n" . trim(print_r($value, true)) . "\n";
            }
            $output .= "----------------------------------------------------------------\n" . $print_trace . "\n================================================================\n";
        } else {
            # HTML output for web requests
            $type_info = ($type_info !== "" ? "<sup style='font-family:Calibri, Verdana, Helvetica, Arial, sans-serif !important;padding-left:2px;font-size:11px;'>({$type_info})</sup>" : "");
            $output    .= "<div class='xdump xdump-{$counter}' style='text-align:left;display:block !important;position:relative !important;clear:both !important;box-sizing:border-box;background-color:#{$color};border:4px solid rgba(255,255,255,0.5);border-radius:5px;padding:12px !important;margin:16px;font-family:Consolas,\"Courier New\", \"Arial\", sans-serif !important;font-size:13px;text-transform: none !important;line-height: initial !important;opacity:1.0 !important;float:none;'>";
            $output    .= "<div class='xdump-varinfo' style='box-sizing:border-box;background-color:rgba(255,255,255,0.4);border-radius:4px;padding:8px;margin:4px 0;color:rgba(0,0,0,0.72) !important;font-size:14px;'><span style='text-decoration: underline;font-weight:bold;font-family:Consolas,\"Courier New\", \"Arial\", sans-serif !important;color:rgba(0,0,0,0.72) !important;'>{$type}</span>{$type_info} <span style='opacity:0.5;color:rgba(0,0,0,0.72) !important;font-weight:normal !important;font-family:Consolas,\"Courier New\", \"Arial\", sans-serif !important;font-size:11px;'>(~" . ($bool ? "true" : "false") . ")</span></div>";
            if ($value !== null && $value !== true && $value !== false && $value !== []) {
                $output .= "<pre style='box-sizing:border-box;background-color:rgba(255,255,255,0.96);color:rgba(0,0,0,0.9);padding:8px;margin:8px 0;max-height:512px;overflow:auto;border:none;border-radius:4px;'>" . htmlentities(print_r($value, true), ENT_QUOTES, "UTF-8") . "</pre>";
            }
            $output .= "<div class='xdump-trace' style='font-family:Consolas,Monospace,\"Courier New\",Calibri, Verdana, Helvetica, Arial, sans-serif !important;text-align:left;box-sizing:border-box;padding:4px;color:rgba(255,255,255,0.4);font-size:10px;font-weight:normal;font-weight:100;'>" . nl2br($print_trace) . "</div></div>";
        }
        $counter++;
    }

    endif;
    return $output;
}


/***********************
 * Dump and die
 */
function dd(...$data) {
    #echo xdump(...$data);
    vardump($data);
    exit();
}



/****************************
 * Summary of Tools
 */
class Tools
{
    /***********************
     * Stammverzeichnis festlegen, bei Aufruf aus Unterverzeichnis (wie auth/login.php)
     * sonst Probleme zB. mit css Aufruf
     * nötig für Cookies, Header, Footer
     */
    public static function rootdir(): string
    {
        // mögliche root_Dir: "/" bei externem Aufruf, "stamps" bei internem Aufruf, "_prepare" debug-Mode
        $root_dir = ['stamps', '_prepare', 'localhost'];
        $act_pth = explode('/', dirname($_SERVER['REQUEST_URI']));  // array aus den Dir-Ebenen des Aufrufs
        $pth_len = count($act_pth)-1;  // Anzahl der Dir-Ebenen

        // root_dir-Elemente in den Dir-Ebenen finden
        for ($i=$pth_len; $i>0; $i--)
            if (in_array($act_pth[$i], $root_dir)) break;

        $dir = implode('/', array_slice($act_pth, 0, $i+1));
        if ($dir==='/' || $dir==='\\') $dir = '';

        return $dir;
    }


    /***********************
     * Returns the URL to the site without the script name    (-> passwortvergessen.php)
     */
    public static function getSiteURL(): string
    {
        $myHost = 'www.danzigmarken.de';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        return $protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
        #return $protocol.$myHost.'/';
    }


    /***********************
     * Input-Eingabe bereinigen
     */
    public static function clean_input($data): string
    {
        $data = trim($data);            // Leerzeichen anfang/ende entfernen
        $data = stripslashes($data);    // Blackslash entfernen
        $data = strip_tags($data);      // html-tags entfernen
        return $data;
    }


    /***********************
     * Statusmeldung auf html ausgeben
     */
    public static function statusmeldung_ausgeben(): string
    {
        global $success_msg, $error_msg, $exit;
        global $tab;

        $ausgabe = '';

        if(isset($success_msg) && $success_msg !== "") {
            $ausgabe =
                '<div class="alert alert-success">'.
                '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.
                $success_msg.'</div>';
                #'&emsp;&emsp;<a href="'.htmlspecialchars(self::clean_input($_SERVER["PHP_SELF"])).'?tab='.$tab.'">< Reload ></a>'.

        } elseif((isset($error_msg) && $error_msg !== "") || !empty($error_msg)) {
            $exit_info = $exit ? '<br>-- <b>< <a href="logout">Logout</a> in 5sec ></b> --' : '';

            $ausgabe =
                '<div class="alert alert-danger">'.
                '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.
                $error_msg.$exit_info.'</div>';
        }
        return $ausgabe;
    }


    /***********************
     * Summary of status_out
     */
    public static function status_out($msg_success, $msg_error, $exit_true=false): string
    {
        global $success_msg, $error_msg, $exit;
        $success_msg = $msg_success;
        $error_msg = $msg_error;
        if($exit_true) {$exit = $exit_true;}
        return self::statusmeldung_ausgeben();
    }


    /***********************
     * Summary of dyndns_updater
     */
    public static function dyndns_updater()
    {
        // Update der IP4 von danzig.goip.de auf den Rainbow-Server von danzigmarken.de
        # ip4: 45.10.26.7
        # ip6: 2a0a:51c0:0:18::3

        $pdo = Database::get_pdo();

        $username = "ORXcbjSAlkVmoGV";
        $password = "xI154a2NCsTzFXr";
        $current_ip = $_SERVER['SERVER_ADDR'];
        $last_ip = "";
        $url = "https://www.goip.de/setip?username=".$username."&password=".$password."ip=".$current_ip;

        # "https://www.goip.de/setip?username=".$username."&password=".$password."ip=".$current_ip."ip6=".$current_ip6;
        # https://www.goip.de/setip?username=BENUTZER&password=PASSWORT&subdomain=meinesubdomain.goip.de&ip6=<ip6>
        # https://www.goip.de/setip?username=BENUTZER&password=PASSWORT&subdomain=meinesubdomain.goip.de&ip=<ipaddr>
        # https://www.goip.de/setip?username=BENUTZER&password=PASSWORT&shortResponse=true


        $stmt = "SELECT notiz FROM site_users WHERE userid = 2";
        try {
            $qry = $pdo->query($stmt);
            $last_ip = $qry->fetch()[0];
        } catch(PDOException $e) {die($e->getMessage().': auth.func_updater-lesen');}

        if ($current_ip !== $last_ip) {
            // send update to $url
            echo "Server IP hat sich geändert zu: {$current_ip} --> goip.de aktualisieren";

            // $last_ip = current_ip;
            $stmt = "UPDATE site_users SET notiz = ? WHERE userid = 2";
            try {
                $qry = $pdo->prepare($stmt);
                #$qry->execute([$current_ip]);
            } catch(PDOException $e) {die($e->getMessage().': auth.func_updater-schreiben');}
        }
    }

}