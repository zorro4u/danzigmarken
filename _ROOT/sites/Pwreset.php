<?php
/* Prozess: ForgetSeite-->email(Admin)/email(Code)-->dieseSeite:ResetSeite-->Login */

namespace Dzg\Sites;
use Dzg\SitePrep\PWreset as Pre;
use Dzg\SitePrep\{Header, Footer};

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);
session_start();

require_once __DIR__.'/../siteprep/pwreset.php';
require_once __DIR__.'/../siteprep/loader_default.php';


// TODO: alle Autologins beenden

/***********************
 * Summary of Pw_reset
 */
class PWreset extends Pre
{
    /****************************
     * Summary of show
     */
    public static function show()
    {
        self::dataPreparation();

        Header::show();
        self::view();
        Footer::show("auth");
    }


    /****************************
     * Summary of view
     */
    private static function view()
    {
        $show_form = self::$show_form;
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
        if ($show_form):
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