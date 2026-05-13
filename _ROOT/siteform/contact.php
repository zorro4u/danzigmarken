<?php
namespace Dzg;
use Dzg\Tools\Tools;
use Dzg\Mail\{MailConfig, Mail, AntiSpam};

require_once __DIR__.'/../siteprep/contact.php';
require_once __DIR__.'/../sitemsg/contact.php';
require_once __DIR__.'/../tools/tools.php';
require_once __DIR__.'/../mail/MailConfig.php';
require_once __DIR__.'/../mail/Mail.php';
require_once __DIR__.'/../mail/AntiSpam.php';
#require_once __DIR__.'/../mail/RateLimiting.php';
#require_once __DIR__.'/../mail/Captcha.php';

#require $_SERVER['DOCUMENT_ROOT']."/assets/vendor/autoload.php";
#use Gregwar\Captcha\PhraseBuilder;


/**
 * Webseite: Kontaktformular
 */
class ContactForm extends ContactPrep
{
    protected const MSG = ContactMsg::MSG;

    // username: beginnt mit Buchstaben, kann dann auch Zahlen und (._-) enthalten aber nicht damit enden, ist 3-20 Zeichen lang
    protected const REGEX_USR = "/^[a-zA-Z]((\.|_|-)?[a-zA-Z0-9äüößÄÜÖ]+){3,20}$/D";

    // (Doppel-)Name mit Bindestrich/Leerzeichen, ohne damit zu beginnen oder zu enden und ohne doppelte Striche/Leerz., ist 0-50 Zeichen lang
    protected const REGEX_NAME = "/^[a-zA-ZäüößÄÜÖ]+([0-9a-zA-ZäüößÄÜÖ]|[ -._](?=[0-9a-zA-ZäüößÄÜÖ])){0,50}$/";
    protected const REGEX_NAME_NO = "/^[^a-zA-ZäüößÄÜÖ]+|[^0-9a-zA-ZäüößÄÜÖ -._]+|[_.- ]{2,}|[^0-9a-zA-ZäüößÄÜÖ]+$/";

    // Nachricht: enthält Buchstaben/Zahlen, Leer, Tab, Zeilenwechsel, Sonderzeichen und special escaped regex-Zeichen; negiert um falsche Zeichen auszugeben
    protected const REGEX_MESS_NO = "/[^\w\s\t\r\n ×÷=<>:;,!@#€%&§`´~£¥₩₽° •○●□■♤♡◇♧☆▪︎¤《》¡¿♠︎♥︎◆♣︎★±≈≠≡【〔「『】〕」』¡№٪‰–—‽· \\\\\^\$\.\|\?\*\+\-\(\)\[\]\{\}\/\"\']/mu";

    protected const BREAKER = "/\r\r|\r\n|\n\r|\n\n|<br>/";

    protected static bool $show_form;
    protected static string $status_message;
    protected static string $success_msg;
    protected static string $input_name;
    protected static string $input_email;
    protected static string $input_message_first;
    protected static $cfg;
    protected static $fehler;
    protected static $datenschutzerklaerung;
    public static $captcha;


    /**
     * Summary of formEvaluation
     * [https://www.kontaktformular.com/]
     */
    protected static function formEvaluation()
    {
        #require_once $_SERVER['DOCUMENT_ROOT'].'/contact/mail-setup.php';
        #global $cfg, $smtp;

        $msg = self::MSG;
        $cfg  = MailConfig::$cfg;
        $smtp = MailConfig::$smtp[0];
        $datenschutzerklaerung = MailConfig::$datenschutzerklaerung;

        $success = false;
        $formMessage = '';
        $buttonClass = '';

        $error_arr = [];
        $error_msg = "";
        $success_msg = "";
        $show_form   = True;
        $input_name  = "";
        $input_email = "";
        $input_message_first = "";
        $input_message = "";
        $fehler = [];

        // Formularwerte empfangen
        # (isset($_GET['send']) && strtoupper($_SERVER['REQUEST_METHOD'] === "POST")
        if (!empty($_POST)) {

            // clean post
            foreach ($_POST as $key => $value) {
                $_POST[$key] = htmlentities($value, ENT_QUOTES, "UTF-8");
            };

            // Nutzereingaben auswerten
            if (isset($_POST['message'])
                && (isset($_POST['email'])
                || isset($_POST['name'])))
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

                // Plausi-Check Input
                $input_name = isset($_POST['name'])
                    ? htmlspecialchars(Tools::cleanInput($_POST['name']))
                    : '';

                $regex = !preg_match(self::REGEX_NAME, $input_name, $match);

                if ($input_name !== "" && $regex) {
                    $error_arr []= $msg[210];
                }
                else {
                    $name_arr = explode(' ', $input_name, 20);
                    if (count($name_arr) < 2) {
                        // nur ein Element = Vorname
                        [$input_vor] = $name_arr;
                        $input_nach = '';
                    }
                    else {
                        // letztes Element = Nachname, davor alles als Vorname
                        $input_nach = array_pop($name_arr);
                        $input_vor = implode(' ', $name_arr);
                    };
                };

                // Email
                $input_email = isset($_POST['email'])
                    ? htmlspecialchars(Tools::cleanInput($_POST['email']))
                    : '';
                if ($input_email !== ""
                    && !filter_var($input_email, FILTER_VALIDATE_EMAIL))
                {
                    $error_arr []= $msg[211];
                };

                // Nachricht
                $input_message_first = $_POST['message'];   // wird nicht weiter verwendet
                $input_message = isset($_POST['message'])
                    ? Tools::cleanInput($_POST['message'])
                    : '';


                $input_message = preg_replace(self::BREAKER, "\n", $input_message);
                if ($input_email !== ""
                    && preg_match_all(self::REGEX_MESS_NO, $input_message, $match))
                {
                    $error_arr []= $msg[212].': "'.htmlentities(implode(" ", $match[0])).'"';
                };

                $input_message = htmlspecialchars($input_message);
                #echo $input_message = htmlspecialchars($input_message, ENT_QUOTES);
                #$input_message = htmlentities($input_message, ENT_QUOTES);     // auch Umlaute werden auch umgewandelt

            } else {
                $error_arr []= $msg[213];
            };


            // -------------------- SPAMPROTECTION ERROR MESSAGES START ----------------------

            if ($cfg['Aufrufe_limitieren'])
                AntiSpam::rateLimiting();

            if ($cfg['Sicherheitscode']) {
                // mit CAPTCHA Bibliothek (sicherer, teilsweise aber schwer zu lesen)
                // muss in Captcha.php auch aktiviert sein
                #$compare = (PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['sicherheitscode'])) ? true : false;
                $compare = ($_SESSION['phrase'] == $_POST['sicherheitscode']);

                // Session wurde in AntiSpam::loadCaptchaPic() gesetzt
                if (!isset($_POST['sicherheitscode'], $_SESSION['phrase'])
                    || !$compare)
                {
                    $fehler['captcha'] = "<span class='errormsg'>{$msg[214]}</span>";
                    unset($_SESSION['phrase']);
                }
                elseif (isset($_POST['sicherheitscode'])) {
                    unset($_SESSION['phrase']);
                };
            };

            if ($cfg['Sicherheitsfrage']
                && isset($_SESSION['Sicherheitsfrage']))
            {
                $answer = AntiSpam::getAnswerById($_SESSION['Sicherheitsfrage']);
                unset($_SESSION['Sicherheitsfrage']);

                if (!isset($_POST['answer'])
                    || $answer != $_POST['answer'])
                {
                    $fehler['q_id12'] = "<span class='errormsg'>{$msg[215]}</span>";
                };
            };

            if ($cfg['Honeypot']
                && (!isset($_POST['mail'])
                    || $_POST['mail'] != ''))
            {
                $fehler['Honeypot'] = "<span class='errormsg-spamprotection' style='display: block;'>{$msg[216]}</span>";
            };

            if ($cfg['Zeitsperre']
                && (!isset($_POST['chkspmtm'])
                    || $_POST['chkspmtm'] == ''
                    || $_POST['chkspmtm'] == '0'
                    || (time() - (int) $_POST['chkspmtm']) < (int) $cfg['Zeitsperre']))
            {
                $fehler['Zeitsperre'] = "<span class='errormsg-spamprotection' style='display: block;'>{$msg[217]}</span>";
            };

            if ($cfg['Klick-Check']
                && (!isset($_POST['chkspmkc'])
                    || 'chkspmhm' != $_POST['chkspmkc']))
            {
                $fehler['Klick-Check'] = "<span class='errormsg-spamprotection' style='display: block;'>{$msg[218]}</span>";
            };

            if ($cfg['Links'] < preg_match_all('#http(s?)\:\/\/#is', $input_message, $irrelevantMatches))
            {
                $fehler['Links'] = "
                    <span class='errormsg-spamprotection' style='display: block;'>{$msg[219]} ".
                    (0==$cfg['Links']
                        ? $msg[220]
                        : (1==$cfg['Links']
                            ? $msg[221]
                            : $msg[222].' '.$cfg['Links'].' '.$msg[223]
                            )
                    )." {$msg[223]}.</span>";
            };

            if ($cfg['Badwordfilter'] != ''
                && $cfg['Badwordfilter'] !== 0
                && $cfg['Badwordfilter'] != '0')
            {
                $badwords = explode(',', $cfg['Badwordfilter']);            // the configured badwords
                $badwordFields = explode(',', $cfg['Badwordfields']);        // the configured fields to check for badwords
                $badwordMatches = [];                                    // the badwords that have been found in the fields

                if (0 < count($badwordFields)) {
                    foreach ($badwords as $badword) {
                        $badword = trim($badword);                                                // remove whitespaces from badword
                        $badwordMatch = str_replace('%', '', $badword);                            // take human readable badword for error-message
                        $badword = addcslashes($badword, '.:/');                                // make ., : and / preg_match-valid
                        if ('%'!=substr($badword, 0, 1)) {
                            $badword = '\\b'.$badword;
                        };           // if word mustn't have chars before > add word boundary at the beginning of the word
                        if ('%'!=substr($badword, -1, 1)) {
                            $badword = $badword.'\\b';
                        };            // if word mustn't have chars after > add word boundary at the end of the word
                        $badword = str_replace('%', '', $badword);                                // if word is allowed in the middle > remove all % so it is also allowed in the middle in preg_match
                        foreach ($badwordFields as $badwordField) {
                            if (preg_match('#'.$badword.'#is', $_POST[trim($badwordField)])
                                && !in_array($badwordMatch, $badwordMatches))
                            {
                                $badwordMatches[] = $badwordMatch;
                            };
                        };
                    };

                    if (0 < count($badwordMatches)) {
                        $fehler['Badwordfilter'] = "
                            <span class='errormsg-spamprotection' style='display: block;'>{$msg[225]}: ".
                            implode(', ', $badwordMatches)."</span>";
                    };
                };
            };
            // -------------------- SPAMPROTECTION ERROR MESSAGES ENDE ----------------------


            if ($cfg['Datenschutz_Erklaerung']) {
                $datenschutz = stripslashes($_POST['datenschutz']);

                if (isset($datenschutz) && $datenschutz == "") {
                    $fehler['datenschutz'] = "
                        <span class='errormsg'>{$msg[226]}</span>";
                };
            };

            $buttonClass = 'failed';
            $formMessage = "
                <img src='img/failed.png' style='width:25px;height:25px;vertical-align: middle;'>
                <span class='error_in_email_sending'>{$msg[227]}</span>";


            // there are NO errors > upload-check
            if (!isset($fehler) || count($fehler) == 0) {};


            if (!empty($fehler)) {
                $error_arr []= implode("<br>", $fehler);
            };


            // Eingaben okay, Mail vorbereiten und versenden
            if (empty($error_arr)) {

                // === EMAIL ===
                //
                $email_okay = false;
                $remote_ip  = getenv("REMOTE_ADDR");
                $ip   = $_SERVER['REMOTE_ADDR'];
                $host = getHostByAddr($ip);
                $date = date("d.m.Y | H:i");
                $UserAgent = $_SERVER['HTTP_USER_AGENT'];

                // ---- create mail for admin (from admin to admin with user message)
                $mailto  = $smtp['from_addr'];
                $subject = "[EMAIL:] Kontaktformular auf www.danzigmarken.de";
                $mailcontent = "Folgendes wurde am {$date} Uhr per Formular empfangen:\n".
                    "-------------------------------------------------------------------------\n\n".
                    "E-Mail: {$input_email}\n".
                    "Name: {$input_name}\n\n".

                    "Nachricht:\n\n".preg_replace("/\r\r|\r\n|\n\r|\n\n/","\n",$input_message)."\n\n\n".
                    "IP Adresse: {$ip} - {$host} - {$UserAgent}\n";

                // mail it to admin
                $email_okay = Mail::sendMyMail(
                    $smtp['from_addr'],
                    $smtp['from_name'],
                    $mailto,
                    $subject,
                    $mailcontent
                );

                // === ENDE EMAIL-Abschnitt ===

                if ($email_okay) {
                    $success_msg = $msg[228];
                    #$show_form = False;
                } else {
                    $error_arr []= $msg[229];
                };

                // Formular nach Verarbeitung wieder löschen
                #$input_name = "";
                #$input_email = "";
                $input_message_first = "";

            // Eingabewerte nicht okay, kein Mailversand
            } else {};



        };  # Formularwerte empfangen

        if (!empty($error_arr)) {
            $error_arr = [implode("<br>", $error_arr)];
        };
        $error_msg = implode("", $error_arr);


        #$show_form = ($error_msg === "") ? true : false;
        $status_message = Tools::statusOut($success_msg, $error_msg);

        unset($_POST, $_GET, $_REQUEST, $_SESSION['captcha'], $_SESSION['captcha_code']);

        #self::$fehler = $fehler;
        self::$cfg = $cfg;
        self::$show_form  = $show_form;
        self::$input_name = $input_name;
        self::$input_email = $input_email;
        self::$success_msg = $success_msg;
        self::$status_message = $status_message;
        self::$input_message_first = $input_message_first;
        self::$datenschutzerklaerung = $datenschutzerklaerung;
    }
}


// EOF