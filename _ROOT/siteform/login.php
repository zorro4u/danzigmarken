<?php
namespace Dzg;
use Dzg\Tools\{Auth, Tools};

require_once __DIR__.'/../siteprep/login.php';
require_once __DIR__.'/../sitedata/login.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


class LoginForm extends LoginPrep
{
    protected static bool $show_form;
    #protected static string $cookie;
    protected static string $status_message;
    protected static string $user_value;
    protected static string $input_email1;
    protected static string $input_usr;


    /**
     * Formular-Eingabe verarbeiten
     * und an DB schicken
     */
    protected static function formEvaluation()
    {
        $msg = self::MSG;
        $error_arr = [];
        $success_msg = self::$success_msg;
        $input_usr = "";
        $input_email1 = "";
        $input_pwNEU1 = "";

        // Loginformular empfangen
        if(isset($_GET['login']) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST") {

            // Eingabewerte auf Plausibilität prüfen
            if(isset($_POST['email'], $_POST['passwort'])) {

                // Eingabeformular hat Daten mit $_POST gesendet
                $input_email1 = htmlspecialchars(Tools::cleanInput($_POST['email']));
                $input_pwNEU1 = $_POST['passwort'];
                $input_usr = "";

                // Passwortlänge prüfen
                if(strlen($input_pwNEU1) < 4 || strlen($input_pwNEU1) > 50) {
                    $error_arr []= $msg[210];
                }
                // Passwort-Zeichen prüfen (nur alphanumerisch + ein paar Sonderzeichen (keine sql kritischen), Länge <100 Zeichen
                $regex = "/^[\w<>()?!,.:_=$%&#+*~^ @€µÄÜÖäüöß]{1,100}$/";  // attention: add a slash at the begin and the end
                if (!preg_match($regex, $input_pwNEU1))
                    $error_arr []= $msg[211];

                // Email / Name prüfen
                if (!filter_var($input_email1, FILTER_VALIDATE_EMAIL)) {
                    // keine Email -> Eingabe als Benutzername (nur alphanumerisch, 1-50 Zeichen)
                    (!preg_match("/^\w{1,50}$/", $input_email1))
                        ? $error_arr []= $msg[212]
                        : $input_usr = strtolower($input_email1);
                }
            }
            if(!empty($error_arr)) {
                unset($_SESSION['userid']);
                $_SESSION['loggedin'] = False;

            // Plausi-Check okay --> einloggen
            } else {

                // Nutzerdaten in DB finden & holen
                $data = [':email' => $input_email1, ':username' => $input_usr];
                $usr_data = LoginData::searchUser($data);

                // Nutzer gefunden und Passwort korrekt
                if($usr_data !== False) {

                    // Nutzer Status aktiv
                    if ($usr_data['status'] === "activated") {

                        // Passwort Vergleich okay
                        if(password_verify($input_pwNEU1, $usr_data['pw_hash'])) {
                            $userid = $usr_data['userid'];
                            $ip = Auth::remoteAddr();

                            // Passwort neu hashen, wenn Algo nicht übereinstimmen
                            // und in der DB den alten Hash durch den neuen ersetzen
                            if(password_needs_rehash($usr_data['pw_hash'], PASSWORD_DEFAULT)) {
                                $pw_hash = password_hash($input_pwNEU1, PASSWORD_DEFAULT);
                                #$pw_hash = password_hash($input_pw, PASSWORD_BCRYPT, ['cost' => 12]);

                                $data = [
                                    ':userid'   => $userid,
                                    ':ip'       => $ip,
                                    ':pw_hash'  => $pw_hash ];
                                LoginData::storePWhash($data);
                            }

                            // Nutzer möchte angemeldet bleiben (1 Jahr)
                            if(isset($_POST['angemeldet_bleiben'])) {
                                $identifier = Auth::generateRandomString();
                                $token_hash = sha1(Auth::generateRandomString());
                                $token_timer = Auth::getTokenTimer();  # gültig für 1 Jahr
                                $token_endtime = date('Y-m-d H:i:s', $token_timer);

                                // Autologin: Identifier/Token eintragen
                                $data = [
                                    ':userid'        => $userid,   # int
                                    ':identifier'    => $identifier,
                                    ':token_hash'    => $token_hash,
                                    ':token_endtime' => $token_endtime,
                                    ':ip'            => $ip ];
                                $result = LoginData::storeToken($data);

                                is_int($result)
                                ? $login_id = $result
                                : exit($result);

                                // Cookies setzen --- (name, value, expire, path, domain, ...) siehe neu: cookies vs. localStorage
                                #session_regenerate_id();
                                setcookie("auto_identifier", $identifier, $token_timer, "/", "", 1);
                                setcookie("auto_token", $token_hash, $token_timer, "/", "", 1);
                                $_COOKIE['auto_identifier'] = $identifier;
                                $_COOKIE['auto_token'] = $token_hash;

                                // Session-Autologin-Werte setzen
                                Auth::setAutologinSession($login_id, $identifier);


                                // Cookie Variante, LocalStorage
                                // speichert hier aber nix :-(
                                // erst in Auth::checkUser()->refresh_token()
                                /*self::$cookie =
                                    "<script>
                                        localStorage.setItem('auto_identifier', $identifier);
                                        localStorage.setItem('auto_token', $token_hash);
                                    </script>";*/


                            // Anmelden ohne Autologin
                            } else {
                                // Nutzereintrag ohne Autologin (identifier) finden
                                // dadurch nur ein db-Eintrag pro Einfach-Login
                                /*
                                $stmt = "SELECT userid FROM site_login WHERE userid=:userid AND identifier IS NULL";
                                try {
                                    $qry = $pdo->prepare($stmt);
                                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                                    $qry->execute();
                                    [$usr_id] = $qry->fetch();
                                } catch(PDOException $e) {die($e->getMessage().': login.inc_finduser');}

                                // Login speichern
                                $stmt = ($usr_id == $userid)
                                    ? "UPDATE site_login SET login=1, ip=:ip WHERE userid=:userid AND identifier IS NULL"
                                    : "INSERT INTO site_login (userid, login, ip) VALUES (:userid, 1, :ip)";
                                */

                                // Login speichern
                                $data = [':userid' => $userid, ':ip' => $ip];
                                LoginData::storeLogin($data);
                            }
                            $success_msg = $msg[213];

                            // Session-Login-Werte setzen
                            Auth::setLoginSession($usr_data);

                            // Rücksprung zur Herkunftsseite
                            header("location: {$_SESSION['lastsite']}");
                            exit;

                        } else {
                            $error_arr []= $msg[214];
                        }

                    } elseif ($usr_data['status'] === "deaktiv") {
                        $error_arr []= $msg[215];

                    } else {
                        $error_arr []= $msg[216];
                    }

                } elseif ($input_email1 !== "") {
                    $error_arr []= $msg[217];
                }
            }  # Plausi-Check okay .. einloggen
        }      # Ende Auswertung Login-Formular


        // Wert für das Vorausfüllen des Login-Formulars
        $user_value = "";
        if(isset($_GET['usr']) || $input_usr !== "") {
            isset($_GET['usr'])
                ? $user_value = htmlspecialchars(Tools::cleanInput($_GET['usr']))
                : $user_value = $input_usr;
        } else {
            if($input_email1 !== "")
                $user_value = $input_email1;
        }

        // Fehlermeldung
        $error_msg = (!empty($error_arr)) ? Tools::arr2str($error_arr) : "";

        self::$show_form = ($success_msg === "") ? true : false;
        self::$status_message = Tools::statusOut($success_msg, $error_msg);
        self::$user_value = $user_value;
        self::$input_email1 = $input_email1;
        self::$input_usr = $input_usr;
    }
}


// EOF