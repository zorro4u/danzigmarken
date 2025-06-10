<?php
namespace Dzg\Cls;

/****************************
 * Funktionscontainer für den Anmelde- und Verifizierungsprozess
 *
 ****************************/


/*
 * A complete login script with registration and members area.
 *
 * @author: Nils Reimers / http://www.php-einfach.de/experte/php-codebeispiele/loginscript/
 * @license: GNU GPLv3
 */


date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/Database.php';
include_once __DIR__.'/../inc/auth.password.func.php';
include_once __DIR__.'/Tools.php';

use PDO, PDOException;
use Dzg\Cls\{Database, Tools};

/*
 * Stammverzeichnis festlegen, bei Aufruf aus Unterverzeichnis (wie auth/login.php)
 * sonst Probleme zB. mit css Aufruf
 * nötig für Cookies, Header, Footer
 */
$_SESSION['rootdir'] = Tools::rootdir();


// Startseite festlegen, $_SESSION['main']
if (!isset($_SESSION['main'])) {
    $_SESSION['main'] = $_SESSION['rootdir'].'/index.php';
}


/****************************
 * Summary of Auth
 * Funktionscontainer für den Anmelde- und Verifizierungsprozess
 */
class Auth
{
    public static $pdo;
    public static function get_pdo(): PDO
    {
        if (!is_object(self::$pdo)) self::set_pdo();
        return self::$pdo;
    }
    protected static function set_pdo(): PDO
    {
        // Verbindung zur Datenbank
        $pdo = (is_object(Database::$pdo))
            ? Database::get_pdo()
            : Database::connect_mariadb();

        self::$pdo = $pdo;
        return $pdo;
    }


    /***********************
     * Logge den Benutzer ein, hole dessen Daten aus der DB
     */
    public static function login($userid): array
    {
        $pdo = self::get_pdo();

        if (preg_match('/^[0-9]{0,1000}$/', $userid)) {
            $error_msg = [];
            $stmt = "SELECT * FROM site_users WHERE userid = :userid";
            try {
                $qry = $pdo->prepare($stmt);
                $qry->bindParam(":userid", $userid, PDO::PARAM_INT);
                $qry->execute();
                $usr_data = $qry->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {die($e->getMessage().': auth.func.login()_usrdata');}

            if ($usr_data) {
                if (self::plausi_check_autocookie()) {
                    $identifier = $_COOKIE['login_ID'];
                    $token_hash = $_COOKIE['login_token'];
                    $securitytoken_row = self::securitytoken_holen($identifier, $token_hash);
                } else {
                    $stmt = "SELECT * FROM site_login WHERE userid = :userid AND identifier IS NULL";
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->bindParam(":userid", $userid, PDO::PARAM_INT);
                        $qry->execute();
                        $securitytoken_row = $qry->fetch(PDO::FETCH_ASSOC);
                    } catch(PDOException $e) {die($e->getMessage().': auth.func.login()_secrow');}
                }

                $_SESSION['loggedin'] = True;
                $_SESSION['userid'] = $userid;
                if ((int)$usr_data['su'] === 1) $_SESSION['su'] = True; else unset($_SESSION['su']);
                if ($usr_data['status'] === "activated") $_SESSION['status'] = "activ"; else unset($_SESSION['status']);
    /*
                //tote Logins löschen
                $stmt = "DELETE FROM site_login WHERE userid = :userid AND (login IS NULL AND autologin IS NULL)";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(":userid", $userid, PDO::PARAM_INT);
                    $qry->execute();
                } catch(PDOException $e) {die($e->getMessage().': auth.func.login()_del-tote-logs');}
    */
            } else {
                $error_msg []= '- User-ID nicht gefunden -';
                self::logout();
            }
        } else
            $error_msg []= 'error: keine userID übergeben';
        return [$usr_data, $securitytoken_row, self::arr2str($error_msg)];
    }


    /***********************
     * Summary of logout
     */
    public static function logout($target = '')
    {
        $pdo = self::get_pdo();

        if ($target === '') {
            if (empty($_SESSION['lastsite'])) {
                // Herkunftsseite speichern
                $return2 = ['index', 'index2', 'details', 'login', 'email'];
                if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']) === false)) {
                    // wenn VorgängerSeite bekannt und nicht die aufgerufene Seite selbst ist, speichern
                    $referer = str_replace("change", "details", $_SERVER['HTTP_REFERER']);
                    $fn_referer = pathinfo($referer)['filename'];
                    // wenn Herkunft von den target-Seiten, dann zu diesen, ansonsten Standardseite
                    $_SESSION['lastsite'] =  (in_array($fn_referer, $return2))
                        ? $referer
                        : $_SESSION['main'];
                } elseif (empty($_SERVER['HTTP_REFERER']) && empty($_SESSION['lastsite'])) {
                    // wenn nix gesetzt ist, auf Standard index.php verweisen
                    $_SESSION['lastsite'] = (!empty($_SESSION['main'])) ? $_SESSION['main'] : "/";
                }
            }
            $target = $_SESSION['lastsite'];
        }

        $error_msg = "";
        if (isset($_SESSION['userid'])) {
            $userid = $_SESSION['userid'];

            if (!preg_match('/^[0-9]{0,1000}$/', $userid))
                $error_msg = ' - unzulässige Zeichen in Session-User-ID - ';

            else {
                // Cookies löschen --> DB auslogen
                if (self::plausi_check_autocookie()) {
                    $identifier = $_COOKIE['login_ID'];
                    $token_hash = $_COOKIE['login_token'];

                    $stmt0 = "UPDATE site_login SET login = NULL, autologin = NULL WHERE identifier = :identifier"; // muss per Admin gelöscht werden (tote Logins)
                    $stmt = "DELETE FROM site_login WHERE identifier = :identifier";
                    $data = [":identifier" => $identifier];
                    try {
                        $qry = $pdo->prepare($stmt0);
                        $qry->execute($data);
                    } catch(PDOException $e) {die($e->getMessage().': auth.func.logout()_del-user#1');}

                    self::delete_autocookies();

                } else {
                    $stmt0 = "UPDATE site_login SET login = NULL WHERE userid = :userid AND identifier IS NULL";  // dadurch Info 'last.seen'
                    $stmt = "DELETE FROM site_login WHERE userid = :userid AND identifier IS NULL";
                    try {
                        $qry = $pdo->prepare($stmt0);
                        $qry->bindParam(":userid", $userid, PDO::PARAM_INT);
                        $qry->execute();
                    } catch(PDOException $e) {die($e->getMessage().': auth.func.logout()_del-user#2');}
                }
            }
        }

        #$session = ['userid', 'status', 'su', 'sort', 'dir', 'col', 'start', 'proseite', 'thema', 'search', 'loggedin', 'autologin'];
        $session = ['userid', 'status', 'su', 'loggedin', 'autologin'];
        foreach($session AS $i) {
            unset($_SESSION[$i]);
        }
        #unset($_REQUEST, $_POST, $_GET);
        $pdo = $myDB = NULL;
        header("location: {$target}");
        exit;
    }


    /***********************
     * Summary of delete_autocookies
     */
    public static function delete_autocookies()
    {
        // Remove Cookies
        if (isset($_COOKIE['login_ID']) || isset($_COOKIE['login_token'])) {
            setcookie("login_ID", "", time() - 3600, "/", "", 1);
            setcookie("login_token", "", time() - 3600, "/", "", 1);
            unset($_COOKIE['login_ID'], $_COOKIE['login_token']);

            // löscht hier aber nix :-(
            echo "<script>
                localStorage.removeItem('login_ID');
                localStorage.removeItem('login_token');
                localStorage['login_token']= '';
                localStorage.clear();
            </script>";

            session_regenerate_id();
        }
    }


    /***********************
     * Security-Cookies löschen und aus Datenbank austragen
     */
    public static function delete_security_token()
    {
        $pdo = self::get_pdo();

        // wenn Autologin-Cookies --> DB auslogen
        if (self::plausi_check_autocookie()) {
            $identifier = $_COOKIE['login_ID'];
            $token_hash = $_COOKIE['login_token'];

            #$stmt = "DELETE FROM site_login WHERE identifier=:ident AND token_hash=:token_hash";
            $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
                WHERE identifier = :ident AND token_hash = :token_hash";

            $data = [':ident' => $identifier, ':token_hash' => $token_hash];
            try {
                $qry = $pdo->prepare($stmt);
                $qry->execute($data);
            } catch(PDOException $e) {die($e->getMessage().': auth.func.delsecrow()');}

            self::delete_autocookies();
        }
    }


    /***********************
     * Summary of plausi_check_autocookie
     */
    public static function plausi_check_autocookie(): bool
    {
        if (isset($_COOKIE['login_ID'], $_COOKIE['login_token'])) {

            $identifier = $_COOKIE['login_ID'];
            $token_hash = $_COOKIE['login_token'];
            $error_msg = "";

            // Plausi-Check...
            if (!preg_match("/^[a-zA-Z0-9]{1,1000}$/", $identifier))
                $error_msg = '- unzulässige Zeichen in Identifier-Cookies -';
            elseif (!preg_match('/^[a-zA-Z0-9]{1,1000}$/', $token_hash) )
                $error_msg = '- unzulässige Zeichen in Token-Cookies -';

            if ($error_msg) {
                self::delete_autocookies();  // Remove Autologin-Cookies
                self::logout();
            }
        } else $error_msg = 'keine Autologin-Cookies gefunden';
        return (!$error_msg) ? True : False;
    }


    /***********************
     * Summary of securitytoken_holen
     */
    public static function securitytoken_holen($identifier, $token_hash)
    {
        $pdo = self::get_pdo();

        // Kombi Identifier/Token in DB finden und Login-Infos holen
        $stmt = "SELECT * FROM site_login WHERE token_hash = :token_hash AND identifier = :ident";
        $data = [':token_hash' => $token_hash, ':ident' => $identifier];
        try {
            $qry = $pdo->prepare($stmt);
            $qry->execute($data);
            $securitytoken = $qry->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {die($e->getMessage().': auth.func.securitytoken_holen().site_login');}
        return $securitytoken;
    }


    /***********************
     * Summary of refresh_token
     */
    public static function refresh_token($identifier, $token_hash, $token_timer)
    {
        $pdo = self::get_pdo();

        $token_endtime = date('Y-m-d H:i:s', $token_timer);
        $ip = self::remote_addr();

        // neuen Token setzen
        $newtoken_hash = sha1(self::random_string());
        $stmt = "UPDATE site_login
            SET token_hash = :newtoken, token_endtime = :token_endtime, login = 1, autologin = 1, ip = :ip
            WHERE token_hash = :oldtoken AND identifier = :ident";
        try {
            $qry = $pdo->prepare($stmt);
            $qry->bindParam(":newtoken", $newtoken_hash, PDO::PARAM_STR);
            $qry->bindParam(":oldtoken", $token_hash, PDO::PARAM_STR);
            $qry->bindParam(":ident", $identifier, PDO::PARAM_STR);
            $qry->bindParam(":token_endtime", $token_endtime, PDO::PARAM_STR);
            $qry->bindParam(":ip", $ip, PDO::PARAM_STR);
            $qry->execute();
        } catch(PDOException $e) {die($e->getMessage().': auth.func.refresh_token()');}

        // Cookies neu setzen
        setcookie("login_ID", $identifier, $token_timer, "/", "", 1);
        setcookie("login_token", $newtoken_hash, $token_timer, "/", "", 1);
        $_COOKIE['login_ID'] = $identifier;
        $_COOKIE['login_token'] = $newtoken_hash;
        session_regenerate_id();

        // Cookie Variante, LocalStorage
        /*
        echo "<script>
            localStorage.setItem('login_ID', '$identifier');
            localStorage.setItem('login_token', '$newtoken_hash');
        </script>"; */
    }


    /***********************
     * Checks that the user is logged in.
     * Returns the row of the logged in user
     */
    public static function check_user(): array
    {
        $pdo = self::get_pdo();

        $ip = self::remote_addr();
        $now = time();
        $token_timer = $now + 3600*24*365;  // gültig für 1 Jahr

        $usr_data = [];
        $autologin = [];
        $error_msg = [];
        $error_str = '';

        // -0- Heimnetz -> ohne Anmeldung

        // -1- nicht angemeldet, aber Cookies (dauerhaft)
        if (!isset($_SESSION['userid']) && self::plausi_check_autocookie()) {
            $identifier = $_COOKIE['login_ID'];
            $token_hash = $_COOKIE['login_token'];

            $autologin = self::securitytoken_holen($identifier, $token_hash);

            // die Kombi Identifier/Token nicht in der DB gefunden  --> no login
            if (empty($autologin)) {
                // Remove Security Cookies
                self::delete_autocookies();
                $error_msg []= 'error: #1 - cookie trouble, token not found .. no autologin';
                #exit(self::arr2str($error_msg));

            // Token ist veraltet --> no login
            } elseif (strtotime($autologin['token_endtime']) < $now) {
                self::delete_security_token();
                $error_msg []= 'error: #1 - Sitzung ist abgelaufen .. neue <a href="login.php">Anmeldung</a> notwendig ';
                #exit(self::arr2str($error_msg));

            // Cookie-ID/Token in DB gefunden, aber abgemeldet auto=0
            } elseif (empty($autologin["autologin"])) {
                #self::delete_security_token();
                $error_msg []= 'error: #1.1 - Sitzung wurde abgemeldet .. neue <a href="login.php">Anmeldung</a> notwendig ';

            // ID/Token war korrekt --> einloggen
            } else {
                self::refresh_token($identifier, $token_hash, $token_timer);

                // Hole Datensatz und logge ein
                $userid = $autologin['userid'];
                [$usr_data, $autologin, $error_msg] = self::login($userid);
                $success_msg = "#1 -- auto logged in";
            }

        // -2- angemeldet, mit Autologin-Cookies
        } elseif ((isset($_SESSION['userid']) && $_SESSION['userid'] !== "") && self::plausi_check_autocookie()) {
            $userid = $_SESSION['userid'];
            $identifier = $_COOKIE['login_ID'];
            $token_hash = $_COOKIE['login_token'];

            // Plausi-Check...
            if (!preg_match('/^[0-9]{1,1000}$/', $userid))
                $error_msg []= '- unzulässige Zeichen in User-ID -';

            if ($error_msg) {
                $exit = True;

            // Plausi-Check okay --> einloggen
            } else {
                $autologin = self::securitytoken_holen($identifier, $token_hash);

                // die Kombi Identifier/Token nicht in der DB gefunden  --> ausloggen
                if (!$autologin) {

                    // Remove Security Cookies
                    $error_msg []= 'error: #2 - cookie trouble, token not found .. logout';
                    $exit = True;

                // Token ist veraltet --> ausloggen
                } elseif (strtotime($autologin['token_endtime']) < $now) {
                    $error_msg []= 'error: #2 - Token veraltet/abgelaufen .. logout';
                    $exit = True;

                // Cookie-ID/Token in DB gefunden, aber abgemeldet auto=0
                } elseif (empty($autologin['autologin'])) {
                    #self::delete_security_token();
                    $error_msg []= 'error: #2.1 - Sitzung wurde abgemeldet .. neue <a href="login.php">Anmeldung</a> notwendig ';
                    $exit = True;

                // Token war korrekt  --> einloggen
                } elseif ($autologin['userid'] == $userid) {
                    self::refresh_token( $identifier, $token_hash, $token_timer);

                    // Hole Datensatz und logge ein
                    [$usr_data, $autologin, $error_msg] = self::login($userid);
                    $success_msg = "#2 -- auto logged in";

                // UserID <> TokenID verschieden --> ausloggen
                } else {
                    self::delete_security_token();  // Remove Token & Cookies
                    $error_msg []= 'error: #2 - UserID passt nicht zur TokenID .. logout';
                    $exit = True;
                }
            }  // Plausi-Check okay

        // -3- angemeldet, ohne Cookie
        } elseif ((isset($_SESSION['userid']) && $_SESSION['userid'] !== "") && !self::plausi_check_autocookie()) {
            $userid = $_SESSION['userid'];

            // Plausi-Check...
            if (!preg_match('/^[0-9]{0,1000}$/', $userid)) {
                $error_msg []= '- unzulässige Zeichen in Session-User-ID -';
                $exit = True;

            // Login erneuern
            } else {
                $stmt = "SELECT userid FROM site_login WHERE userid = :userid AND identifier IS NULL";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(":userid", $userid, PDO::PARAM_INT);
                    $qry->execute();
                    [$usr_id] = $qry->fetch();
                } catch(PDOException $e) {die($e->getMessage().': auth.func.check_user()_#3-userid');}

                // Login speichern
                $stmt = (!empty($usr_id))
                    ? "UPDATE site_login SET login = 1, ip = :ip WHERE userid = :userid AND identifier IS NULL"
                    : "INSERT INTO site_login (userid, login, ip) VALUES (:userid, 1, :ip)";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(":userid", $userid, PDO::PARAM_INT);
                    $qry->bindParam(":ip", $ip, PDO::PARAM_STR);
                    $qry->execute();
                } catch(PDOException $e) {die($e->getMessage().': auth.func.check_user()_#3-storelogin');}

                // Hole Datensatz und logge ein
                [$usr_data, $autologin, $error_msg] = self::login($userid);
                $success_msg = "angemeldet.";
            }

        // -4- nicht angemeldet, keine Cookies
        } else {
            #$exit = True;
        }

        // Erfolgsmeldung
        if (isset($success_msg) && $success_msg !== "") {
        }
        // Fehlermeldung
        if (isset($error_msg) && $error_msg !== "") {
            $error_str = self::arr2str($error_msg);
        }
        if (isset($exit) && $exit === True) {
            self::logout();
            #echo $error_str;
            #self::error($error_str);
        }
        return [$usr_data, $autologin, $error_str];
    }


    /***********************
     * Returns True when the user is checked in, else false
     */
    public static function is_checked_in(): bool
    {
        return isset($_SESSION['userid']);
        #return isset($_SESSION['loggedin']);
    }


    /***********************
     * (Error-) Array zu String wandeln
     */
    public static function arr2str($array): string
    {
        return implode("<br>", $array);
    }


    /***********************
     * abfragende Adresse
     */
    public static function remote_addr(): string
    {
        return $_SERVER['REMOTE_ADDR'];
    }


    /***********************
     * Returns a random string
     */
    public static function random_string(): string
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(16);
            $str = bin2hex($bytes);
        } else {
            //Replace 'your_secret_string' with a string of your choice (>12 characters) ... Die_Sonne_Der_Mond_Hurz
            $str = md5(uniqid('M;0bAP&2hsS(8nxJS5~S6=kuC', True));
            echo '--> auth.functions.inc.php -> func -> random_string() -> (Zeile 353)';
        }
        return $str;
    }

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}

