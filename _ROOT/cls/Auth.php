<?php
namespace Dzg;

/****************************
 * Funktionscontainer für den Anmelde- und Verifizierungsprozess
 *
 * inspired by:
 * @author: Nils Reimers / http://www.php-einfach.de/experte/php-codebeispiele/loginscript/
 * @license: GNU GPLv3
 *
 * A complete login script with registration and members area.
 */


date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/Database.php';
include_once __DIR__.'/../inc/auth.password.func.php';
include_once __DIR__.'/Tools.php';

require $_SERVER['DOCUMENT_ROOT']."/assets/vendor/autoload.php";
use PHPAuth\Config as PHPAuthConfig;
use PHPAuth\Auth as PHPAuth;


/****************************
 * Stammverzeichnis festlegen, bei Aufruf aus Unterverzeichnis (wie auth/login.php)
 * sonst Probleme zB. mit css Aufruf
 * nötig für Cookies, Header, Footer
 */
$_SESSION['rootdir'] = Tools::rootDir();
if (!isset($_SESSION['main']))
    $_SESSION['main'] = $_SESSION['rootdir'].'/index.php';


/****************************
 * Summary of class Auth
 * Funktionscontainer für den Anmelde- und Verifizierungsprozess
 */
class Auth
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private const TOKEN_TIMER_DAY = 365;  # Autologin gültig für 1 Jahr
    private const PWCODE_TIMER_DAY = 30;  # PW-Code gültig für 30 Tage

    private static array $error_arr;


    /***********************
     * Timer
     */
    public static function getTokenTimer(int $duration_day=0): int
    {
        $day = (empty($duration_day))
            ? self::TOKEN_TIMER_DAY
            : $duration_day;
        return time() + 3600*24 * $day;
    }
    public static function getPWcodeTimer(int $duration_day=0): int
    {
        $day = (empty($duration_day))
            ? self::PWCODE_TIMER_DAY
            : $duration_day;
        return time() + 3600*24 * $day;
    }


    /***********************
     * Summary of login
     * Logge den Benutzer ein, hole dessen Daten aus der DB
     *
     * @param mixed $userid
     * @return array<mixed|string>
     */
    private static function login($userid): array
    {
        // userid prüfen
        if ($userid == (int)$userid) {
            $error_arr = [];

            // Benutzerdaten aus DB holen
            $usr_data = self::getUserData($userid);

            // UserID in DB gefunden, globale Login-Session-Werte setzen
            if ($usr_data) {
                self::setLoginSession($usr_data);

            } else {
                $error_arr []= "#login: User-ID nicht gefunden ";
                self::logout();
            }
        } else
            $error_arr []= "#login: keine korrekte Login-User-ID übergeben ";

        if (!empty($error_arr)) self::$error_arr []= $error_arr;
        return [$usr_data, Tools::arr2str($error_arr)];
    }


    /***********************
     * Summary of logout
     */
    public static function logout($target = "")
    {
        if ($target == "") {
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

        self::logout2();

        header("location: {$target}");
        exit;
    }

    private static function logout2(): bool
    {
        $status = false;

        $error_arr = [];
        if (isset($_SESSION['userid'])) {
            $userid = $_SESSION['userid'];

            if (!preg_match('/^[0-9]{1,19}$/', $userid)
                || $userid != (int)$userid)
            {
                $error_arr []= "#logout: unzulässige User-ID ";

            } else {
                // Cookies löschen --> DB ausloggen
                if (self::checkAutocookie()) {
                    $identifier = $_COOKIE['auto_identifier'];
                    $token_hash = $_COOKIE['auto_token'];

                    $stmt0 = "UPDATE site_login SET login = NULL, autologin = NULL
                        WHERE identifier = :identifier";  # muss per Admin gelöscht werden (tote Logins)
                    $stmt = "DELETE FROM site_login WHERE identifier = :identifier";

                    $data = [":identifier" => $identifier];
                    Database::sendSQL($stmt0, $data);
                    self::deleteAutocookies();
                    $status = true;

                } else {
                    $stmt0 = "UPDATE site_login SET login = NULL
                        WHERE userid = :userid AND identifier IS NULL";  # dadurch Info 'last.seen'
                    $stmt = "DELETE FROM site_login WHERE userid = :userid AND identifier IS NULL";

                    $data = [":userid" => $userid];     # int
                    Database::sendSQL($stmt, $data);
                    $status = true;
                }
            }
        }
        if (!empty($error_arr)) self::$error_arr []= $error_arr;

        #$session = ['userid', 'status', 'su', 'sort', 'dir', 'col', 'start', 'proseite', 'thema', 'search', 'loggedin', 'autologin'];
        $session = ['userid', 'status', 'su', 'loggedin', 'autologin'];
        foreach($session AS $i) {
            unset($_SESSION[$i]);
        }
        #unset($_REQUEST, $_POST, $_GET);

        return $status;
    }


    /***********************
     * Summary of deleteAutocookies
     */
    private static function deleteAutocookies()
    {
        // Remove Cookies
        if (isset($_COOKIE['auto_identifier']) || isset($_COOKIE['auto_token'])) {
            setcookie("auto_identifier", "", time() - 3600, "/", "", 1);
            setcookie("auto_token", "", time() - 3600, "/", "", 1);
            unset($_COOKIE['auto_identifier'], $_COOKIE['auto_token']);

            // löscht hier aber nix :-(
            /*echo "<script>
                localStorage.removeItem('auto_identifier');
                localStorage.removeItem('auto_token');
                localStorage['auto_token']= '';
                localStorage.clear();
            </script>";*/

            #session_regenerate_id();
        }

        $session = ['login_id', 'ident', 'autologin'];
        foreach ($session as $k) {unset($_SESSION[$k]);}

        // Remove OLD Cookies
        if (isset($_COOKIE['login_ID']) || isset($_COOKIE['login_token'])) {
            setcookie("login_ID", "", time() - 3600, "/", "", 1);
            setcookie("login_token", "", time() - 3600, "/", "", 1);
            unset($_COOKIE['login_ID'], $_COOKIE['login_token']);

            // löscht hier aber nix :-(
            /*echo "<script>
                localStorage.removeItem('login_ID');
                localStorage.removeItem('login_token');
                localStorage['login_token']= '';
                localStorage.clear();
            </script>";*/

            #session_regenerate_id();
        }
    }


    /***********************
     * Autologin-Cookies/Session löschen und aus Datenbank austragen
     */
    private static function deleteAutologin()
    {
        // wenn Autologin-Cookies --> DB auslogen
        if (self::checkAutocookie()) {
            $identifier = $_COOKIE['auto_identifier'];
            $token_hash = $_COOKIE['auto_token'];

            #$stmt = "DELETE FROM site_login WHERE identifier=:ident AND token_hash=:token_hash";
            $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
                WHERE identifier = :ident AND token_hash = :token_hash";
            $data = [':ident' => $identifier, ':token_hash' => $token_hash];
            Database::sendSQL($stmt, $data);
            self::deleteAutocookies();
        }
    }


    /***********************
     * Summary of checkAutocookie
     */
    private static function checkAutocookie(): bool
    {
        if (isset($_COOKIE['auto_identifier'], $_COOKIE['auto_token'])) {

            $identifier = (!empty($_COOKIE['auto_identifier']))
                ? htmlspecialchars($_COOKIE['auto_identifier'], ENT_QUOTES)
                : "";
            $token_hash = (!empty($_COOKIE['auto_token']))
                ? htmlspecialchars($_COOKIE['auto_token'], ENT_QUOTES)
                : "";
            $error_arr = [];

            // Plausi-Check...
            // random_16bytes: 32 Zeichen
            if (!preg_match("/^[a-zA-Z0-9]{32}$/", $identifier)) {
                $error_arr []= "*** unzulässige Zeichen im Identifier-Cookies ***";
            }
            // sha1_hash: 40 Zeichen
            if (!preg_match("/^[a-zA-Z0-9]{40}$/", $token_hash)) {
                $error_arr []= "*** unzulässige Zeichen im Token-Cookie ***";
            }

            if (!empty($error_arr)) {
                self::deleteAutocookies();  # Remove Autologin-Cookies
                #self::logout();
            }
        } else $error_arr []= "keine Autologin-Cookies gefunden";


        if (isset($_COOKIE['login_ID'], $_COOKIE['login_token'])) {
            // Set New Cookies
            setcookie("auto_identifier", $_COOKIE['login_ID'], time() + 3600*24*180, "/", "", 1);
            setcookie("auto_token", $_COOKIE['login_token'], time() + 3600*24*180, "/", "", 1);
            $_COOKIE['auto_identifier'] = $_COOKIE['login_ID'];
            $_COOKIE['auto_token'] = $_COOKIE['login_token'];

            // Remove Old Cookies
            setcookie("login_ID", "", time() - 3600, "/", "", 1);
            setcookie("login_token", "", time() - 3600, "/", "", 1);
            unset($_COOKIE['login_ID'], $_COOKIE['login_token']);

            #session_regenerate_id();
        }

        if (!empty($error_arr)) self::$error_arr []= $error_arr;
        return (empty($error_arr)) ? true : false;
    }


    /***********************
     * Summary of getUserData
     * Benutzerdaten aus DB holen
     */
    private static function getUserData($userid): array
    {
        $stmt = "SELECT * FROM site_users WHERE userid = :userid";
        $data = [":userid" => $userid];     # int
        $usr_data = Database::sendSQL($stmt, $data, 'fetch');
        return $usr_data;
    }


    /***********************
     * Summary of getLoginData
     * Login-Daten aus DB holen
     */
    private static function getLoginData(int $userid=0): array
    {
        $login_data = [];

        // mit Autologin
        if (!$userid) {

            $identifier = $_COOKIE['auto_identifier'];
            $token_hash = $_COOKIE['auto_token'];

            // Kombi Identifier/Token in DB finden und Login-Infos holen
            $stmt = "SELECT * FROM site_login
                    WHERE token_hash = :token_hash AND identifier = :ident";
            $data = [':token_hash' => $token_hash, ':ident' => $identifier];
            $login_data = Database::sendSQL($stmt, $data, 'fetch');

        // ohne Autologin
        } else {
            $stmt = "SELECT * FROM site_login WHERE userid = :userid AND identifier IS NULL";
            $data = [':userid', $userid];   # int
            $login_data = Database::sendSQL($stmt, $data, 'fetch');
        }

        return $login_data;
    }


    /***********************
     * Summary of setLoginSession
     */
    public static function setLoginSession($usr_data)
    {
        $_SESSION['userid'] = $usr_data['userid'];
        $_SESSION['loggedin'] = true;
        $_SESSION['su'] = ($usr_data['su'] == 1) ? true : null;
        $_SESSION['status'] = ($usr_data['status'] === "activated") ? "activ" : null;
        foreach ($_SESSION as $k=>$v) {
            if ($v === null) unset($_SESSION[$k]);
        }
    }

    /***********************
     * Summary of setAutologinSession
     */
    public static function setAutologinSession($login_id, $identifier)
    {
        $_SESSION['login_id'] = $login_id;
        $_SESSION['ident'] = $identifier;
        $_SESSION['autologin'] = true;
    }


    /***********************
     * Summary of refreshAutologin
     *
     * neuen Token & Gültigkeitsdauer setzen
     * DB, Cookies, Autologin-Session aktualisieren
     * DB-Login-Daten zuückgeben
     *
     * @param mixed $login_data
     */
    private static function refreshAutologin(&$login_data)
    {
        #if (self::checkAutocookie()):

        $identifier = $_COOKIE['auto_identifier'];
        $token_hash = $_COOKIE['auto_token'];
        $token_timer = self::getTokenTimer();  # gültig für 1 Jahr

        $token_endtime = date('Y-m-d H:i:s', $token_timer);
        $ip = self::remoteAddr();

        // neuen Token setzen
        $newtoken_hash = sha1(self::generateRandomString());

        $stmt1 = "UPDATE site_login
            SET token_hash = :newtoken, token_endtime = :token_endtime, login = 1, autologin = 1, ip = :ip
            WHERE token_hash = :oldtoken AND identifier = :ident";

        $data1 = [
            ':newtoken' => $newtoken_hash,
            ':oldtoken' => $token_hash,
            ':ident' => $identifier,
            ':token_endtime' => $token_endtime,
            ':ip' => $ip
        ];
        Database::sendSQL($stmt1, $data1);

        $stmt2 = "SELECT id FROM site_login WHERE identifier = :ident";
        $data2 = [':ident' => $identifier];
        $login_id = Database::sendSQL($stmt2, $data2, 'fetch', 'num')[0];

        // Cookies neu setzen
        #session_regenerate_id();
        setcookie("auto_identifier", $identifier, $token_timer, "/", "", 1);
        setcookie("auto_token", $newtoken_hash, $token_timer, "/", "", 1);
        $_COOKIE['auto_identifier'] = $identifier;
        $_COOKIE['auto_token'] = $newtoken_hash;

        // Session-Autologin-Werte setzen
        self::setAutologinSession($login_id, $identifier);

        // Cookie Variante, LocalStorage
        /*echo "<script>
            localStorage.setItem('auto_identifier', '$identifier');
            localStorage.setItem('auto_token', '$newtoken_hash');
        </script>";*/


        // Array-Einträge mit neuen Werten überschreiben
        // durch den & Parameter im Funktionsaufruf wird das Original-Array verändert
        $new = [
            'token_hash' => $newtoken_hash,
            'token_endtime' => $token_endtime,
            'ip' => $ip];
        $login_data = array_merge($login_data, $new);
    }


    /***********************
     * Summary of checkUser
     *
     * Checks that the user is logged in.
     * Returns the row of the logged in user
     *
     * @return array<array|array|string>
     */
    public static function checkUser(): array
    {
        $ip = self::remoteAddr();
        $now = time();

        $usr_data = [];
        $login_data = [];
        $error_arr = [];
        $error_msg = "";
        $success_msg = "";
        $exit = false;

        $_1 = "#1.chkusr:";
        $_2 = "#2.chkusr:";
        $_3 = "#3.chkusr:";
        $txt_login = ".. neue <a href='login.php'>Anmeldung</a> notwendig";
        $error_txt = [
            '0' => "",
            '1.0' => "{$_1} cookie trouble, token not found {$txt_login}",
            '1.1' => "{$_1} Sitzung ist abgelaufen {$txt_login}",
            '1.2' => "{$_1} Sitzung wurde abgemeldet {$txt_login}",
            '2.1' => "{$_2} unzulässige User-ID {$txt_login}",
            '2.2' => "{$_2} cookie trouble, token not found {$txt_login}",
            '2.3' => "{$_2} Sitzung ist abgelaufen {$txt_login}",
            '2.4' => "{$_2} Sitzung wurde abgemeldet {$txt_login}",
            '2.5' => "{$_2} Online.UserID passt nicht zur registr. UserID {$txt_login}",
            '3.1' => "{$_3} unzulässige User-ID {$txt_login}",
            '3.2' => "{$_3} nichtvorhandene User-ID",
            '4' => "" ];
        $success_txt = [
            '0' => "",
            '1' => "{$_1} auto.login",
            '2' => "{$_2} auto.login",
            '3' => "{$_3} login",
            '4' => "" ];

        // -0- Heimnetz -> ohne Anmeldung

        // -1- nicht angemeldet, aber Cookies (dauerhaft) --> anmelden
        if (!isset($_SESSION['userid']) && self::checkAutocookie()):

            // mit den Autologin-Cookies die DB-Login-Daten holen
            $login_data = self::getLoginData();

            // die Kombi Identifier/Token nicht in der DB gefunden  --> no login
            if (empty($login_data)) {
                self::deleteAutocookies();
                $error_arr []= $error_txt['1.0'];
                #$exit = true;

            // Token ist veraltet --> no login
            } elseif (strtotime($login_data['token_endtime']) < $now) {
                self::deleteAutologin();
                $error_arr []= $error_txt['1.1'];
                #$exit = true;

            // Cookie-ID/Token in DB gefunden, aber abgemeldet auto=0
            } elseif (empty($login_data['autologin'])) {
                self::deleteAutocookies();
                $error_arr []= $error_txt['1.2'];
                #$exit = true;

            // Cookie-Ident/Token korrekt --> einloggen
            } else {
                self::refreshAutologin($login_data);
                [$usr_data, $error_X] = self::login($login_data['userid']);
                $success_msg = $success_txt['1'];
            }


        // -2- angemeldet, mit Autologin-Cookies
        elseif (!empty($_SESSION['userid']) && self::checkAutocookie()):

            $userid = $_SESSION['userid'];

            // Plausi-Check userid ...
            //
            // Zahl mit 1 bis 19 Ziffern, 10*10^18-1, in php aber nur 9*10^18 möglich
            if (!preg_match('/^[0-9]{1,19}$/', $userid)
                || $userid != (int)$userid)
            {
                $error_arr []= $error_txt['2.1'];
                $exit = true;

            // Plausi-Check okay --> Anmeldeprozedur
            } else {

                // mit den Autologin-Cookies die DB-Login-Daten holen
                $login_data = self::getLoginData();

                // die Kombi Identifier/Token nicht in der DB gefunden  --> ausloggen
                if (empty($login_data)) {
                    self::deleteAutocookies();
                    $error_arr []= $error_txt['2.2'];
                    $exit = true;

                // Token ist veraltet --> ausloggen
                } elseif (strtotime($login_data['token_endtime']) < $now) {
                    self::deleteAutologin();
                    $error_arr []= $error_txt['2.3'];
                    $exit = true;

                // Cookie-ID/Token in DB gefunden, aber abgemeldet auto=0
                } elseif (empty($login_data['autologin'])) {
                    self::deleteAutocookies();
                    $error_arr []= $error_txt['2.4'];
                    $exit = true;

                // DB_login.UserID <> Session_UserID verschieden --> ausloggen
                } elseif ($login_data['userid'] != $userid) {
                    self::deleteAutocookies();
                    $error_arr []= $error_txt['2.5'];
                    $exit = true;

                // UserID & Cookie-Ident/Token korrekt --> einloggen
                } else {
                    self::refreshAutologin( $login_data);
                    [$usr_data, $error_X] = self::login($login_data['userid']);
                    $success_msg = $success_txt['2'];
                }

            }  // Plausi-Check okay


        // -3- angemeldet, ohne Cookie
        elseif (!empty($_SESSION['userid']) && !self::checkAutocookie()):

            $userid = $_SESSION['userid'];

            // Plausi-Check userid ...
            if ($userid != (int)$userid) {
                $error_arr []= $error_txt['3.1'];
                $exit = true;

            // Plausi-Check okay --> Login erneuern
            } else {

                // userid in DB suchen
                $stmt = "SELECT userid FROM site_login
                        WHERE userid=:userid AND identifier IS NULL";
                $data = [':userid' => $userid];     # int
                $usr_id = Database::sendSQL($stmt, $data, 'fetch', 'num')[0];

                // wenn Benutzer vorhanden, Login setzen
                if (!empty($usr_id)) {

                    $stmt = "UPDATE site_login SET login=1, ip=:ip
                        WHERE userid=:userid AND identifier IS NULL";
                    $data = [':userid' => $userid, ':ip' => $ip];
                    Database::sendSQL($stmt, $data);

                    [$usr_data, $error_X] = self::login($userid);
                    $login_data = self::getLoginData($userid);
                    $success_msg = $success_txt['3'];

                } else {
                    $error_arr []= $error_txt['3.2'];
                    $exit = true;
                }
            }

        // -4- nicht angemeldet, keine Cookies
        else:

        endif;


        // Erfolgsmeldung
        if ($success_msg !== "") {
            #echo $msg;
        }

        // Fehlermeldung
        if (!empty($error_arr)) {
            self::$error_arr []= $error_arr;
            $error_msg = Tools::arr2str($error_arr);
            #echo $msg;
        }

        if ($exit) {
            #self::logout2();
            #exit;
            self::logout();
            #self::logout($_SERVER['PHP_SELF']);
        }

        return [$usr_data, $login_data, $error_msg];
    }


    /***********************
     * Returns true when the user is checked in, else false
     */
    public static function isCheckedIn(): bool
    {
        #if (!isset($_SESSION['userid'])) {
        if (!isset($_SESSION['loggedin'])) {
            self::checkUser();
        };
        #return isset($_SESSION['userid']);
        return isset($_SESSION['loggedin']);
    }


    /***********************
     * abfragende Adresse
     */
    public static function remoteAddr(): string
    {
        return $_SERVER['REMOTE_ADDR'];
    }


    /***********************
     * Returns a random string
     */
    public static function generateRandomString(): string
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes(16);
            $str = bin2hex($bytes);
        } else {
            // Replace 'your_secret_string' with a string of your choice (>12 characters) ... Die_Sonne_Der_Mond_Hurz
            $str = md5(uniqid('M;0bAP&2hsS(8nxJS5~S6=kuC', true));
            echo '--> auth.functions.inc.php -> func -> generateRandomString() -> (Zeile 353)';
        }
        return $str;
    }

    private static function generateRandomString0($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $random_string = '';

        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $random_string;
    }
}

