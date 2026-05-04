<?php
namespace Dzg\SiteForm;
use Dzg\SitePrep\Register as Prep;
use Dzg\SiteData\Register as Database;
use Dzg\Tools\{Auth, Tools};
use Dzg\Mail\{MailConfig, Mail};

require_once __DIR__.'/../siteprep/register.php';
require_once __DIR__.'/../sitedata/register.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';
require_once __DIR__.'/../mail/Mail.php';
require_once __DIR__.'/../mail/MailConfig.php';


/**
 * Summary of Class Register
 */
class Register extends Prep
{
    protected const MSG = parent::MSG + [
        50 => "Name und Email angeben.",
        51 => "nur Kleinbuchstaben/Zahlen im Anmeldenamen zulässig",
        52 => "Keine gültige Email-Adresse.",
        53 => "Der Benutzername ist schon vergeben.",
        54 => "Die E-Mail-Adresse ist bereits registriert.",
        55 => "Bitte identische Passwörter eingeben",
        56 => "Passwort muss zwischen 4 und 50 Zeichen lang sein!",
        57 => "Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>",
        58 => "Name, Email und Passwort angeben.",
        59 => "Eine Email wurde dir soeben zur Bestätigung zugesandt, in der die Registrierung abschließend noch aktiviert werden muss. Danach kannst du dich anmelden.",
        60 => "Oh, die Email konnte <b>NICHT</b> gesendet werden :-(",
        61 => "Du wurdest erfolgreich registriert und kannst dich jetzt anmelden",
        62 => "Kontoaktivierung auf www.danzigmarken.de",
        63 => "Hallo",
        64 => "dein Konto auf www.danzigmarken.de muss noch bis zum",
        65 => "aktiviert werden.",
        66 => "Rufe dazu folgenden Link auf",
        67 => "Herzliche Grüße",
        68 => "Dein Support-Team von www.danzigmarken.de",
    ];
    private const REGEX_USR = "/^[\wäüößÄÜÖ\-]{3,50}$/";
    private const REGEX_EMAIL = "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix";
    private const REGEX_PW = "/^[\w<>()?!,.:_=$%&#+*~^ @€µäüößÄÜÖ]{1,100}$/";
    private const PW_MIN = 4;
    private const PW_MAX = 50;

    protected static bool $show_form;
    protected static string $status_message;
    protected static string $input_usr;
    protected static bool $activate_needed;


    /**
     * Formular-Eingabe verarbeiten
     * und an DB schicken
     */
    protected static function formEvaluation(): void
    {
        $usr_data  = self::$usr_data;
        $error_msg = self::$error_msg;
        $input = [
            'usr'  => "",
            'mail' => "",
            'pw1' => "",
            'pw2' => "",
        ];
        $show_form = True;
        $activate_needed = False;

        # Registrierungs-Link nicht okay, Seite nicht starten
        if ($error_msg):
            $show_form = False;

        # Registrierung-Link okay, Seite starten
        else:


        // Formularwerte empfangen
        //
        if (isset($_GET['regon'])
            && (strtoupper($_SERVER["REQUEST_METHOD"]) === "POST"))
        {

            // Nutzereingaben $_POST auswerten
            //
            if (isset($_POST['passwort'], $_POST['passwort2'])
                && isset($_POST['username'], $_POST['email']))
            {
                $input = [
                    'usr'  => strtolower(htmlspecialchars(Tools::cleanInput($_POST['username']))),
                    'mail' => htmlspecialchars(Tools::cleanInput($_POST['email'])),
                    'pw1'  => $_POST['passwort'],
                    'pw2'  => $_POST['passwort2'],
                ];

                # Plausi-Check Input
                $error_msg = self::plausi_check($input);
            }

            # $_POST Auswertung, Nutzereingaben unvollständig
            else {
                $error_msg = self::MSG[58];
            };


            // Eingaben okay, jetzt in Datenbank schreiben
            //
            if ($error_msg === "") {

                # wenn email gleich wie anfangs,
                # dann keine Email Verifizierung nötig.
                if ($input['mail'] == str_replace("_dummy_", "", $usr_data['email'])) {
                    $activate_needed = False;

                    $pwcode_endtime = NULL;
                    $status = 'activated';
                    $notiz = NULL;

                    $success_msg = self::MSG[61].': <a href="login.php?usr='.$input['usr'].'">Login</a>.';
                    $show_form = False;
                }

                # wenn Konto-Email anders als Anfrage-Email,
                # dann noch Verifizierung per Aktivierungs-Mail
                else {
                    $activate_needed = True;

                    # temporärer Aktivierungscode, 30 Tage gültig
                    $act_code = uniqid();
                    $pwcode_endtime = Auth::getPWcodeTimer();

                    # Links für Email-Versand erzeugen
                    $activate_url = Tools::getSiteURL().'activate.php?code='.$act_code;
                    $activate_link = 'activate.php?code='.$act_code;  // intern

                    $status = $act_code;
                    $notiz  = $activate_url;

                    # Email senden
                    if (self::send_email($input['mail'], $pwcode_endtime, $activate_url)) {
                        $success_msg = self::MSG[59];
                        $show_form = False;
                    }
                    else {
                        $error_msg = self::MSG[60];
                    };
                };

                # Passwort verschlüsseln
                $passwort_hash = password_hash($input['pw1'], PASSWORD_DEFAULT);

                # Nutzerdaten in DB eintragen
                $data = [
                    ':userid'         => $usr_data['userid'],   // int
                    ':username'       => $input['usr'],
                    ':email'          => $input['mail'],
                    ':pw_hash'        => $passwort_hash,
                    ':pwcode_endtime' => $pwcode_endtime,
                    ':status'         => $status,
                    ':notiz'          => $notiz
                    ];
                Database::storeUser($data);

            };  // Eingabewerte in Datenbank schreiben
        };      // Formularwerte empfangen
        endif;  // Registrierung-Link okay, Seite starten

        $status_message = Tools::statusOut($success_msg, $error_msg);


        self::$show_form = $show_form;
        self::$input_usr = $input['usr'];
        self::$error_msg = $error_msg;
        self::$status_message  = $status_message;
        self::$activate_needed = $activate_needed;
    }



    /**
     * prüft Benutzereingaben die im $input Array übergeben werden
     *
     * @param array $input : Array der Benutzereingaben,
     * - @param string $input['usr']  : Name
     * - @param string $input['mail'] : Email
     * @return string : Fehlermeldung
     */
    private static function plausi_check(array $input): string
    {
        $error_msg = "";

        # kein Name oder Email angegeben
        if ($input['usr'] === "" || $input['mail'] === "") {
            $error_msg = self::MSG[50];
        }

        # Username-Check nicht alphanumerisch
        elseif ($input['usr'] !== ""
            && preg_match("/\W/", $input['usr'], $matches))
        {
            $error_msg = self::MSG[51].': '.htmlspecialchars($matches[0]);
        };

        # angegebene Email nicht okay
        if ($input['mail'] !== "" && (!filter_var($input['mail'], FILTER_VALIDATE_EMAIL))) {
            $error_msg = self::MSG[52];
        };

        # Eingabe usernamen/email mit DB abgleichen
        if ($error_msg === "") {
            $error_msg = self::db_check($input);
        };

        # usernamen/email nutzbar --> Passwort Check
        if ($error_msg === "") {
            $error_msg = self::pw_check($input);
        };

        return $error_msg;
    }


    private static function db_check(array $input): string
    {
        $input_usr   = $input['usr'];
        $input_email = $input['mail'];
        $exist = $update_username = $update_email = false;
        $error_msg = "";

        # usernamen/email im Bestand suchen
        $data = [':username' => $input_usr, ':email' => $input_email];
        $userliste = Database::searchActivatedUser($data);

        # Benutzername vergeben
        if ($input_usr !== "") {
            if ($userliste) {
                foreach ($userliste AS $user_info) {
                    $exist = array_search($input_usr, $user_info);
                    if ($exist) break;
                };
            };
            $exist
                ? $error_msg = self::MSG[53]
                : $update_username = True;
        };

        # Email vergeben
        if ($input_email !== "") {
            if ($userliste) {
                foreach ($userliste AS $user_info) {
                    $exist = array_search($input_email, $user_info);
                    if ($exist) break;
                };
            };
            $exist
                ? $error_msg = self::MSG[54]
                : $update_email = True;
        };

        return $error_msg;
    }


    private static function pw_check(array $input): string
    {
        $input_pw1 = $input['pw1'];
        $input_pw2 = $input['pw2'];
        $error_msg = "";

        # Kontrollpasswort unterscheidet sich
        if ($input_pw1 !== $input_pw2) {
            $error_msg = self::MSG[55];
        }

        # Passwortlänge (4..50 Zeichen) stimmt nicht
        elseif (strlen($input_pw1) < self::PW_MIN
            || strlen($input_pw1) > self::PW_MAX)
        {
            $error_msg = self::MSG[56];
        }

        # ungültige Zeichen verwendet
        elseif (!preg_match(self::REGEX_PW, $input_pw1)) {
            $error_msg = self::MSG[57];
        };

        return $error_msg;
    }


    private static function
    send_email(string $input_email, string $pwcode_endtime, string $activate_url): bool
    {
        $cfg  = MailConfig::$cfg;
        $smtp = MailConfig::$smtp[0];
        $email1_okay = $email2_okay = false;

        $ip   = $_SERVER['REMOTE_ADDR'];
        $host = getHostByAddr($ip);
        $date = date("d.m.Y | H:i");
        $UserAgent = $_SERVER["HTTP_USER_AGENT"];


        # create mail for customer
        $mailto1  = $input_email;
        $subject1 = self::MSG[62];
        $mailcontent1 = self::MSG[63].' '.$mailto1.",\n\n".
            self::MSG[64].date(" d.m.y ", $pwcode_endtime).
            self::MSG[65].' '.
            self::MSG[66].": \n\n".$activate_url."\n\n".
            self::MSG[67]."\n".
            self::MSG[68]."\n";

        # create mail for admin
        $mailto2  = $smtp['from_addr'];
        $subject2 = "[Info] Anfrage zur Kontoaktivierung auf www.danzigmarken.de";
        $mailcontent2 = "Folgendes wurde am ". $date ." Uhr verschickt:\n".
            "-------------------------------------------------------------------------\n\n".
            "Eine Email an den Anfragenden wurde gesendet.\n\n".
            "Von: ".$smtp['from_name']." <".$smtp['from_addr'].">\n".
            "An: ".$mailto1."\n".
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