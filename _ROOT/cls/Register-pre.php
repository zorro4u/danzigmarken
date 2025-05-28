<?php
session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Auth.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Footer.php';


/* Prozess: admin:-->dieseSeite:RegPre (statt:RegInfo) [-->(RegCode)-->RegSeite-->email(Admin)/email(AktLink)-->ActivateSeite-->Login] */


Register_pre::show();


/***********************
 * Summary of Register_pre
 */
class Register_pre
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private static $pdo;
    private static $showForm;
    private static string $status_message;


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


        // Nutzer nicht angemeldet? Dann weg hier ...
        Auth::check_user();
        if (!Auth::is_checked_in()) {
            Auth::check_user();
            if (!Auth::is_checked_in()) {
                header("location: login.php");
                exit;
            }
        }

        // Nutzer kein Admin? Dann auch weg hier ...
        if ((int)$_SESSION['su'] !== 1) {
            header("location: ../account/settings.php");
            exit;
        }


        // Soll dasRegistrierungsformular angezeigt werden?
        $showForm = True;
        $error_msg = "";
        $success_msg = "";

        if(isset($_REQUEST['reg'])):

            // Code für Zugang zur Registrierungsseite
            $reg_code = uniqid();
            $pwcode_endtime = time() + 3600*24*30;  // 4 Woche gültig

            $input_usr = $reg_code;
            $input_email = $reg_code."@dummy.de";

            // Links für Email-Versand erzeugen
            $reg_url = getSiteURL().'register.php?code='.$reg_code;
            $reg_link = 'register.php?code='.$reg_code;     // intern

            $status = $reg_code;
            $notiz = $reg_url;

            $stmt = "INSERT
                INTO site_users (username, email, status, pwcode_endtime, notiz)
                VALUES (:username, :email, :status, :pwcode_endtime, :notiz)";
            $data = [
                ':username' => $input_usr, ':email' => $input_email,
                ':status' => $status, ':pwcode_endtime' => $pwcode_endtime, ':notiz' => $notiz];
            try {
                $qry = $pdo->prepare($stmt);
                $qry->execute($data);
            } catch(PDOException $e) {die($e->getMessage().': register-pre.inc');}

            // Email-Versand vorbereiten ...
            $success_msg = 'Registrierungs-Link für www.danzigmarken.de <hr>'.
                'Hallo, <br>du kannst dich jetzt auf <b>www.danzigmarken.de</b> registrieren. '.
                'Rufe dazu in den nächsten 4 Wochen (bis zum <b>'.date('d.m.y', $pwcode_endtime).'</b>) '.
                'den folgenden Link auf: <br><br>'.
                '<a href="'.$reg_url.'" target="_blank">'.$reg_url.'</a><br><br>'.
                'Herzliche Grüße';

            $showForm = False;

        endif;  // request

        $showForm = ($showForm === True && $_SESSION['su'] === True) ? True : False;
        #$showForm = ($error_msg === "") ? True : False;
        $status_message = status_out($success_msg, $error_msg);


        self::$showForm = $showForm;
        self::$status_message = $status_message;

    }


    /****************************
     * Summary of site_output
     */
    public static function site_output()
    {
        $showForm = self::$showForm;
        $status_message = self::$status_message;

        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='registration-form'>";
        $output .= "
            <h2>Registrierung</h2>
            <p>Link zur Registrierung für einen neuen Benutzer erzeugen.<br><br></p>";

        // Seite anzeigen
        if ($showForm):
            $output .= "
            <form action='?reg' method='post' style='margin-top: 30px;'>
            <button type='submit' class='btn btn-lg btn-primary btn-block'>Link erzeugen</button>
            </form>";

        else:  // nach Statusausgabe Linie ziehen
            $output .= "
            <br><br><hr><br>
            <!--
            <div><form><button class='btn btn-lg btn-primary btn-block' type='submit'  formaction='?reg=1'>neuen Link erzeugen</button></form></div>
            -->";

        endif;


        $output .= "</div>";
        $output .= "</div>";


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }
}