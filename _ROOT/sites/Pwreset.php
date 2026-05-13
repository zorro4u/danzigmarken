<?php
/* Prozess: ForgetSeite-->email(Admin)/email(Code)-->dieseSeite:ResetSeite-->Login */

namespace Dzg;

require_once __DIR__.'/../siteprep/pwreset.php';
require_once __DIR__.'/../siteprep/loader_default.php';


// TODO: alle Autologins beenden

/**
 * Summary of Class PWreset
 */
class PWreset extends PWresetPrep
{
    public static function show(): void
    {
        self::dataPreparation();

        Header::show();
        self::show_body();
        Footer::show("auth");
    }


    /**
     * HTML Ausgabe
     */
    private static function show_body(): void
    {
        $msg = self::MSG;
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $success_msg = self::$success_msg;
        $name = self::$name;
        $input_code = self::$input_code;
        $usr_data = self::$usr_data;

        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='small-container-500'>";
        $output .= "<h2>{$msg[310]}</h2><br>";

        // Seite anzeigen  ... 4-50 Zeichen: alphanumerisch, !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>
        if ($show_form):
        $output .= "

<br><p>{$msg[311]} <b>{$name}</b>, {$msg[311]}.</p>

<br><br>
<form action='?send&amp;pwcode=".htmlentities($input_code)."' method='POST'>
    <label for='passwort'>{$msg[312]}:</label><br>
    <input type='password' required id='passwort' name='passwort' autocomplete='new-password' placeholder='' class='form-control' spellcheck='false' onfocusin='(this.type=\"text\")' onfocusout='(this.type=\"password\")' /><br>

    <label for='passwort2'>{$msg[313]}:</label><br>
    <input type='password' required id='passwort2' name='passwort2' autocomplete='off' placeholder='' class='form-control' spellcheck='false' onfocusin='(this.type=\"text\")' onfocusout='(this.type=\"password\")' /><br>
    <br>
    <input type='submit' value='{$msg[314]}' class='btn btn-lg btn-primary btn-block' />
</form>";

// positive Statusausgabe ohne Formular
elseif ($success_msg !== ""):
    $output .= "
<br>
<form action='./login.php?usr=".$usr_data['username']."' method='POST' style='margin-top: 30px;'>
    <button class='btn btn-lg btn-primary btn-block' type='submit'>{$msg[315]}</button>
</form>";

endif;


        $output .= "</div>";
        $output .= "</div>";


        // HTML Ausgabe
        //
        echo $output;
    }
}


// EOF