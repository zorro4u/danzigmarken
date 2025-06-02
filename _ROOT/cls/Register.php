<?php
/* Prozess: RegInfoSeite-->email(Admin)-->email(RegCode)-->dieseSeite:RegSeite-->email(Admin)/email(AktLink)-->ActivateSeite-->Login */
namespace Dzg\Cls;

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

#require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Auth.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Kontakt.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Footer.php';

use Dzg\Cls\{Database, Auth, Tools, Kontakt, Header, Footer};
use Dzg\Mail\{Mailcfg, Smtp};
use PDO, PDOException, Exception;


/***********************
 * Summary of Register
 */
class Register
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private static $pdo;
    private static $showForm;
    private static string $status_message;
    private static $usr_data;
    private static $input_code;
    private static $input_usr;
    private static $activate_needed;
    private static $error_msg;


    /****************************
     * Summary of show
     */
    public static function show()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connect_mariadb();
        }

        self::data_preparation();

        Header::show();
        self::site_output();
        Footer::show("auth");

        // Datenbank schließen
        self::$pdo = Null;
    }


    /***********************
     * Summary of data_preparation
     */
    private static function data_preparation()
    {
        $pdo = self::$pdo;

        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details'];

        if (isset($_SERVER['HTTP_REFERER']) &&
            (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']) === false))
        {
            // wenn VorgängerSeite bekannt und nicht die aufgerufene Seite selbst ist, speichern
            $referer = str_replace("change", "details", $_SERVER['HTTP_REFERER']);
            $fn_referer = pathinfo($referer)['filename'];
            // wenn Herkunft von den target-Seiten, dann zu diesen, ansonsten Standardseite
            $_SESSION['lastsite'] =  (in_array($fn_referer, $return2))
                ? $referer
                : $_SESSION['main'];

        } elseif (empty($_SERVER['HTTP_REFERER']) &&
            empty($_SESSION['lastsite']))
        {
            // wenn nix gesetzt ist, auf Standard index.php verweisen
            $_SESSION['lastsite'] = (!empty($_SESSION['main'])) ? $_SESSION['main'] : "/";
        }
        unset($return2, $referer, $fn_referer);


        /*
        * Seitenaufruf mit Registrierungscode
        */

        $error_msg = "";
        $success_msg = "";
        $exist = False;
        $showForm = True;
        $activate_needed = False;


        // Registrierungs-Code checken
        if (!isset($_GET['code'])) {
            $error_msg = "Es fehlt ein gültiger Registrierungs-Link. <br>Wende dich dafür an den Seitenbetreiber.";
        } else {
            $input_code = htmlspecialchars(Tools::clean_input($_GET['code']));

            // Plausi-Check
            if ($input_code === "")
                $error_msg = 'Es wurden kein Code zum Start der Registrierung übermittelt.';
            elseif (!preg_match('/^[a-zA-Z0-9]{1,1000}/', $input_code))
                $error_msg = 'Der Code enhält ungültige Zeichen.';
            else {}
        }

        // Link mit DB abgleichen
        if ($error_msg === "") {

            // Registrierungs-Link auf Gültigkeit prüfen
            $stmt = "SELECT userid, email, pwcode_endtime FROM site_users WHERE status = :status";
            $data = [':status' => $input_code];
            try {
                $qry = $pdo->prepare($stmt);
                $qry->execute($data);
                $usr_data = $qry->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {die($e->getMessage().': register.inc_status');}

            if (!$usr_data) {
                $error_msg = "Registrierungs-Link wurde verändert oder ist nicht gültig.";

            } elseif (($usr_data['pwcode_endtime'] + 3600*1) < time()) {  // +1 Std. Karenz
                // veralteten Eintrag löschen
                $stmt0 = "UPDATE site_users SET status=NULL, pwcode_endtime=NULL, notiz=NULL, pwc=NULL WHERE userid=:userid";
                $stmt = "DELETE FROM site_users WHERE userid=:userid";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(':userid', $usr_data['userid'], PDO::PARAM_INT);
                    $qry->execute();
                } catch(PDOException $e) {die($e->getMessage().': register.inc_del-old-link');}
                $error_msg = "Registrierungs-Link ist nach 4 Wochen am ".date('d.m.Y', $usr_data['pwcode_endtime'])." abgelaufen.";

            } else {}
        }

        // Registrierung-Link okay, Seite starten
        if ($error_msg === "") {

        // Formularwerte empfangen
        if (isset($_GET['regon']) && (strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")) {

            // Nutzereingaben $_POST auswerten
            if (isset($_POST['passwort'], $_POST['passwort2']) && isset($_POST['username'], $_POST['email'])) {
                $regex_usr = "/^[\wäüößÄÜÖ\-]{3,50}$/";
                $regex_email = "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix";
                $regex_pw = "/^[\w<>()?!,.:_=$%&#+*~^ @€µäüößÄÜÖ]{1,100}$/";

                $input_usr = strtolower(htmlspecialchars(Tools::clean_input($_POST['username'])));
                $input_email = htmlspecialchars(Tools::clean_input($_POST['email']));
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
                    $stmt = "SELECT username, email FROM site_users WHERE status='activated' AND (username = :username OR email = :email)";
                    $data = [':username' => $input_usr, ':email' => $input_email];
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->execute($data);
                        $userliste = $qry->fetchALL(PDO::FETCH_ASSOC);
                    } catch(PDOException $e) {die($e->getMessage().': register.inc_name-mail-suchen');}

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
                }  // Eingabe usernamen/email okay

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

            $userliste = [];  // Bestandsliste wieder löschen;

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

                    $act_code = uniqid();  // temporärer Aktivierungscode
                    $pwcode_endtime = time() + 3600*24*30;  // 4 Woche Frist für Code

                    // Links für Email-Versand erzeugen
                    $activate_url = Tools::getSiteURL().'activate.php?code='.$act_code;
                    $activate_link = 'activate.php?code='.$act_code;  // intern

                    $status = $act_code;
                    $notiz = $activate_url;
                }

                $passwort_hash = password_hash($input_pw1, PASSWORD_DEFAULT);

                // Nutzerdaten in DB eintragen
                $stmt = "UPDATE site_users
                    SET username = :username, email = :email, pw_hash = :pw_hash,
                        status = :status, pwcode_endtime = :pwcode_endtime, notiz = :notiz
                    WHERE userid = :userid";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(':userid', $usr_data['userid'], PDO::PARAM_INT);
                    $qry->bindParam(':username', $input_usr, PDO::PARAM_STR);
                    $qry->bindParam(':email', $input_email, PDO::PARAM_STR);
                    $qry->bindParam(':pw_hash', $passwort_hash, PDO::PARAM_STR);
                    $qry->bindParam(':pwcode_endtime', $pwcode_endtime, PDO::PARAM_STR);
                    $qry->bindParam(':status', $status, PDO::PARAM_STR);
                    $qry->bindParam(':notiz', $notiz, PDO::PARAM_STR);
                    $qry->execute();
                } catch(PDOException $e) {die($e->getMessage().': register.inc_store-user');}

                // wenn Konto-Email anders als Anfrage-Email, dann noch Verifizierung per Aktivierungs-Mail
                if ($activate_needed) {

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
                    $subject1 = "Kontoaktivierung auf www.danzigmarken.de";
                    $mailcontent1  = "Hallo ".$mailto1.",\n\n".
                        "dein Konto auf www.danzigmarken.de muss noch bis zum ".date("d.m.y", $pwcode_endtime)." aktiviert werden. ".
                        "Rufe dazu folgenden Link auf: \n\n".$activate_url."\n\n".
                        "Herzliche Grüße\n".
                        "Dein Support-Team von www.danzigmarken.de\n";

                    // ---- create mail for admin
                    $mailto2      = $smtp['from_addr'];
                    $subject2 = "[Info:] Anfrage zur Kontoaktivierung auf www.danzigmarken.de";
                    $mailcontent2  = "Folgendes wurde am ". $date ." Uhr verschickt:\n".
                        "-------------------------------------------------------------------------\n\n".
                        "Eine Email an den Anfragenden wurde gesendet.\n\n".
                        "Von: ".$smtp['from_name']." <".$smtp['from_addr'].">\n".
                        "An: ".$mailto1."\n".
                        "Betreff: ".$subject1."\n".
                        "Nachricht:\n\n".$mailcontent1;

                    // via SMTP
                    if ($smtp['enabled'] !== 0) {

                        // mail it to customer from rain.0
                        $email_send1 = SMTP::send(
                            $smtp['mail_host'],
                            $smtp['login_usr'],
                            $smtp['login_pwd'],
                            $smtp['encryption'],
                            $smtp['smtp_port'],
                            $smtp['from_addr'],
                            $smtp['from_name'],
                            $mailto1,
                            $subject1,
                            $mailcontent1,
                            [],
                            'upload_directory',
                            $smtp['debug']
                        );

                        // mail it to admin from rain.0
                        $email_send2 = SMTP::send(
                            $smtp['mail_host'],
                            $smtp['login_usr'],
                            $smtp['login_pwd'],
                            $smtp['encryption'],
                            $smtp['smtp_port'],
                            $smtp['from_addr'],
                            $smtp['from_name'],
                            $mailto2,
                            $subject2,
                            $mailcontent2,
                            [],
                            'upload_directory',
                            $smtp['debug']
                        );

                    // via PHP_included_function
                    } else {
                        $email_send1 = Kontakt::sendMyMail(
                            $smtp['from_addr'],
                            $smtp['from_name'],
                            $mailto1,
                            $subject1,
                            $mailcontent1
                        );
                        $email_send2 = Kontakt::sendMyMail(
                            $smtp['from_addr'],
                            $smtp['from_name'],
                            $mailto2,
                            $subject2,
                            $mailcontent2
                        );
                    }

                    // === ENDE EMAIL-Abschnitt ===

                }  // ende.Email-Verifizierung notwendig

                if ($activate_needed) {
                    if ($email_send1) {
                        $success_msg = "Eine Email wurde dir soeben zur Bestätigung zugesandt, in der die Registrierung abschließend noch aktiviert werden muss. Danach kannst du dich anmelden.";
                        $showForm = False;
                    } else {
                        $error_arr []= 'Oh, die Email konnte <b>NICHT</b> gesendet werden :-(';
                    }
                } else {
                    $success_msg = 'Du wurdest erfolgreich registriert und kannst dich jetzt <a href="login.php?usr='.$input_usr.'">anmelden</a>.';
                    $showForm = False;
                }

            }  // Eingabewerte in Datenbank schreiben
        }  // Formularwerte empfangen

        } else {
            // Registrierungs-Link nicht okay, Seite nicht starten
            $showForm = False;
        }


        #$showForm = ($error_msg === "") ? True : False;
        $status_message = Tools::status_out($success_msg, $error_msg);


        self::$showForm = $showForm;
        self::$status_message = $status_message;
        self::$usr_data = $usr_data;
        self::$input_code = $input_code;
        self::$input_usr = $input_usr;
        self::$activate_needed = $activate_needed;
        self::$error_msg = $error_msg;
    }


    /****************************
     * Summary of site_output
     */
    public static function site_output()
    {
        $showForm = self::$showForm;
        $status_message = self::$status_message;
        $usr_data = self::$usr_data;
        $input_code = self::$input_code;
        $input_usr = self::$input_usr;
        $activate_needed = self::$activate_needed;
        $error_msg = self::$error_msg;

        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='main-container registration-form'>";
        $output .= "
            <h2>Registrierung</h2>
            <p><br></p>";

        // Seite anzeigen
        if ($showForm):

        $pre_user = (!empty($usr_data['username']))
            ? str_replace("_dummy_", "", $usr_data['username'])
            : '';
        $pre_mail = (!empty($usr_data['email']))
            ? str_replace("_dummy_", "", $usr_data['email'])
            : '';

        $output .= "

<form action='./register.php?code=".$input_code."&regon=1' method='POST' style='margin-top: 30px;'>

<div class='form-group'>
    <label for='inputName'>Benutzername: <span style='color:red'>*</span></label>
    <input type='text' required id='inputName' name='username' autocomplete='name' value='".$pre_user."' size='40' maxlength='250' class='form-control' >
</div>

<div class='form-group'>
    <label for='inputEmail'>E-Mail: <span style='color:red'>*</span></label>
    <input type='email' required id='inputEmail' name='email' autocomplete='email' value='".$pre_mail."' size='40' maxlength='250' class='form-control' >
</div>

<div class='form-group'>
    <label for='inputPasswort'>Passwort: <span style='color:red'>*</span></label>
    <input type='password' required id='inputPasswort' name='passwort' autocomplete='new-password' autofocus size='40'  maxlength='250' class='form-control' spellcheck='false' onfocusin='(this.type='text')' onfocusout='(this.type='password')'>
</div>

<div class='form-group'>
    <label for='inputPasswort2'>Passwort wiederholen: <span style='color:red'>*</span></label>
    <input type='password' required id='inputPasswort2' name='passwort2' autocomplete='off' size='40' maxlength='250' class='form-control' spellcheck='false' onfocusin='(this.type='text')' onfocusout='(this.type='password')'>
</div>

    <br>
    <button type='submit' class='btn btn-lg btn-primary btn-block'>Registrieren</button>
</form>

<br><br><hr>
<b>Hinweise:</b>
<ul>
    <li><span style='color:red'>*</span> Felder bitte ausfüllen.</li>
    <li><span style='text-decoration:underline;'>Name</span>: Buchstaben, Zahlen oder Bindestriche</li>
    <li><span style='text-decoration:underline;'>Passwort</span>: Buchstaben, Zahlen oder ausgewählte Sonderzeichen, mind. 4 Zeichen</li><br>
<!--    <li>Du wirst eine Email mit einem Bestätigungs-Link zur Verifizierung erhalten. Danach ist eine Anmeldung möglich.</li> -->
</ul>";

// positive Statusausgabe ohne Formular
elseif (!$activate_needed && $error_msg === ''):  // positive Statusausgabe ohne Formular
        $output .= "
<br><br><hr><br>
<div><form action='./login.php?usr=".$input_usr."' method='POST'>
    <button class='btn btn-lg btn-primary btn-block'>Anmelden</button>
</form></div>

<?php else: // bei Fehler oder Mailbestätigung?>
<div><form action='/index.php' method='POST'>
    <button class='btn btn-lg btn-primary btn-block'>Startseite</button>

</form></div>";

endif;


        $output .= "</div>";
        $output .= "</div>";


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }

}