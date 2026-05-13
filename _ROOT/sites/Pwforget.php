<?php
/* Prozess: dieseSeite:Forget-->email(Admin)/email(Code)-->ResetSeite-->Login */

namespace Dzg;

require_once __DIR__.'/../siteprep/pwforget.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/**
 * Summary of Class PWforget
 */
class PWforget extends PWforgetPrep
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
        $pre_email = self::$pre_email;

        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='small-container-330'>";
        $output .= "<h2>{$msg[310]}</h2><br>";

        // Seite anzeigen
        if ($show_form):
        $output .= "

<p>{$msg[311]}</p>

<form action='?send' method='POST'>
<label for='inputEmail'></label>
<input type='email' required id='inputEmail' name='email' autocomplete='email' placeholder='{$msg[312]}' value='{$pre_email}' class='form-control' />
<br><br>
<input  class='btn btn-lg btn-primary btn-block' type='submit' value='{$msg[313]}' autocomplete='off' />
</form>";

// positive Statusausgabe ohne Formular
elseif ($success_msg !== ""):
    $output .= "
<br><form action='".$_SESSION['main']."' method='POST'>
    <button class='btn btn-lg btn-primary btn-block' type='submit'>{$msg[314]}</button>
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