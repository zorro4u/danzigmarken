<?php
/* Prozess: dieseSeite:Forget-->email(Admin)/email(Code)-->ResetSeite-->Login */

namespace Dzg\Sites;
use Dzg\SitePrep\PWforget as Pre;
use Dzg\SitePrep\{Header, Footer};

require_once __DIR__.'/../siteprep/pwforget.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/***********************
 * Summary of Pw_forget
 */
class PWforget extends Pre
{
    /****************************
     * Summary of show
     */
    public static function show()
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


        // HTML Ausgabe
        //
        echo $output;
    }
}


// EOF