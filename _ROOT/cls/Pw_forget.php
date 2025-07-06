<?php
/* Prozess: dieseSeite:Forget-->email(Admin)/email(Code)-->ResetSeite-->Login */
namespace Dzg;

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);
session_start();

require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Auth.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/mail/Mail.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Footer.php';


/***********************
 * Summary of Pw_forget
 */
class Pw_forget
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private static $show_form;
    private static string $status_message;
    private static $success_msg;
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
        $return2 = ['index', 'index2', 'details', 'login'];
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

        #$error_msg = "Zum Zurücksetzen des Passwortes wende dich an den Seitenbetreiber.";

        $error_msg = "";
        $success_msg = "";
        $show_form = True;

        // Formularwerte empfangen
        if(isset($_GET['send']) && strtoupper($_SERVER["REQUEST_METHOD"] === "POST")):

            // Eingabeformular hat Daten mit $_POST gesendet
            if(isset($_POST['email'])) {
                $input_email = htmlspecialchars(Tools::cleanInput($_POST['email']));

                // Email auf Plausibilität prüfen
                if (!filter_var($input_email, FILTER_VALIDATE_EMAIL))
                    $error_msg = 'Keine gültige Email-Adresse.';
            } else
                $error_msg = "Bitte eine E-Mail-Adresse eintragen.";

            // und okay...
            if(empty($error_msg)):

                // Email in DB suchen
                $stmt = "SELECT userid, email, username, vorname, nachname FROM site_users WHERE email = :email";
                $data = [':email' => $input_email];
                $usr_data = Database::sendSQL($stmt, $data, 'fetch');

                if(!$usr_data) {
                    $error_msg = "Keine solche E-Mail-Adresse im System hinterlegt.";

                // okay... temporären Passwort-Code in DB schreiben, 2 Tage gültig
                } else {
                    $pwcode = Auth::generateRandomString();
                    $pwcode_hash = sha1($pwcode);
                    $pwcode_endtime = Auth::getPWcodeTimer(2);
                    $pwcode_endtime_str = date("d.m.y H:i", $pwcode_endtime);
                    $pwcode_url = Tools::getSiteURL().'pwreset.php?pwcode='.$pwcode;

                    $stmt = "UPDATE site_users
                        SET pwcode_hash = :pwcode_hash, pwcode_endtime = :pwcode_endtime, notiz = :notiz
                        WHERE userid = :userid";
                    $data = [
                        ':userid'         => $usr_data['userid'],   # int
                        ':notiz'          => $pwcode_url,
                        ':pwcode_hash'    => $pwcode_hash,
                        ':pwcode_endtime' => $pwcode_endtime ];
                    Database::sendSQL($stmt, $data);

                    // Anrede
                    if (!empty($usr_data['vorname']))
                        $name = $usr_data['vorname'];
                    elseif ($usr_data['username'])
                        $name = $usr_data['username'];
                    else
                        $name = $usr_data['email'];


                // === EMAIL ===
                //
                $cfg = MailConfig::$cfg;
                $smtp = MailConfig::$smtp;

                $ip = $_SERVER['REMOTE_ADDR'];
                $host = getHostByAddr($ip);
                $UserAgent = $_SERVER["HTTP_USER_AGENT"];
                $date = date("d.m.Y | H:i");

                // ---- create mail for customer
                $mailto1  = $input_email;
                $subject1 = 'Neues Passwort für www.danzigmarken.de';
                $mailcontent1  = "Hallo ".$name.",\n\n".
                    "für deinen Account auf www.danzigmarken.de wurde nach einem neuen Passwort gefragt.\n".
                    "Um ein neues Passwort zu vergeben, rufe innerhalb der nächsten 48 Stunden (bis ".$pwcode_endtime_str.") ".
                    "die folgende Website auf:\n\n".$pwcode_url."\n\n".
                    "Sollte dir dein Passwort wieder eingefallen sein oder hast du dies nicht angefordert, so ignoriere diese E-Mail.\n\n".
                    "Herzliche Grüße\n".
                    "Dein Support-Team von www.danzigmarken.de\n";

                // ---- create info mail for admin
                $mailto2  = $smtp['from_addr'];
                $subject2 = "[Info:] Reset Passwort auf www.danzigmarken.de";
                $mailcontent2  = "Folgendes wurde am ". $date ." Uhr verschickt:\n".
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

                // === ENDE EMAIL-Abschnitt ===


                if ($email_send1) {
                    $success_msg =
                        "Dir wurde soeben eine Email an ".$usr_data['email']." zugesandt, mit der du innerhalb der nächsten 48 Stunden ".
                        "(bis zum <b>".$pwcode_endtime_str."</b>) dir eine neues Passwort vergeben kannst.";
                    $show_form = False;
                } else {
                    $error_arr []= 'Oh, die Email konnte <b>NICHT</b> gesendet werden :-(';
                }
            }
            endif;
        endif;


        // Wert für das Vorausfüllen des Login-Formulars
        $pre_email = "";
        if (isset($_GET['email']))
            $pre_email = htmlspecialchars(Tools::cleanInput($_GET['email']));
        elseif (!empty($input_email))
            $pre_email = $input_email;

        $show_form = ($error_msg === "") ? True : False;
        $status_message = Tools::statusOut($success_msg, $error_msg);


        self::$show_form = $show_form;
        self::$status_message = $status_message;
        self::$success_msg = $success_msg;
        self::$pre_email = $pre_email;
    }


    /****************************
     * Summary of siteOutput
     */
    public static function siteOutput()
    {
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $success_msg = self::$success_msg;
        $pre_email = self::$pre_email;

        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='small-container-330'>";
        $output .= "
            <h2>Passwort vergessen?</h2>
            <br>";

        // Seite anzeigen
        if ($show_form):
        $output .= "

<p>Gib deine registrierte E-Mail-Adresse an, um ein neues Passwort anzufordern.</p>

<form action='?send' method='POST'>
<label for='inputEmail'></label>
<input type='email' required id='inputEmail' name='email' autocomplete='email' placeholder='E-Mail' value='{$pre_email}' class='form-control'>
<br><br>
<input  class='btn btn-lg btn-primary btn-block' type='submit' value='Neues Passwort anfordern' autocomplete='off'>
</form>";

// positive Statusausgabe ohne Formular
elseif ($success_msg !== ""):
    $output .= "
<br><form action='".$_SESSION['main']."' method='POST'>
    <button class='btn btn-lg btn-primary btn-block' type='submit'>Startseite</button>
</form>";

endif;


        $output .= "</div>";
        $output .= "</div>";


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }

}