<?php
namespace Dzg\SiteForm;
use Dzg\SitePrep\Register as Prep;
use Dzg\SiteData\Register as Data;
use Dzg\Tools\{Auth, Tools};
use Dzg\Mail\{MailConfig, Mail};

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__.'/../siteprep/register.php';
require_once __DIR__.'/../sitedata/register.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';
require_once __DIR__.'/../mail/Mail.php';
require_once __DIR__.'/../mail/MailConfig.php';


/***********************
 * Summary of Register
 */
class Register extends Prep
{
    protected static $show_form;
    protected static string $status_message;
    protected static $input_usr;
    protected static $activate_needed;


    /***********************
     * Formular-Eingabe verarbeiten
     * und an DB schicken
     */
    protected static function formEvaluation()
    {
        $usr_data  = self::$usr_data;
        $error_msg = self::$error_msg;

        $input_usr = "";
        $success_msg = "";
        $exist = False;
        $show_form = True;
        $activate_needed = False;

        // Registrierungs-Link nicht okay, Seite nicht starten
        if ($error_msg):
            $show_form = False;

        // Registrierung-Link okay, Seite starten
        else:


        // Formularwerte empfangen
        if (isset($_GET['regon']) && (strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")) {

            // Nutzereingaben $_POST auswerten
            if (isset($_POST['passwort'], $_POST['passwort2']) && isset($_POST['username'], $_POST['email'])) {
                $regex_usr = "/^[\wäüößÄÜÖ\-]{3,50}$/";
                $regex_email = "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix";
                $regex_pw = "/^[\w<>()?!,.:_=$%&#+*~^ @€µäüößÄÜÖ]{1,100}$/";

                $input_usr = strtolower(htmlspecialchars(Tools::cleanInput($_POST['username'])));
                $input_email = htmlspecialchars(Tools::cleanInput($_POST['email']));
                $input_pw1 = $_POST['passwort'];
                $input_pw2 = $_POST['passwort2'];

                // Plausi-Check Input

                // Name und Email angegeben
                if ($input_usr === "" || $input_email === "") {
                    $error_msg = 'Name und Email angeben.';

                // Username-Check
                } elseif ($input_usr !== "" && preg_match("/\W/", $input_usr, $matches)) {     // nur alphanumerisch
                    $error_msg = 'nur Kleinbuchstaben/Zahlen im Anmeldenamen zulässig: '.htmlspecialchars($matches[0]);
                }

                // Email
                if ($input_email !== "" && (!filter_var($input_email, FILTER_VALIDATE_EMAIL)))
                    $error_msg = 'Keine gültige Email-Adresse.';


                // Eingabe usernamen/email okay
                if ($error_msg === "") {

                    // usernamen/email im Bestand suchen
                    $data = [':username' => $input_usr, ':email' => $input_email];
                    $userliste = Data::searchActivatedUser($data);

                    // Benutzername vergeben
                    if ($input_usr !== "") {
                        if ($userliste) {
                            foreach ($userliste AS $user_info) {
                                $exist = array_search($input_usr, $user_info);
                                if ($exist) break;
                            }
                        }
                        $exist
                            ? $error_msg = "Der Benutzername ist schon vergeben."
                            : $update_username = True;
                    }

                    // Email vergeben
                    if ($input_email !== "") {
                        if ($userliste) {
                            foreach ($userliste AS $user_info) {
                                $exist = array_search($input_email, $user_info);
                                if ($exist) break;
                            }
                        }
                        $exist
                            ? $error_msg = "Die E-Mail-Adresse ist bereits registriert."
                            : $update_email = True;
                    }
                }  # Eingabe usernamen/email okay

                // usernamen/email nutzbar --> Passwort Check
                if ($error_msg === "") {
                    if ($input_pw1 !== $input_pw2) {
                        $error_msg = "Bitte identische Passwörter eingeben";

                    } elseif (strlen($input_pw1) < 4 || strlen($input_pw1) > 50) {
                            $error_msg = 'Passwort muss zwischen 4 und 50 Zeichen lang sein!';

                    } elseif (!preg_match($regex_pw, $input_pw1)) {
                            $error_msg = 'Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>';

                    } else {
                        // PW-Check, alles okay
                    }
                }

            } else {
                // $_POST auswerten, Nutzereingaben unvollständig
                $error_msg = 'Name, Email und Passwort angeben.';
            }

            $userliste = [];  # Bestandsliste wieder löschen;

            // Eingaben okay, jetzt in Datenbank schreiben
            if ($error_msg === "") {

                // wenn email gleich wie anfangs, dann keine Email Verifizierung nötig.
                if ($input_email == str_replace("_dummy_", "", $usr_data['email'])) {
                    $activate_needed = False;

                    $pwcode_endtime = NULL;
                    $status = 'activated';
                    $notiz = NULL;

                } else {
                    $activate_needed = True;

                    // temporärer Aktivierungscode, 30 Tage gültig
                    $act_code = uniqid();
                    $pwcode_endtime = Auth::getPWcodeTimer();

                    // Links für Email-Versand erzeugen
                    $activate_url = Tools::getSiteURL().'activate.php?code='.$act_code;
                    $activate_link = 'activate.php?code='.$act_code;  // intern

                    $status = $act_code;
                    $notiz = $activate_url;
                }

                $passwort_hash = password_hash($input_pw1, PASSWORD_DEFAULT);

                // Nutzerdaten in DB eintragen
                $data = [
                    ':userid'         => $usr_data['userid'],   # int
                    ':username'       => $input_usr,
                    ':email'          => $input_email,
                    ':pw_hash'        => $passwort_hash,
                    ':pwcode_endtime' => $pwcode_endtime,
                    ':status'         => $status,
                    ':notiz'          => $notiz ];
                Data::storeUser($data);

                // wenn Konto-Email anders als Anfrage-Email,
                // dann noch Verifizierung per Aktivierungs-Mail
                if ($activate_needed) {

                    // === EMAIL ===
                    //
                    $cfg  = MailConfig::$cfg;
                    $smtp = MailConfig::$smtp[0];

                    $ip   = $_SERVER['REMOTE_ADDR'];
                    $host = getHostByAddr($ip);
                    $UserAgent = $_SERVER["HTTP_USER_AGENT"];
                    $date = date("d.m.Y | H:i");

                    // ---- create mail for customer
                    $mailto1  = $input_email;
                    $subject1 = "Kontoaktivierung auf www.danzigmarken.de";
                    $mailcontent1 = "Hallo ".$mailto1.",\n\n".
                        "dein Konto auf www.danzigmarken.de muss noch bis zum ".date("d.m.y", $pwcode_endtime)." aktiviert werden. ".
                        "Rufe dazu folgenden Link auf: \n\n".$activate_url."\n\n".
                        "Herzliche Grüße\n".
                        "Dein Support-Team von www.danzigmarken.de\n";

                    // ---- create mail for admin
                    $mailto2  = $smtp['from_addr'];
                    $subject2 = "[Info:] Anfrage zur Kontoaktivierung auf www.danzigmarken.de";
                    $mailcontent2 = "Folgendes wurde am ". $date ." Uhr verschickt:\n".
                        "-------------------------------------------------------------------------\n\n".
                        "Eine Email an den Anfragenden wurde gesendet.\n\n".
                        "Von: ".$smtp['from_name']." <".$smtp['from_addr'].">\n".
                        "An: ".$mailto1."\n".
                        "Betreff: ".$subject1."\n".
                        "Nachricht:\n\n".$mailcontent1;

                    // mail it to customer
                    $email_send1 = Mail::sendMyMail(
                        $smtp['from_addr'],
                        $smtp['from_name'],
                        $mailto1,
                        $subject1,
                        $mailcontent1
                    );
                    // mail it to admin
                    $email_send2 = Mail::sendMyMail(
                        $smtp['from_addr'],
                        $smtp['from_name'],
                        $mailto2,
                        $subject2,
                        $mailcontent2
                    );
                }  # ende.Email-Verifizierungs-Abschnitt


                if ($activate_needed) {
                    if ($email_send1) {
                        $success_msg = "Eine Email wurde dir soeben zur Bestätigung zugesandt, in der die Registrierung abschließend noch aktiviert werden muss. Danach kannst du dich anmelden.";
                        $show_form = False;
                    } else {
                        $error_arr []= 'Oh, die Email konnte <b>NICHT</b> gesendet werden :-(';
                    }
                } else {
                    $success_msg = 'Du wurdest erfolgreich registriert und kannst dich jetzt <a href="login.php?usr='.$input_usr.'">anmelden</a>.';
                    $show_form = False;
                }

            }   # Eingabewerte in Datenbank schreiben
        };      # Formularwerte empfangen
        endif;  # Registrierung-Link okay, Seite starten


        #$show_form = ($error_msg === "") ? True : False;
        $status_message = Tools::statusOut($success_msg, $error_msg);


        self::$status_message  = $status_message;
        self::$activate_needed = $activate_needed;
        self::$show_form = $show_form;
        self::$input_usr = $input_usr;
        self::$error_msg = $error_msg;
    }
}