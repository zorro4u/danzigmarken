<?php
/* Prozess: dieseSeite:RegInfo-->email(Admin)-->email(RegCode)-->RegSeite-->email(Admin)/email(AktLink)-->ActivateSeite-->Login */
namespace Dzg;

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

#header('Content-type: text/html; charset=utf-8');

require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Auth.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/mail/Mail.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Footer.php';


/***********************
 * Summary of Register_info
 */
class Register_info
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private static $show_form;
    private static string $status_message;
    private static $success_msg;
    private static $input_message_first;

    private static $pre_usr;
    private static $pre_email;


    /****************************
     * Summary of show
     */
    public static function show()
    {
        self::dataPreparation();

        Header::show();
        self::siteOutput();
        Footer::show("auth");
    }


    /***********************
     * Summary of dataPreparation
     */
    private static function dataPreparation()
    {
        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details'];
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
        unset($return2, $referer, $fn_referer);

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
                $reg_url = Tools::getSiteURL().'register.php?code='.$reg_code;
                $reg_link = 'register.php?code='.$reg_code;     // intern

                $input_usr_temp = $input_vor."_dummy_";
                $input_email_temp = $input_email."_dummy_";
                $status = $reg_code;
                $notiz = $reg_url;

                // Nutzerdaten schon einmal in DB temporär erfassen
                $stmt = "INSERT
                    INTO site_users (username, email, status, pwcode_endtime, notiz, vorname, nachname)
                    VALUES (:username, :email, :status, :pwcode_endtime, :notiz, :vorname, :nachname)";
                $data = [
                    ':username'       => $input_usr_temp,
                    ':email'          => $input_email_temp,
                    ':status'         => $status,
                    ':pwcode_endtime' => $pwcode_endtime,
                    ':notiz'          => $notiz,
                    ':vorname'        => $input_vor,
                    ':nachname'       => $input_nach ];
                Database::sendSQL($stmt, $data);

                // === EMAIL ===
                //
                $cfg = Mailcfg::$cfg;
                $smtp = Mailcfg::$smtp;

                $ip = $_SERVER['REMOTE_ADDR'];
                $host = getHostByAddr($ip);
                $UserAgent = $_SERVER["HTTP_USER_AGENT"];
                $date = date("d.m.Y | H:i");

                // ---- create mail for customer
                $mailto1  = $input_email;
                $subject1 = 'Registrierungs-Link für www.danzigmarken.de';
                $mailcontent1  = "Hallo ".$input_name.",\n\n".
                    "du kannst dich jetzt auf www.danzigmarken.de registrieren. ".
                    "Rufe dazu in den nächsten 4 Wochen (bis zum ".date('d.m.y', $pwcode_endtime).") ".
                    "den folgenden Link auf: \n\n".$reg_url ."\n\n".
                    "Herzliche Grüße\n".
                    "Dein Support-Team von www.danzigmarken.de\n";

                // ---- create mail for admin(steffen)
                $mailto2      = $smtp['from_addr'];
                $subject2 = "[Info:] Anfrage zur Registrierung auf www.danzigmarken.de";
                $mailcontent2  = "Folgendes wurde am ". $date ." Uhr per Formular empfangen:\n".
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
        $pre_usr = "";
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


    /****************************
     * Summary of siteOutput
     */
    public static function siteOutput()
    {
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $input_message_first = self::$input_message_first;
        $success_msg = self::$success_msg;
        $pre_usr = self::$pre_usr;
        $pre_email = self::$pre_email;

        $output = "<div class='container'>";
        #$output = "<div class='container main-container registration-form'>";
        $output .= $status_message;

        $output .= "<div class='registration-form'>";
        $output .= "
            <h2>Registrierung</h2>
            <br>";

        // Seite anzeigen
        if ($show_form):
        $output .= "

<p>Du interessierst dich für diese Seiten und willst erweiterten Zugriff auf den Inhalt haben? <br>
Informiere mich kurz darüber und du erhälst Zugang via deiner Email-Adresse.</p>

<form action='?regon' method='POST' style='margin-top: 30px;'>

<div class='form-group'>
    <label for='inputName'>Dein Name:</label>
    <input type='text' id='inputName' name='name' autocomplete='name' value='{$pre_usr}' class='form-control' size='40' maxlength='50' autofocus>
</div>

<div class='form-group'>
    <label for='inputEmail'>Deine E-Mail: <span style='color:red'>*</span></label>
    <input type='email' required id='inputEmail' name='email' autocomplete='email' value='{$pre_email}' class='form-control' size='40' maxlength='100'>
</div>

<div class='form-group'>
    <label for='inputMessage'>Deine Nachricht: <span style='color:red'>*</span></label>
    <textarea  required id='inputMessage' name='message' rows='9' style='width:100%;' maxlength='250' spellcheck='true' class='form-control'>{$input_message_first}</textarea>
</div>

    <br>
    <button type='submit' class='btn btn-lg btn-primary btn-block'>Anfrage senden</button>
</form>

<br><br><hr>
<b>Hinweise:</b>
<ul>
    <li>Alle <span style='color:red'>*</span> Felder bitte ausfüllen.</li>
    <li>Du wirst als nächstes eine Email mit deinem Registrierungs-Link erhalten.</li>
</ul>";

// positive Statusausgabe ohne Formular
elseif ($success_msg !== ""):
    $output .= "
<br><br><hr><br>
<div><form action='".$_SESSION['main']."' method='POST'>
    <button class='btn btn-lg btn-primary btn-block' type='submit'>Startseite</button>
</form></div>";

endif;  // Seite anzeigen

/*
<!-- mit Scroll-Leiste: <iframe src='./kontakt3/kontakt.php' style='border: none; width:100%; height:700px;'></iframe>-->
<!-- ohne Scroll-Leiste: <iframe src='./kontakt3/kontakt.php' id='idIframe' onload='iframeLoaded()' style='border: none; width:100%;' allowfullscreenscrolling='no'> </iframe>-->
<!--<iframe src='./kontakt1/kontakt.temp.php' id='idIframe' onload='iframeLoaded()' style='border: none; width:100%;' allowfullscreenscrolling='no'> </iframe>-->

<!-- <?php #include_once "./kontakt1/kontakt.temp.php"; ?> -->
*/

        $output .= "</div>";
        $output .= "</div>";



        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }
}
