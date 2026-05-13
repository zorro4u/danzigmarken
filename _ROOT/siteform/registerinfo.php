<?php
namespace Dzg;
use Dzg\Tools\{Auth, Tools};
use Dzg\Mail\{MailConfig, Mail};

require_once __DIR__.'/../siteprep/registerinfo.php';
require_once __DIR__.'/../sitedata/registerinfo.php';
require_once __DIR__.'/../sitemsg/registerinfo.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';
require_once __DIR__.'/../mail/Mail.php';
require_once __DIR__.'/../mail/MailConfig.php';


/**
 * Summary of Class RegisterInfo
 */
class RegisterInfoForm extends RegisterInfoPrep
{
    protected const MSG = RegisterInfoMsg::MSG;

    // username: beginnt mit Buchstaben, kann dann auch Zahlen und (._-) enthalten aber nicht damit enden, ist 3-20 Zeichen lang
    private const REGEX_USR = "/^[a-zA-Z]((\.|_|-)?[a-zA-Z0-9äüößÄÜÖ]+){3,20}$/D";

    // (Doppel-)Name mit Bindestrich/Leerzeichen, ohne damit zu beginnen oder zu enden und ohne doppelte Striche/Leerz., ist 0-50 Zeichen lang
    private const REGEX_NAME = "/^[a-zA-ZäüößÄÜÖ]+([a-zA-ZäüößÄÜÖ]|[ -](?=[a-zA-ZäüößÄÜÖ])){0,50}$/";
    private const REGEX_NAME_NO = "/^[^a-zA-ZäüößÄÜÖ]+|[^a-zA-ZäüößÄÜÖ -]+|[- ]{2,}|[^a-zA-ZäüößÄÜÖ]+$/";

    // Nachricht: enthält Buchstaben/Zahlen, Leer, Tab, Zeilenwechsel, Sonderzeichen und special escaped regex-Zeichen; negiert um falsche Zeichen auszugeben
    private const REGEX_MESS_NO = "/[^\w\s\t\r\n ×÷=<>:;,!@#€%&§`´~£¥₩₽° •○●□■♤♡◇♧☆▪︎¤《》¡¿♠︎♥︎◆♣︎★±≈≠≡【〔「『】〕」』¡№٪‰–—‽· \\\\\^\$\.\|\?\*\+\-\(\)\[\]\{\}\/\"\']/mu";

    protected static bool $show_form;
    protected static string $status_message;
    protected static string $success_msg;
    protected static string $input_message_first;

    protected static string $pre_usr;
    protected static string $pre_email;


    /**
     * Formular-Eingabe verarbeiten
     * und an DB schicken
     */
    protected static function formEvaluation(): void
    {
        $error_msg = [];
        $success_msg = "";
        $show_form = True;
        $input = [
            'name' => "",
            'mail' => "",
            'message' => "",
            'first' => "",
            'vor'  => "",
            'nach' => ""
        ];


        // Formularwerte empfangen
        //
        if (isset($_GET['regon'])
            && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")
        {
            // Nutzereingaben $_POST auswerten
            //
            $error_msg []= (isset($_POST['email'], $_POST['message']))
                ? self::plausi_check($input)
                : self::MSG[214];


            // Eingaben okay, Mail vorbereiten und versenden
            //
            if (empty($error_msg)) {

                # Code für Zugang zur Registrierungsseite erzeugen, 30 Tage gültig
                $reg_code = uniqid();
                $pwcode_endtime = Auth::getPWcodeTimer();

                # Links für Email-Versand erzeugen
                $reg_url  = Tools::getSiteURL().'register.php?code='.$reg_code;
                $reg_link = 'register.php?code='.$reg_code;     # intern

                $input_usr_temp   = $input['vor']."_dummy_";
                $input_email_temp = $input['mail']."_dummy_";
                $status = $reg_code;
                $notiz  = $reg_url;

                # Nutzerdaten schon einmal in DB temporär erfassen
                $data = [
                    ':username'       => $input_usr_temp,
                    ':email'          => $input_email_temp,
                    ':status'         => $status,
                    ':pwcode_endtime' => $pwcode_endtime,
                    ':notiz'          => $notiz,
                    ':vorname'        => $input['vor'],
                    ':nachname'       => $input['nach'] ];
                RegisterInfoData::storeUser($data);

                # Email senden
                if (self::send_email($input, $pwcode_endtime, $reg_url)) {
                    $success_msg = self::MSG[215];
                    $show_form = False;
                } else {
                    $error_msg []= self::MSG[216];
                };
            }

            # Eingabewerte nicht okay, dann auch kein Mailversand
            else {};

            # Formular nach Verarbeitung wieder löschen
            unset($_POST);

        };  // Formularwerte empfangen


        $error_msg = !empty($error_msg)
            ? implode("<br>", $error_msg)  # array -> string
            : "";


        // Wert für das Vorausfüllen des Login-Formulars
        //
        $pre_email = "";
        $pre_usr   = "";
        if (isset($_GET['email'])) {
            $pre_email = htmlspecialchars(Tools::cleanInput($_GET['email']));
        }
        elseif ($input['mail'] != "") {
            $pre_email = $input['mail'];
        }
        elseif (isset($_GET['usr'])) {
            $pre_usr = htmlspecialchars(Tools::cleanInput($_GET['usr']));
        }
        elseif ($input['name'] != "") {
            $pre_usr = $input['name'];
        };


        $show_form = ($error_msg === "") ? True : False;
        $status_message = Tools::statusOut($success_msg, $error_msg);


        self::$show_form = $show_form;
        self::$status_message = $status_message;
        self::$success_msg = $success_msg;
        self::$input_message_first = $input['message'];
        #self::$input_message_first = $input['first'];
        #self::$input_name = $input['name'];
        #self::$input_email = $input['mail'];
        self::$pre_usr = $pre_usr;
        self::$pre_email = $pre_email;
    }



    /**
     * liest und prüft $_POST und übergibt es dem $input Array
     *
     * @param array $input : Array der Benutzereingaben, wird überarbeitet zurückgegeben
     * - @param string $input['name'] : Name
     * - @param string $input['mail'] : Email
     * - @param string $input['message'] : Nachricht
     * - @param string $input['vor']  : Vorname
     * - @param string $input['nach'] : Nachname
     * @return string : Fehlermeldung
     */
    private static function plausi_check(array &$input): string
    {
        // mysql_real_escape_string(): reservierte Zeichen in (my)SQL durch die entsprechenden Escape-Sequenzen ersetzen.
        // Darüber hinaus sollte man alle Eingaben auf Korrektheit überprüfen und ggfs. unerwünschte Zeichen oder Zeichenketten ausfiltern,
        // etwa auch CR und LF-Zeichen, wenn die Eingabe an sich nur einzeilige Angaben zuläßt.
        // Es gibt genügend Tricks, um auch in einem an sich einzeiligen Formularfeld Zeilenvorschübe zu verstecken,
        // was u.a. bei Mail-Formularen zu Problemen führen kann, wenn eine eingegebene Adresse ungefiltert an die mail() - Funktion übergeben wird.

        // htmlspecialchars(): bei der Ausgabe eingesetzen, um zu verhindern, daß vom Benutzer eingegebene HTML-Tags (vor allem <script> aber auch z.B. <iframe>
        // im Kontext der eigenen Webseite erscheinen). Zusätzlich auch strip_tags() verwenden, um alle oder bestimmte HTML-Tags aus der Usereingabe zu filtern.

        # htmlentities(ENT_QUOTES)/htmlspecialchars(ENT_QUOTES) + strip_tags() + stripslashes()
        # urlencode()

        # Name
        $error_msg = "";
        $input['name'] = isset($_POST['name'])
            ? htmlspecialchars(Tools::cleanInput($_POST['name']))
            : "";

        if ($input['name'] !== ""
            && preg_match_all(self::REGEX_NAME_NO, $input['name'], $match))
        {
            $error_msg = self::MSG[210].': "'.htmlentities(implode(" ", $match[0])).'"';
        }
        else {
            $name_arr = explode(' ', $input['name'], 20);

            # nur ein Element = Vorname
            if (count($name_arr) < 2) {
                [$input['vor']] = $name_arr;
                $input['nach']  = '';
            }

            # letztes Element = Nachname, davor alles als Vorname
            else {
                $input['nach'] = array_pop($name_arr);
                $input['vor']  = implode(' ', $name_arr);
            };
        };


        # Email
        $input['mail'] = isset($_POST['email'])
            ? htmlspecialchars(Tools::cleanInput($_POST['email']))
            : "";

        if ($input['mail'] === "") {
            $error_msg = self::MSG[211];
        }
        elseif (!filter_var($input['mail'], FILTER_VALIDATE_EMAIL)) {
            $error_msg = self::MSG[212];
        };


        # Nachricht
        #$input['first']   = $_POST['message'];   // wird nicht weiter verwendet
        $input['message'] = isset($_POST['message'])
            ? Tools::cleanInput($_POST['message'])
            : "";
        $newline = "/\r\r|\r\n|\n\r|\n\n|<br>/";
        $input['message'] = preg_replace($newline,"\n", $input['message']);

        if ($input['mail'] !== ""
            && preg_match_all(self::REGEX_MESS_NO, $input['message'], $match))
        {
            $error_msg = self::MSG[213] . ': "' . htmlentities(implode(" ", $match[0])).'"';
        };


        $input['message'] = htmlspecialchars($input['message']);
        #$input['message'] = htmlentities($input['message'], ENT_QUOTES);  // auch Umlaute werden umgewandelt

        return $error_msg;
    }


    private static function
    send_email(array $input, string $pwcode_endtime, string $reg_url): bool
    {
        $cfg  = MailConfig::$cfg;
        $smtp = MailConfig::$smtp[0];
        $email1_okay = $email2_okay = false;

        $ip   = $_SERVER['REMOTE_ADDR'];
        $host = getHostByAddr($ip);
        $date = date("d.m.Y | H:i");
        $UserAgent = $_SERVER["HTTP_USER_AGENT"];


        # create mail for customer
        $mailto1  = $input['mail'];
        $subject1 = self::MSG[217];
        $mailcontent1 = self::MSG[218].' '.$input['name'].",\n\n".
            self::MSG[219].' '.
            self::MSG[220].date(' d.m.y ', $pwcode_endtime).") ".
            self::MSG[221].": \n\n".$reg_url ."\n\n".
            self::MSG[222]."\n".
            self::MSG[223]."\n";

        # create mail for admin(steffen)
        $mailto2  = $smtp['from_addr'];
        $subject2 = "[Info] Anfrage zur Registrierung auf www.danzigmarken.de";
        $mailcontent2 = "Folgendes wurde am ". $date ." Uhr per Formular empfangen:\n".
            "-------------------------------------------------------------------------\n\n".
            "E-Mail: ".$input['mail']."\n".
            "Name: ".$input['name']."\n\n".

            "Nachricht:\n\n".preg_replace("/\r\r|\r\n|\n\r|\n\n/", "\n", $input['message'])."\n\n\n".
            "IP Adresse: ".$ip." - ".$host." - ".$UserAgent."\n".
            "-------------------------------------------------------------------------\n\n".
            "Eine Email an den Anfragenden wurde gesendet.\n\n".
            "Von: ".$smtp['from_name']." <".$mailto2.">\n".
            "An: ".$input['mail']."\n".
            "Betreff: ".$subject1."\n".
            "Nachricht:\n\n".$mailcontent1;


        # mail it to customer
        $email1_okay = Mail::sendMyMail(
            $smtp['from_addr'],
            $smtp['from_name'],
            $mailto1,
            $subject1,
            $mailcontent1
        );

        # mail it to admin
        $email2_okay = Mail::sendMyMail(
            $smtp['from_addr'],
            $smtp['from_name'],
            $mailto2,
            $subject2,
            $mailcontent2
        );

        return $email1_okay;
    }
}


// EOF