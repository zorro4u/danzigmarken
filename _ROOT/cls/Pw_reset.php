<?php
/* Prozess: ForgetSeite-->email(Admin)/email(Code)-->dieseSeite:ResetSeite-->Login */
namespace Dzg\Cls;

// TODO: alle Autologins beenden

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

#require_once __DIR__.'/includes/auth.func.php';
#require_once __DIR__.'/includes/register-info.inc.php';

require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Footer.php';

use Dzg\Cls\{Database, Tools, Header, Footer};
use PDO, PDOException;


/***********************
 * Summary of Pw_reset
 */
class Pw_reset
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private static $pdo;
    private static $showForm;
    private static string $status_message;
    private static $success_msg;
    private static $name;
    private static $input_code;
    private static $usr_data;


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


        /*
        * Seitenaufruf mit passwortcode
        */

        $error_msg = "";
        $success_msg = "";
        $name = "";
        $input_code = "";
        $showForm = True;

        if (!isset($_GET['pwcode'])) {
            $error_msg = "Ohne Legitimations-Code kann das Passwort nicht zurückgesetzt werden.";

        // Passcode prüfen
        } else {
            $input_code = htmlspecialchars(Tools::clean_input($_GET['pwcode']));

            // Werte auf Plausibilität prüfen
            if ($input_code === "")
                $error_msg = 'Es wurde kein Legitimations-Code zum Zurücksetzen des Passworts übermittelt.';
            elseif (!preg_match('/^[a-zA-Z0-9]{1,1000}/', $input_code))
                $error_msg = 'Der Passcode enhält ungültige Zeichen.';
        }

        // Link mit DB abgleichen
        if ($error_msg === "") {

            // Passcode auf Gültigkeit prüfen
            $pwcode_hash = sha1($input_code);
            $stmt = "SELECT userid, username, email, vorname, nachname, pwcode_endtime FROM site_users WHERE pwcode_hash = :pwcode_hash";
            $data = [':pwcode_hash' => $pwcode_hash];
            try {
                $qry = $pdo->prepare($stmt);
                $qry->execute($data);
                $usr_data = $qry->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {die($e->getMessage().': pwreset_#1');}

            if (!$usr_data)
                $error_msg = "Der Benutzer wurde nicht gefunden oder hat kein neues Passwort angefordert bzw. der übergebene Code war ungültig. ".
                    "<hr>Stell sicher, dass du den genauen Link in der URL aufgerufen hast. ".
                    "Solltest du mehrmals die Passwortvergessen-Funktion genutzt haben, so ruf den Link in der neuesten E-Mail auf.";

            elseif (($usr_data['pwcode_endtime'] + 3600*1) < time()) {  // +1 Std. Karenz
                // Passcode abgelaufen, veralteten Eintrag löschen
                $stmt = "UPDATE site_users SET pwcode_hash = NULL, pwcode_endtime = NULL, pwc = NULL, notiz = NULL WHERE userid = :userid";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(':userid', $usr_data['userid'], PDO::PARAM_INT);
                    $qry->execute();
                } catch(PDOException $e) {die($e->getMessage().': pwreset_pwc_delete');}

                $error_msg = "Dein Code ist leider am ".date('d.m.y H:i', $usr_data['pwcode_endtime']).
                    " abgelaufen. Benutze die <a href='pwforget'>Passwortvergessen-Funktion</a> erneut.";
            }
            else {
                // Anrede
                if (!empty($usr_data['vorname']))
                    $name = $usr_data['vorname'];
                elseif ($usr_data['username'])
                    $name = $usr_data['username'];
                else
                    $name = $usr_data['email'];
            }
        }

        // Passcode okay, Seite starten
        if ($error_msg === ""):

            // Formularwerte empfangen
            if (isset($_GET['send']) && (strtoupper($_SERVER["REQUEST_METHOD"]) === "POST")) {
                // Eingabewerte auf Plausibilität prüfen
                if (isset($_POST['passwort'], $_POST['passwort2'])) {
                    $input_pwNEU1 = $_POST['passwort'];
                    $input_pw2 = $_POST['passwort2'];
                    $regex_pw = "/^[\w<>()?!,.:_=$%&#+*~^ @€µÄÜÖäüöß]{1,100}$/";  // attention: add a slash at the begin and the end

                    // Passwortlänge prüfen
                    if (strlen($input_pwNEU1) < 4 || strlen($input_pwNEU1) > 50)
                        $error_msg = 'Passwort muss zwischen 4 und 50 Zeichen lang sein!';

                    // Passwort-Zeichen prüfen (nur alphanumerisch + ein paar Sonderzeichen (keine sql kritischen), Länge <100 Zeichen
                    elseif (!preg_match($regex_pw, $input_pwNEU1))
                        $error_msg = 'Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>';

                    // Doublette prüfen
                    elseif ($input_pwNEU1 !== $input_pw2)
                        $error_msg = "Bitte identische Passwörter eingeben";

                    else {}
                } else
                    $error_msg = 'Passwort angeben.';

                // Plausi-Check okay, speichere neues Passwort und lösche den Code
                // TODO: alle Autologins beenden
                if ($error_msg === "") {
                    $passwort_hash = password_hash($input_pwNEU1, PASSWORD_DEFAULT);

                    $stmt = "UPDATE site_users
                        SET pw_hash = :pw_hash, status = 'activated', pwcode_hash = NULL, pwcode_endtime = NULL, pwc = NULL, notiz = NULL
                        WHERE userid = :userid";
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->bindParam(':userid', $usr_data['userid'], PDO::PARAM_INT);
                        $qry->bindParam(':pw_hash', $passwort_hash, PDO::PARAM_STR);
                        $qry->execute();
                    } catch(PDOException $e) {die($e->getMessage().': pwreset_storePW');}

                    $success_msg = "Dein Passwort wurde geändert";
                    $showForm = False;

                }  // Eingabewerte in Datenbank schreiben
            }  // Formularwerte empfangen

        endif;  // Passcode okay, Seite starten


        $showForm = ($error_msg === "") ? True : False;
        $status_message = Tools::status_out($success_msg, $error_msg);


        self::$showForm = $showForm;
        self::$status_message = $status_message;
        self::$success_msg = $success_msg;
        self::$name = $name;
        self::$input_code = $input_code;
        self::$usr_data = $usr_data;
    }


    /****************************
     * Summary of site_output
     */
    public static function site_output()
    {
        $showForm = self::$showForm;
        $status_message = self::$status_message;
        $success_msg = self::$success_msg;
        $name = self::$name;
        $input_code = self::$input_code;
        $usr_data = self::$usr_data;

        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='small-container-500'>";
        $output .= "
            <h2>Neues Passwort vergeben</h2>
            <br>";

        // Seite anzeigen  ... 4-50 Zeichen: alphanumerisch, !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>
        if ($showForm):
        $output .= "

<br><p>Hallo <b>{$name}</b>, du kannst dir hier ein neues Passwort vergeben.</p>

<br><br>
<form action='?send&amp;pwcode=".htmlentities($input_code)."' method='POST'>
    <label for='passwort'>Neues Passwort:</label><br>
    <input type='password' required id='passwort' name='passwort' autocomplete='new-password' placeholder='' class='form-control' spellcheck='false' onfocusin='(this.type=\"text\")' onfocusout='(this.type=\"password\")'><br>

    <label for='passwort2'>Passwort wiederholen:</label><br>
    <input type='password' required id='passwort2' name='passwort2' autocomplete='off' placeholder='' class='form-control' spellcheck='false' onfocusin='(this.type=\"text\")' onfocusout='(this.type=\"password\")'><br>
    <br>
    <input type='submit' value='Passwort speichern' class='btn btn-lg btn-primary btn-block'>
</form>";

// positive Statusausgabe ohne Formular
elseif ($success_msg !== ""):
    $output .= "
<br>
<form action='./login.php?usr=".$usr_data['username']."' method='POST' style='margin-top: 30px;'>
    <button class='btn btn-lg btn-primary btn-block' type='submit'>Anmelden</button>
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