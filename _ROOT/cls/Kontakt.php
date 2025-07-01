<?php
namespace Dzg;

// Datenbank- & Auth-Funktionen laden
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';
use Dzg\{Tools, Header, Footer};

require_once __DIR__.'/../mail/Mail.php';
require_once __DIR__.'/../mail/AntiSpam.php';
require_once __DIR__.'/../mail/RateLimiting.php';
require_once __DIR__.'/../mail/Captcha.php';
use Dzg\Mail\{Mailcfg, Mail, AntiSpam, RateLimiting, Captcha};

#require_once $_SERVER['DOCUMENT_ROOT']."/assets/inc/captcha_pic.php";

/***********************
 * Webseite: Kontaktformular
 *
 * __public__
 * show()
 */
class Kontakt
{
    /**
     * Anzeige der Webseite
     */
    public static function show()
    {
        self::data_preparation();
        self::form_evaluation();

        Header::show();
        self::site_output();
        Footer::show("kontakt");
    }


    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private static $showForm;
    private static string $status_message;
    private static $success_msg;
    private static $input_name;
    private static $input_email;
    private static $input_message_first;
    private static $cfg;
    private static $fehler;
    private static $question;
    private static $datenschutzerklaerung;


    /***********************
     * Summary of data_preparation
     */
    private static function data_preparation()
    {
        $cfg = Mailcfg::$cfg;
        $question = [];

        if ($cfg['Sicherheitsfrage'])
            $question = AntiSpam::getRandomQuestion();      # [id, question]

        $script_root = substr(__FILE__, 0,
                            strrpos(__FILE__,
                                    DIRECTORY_SEPARATOR)
                            ).DIRECTORY_SEPARATOR;

        // Herkunftsseite speichern
        Tools::lastsite(['index', 'index2', 'details', 'settings', 'admin']);

        self::$question = $question;
    }


    /**
     * Summary of form_evaluation
     * [https://www.kontaktformular.com/]
     */
    private static function form_evaluation()
    {
        #require $_SERVER['DOCUMENT_ROOT'].'/kontakt/mail-setup.php';
        #global $cfg, $smtp;

        #Mailcfg::load();
        $cfg = Mailcfg::$cfg;
        $smtp = Mailcfg::$smtp;
        #$smtp = array_merge(Mailcfg::$smtp, Mailcfg::$smtp1);
        $datenschutzerklaerung = Mailcfg::$datenschutzerklaerung;

        $success = false;
        $formMessage = '';
        $buttonClass = '';

        $error_arr = [];
        $error_msg = "";
        $success_msg = "";
        $showForm = True;
        $input_name = "";
        $input_email = "";
        $input_message_first = "";
        $fehler = [];

        // Formularwerte empfangen
        # (isset($_GET['send']) && strtoupper($_SERVER["REQUEST_METHOD"] === "POST")
        if (!empty($_POST)) {

            // clean post
            foreach ($_POST as $key => $value) {
                $_POST[$key] = htmlentities($value, ENT_QUOTES, "UTF-8");
            }

            // Nutzereingaben auswerten
            if (isset($_POST['message']) &&
                (isset($_POST['email']) || isset($_POST['name'])))
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

                // username: beginnt mit Buchstaben, kann dann auch Zahlen und (._-) enthalten aber nicht damit enden, ist 3-20 Zeichen lang
                $regex_usr = "/^[a-zA-Z]((\.|_|-)?[a-zA-Z0-9äüößÄÜÖ]+){3,20}$/D";

                // (Doppel-)Name mit Bindestrich/Leerzeichen, ohne damit zu beginnen oder zu enden und ohne doppelte Striche/Leerz., ist 0-50 Zeichen lang
                $regex_name = "/^[a-zA-ZäüößÄÜÖ]+([0-9a-zA-ZäüößÄÜÖ]|[ -._](?=[0-9a-zA-ZäüößÄÜÖ])){0,50}$/";
                $regex_name_no = "/^[^a-zA-ZäüößÄÜÖ]+|[^0-9a-zA-ZäüößÄÜÖ -._]+|[_.- ]{2,}|[^0-9a-zA-ZäüößÄÜÖ]+$/";

                $input_name = isset($_POST['name'])
                    ? htmlspecialchars(Tools::clean_input($_POST['name']))
                    : "";

                $regex = !preg_match($regex_name, $input_name, $match);
                #var_dump($regex0, $regex_name, $input_name, $match);echo' #1<br>';

                #$regex1 = preg_match_all($regex_name_no, $input_name, $match);
                #var_dump($regex1, $regex_name_no, $input_name, $match);echo' #2<br>';

                if ($input_name !== "" && $regex) {
                    $error_arr []= 'nur Buchstaben im Namen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen)';
                    #$error_arr []= 'nur Buchstaben im Namen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen): "'.htmlentities(implode(" ", $match[0])).'"';
                } else {
                    # [$input_vor, $input_nach] = explode(' ',$input_name,2);
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
                $input_email = isset($_POST['email'])
                    ? htmlspecialchars(Tools::clean_input($_POST['email']))
                    : "";
                if ($input_email !== "" &&
                    !filter_var($input_email, FILTER_VALIDATE_EMAIL))
                {
                    $error_arr []= 'Keine gültige Email-Adresse.';
                }


                // Nachricht: enthält Buchstaben/Zahlen, Leer, Tab, Zeilenwechsel, Sonderzeichen und special escaped regex-Zeichen; negiert um falsche Zeichen auszugeben
                $regex_mess_no = "/[^\w\s\t\r\n ×÷=<>:;,!@#€%&§`´~£¥₩₽° •○●□■♤♡◇♧☆▪︎¤《》¡¿♠︎♥︎◆♣︎★±≈≠≡【〔「『】〕」』¡№٪‰–—‽· \\\\\^\$\.\|\?\*\+\-\(\)\[\]\{\}\/\"\']/mu";

                $input_message_first = $_POST['message'];   // wird nicht weiter verwendet
                $input_message = isset($_POST['message'])
                    ? Tools::clean_input($_POST['message'])
                    : "";
                $input_message = preg_replace("/\r\r|\r\n|\n\r|\n\n|<br>/","\n", $input_message);
                if ($input_email !== "" &&
                    preg_match_all($regex_mess_no, $input_message, $match))
                {
                    $error_arr []= 'Die Nachricht verwendet unzulässige Zeichen: "'.htmlentities(implode(" ", $match[0])).'"';
                }

                #$error_arr []= ' -regex.end-';

                $input_message = htmlspecialchars($input_message);
                #echo $input_message = htmlspecialchars($input_message, ENT_QUOTES);
                #$input_message = htmlentities($input_message, ENT_QUOTES);     // auch Umlaute werden auch umgewandelt

            } else {
                $error_arr []= 'kein Name/Email und Nachricht angeben.';
            }


            if ($cfg['Datenschutz_Erklaerung'])
                $datenschutz = stripslashes($_POST["datenschutz"]);

            if ($cfg['Sicherheitscode'])
                $sicherheits_eingabe = Captcha::encrypt($_POST["sicherheitscode"]);

            // Anzahl der Seiten-Aufrufe begrenzen
            if ($cfg['Aufrufe_limitieren'])
                RateLimiting::run();


            // -------------------- SPAMPROTECTION ERROR MESSAGES START ----------------------
            if ($cfg['Sicherheitscode'] &&
                $sicherheits_eingabe != $_SESSION['captcha_spam'])
            {
                unset($_SESSION['captcha_spam']);
                $fehler['captcha'] = "<span class='errormsg'>Der <strong>Sicherheitscode</strong> wurde falsch eingegeben.</span>";
            }

            if ($cfg["Sicherheitsfrage"]) {
                $answer = AntiSpam::getAnswerById(intval($_POST["question_id"]));
                if (!isset($_POST["answer"]) || $_POST["answer"] != $answer) {
                    $fehler['q_id12'] = "<span class='errormsg'>Bitte die <strong>Sicherheitsfrage</strong> richtig beantworten.</span>";
                }
            }

            if ($cfg['Honeypot'] &&
                (!isset($_POST["mail"]) || ''!=$_POST["mail"]))
            {
                $fehler['Honeypot'] = "<span class='errormsg-spamprotection' style='display: block;'>Es besteht Spamverdacht. Bitte überprüfen Sie Ihre Angaben.</span>";
            }

            if ($cfg['Zeitsperre'] &&
                (!isset($_POST["chkspmtm"]) || ''==$_POST["chkspmtm"] || '0'==$_POST["chkspmtm"] ||
                (time() - (int) $_POST["chkspmtm"]) < (int) $cfg['Zeitsperre']))
            {
                $fehler['Zeitsperre'] = "<span class='errormsg-spamprotection' style='display: block;'>Bitte warten Sie einige Sekunden, bevor Sie das Formular erneut absenden.</span>";
            }

            if ($cfg['Klick-Check'] &&
                (!isset($_POST["chkspmkc"]) || 'chkspmhm'!=$_POST["chkspmkc"]))
            {
                $fehler['Klick-Check'] = "<span class='errormsg-spamprotection' style='display: block;'>Sie müssen den Senden-Button mit der Maus anklicken, um das Formular senden zu können.</span>";
            }

            if ($cfg['Links'] < preg_match_all('#http(s?)\:\/\/#is', $input_message, $irrelevantMatches))
            {
                $fehler['Links'] = "
                    <span class='errormsg-spamprotection' style='display: block;'>Ihre Nachricht darf ".
                    (0==$cfg['Links']
                        ? 'keine Links'
                        : (1==$cfg['Links']
                            ? 'nur einen Link'
                            : 'maximal '.$cfg['Links'].' Links'
                            )
                    )." enthalten.</span>";
            }

            if (''!=$cfg['Badwordfilter'] &&
                0!==$cfg['Badwordfilter'] &&
                '0'!=$cfg['Badwordfilter'])
            {
                $badwords = explode(',', $cfg['Badwordfilter']);            // the configured badwords
                $badwordFields = explode(',', $cfg['Badwordfields']);        // the configured fields to check for badwords
                $badwordMatches = array();                                    // the badwords that have been found in the fields

                if (0 < count($badwordFields)) {
                    foreach ($badwords as $badword) {
                        $badword = trim($badword);                                                // remove whitespaces from badword
                        $badwordMatch = str_replace('%', '', $badword);                            // take human readable badword for error-message
                        $badword = addcslashes($badword, '.:/');                                // make ., : and / preg_match-valid
                        if ('%'!=substr($badword, 0, 1)) {
                            $badword = '\\b'.$badword;
                        }            // if word mustn't have chars before > add word boundary at the beginning of the word
                        if ('%'!=substr($badword, -1, 1)) {
                            $badword = $badword.'\\b';
                         }            // if word mustn't have chars after > add word boundary at the end of the word
                        $badword = str_replace('%', '', $badword);                                // if word is allowed in the middle > remove all % so it is also allowed in the middle in preg_match
                        foreach ($badwordFields as $badwordField) {
                            if (preg_match('#'.$badword.'#is', $_POST[trim($badwordField)]) &&
                                !in_array($badwordMatch, $badwordMatches))
                            {
                                $badwordMatches[] = $badwordMatch;
                            }
                        }
                    }

                    if (0 < count($badwordMatches)) {
                        $fehler['Badwordfilter'] = "
                            <span class='errormsg-spamprotection' style='display: block;'>Folgende Begriffe sind nicht erlaubt: ".
                            implode(', ', $badwordMatches)."</span>";
                    }
                }
            }
            // -------------------- SPAMPROTECTION ERROR MESSAGES ENDE ----------------------


            if ($cfg['Datenschutz_Erklaerung'] && isset($datenschutz) && $datenschutz == "") {
                $fehler['datenschutz'] = "<span class='errormsg'>Sie müssen die <strong>Datenschutz&shy;erklärung</strong> akzeptieren.</span>";
            }

            $buttonClass = 'failed';
            $formMessage = '
                <img src="img/failed.png" style="width:25px;height:25px;vertical-align: middle;">
                <span class="error_in_email_sending">Bitte überprüfen und korrigieren Sie Ihre Eingaben.</span>';


            // there are NO errors > upload-check
            if (!isset($fehler) || count($fehler) == 0) {}


            if (!empty($fehler)) {
                $error_arr []= implode("<br>", $fehler);
            }


            // Eingaben okay, Mail vorbereiten und versenden
            if (empty($error_arr)) {

                // === EMAIL ===
                //
                $remote_ip = getenv("REMOTE_ADDR");
                $ip = $_SERVER['REMOTE_ADDR'];
                $host = getHostByAddr($ip);
                $UserAgent = $_SERVER["HTTP_USER_AGENT"];
                $date = date("d.m.Y | H:i");

                // ---- create mail for admin (from admin to admin with user message)
                $mailto  = $smtp['from_addr'];
                $subject = "[EMAIL:] Kontaktformular auf www.danzigmarken.de";
                $mailcontent  = "Folgendes wurde am ". $date ." Uhr per Formular empfangen:\n".
                    "-------------------------------------------------------------------------\n\n".
                    "E-Mail: ".$input_email."\n".
                    "Name: ".$input_name."\n\n".

                    "Nachricht:\n\n".preg_replace("/\r\r|\r\n|\n\r|\n\n/","\n",$input_message)."\n\n\n".
                    "IP Adresse: ".$ip." - ".$host." - ".$UserAgent."\n";

                // mail it to admin
                $email_send = Mail::sendMyMail(
                    $smtp['from_addr'],
                    $smtp['from_name'],
                    $mailto,
                    $subject,
                    $mailcontent
                );

                // === ENDE EMAIL-Abschnitt ===

                if ($email_send) {
                    $success_msg = 'Deine Nachricht wurde versandt. Du erhälst in Kürze eine Antwort.';
                    $showForm = False;
                } else {
                    $error_arr []= 'Oh, die Nachricht konnte <b>NICHT</b> gesendet werden :-(';
                }

                /*
                if (!empty($fehler['Sendmail'])) {
                    $buttonClass = '<span style=display:none;>failed</span>';
                    $formMessage = '<span style=display:none;>Ihre Nachricht wurde NICHT gesendet.</span>';
                }
                else {
                    $buttonClass = 'finished';
                    $formMessage = '<img src="img/finished.png" style="width:29px;height:29px;vertical-align: middle;"> <span class="successfully_sent">Ihre Nachricht wurde gesendet.</span>';
                }
                */

            // Eingabewerte nicht okay, kein Mailversand
            } else {}

            // Formular nach Verarbeitung wieder löschen
            unset($_POST);
            $input_name = "";
            $input_email = "";
            $input_message_first = "";

        }  # Formularwerte empfangen


        if (!empty($error_arr)) {
            $error_arr = [implode("<br>", $error_arr)];
        }
        $error_msg = implode("", $error_arr);


        $showForm = ($error_msg === "") ? True : False;
        $status_message = Tools::status_out($success_msg, $error_msg);


        self::$showForm = $showForm;
        self::$status_message = $status_message;
        self::$success_msg = $success_msg;
        self::$input_name = $input_name;
        self::$input_email = $input_email;
        self::$input_message_first = $input_message_first;
        self::$cfg = $cfg;
        #self::$fehler = $fehler;
        self::$datenschutzerklaerung = $datenschutzerklaerung;

    }


    /***********************
     * Summary of site_output
     * [https://www.kontaktformular.com/]
     */
    private static function site_output()
    {
        /*
        $act_pth = explode('/', __DIR__);
        $pth_len = count($act_pth)-1;
        $root_dir = ['stamps', '_prepare'];
        for ($i=$pth_len; $i>0; $i--)
            if (in_array($act_pth[$i], $root_dir)) break;
        $root_pth_abs = implode('/', array_slice($act_pth, 0, $i+1));
        $root_pth_rel = '/'.implode('/', array_slice($act_pth, 3, $i+1-3));
        $root_site = $root_pth_rel.'/'.basename($_SESSION['main']);
        */

        #$rootdir="";
        #$root_site = $rootdir.'/'.basename($_SESSION['main']);


        $showForm = self::$showForm;
        $status_message = self::$status_message;
        $input_name = self::$input_name;
        $input_email = self::$input_email;
        $input_message_first = self::$input_message_first;
        $cfg = self::$cfg;
        $fehler = self::$fehler;
        [$question_id, $question] = self::$question;
        $datenschutzerklaerung = self::$datenschutzerklaerung;
        $success_msg = self::$success_msg;

        $output = "<div class='container'>";
        #$output = "<div class='container main-container registration-form'>";
        $output .= $status_message;
        #echo statusmeldung_ausgeben();

        $output .= "<div class='registration-form'>";
        $output .= "
                <h1>Kontakt</h1>
                <br>";

        // Seite anzeigen
        if ($showForm):
        $output .= "

<form action='' method='POST' enctype='multipart/form-data' style='margin-top: 30px;'>


<script>
if (navigator.userAgent.search('Safari') >= 0 && navigator.userAgent.search('Chrome') < 0) {
   document.getElementsByTagName('BODY')[0].className += ' safari';
}
</script>


<div class='form-group'>
    <label for='inputName'>Dein Name:</label>
    <input type='text' id='inputName' size='40' maxlength='50' name='name' value='".$input_name."' class='form-control' autocomplete='name' autofocus>
</div>

<div class='form-group'>
    <label for='inputEmail'>Deine E-Mail-Adresse: <span style='color:red'>*</span></label>
    <input type='email' id='inputEmail' size='40' maxlength='100' name='email' value='".$input_email."' class='form-control' autocomplete='email' required>
</div>

<div class='form-group'>
    <label for='inputMessage'>Deine Nachricht: <span style='color:red'>*</span></label>
    <textarea id='inputMessage' name='message' rows='9' style='width:100%;' maxlength='250' spellcheck='true' class='form-control' autocomplete='off' required>".$input_message_first."</textarea>
</div>
<!-- ..................... -->";


/*
$output .= "
<!--
<p id=\"submitMessage\" class=\"".$buttonClass."\">".$formMessage;
    if (
        (isset($fehler["Honeypot"]) && $fehler["Honeypot"] != '') ||
        (isset($fehler["Zeitsperre"]) && $fehler["Zeitsperre"] != '') ||
        (isset($fehler["Klick-Check"]) && $fehler["Klick-Check"] != '') ||
        (isset($fehler["Links"]) && $fehler["Links"] != '') ||
        (isset($fehler["Badwordfilter"]) && $fehler["Badwordfilter"] != '') ||
        (isset($fehler["Sendmail"]) && $fehler["Sendmail"] != '') ||
        (isset($fehler["upload"]) && $fehler["upload"] != '')):
        $output .= "

        <div class=\"row\">
        <div class=\"col-sm-8\">

        ";
        if (isset($fehler["Honeypot"]) && $fehler["Honeypot"] != '') {
            $output .= $fehler["Honeypot"];
        }

        if (isset($fehler["Zeitsperre"]) && $fehler["Zeitsperre"] != '') {
            $output .= $fehler["Zeitsperre"];
        }

        if (isset($fehler["Klick-Check"]) && $fehler["Klick-Check"] != '') {
            $output .= $fehler["Klick-Check"];
        }
        if (isset($fehler["Links"]) && $fehler["Links"] != '') {
            $output .= $fehler["Links"];
        }
        if (isset($fehler["Badwordfilter"]) && $fehler["Badwordfilter"] != '') {
            $output .= $fehler["Badwordfilter"];
        }
        if (isset($fehler["Sendmail"]) && $fehler["Sendmail"] != '') {
            $output .= $fehler["Sendmail"];
        }
        if (isset($fehler["upload"]) && $fehler["upload"] != '') {
            $output .= $fehler["upload"];
        }
        $output .= "

        </div>
        </div>

        ";
    endif;
    $output .= "

</p>

<div class=\"row\">
<div class=\"col-sm-4

";

if (!empty($fehler["name"])) {
    $output .= " error";
}
if (isset($_POST["name"]) && ''!=$_POST["name"]) {
    $output .= " not-empty-field";
} else {
    $output .= "";
}
$output .= "

    \">
    <label class=\"control-label\" for=\"border-right\"><i id=\"user-icon\" class=\"fa fa-user\"></i></label>
    <input ";

    if ($cfg["HTML5_FEHLERMELDUNGEN"]) {
        $output .= "required ";
    } else {
        $output .= "onchange=\"checkField(this)\" ";
    }
    $output .= "type=\"text\" name=\"name\" class=\"field\" placeholder=\"Name *\" value=\"".
        $_POST["name"]."\" maxlength=\"".$zeichenlaenge_name."\" id=\"border-right\"
        onclick=\"setActive(this);\" onfocus=\"setActive(this);\" />";
    if (!empty($fehler["name"])) {
        $output .= $fehler["name"];
    }
$output .= "

</div>
<div class=\"col-sm-4";

if (isset($_POST["name"]) && ''!=$_POST["name"]) {
    $output .= " not-empty-field";
} else {
    $output .= "";
}
$output .= "

    \">
    <label class=\"control-label\" for=\"border-right3\"><i id=\"user-icon\" class=\"fas fa-user\"></i></label>
    <input aria-label=\"Name\" type=\"text\" name=\"name\" class=\"field\" placeholder=\"Name\" value=\"".$_POST["name"]."\" maxlength=\"".$zeichenlaenge_name."\" id=\"border-right\" onclick=\"setActive(this);\" onfocus=\"setActive(this);\" />
</div>
<div class=\"col-sm-4";

if (!empty($fehler["email"])) {
    $output .= " error";
}
if (isset($_POST["email"]) && ''!=$_POST["email"]) {
     $output .= " not-empty-field";
 } else {
    $output .= "";
}
$output .= "

\">
<label class=\"control-label\" for=\"border-right2\"><i id=\"email-icon\" class=\"fa fa-envelope\"></i></label>
<input ";

if ($cfg["HTML5_FEHLERMELDUNGEN"]) {
    $output .= "required ";
} else {
    $output .= "onchange=\"checkField(this)\" ";
}
$output .= "aria-label=\"E-Mail\" type=\"";
if ($cfg["HTML5_FEHLERMELDUNGEN"]) {
    $output .= "email";
} else {
    $output .= "text";
}
$output .= "\" name=\"email\" class=\"field\" placeholder=\"E-Mail *\" value=\"".$_POST["email"]."\" maxlength=\"".$zeichenlaenge_email."\" id=\"border-right2\" onclick=\"setActive(this);\" onfocus=\"setActive(this);\" />";
if (!empty($fehler["email"])) {
    $output .= $fehler["email"];
}
$output .= "

</div>
</div>
<div class=\"row\">
<div class=\"col-sm-8 ";

if (!empty($fehler["nachricht"])) {
    $output .= "error";
} $output .= " ";
if (isset($_POST["message"]) && ''!=$_POST["message"]) {
    $output .= "not-empty-field ";
} else {
    $output .= "";
}
$output .= "

\">
<label  for=\"border-right3\" class=\"control-label textarea-label\"><i class=\"material-icons\">message</i></label>
<textarea ";

if ($cfg["HTML5_FEHLERMELDUNGEN"]) {
    $output .= "required ";
} else {
    $output .= " onchange=\"checkField(this)\" ";
}
$output .= " aria-label=\"Nachricht\" name=\"message\" class=\"field\" rows=\"5\" placeholder=\"Nachricht *\" style=\"height:100%;width:100%;\" id=\"border-right3\" onclick=\"setActive(this);\" onfocus=\"setActive(this);\" >".$_POST["message"]."</textarea>";

if (!empty($fehler["nachricht"])) {
    $output .= $fehler["nachricht"];
}
$output .= "
</div>
</div>
-->
";
#*/


// -------------------- DATEIUPLOAD START ----------------------
if (0 < $cfg["NUM_ATTACHMENT_FIELDS"]) {
    $output .= "
        <div class='row upload-row' style='background-position: 2.85rem center;-webkit-text-size-adjust:none;background-repeat: no-repeat;'>
        <div class='col-sm-8'>
            <label class='control-label' for='upload_field'><i id='fileupload-icon' class='fa fa-upload'></i></label>";
    for ($i=0; $i < $cfg["NUM_ATTACHMENT_FIELDS"]; $i++) {
        $output .= "
            <input aria-label='Dateiupload' type='file' size=12 name='f[]' id='upload_field' style='font-size:17px;' onclick='setActive(this);' onfocus='setActive(this);' />";
    }
    $output .= "
        </div>
        </div>";
}
// -------------------- DATEIUPLOAD ENDE ----------------------


// -------------------- SPAMPROTECTION START ----------------------
if ($cfg["Honeypot"]) {
    $output .= "
    <div style='height: 2px; overflow: hidden;'>
        <label style='margin-top: 10px;'>Das nachfolgende Feld muss leer bleiben, damit die Nachricht gesendet wird!</label>
        <div style='margin-top: 10px;'><input type='email' name='mail' value='' /></div>
    </div>";
}


if ($cfg["Zeitsperre"]) {
    $output .= "
    <input type='hidden' name='chkspmtm' value='".time()."' />";
}


if ($cfg["Klick-Check"]) {
    $output .= "
    <input type='hidden' name='chkspmkc' value='chkspmbt' />";
}


if ($cfg["Sicherheitscode"]) {
    $output .= "
    <div class='row captcha-row ";
    if (!empty($fehler["captcha"])) {
        $output .= "error_container";
    }
    $output .= "
        ' style='background-position: 2.85rem center;-webkit-text-size-adjust:none;background-repeat: no-repeat;'>
        <div class='col-sm-8 ";
        if (!empty($fehler["captcha"])) {
            $output .= "error";
        }
        $output .= "
        '>
        <br />
        <label class='control-label' for='answer2'>
        <div>
        <!-- <i id='securitycode-icon' class='fa fa fa-unlock-alt'></i>&nbsp; -->
        <img aria-label='Captcha' src='/assets/inc/captcha.php' alt='Sicherheitscode' title='captcha code' id='captcha' />
        <a href='javascript:void(0);' title='sicherheitscode' onclick=\"javascript:document.getElementById('captcha').src='/assets/inc/captcha.php?'+Math.random();cursor:pointer;\">
        <span class='captchareload'><i style='color:grey;' class='fas fa-sync-alt'></i></span></a>
        </div></label>
        <input";
        if ($cfg["HTML5_FEHLERMELDUNGEN"]) {
            $output .= " required";
        } else {
            $output .= " onchange='checkField(this)'";
        }
        $output .= " aria-label='Eingabe' id='answer2' placeholder='Sicherheitscode *' type='text' name='sicherheitscode' maxlength='150'  class='field";
        if (!empty($fehler["captcha"])) {
            $output .= " errordesignfields";
        }
        $output .= "  form-control' onclick='setActive(this);' onfocus='setActive(this);'/>";
        if (!empty($fehler["captcha"])) {
            $output .= $fehler["captcha"];
        }
        $output .= "
        </div>
    </div>";
}

if ($cfg["Sicherheitsfrage"]) {
    $output .= "
    <div class='row question-row ";
    if (!empty($fehler["q_id12"])) {
        $output .= "error_container";
    }
    $output .= "' style='background-position: 2.85rem center;-webkit-text-size-adjust:none;background-repeat: no-repeat;'>
        <div class='col-sm-8 ";
    if (!empty($fehler["q_id12"])) {
        $output .= "error";
    }
    $output .= "
        ' >
            <br />
            <label class='control-label' for='answer'>
            <div aria-label='Sicherheitsfrage'>
            <i id='securityquestion-icon' class='fa fa fa-unlock-alt'></i>&nbsp;
            Sicherheitsfrage <span style='color:red'>*</span>
            <input type='hidden' name='question_id' value='{$question_id}' />
            </div></label>
            <input ";
            if ($cfg["HTML5_FEHLERMELDUNGEN"]) {
                $output .= " required ";
            } else {
                $output .= " onchange='checkField(this)' ";
            }
            $output .= " aria-label='Antwort' id='answer' placeholder='{$question}' type='text' class='field";
            if (!empty($fehler["q_id12"])) {
                $output .= " errordesignfields";
            }
            $output .= " form-control' name='answer' onclick='setActive(this);' onfocus='setActive(this);' />";
            if (!empty($fehler["q_id12"])) {
                $output .= $fehler["q_id12"];
            }
            $output .= "
        </div>
    </div>";
}
// -------------------- SPAMPROTECTION ENDE ----------------------


// -------------------- MAIL-COPY START ----------------------
if (1 == $cfg["Kopie_senden"]) {
    $output .= "
        <div class='row checkbox-row' style='background-position: 2.85rem center;-webkit-text-size-adjust:none;background-repeat: no-repeat;'>
        <div class='col-sm-8";

    if (isset($_POST["mail-copy"]) && ''!=$_POST["mail-copy"]) {
        $output .= " not-empty-field";
    } else {
        $output .= "";
    }
    $output .= "
        '>
        <label for='inlineCheckbox11' class='control-label'><i id='mailcopy-icon' class='fa fa-envelope'></i></label>
        <label class='checkbox-inline'>
        <input aria-label='E-Mail-Kopie senden' type='checkbox' id='inlineCheckbox11' name='mail-copy' value='1' ";

    if (isset($_POST["mail-copy"]) && $_POST["mail-copy"]=='1') {
        $output .= "checked='checked' ";
    }
    $output .= "
        onclick='setActive(this);' onfocus='setActive(this);' > <div style='padding-top:4px;padding-bottom:2px;'><span style='line-height:27px;'>Kopie der Nachricht per E-Mail senden</span></div>
        </label>
        </div>
        </div";
}
// -------------------- MAIL-COPY ENDE ----------------------


// -------------------- DATAPROTECTION START ----------------------
if ($cfg["Datenschutz_Erklaerung"]) { $output .= "
    <div class='row checkbox-row ";
    if (!empty($fehler["datenschutz"])) {$output .= "error_container";} $output .= "' style='background-position: 2.85rem center;-webkit-text-size-adjust:none;background-repeat: no-repeat;'>
        <div class='col-sm-8 ";
        if (!empty($fehler["datenschutz"])) {$output .= "error";}
        if (isset($_POST["datenschutz"]) && '' != $_POST["datenschutz"]) {
            $output .= "not-empty-field ";
        } else {
            $output .= "";
        }
        $output .= "
            '>
            <label for='inlineCheckbox12' class='control-label'><i id='dataprotection-icon' class='fas fa-user-shield '></i></label>
            <label class='checkbox-inline'>
            <input ";

        if ($cfg["HTML5_FEHLERMELDUNGEN"]) {
            $output .= " required ";
        } else {
            $output .= " onchange='checkField(this)' ";
        }
        $output .= "
            aria-label='Datenschutz' type='checkbox' id='inlineCheckbox12' name='datenschutz' value='akzeptiert' ";
        if ($_POST["datenschutz"]=='akzeptiert') {
            $output .= " checked='checked' ";
        }
        $output .= "
            onclick='setActive(this);' onfocus='setActive(this);' /> <div style='padding-top:4px;padding-bottom:2px;line-height:27px;'> <a href='".$datenschutzerklaerung."' target='_blank'>Ich stimme der Datenschutz&shy;erklärung zu.</a> *</div>
            </label>";
        if (!empty($fehler["datenschutz"])) {
            $output .= $fehler["datenschutz"];
        }
        $output .= "
        </div>
        </div>";
}
// -------------------- DATAPROTECTION ENDE ----------------------

/*
$output .= "

<!--
<div class=\"row\" id=\"send\">
<div class=\"col-sm-4\"><br>
    <span style=\"line-height:26px;font-size:17px;\"><b>Hinweis:</b> Felder mit <span class=\"pflichtfeld\">*</span> müssen ausgefüllt werden.</span>
    <br><br><br>
    <button type=\"submit\" class=\"senden ".$buttonClass."\" name=\"kf-km\" id=\"submitButton\">
        <span class=\"label\">Nachricht senden</span>
        <svg class=\"loading-spinner\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\">
            <circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle>
            <path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path>
        </svg>
    </button>
</div>
</div>
--> ";
#*/

$output .= "
<br><br>
    <button type='submit' class='btn btn-lg btn-primary btn-block' name='kf-km'>Nachricht senden</button>
</form>

<br><br><hr>

<ul>
    <span style='color:red'>*</span> Felder bitte ausfüllen.
</ul>


<!-- ..................... --> ";


if ($cfg["Loading_Spinner"]):
    $output .= "

    <script type='text/javascript'>
        document.addEventListener('DOMContentLoaded', () => {
            const element = document.getElementById('submitButton');
        });

        document.querySelector('.senden').addEventListener('click', function() {
            var form = document.getElementById('kontaktformular');
            if (form.checkValidity()) {
                this.classList.add('loading');
                this.style.backgroundColor = '#A6A6A6';
            } else {
                console.log('');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('kontaktformular');

            form.addEventListener('submit', function (event) {
                event.preventDefault(); // Prevent default form submission

                const submitButton = document.getElementById('submitButton');
                const submitButtonLabel = submitButton.querySelector('.label');
                const loadingSpinner = submitButton.querySelector('.loading-spinner');

                // Blende den Text aus und zeige den Spinner an
                submitButtonLabel.style.display = 'none';
                loadingSpinner.style.display = 'block';

                submitButton.disabled = true;

                // Simuliere den Ladevorgang und reiche das Formular nach einer Sekunde ein
                setTimeout(() => {
                    form.submit(); // Führe den echten Form-Submit nach 1 Sekunde durch
                }, 1000);
            });
        });
    </script>

    ";
endif;
if ($cfg["Erfolgsmeldung"]):
    $output .= "

    <script>
        // Überprüfe, ob die Klasse 'finished' aktiv ist
        var isFinished = document.querySelector('.senden').classList.contains('finished');

        // Ändere die Beschriftung entsprechend
        if (isFinished) {
            var submitButton = document.getElementById('submitButton');
            submitButton.innerHTML = '<span class=\"label_finished\">Nachricht gesendet</span>';
        }
    </script>

    ";
endif;
if ($cfg["Klick-Check"]):
    $output .= "

    <script type='text/javascript'>
        function chkspmkcfnk(){
            document.getElementsByName('chkspmkc')[0].value = 'chkspmhm';
        }
        document.getElementsByName('kf-km')[0].addEventListener('mouseenter', chkspmkcfnk);
        document.getElementsByName('kf-km')[0].addEventListener('touchstart', chkspmkcfnk);
    </script>

    ";
endif;
$output .= "

<script type='text/javascript'>
    // set class kontaktformular-validate for form if user wants to send the form > so the invalid-styles only appears after validation
    function setValidationStyles(){
        document.getElementById('kontaktformular').setAttribute('class', 'kontaktformular kontaktformular-validate');
    }
    document.getElementsByName('kf-km')[0].addEventListener('click', setValidationStyles);
    document.getElementById('kontaktformular').addEventListener('submit', setValidationStyles);
</script>


";
if (!$cfg["HTML5_FEHLERMELDUNGEN"]):
    $output .= "

    <script type='text/javascript'>
        // set class kontaktformular-validate for form if user wants to send the form > so the invalid-styles only appears after validation
        function checkField(field){
            if (''!=field.value){

                // if field is checkbox: go to parentNode and do things because checkbox is in label-element
                if ('checkbox'==field.getAttribute('type')) {
                    field.parentNode.parentNode.classList.remove('error');
                    field.parentNode.nextElementSibling.style.display = 'none';
                }
                // field is no checkbox: do things with field
                else {
                    field.parentNode.classList.remove('error');
                    field.nextElementSibling.style.display = 'none';
                }

                // remove class error_container from parent-elements
                field.parentNode.parentNode.parentNode.classList.remove('error_container');
                field.parentNode.parentNode.classList.remove('error_container');
                field.parentNode.classList.remove('error_container');
            }
        }
    </script>

    ";
endif;
$output .= "

<script>
    // --------------------- field active / inactive

    // set active-class to field
    function setActive(element){
        // set onblur-function to set field inactive
        element.focus();
        element.setAttribute('onblur', 'setInactive(this)');

        // set active-class to parent-div
        var parentDiv = getParentDiv(element);

        // if field is security-row: go to parentNode and do things
        if (
            parentDiv.classList.contains('question-input-div') ||
            parentDiv.classList.contains('captcha-input-div')
        ){
            parentDiv.parentNode.classList.add('active-field');
        }
        // field is no security-row: do things with field
        else {
            parentDiv.classList.add('active-field');
        }

        // field is a selectBox > mark selected option
        if (element.classList.contains('select-input') && ''!=element.value){
            var selectBox = getSiblingUl(element);
            var selectBoxOptions = selectBox.childNodes;
            for (i = 0; i < selectBoxOptions.length; ++i) {
                if ('li'==selectBoxOptions[i].nodeName.toLowerCase()){
                    if (element.value==selectBoxOptions[i].innerHTML){
                        selectBoxOptions[i].classList.add('active');
                    }
                    else {
                        selectBoxOptions[i].classList.remove('active');
                    }
                }
            }
        }
    }

    // set field inactive
    function setInactive(element){

        // remove active-class from parent-div
        var parentDiv = getParentDiv(element);

        // if field is security-row: go to parentNode and do things
        if (
            parentDiv.classList.contains('question-input-div') ||
            parentDiv.classList.contains('captcha-input-div')
        ){
            parentDiv.parentNode.classList.remove('active-field');
        }
        // field is no security-row: do things with field
        else{
            parentDiv.classList.remove('active-field');
        }

        // field contains string > set not-empty-class
        if (''!=element.value){
            parentDiv.classList.add('not-empty-field');
        }
        // field doesn't contain string > remove not-empty-class
        else{
            parentDiv.classList.remove('not-empty-field');
        }
    }
    // --------------------- helper

    // get the closest parent-div
    function getParentDiv(element) {
        while (element && element.parentNode) {
            element = element.parentNode;
            if (element.tagName && 'div'==element.tagName.toLowerCase()){
                return element;
            }
        }
        return null;
    }
</script>
<!-- ..................... -->

";
elseif ($success_msg !== ""):  // positive Statusausgabe ohne Formular
    $output .= "<br><br><br>";

    /*
    $output .= "
    <!-- <hr><br>
    <div><form action=".$root_site." method=\"POST\">
    <button class=\"btn btn-lg btn-primary btn-block\" type=\"submit\">
    Startseite</button>
    </form></div> -->";
    */

endif;  // Seite anzeigen

/*
$output .= "
<!-- mit Scroll-Leiste: <iframe src=\"./kontakt3/kontakt.php\" style=\"border: none; width:100%; height:700px;\"></iframe> -->
<!-- ohne Scroll-Leiste: <iframe src=\"./kontakt3/kontakt.php\" id=\"idIframe\" onload=\"iframeLoaded()\" style=\"border: none; width:100%;\" allowfullscreenscrolling=\"no\"> </iframe> -->
<!-- <iframe src=\"./kontakt1/kontakt.temp.php\" id=\"idIframe\" onload=\"iframeLoaded()\" style=\"border: none; width:100%;\" allowfullscreenscrolling=\"no\"> </iframe> -->
";
*/

$output .= "</div>";
$output .= "</div>";



        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }

}




######################################
#foreach ($_POST AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_GET AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_REQUEST AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_COOKIE AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_SERVER AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_SESSION AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";

#if (!empty($_SESSION['su'])) var_dump($_SESSION['idx2'], $_SESSION['siteid']);

#print_r('ident: '.$_COOKIE['identifier'].'<br>');
#print_r('token: '.$_COOKIE['securitytoken'].'<br>');
#print_r('token: '.sha1($_COOKIE['securitytoken']).'<br>');
#print_r(pathinfo($_SESSION['main']));
