<?php
namespace Dzg\SiteForm;
use Dzg\SitePrep\RegisterInfo as Prep;
use Dzg\SiteData\RegisterInfo as Data;
use Dzg\Tools\{Auth, Tools};
use Dzg\Mail\{MailConfig, Mail};

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__.'/../siteprep/registerinfo.php';
require_once __DIR__.'/../sitedata/registerinfo.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';
require_once __DIR__.'/../mail/Mail.php';
require_once __DIR__.'/../mail/MailConfig.php';


#header('Content-type: text/html; charset=utf-8');

/***********************
 * Summary of Register_info
 */
class RegisterInfo extends Prep
{
    protected static $show_form;
    protected static string $status_message;
    protected static $success_msg;
    protected static $input_message_first;

    protected static $pre_usr;
    protected static $pre_email;


    /***********************
     * Formular-Eingabe verarbeiten
     * und an DB schicken
     */
    protected static function formEvaluation()
    {
        $error_msg = [];
        $success_msg = "";
        $show_form = True;
        $input_name = "";
        $input_email = "";
        $input_message_first = "";

        // Formularwerte empfangen
        if (isset($_GET['regon']) &&
            strtoupper($_SERVER["REQUEST_METHOD"]) === "POST"):

            // Nutzereingaben $_POST auswerten
            if (isset($_POST['email'], $_POST['message'])) {

                // mysql_real_escape_string(): reservierte Zeichen in (my)SQL durch die entsprechenden Escape-Sequenzen ersetzen.
                // Darüber hinaus sollte man alle Eingaben auf Korrektheit überprüfen und ggfs. unerwünschte Zeichen oder Zeichenketten ausfiltern,
                // etwa auch CR und LF-Zeichen, wenn die Eingabe an sich nur einzeilige Angaben zuläßt.
                // Es gibt genügend Tricks, um auch in einem an sich einzeiligen Formularfeld Zeilenvorschübe zu verstecken,
                // was u.a. bei Mail-Formularen zu Problemen führen kann, wenn eine eingegebene Adresse ungefiltert an die mail() - Funktion übergeben wird.

                // htmlspecialchars(): bei der Ausgabe eingesetzen, um zu verhindern, daß vom Benutzer eingegebene HTML-Tags (vor allem <script> aber auch z.B. <iframe>
                // im Kontext der eigenen Webseite erscheinen). Zusätzlich auch strip_tags() verwenden, um alle oder bestimmte HTML-Tags aus der Usereingabe zu filtern.

                # htmlentities(ENT_QUOTES)/htmlspecialchars(ENT_QUOTES) + strip_tags() + stripslashes()
                # urlencode()

                // Plausi-Check Input

                // username: beginnt mit Buchstaben, kann dann auch Zahlen und (._-) enthalten aber nicht damit enden, ist 3-20 Zeichen lang
                $regex_usr = "/^[a-zA-Z]((\.|_|-)?[a-zA-Z0-9äüößÄÜÖ]+){3,20}$/D";

                // (Doppel-)Name mit Bindestrich/Leerzeichen, ohne damit zu beginnen oder zu enden und ohne doppelte Striche/Leerz., ist 0-50 Zeichen lang
                $regex_name = "/^[a-zA-ZäüößÄÜÖ]+([a-zA-ZäüößÄÜÖ]|[ -](?=[a-zA-ZäüößÄÜÖ])){0,50}$/";
                $regex_name_no = "/^[^a-zA-ZäüößÄÜÖ]+|[^a-zA-ZäüößÄÜÖ -]+|[- ]{2,}|[^a-zA-ZäüößÄÜÖ]+$/";

                $input_name = isset($_POST['name']) ? htmlspecialchars(Tools::cleanInput($_POST['name'])) : "";
                if ($input_name !== "" && preg_match_all($regex_name_no, $input_name, $match))
                    $error_msg []= 'nur Buchstaben im Namen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen): "'.htmlentities(implode(" ", $match[0])).'"';
                else {
                    $name_arr = explode(' ', $input_name, 20);
                    if (count($name_arr) < 2) {
                        // nur ein Element = Vorname
                        [$input_vor] = $name_arr;
                        $input_nach = '';
                    } else {
                        // letztes Element = Nachname, davor alles als Vorname
                        $input_nach = array_pop($name_arr);
                        $input_vor = implode(' ', $name_arr);
                    }
                }
                // Email
                $input_email = isset($_POST['email']) ? htmlspecialchars(Tools::cleanInput($_POST['email'])) : "";
                if ($input_email === "")
                    $error_msg []= 'Email angeben.';
                elseif (!filter_var($input_email, FILTER_VALIDATE_EMAIL))
                    $error_msg []= 'Keine gültige Email-Adresse.';


                // Nachricht: enthält Buchstaben/Zahlen, Leer, Tab, Zeilenwechsel, Sonderzeichen und special escaped regex-Zeichen; negiert um falsche Zeichen auszugeben
                $regex_mess_no = "/[^\w\s\t\r\n ×÷=<>:;,!@#€%&§`´~£¥₩₽° •○●□■♤♡◇♧☆▪︎¤《》¡¿♠︎♥︎◆♣︎★±≈≠≡【〔「『】〕」』¡№٪‰–—‽· \\\\\^\$\.\|\?\*\+\-\(\)\[\]\{\}\/\"\']/mu";

                $input_message_first = $_POST['message'];   // wird nicht weiter verwendet
                $input_message = isset($_POST['message']) ? Tools::cleanInput($_POST['message']) : "";
                $input_message = preg_replace("/\r\r|\r\n|\n\r|\n\n|<br>/","\n", $input_message);
                if ($input_email !== "" && preg_match_all($regex_mess_no, $input_message, $match))
                    $error_msg []= 'Die Nachricht verwendet unzulässige Zeichen: "'.htmlentities(implode(" ", $match[0])).'"';

                #$error_msg []= ' -regex.end-';

                $input_message = htmlspecialchars($input_message);
                #echo $input_message = htmlspecialchars($input_message, ENT_QUOTES);
                #$input_message = htmlentities($input_message, ENT_QUOTES);     // auch Umlaute werden auch umgewandelt

            } else {
                $error_msg []= 'keine Email und Nachricht angeben.';
            }

            // Eingaben okay, Mail vorbereiten und versenden
            if (empty($error_msg)) {

                // Code für Zugang zur Registrierungsseite, 30 Tage gültig
                $reg_code = uniqid();
                $pwcode_endtime = Auth::getPWcodeTimer();

                // Links für Email-Versand erzeugen
                $reg_url  = Tools::getSiteURL().'register.php?code='.$reg_code;
                $reg_link = 'register.php?code='.$reg_code;     // intern

                $input_usr_temp   = $input_vor."_dummy_";
                $input_email_temp = $input_email."_dummy_";
                $status = $reg_code;
                $notiz  = $reg_url;

                // Nutzerdaten schon einmal in DB temporär erfassen
                $data = [
                    ':username'       => $input_usr_temp,
                    ':email'          => $input_email_temp,
                    ':status'         => $status,
                    ':pwcode_endtime' => $pwcode_endtime,
                    ':notiz'          => $notiz,
                    ':vorname'        => $input_vor,
                    ':nachname'       => $input_nach ];
                Data::storeUser($data);

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
                $subject1 = 'Registrierungs-Link für www.danzigmarken.de';
                $mailcontent1 = "Hallo ".$input_name.",\n\n".
                    "du kannst dich jetzt auf www.danzigmarken.de registrieren. ".
                    "Rufe dazu in den nächsten 4 Wochen (bis zum ".date('d.m.y', $pwcode_endtime).") ".
                    "den folgenden Link auf: \n\n".$reg_url ."\n\n".
                    "Herzliche Grüße\n".
                    "Dein Support-Team von www.danzigmarken.de\n";

                // ---- create mail for admin(steffen)
                $mailto2  = $smtp['from_addr'];
                $subject2 = "[Info:] Anfrage zur Registrierung auf www.danzigmarken.de";
                $mailcontent2 = "Folgendes wurde am ". $date ." Uhr per Formular empfangen:\n".
                    "-------------------------------------------------------------------------\n\n".
                    "E-Mail: ".$input_email."\n".
                    "Name: ".$input_name."\n\n".

                    "Nachricht:\n\n".preg_replace("/\r\r|\r\n|\n\r|\n\n/","\n",$input_message)."\n\n\n".
                    "IP Adresse: ".$ip." - ".$host." - ".$UserAgent."\n".
                    "-------------------------------------------------------------------------\n\n".
                    "Eine Email an den Anfragenden wurde gesendet.\n\n".
                    "Von: ".$smtp['from_name']." <".$mailto2.">\n".
                    "An: ".$input_email."\n".
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

                // === ENDE EMAIL-Abschnitt ===

                if ($email_send1) {
                    $success_msg = "Eine Email mit deiner Anfrage wurde versandt. Du erhälst in Kürze eine Antwort.";
                    $show_form = False;
                } else {
                    $error_arr []= 'Oh, die Email konnte <b>NICHT</b> gesendet werden :-(';
                }

                // Eingabewerte nicht okay, kein Mailversand
                } else {}

                // Formular nach Verarbeitung wieder löschen
                unset($_POST);
                #$input_name = "";
                #$input_email = "";
                #$input_message_first = "";

        endif;  # Formularwerte empfangen


        if (!empty($error_arr)) {
            $error_arr = [implode("<br>", $error_arr)];
        }
        $error_msg = implode("", $error_msg);


        // Wert für das Vorausfüllen des Login-Formulars
        $pre_email = "";
        $pre_usr   = "";
        if (isset($_GET['email']))
            $pre_email = htmlspecialchars(Tools::cleanInput($_GET['email']));
        elseif ($input_email != "")
            $pre_email = $input_email;
        elseif(isset($_GET['usr']))
            $pre_usr = htmlspecialchars(Tools::cleanInput($_GET['usr']));
        elseif ($input_name != "")
            $pre_usr = $input_name;



        $show_form = ($error_msg === "") ? True : False;
        $status_message = Tools::statusOut($success_msg, $error_msg);


        self::$show_form = $show_form;
        self::$status_message = $status_message;
        self::$success_msg = $success_msg;
        self::$input_message_first = $input_message_first;
        #self::$input_name = $input_name;
        #self::$input_email = $input_email;
        self::$pre_usr = $pre_usr;
        self::$pre_email = $pre_email;

    }
}
