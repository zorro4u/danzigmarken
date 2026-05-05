<?php
/* Prozess: dieseSeite:Forget-->email(Admin)/email(Code)-->ResetSeite-->Login */

namespace Dzg\SitePrep;
use Dzg\SiteData\PWforget as Data;
use Dzg\Tools\{Auth, Tools};
use Dzg\Mail\{MailConfig, Mail};

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);
session_start();

require_once __DIR__.'/../mail/Mail.php';
require_once __DIR__.'/../mail/MailConfig.php';
require_once __DIR__.'/../sitedata/pwforget.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Pw_forget
 */
class PWforget
{
    protected static bool $show_form;
    protected static string $status_message;
    protected static string $success_msg;
    protected static string $pre_email;


    /**
     * Summary of dataPreparation
     */
    protected static function dataPreparation(): void
    {
        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details', 'login'];
        Tools::lastSite($return2);

        #$error_msg = "Zum Zurücksetzen des Passwortes wende dich an den Seitenbetreiber.";

        $input_email = "";
        $success_msg = "";
        $error_msg = "";
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
                $usr_data = Data::searchEmail($input_email);

                if(!$usr_data) {
                    $error_msg = "Keine solche E-Mail-Adresse im System hinterlegt.";

                // okay... temporären Passwort-Code in DB schreiben, 2 Tage gültig
                } else {
                    $pwcode = Auth::generateRandomString();
                    $pwcode_hash = sha1($pwcode);
                    $pwcode_endtime = Auth::getPWcodeTimer(2);
                    $pwcode_endtime_str = date("d.m.y H:i", $pwcode_endtime);
                    $pwcode_url = Tools::getSiteURL().'pwreset.php?pwcode='.$pwcode;

                    $data = [
                        ':userid'         => $usr_data['userid'],   # int
                        ':notiz'          => $pwcode_url,
                        ':pwcode_hash'    => $pwcode_hash,
                        ':pwcode_endtime' => $pwcode_endtime ];
                    Data::setPassCode($data);

                    // Anrede
                    if (!empty($usr_data['vorname']))
                        $name = $usr_data['vorname'];
                    elseif ($usr_data['username'])
                        $name = $usr_data['username'];
                    else
                        $name = $usr_data['email'];


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
                $subject1 = 'Neues Passwort für www.danzigmarken.de';
                $mailcontent1 = "Hallo ".$name.",\n\n".
                    "für deinen Account auf www.danzigmarken.de wurde nach einem neuen Passwort gefragt.\n".
                    "Um ein neues Passwort zu vergeben, rufe innerhalb der nächsten 48 Stunden (bis ".$pwcode_endtime_str.") ".
                    "die folgende Website auf:\n\n".$pwcode_url."\n\n".
                    "Sollte dir dein Passwort wieder eingefallen sein oder hast du dies nicht angefordert, so ignoriere diese E-Mail.\n\n".
                    "Herzliche Grüße\n".
                    "Dein Support-Team von www.danzigmarken.de\n";

                // ---- create info mail for admin
                $mailto2  = $smtp['from_addr'];
                $subject2 = "[Info:] Reset Passwort auf www.danzigmarken.de";
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
}


// EOF