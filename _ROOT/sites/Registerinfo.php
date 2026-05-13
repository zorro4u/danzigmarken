<?php
/* Prozess: dieseSeite:RegInfo-->email(Admin)-->email(RegCode)-->RegSeite-->email(Admin)/email(AktLink)-->ActivateSeite-->Login */

namespace Dzg;

require_once __DIR__.'/../siteform/registerinfo.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/**
 * Summary of Class RegisterInfo
 */
class RegisterInfo extends RegisterInfoForm
{
    /**
     * zentraler Startpunkt der Seite
     */
    public static function show(): void
    {
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::show_body();
        Footer::show("auth");
    }


    /**
     * HTML Seitenausgabe, Registrierung_Info
     */
    private static function show_body(): void
    {
        $msg = self::MSG;
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $input_message_first = self::$input_message_first;
        $success_msg = self::$success_msg;
        $pre_usr = self::$pre_usr;
        $pre_email = self::$pre_email;


        $output = "<div class='container'>";
        #$output = "<div class='container main-container registration-form'>";
        $output .= $status_message;

        $output .= "<div class='registration-form'>";
        $output .= "<h2>{$msg[310]}</h2><br>";


        // Seite anzeigen
        //
        if ($show_form):
        $output .= "

<p>{$msg[311]}<br>
{$msg[312]}</p>

<form action='?regon' method='POST' style='margin-top: 30px;'>

<div class='form-group'>
    <label for='inputName'>{$msg[313]}:</label>
    <input type='text' id='inputName' name='name' autocomplete='name' value='$pre_usr' class='form-control' size='40' maxlength='50' autofocus />
</div>

<div class='form-group'>
    <label for='inputEmail'>{$msg[314]}: <span style='color:red'>*</span></label>
    <input type='email' required id='inputEmail' name='email' autocomplete='email' value='$pre_email' class='form-control' size='40' maxlength='100' />
</div>

<div class='form-group'>
    <label for='inputMessage'>{$msg[315]}: <span style='color:red'>*</span></label>
    <textarea  required id='inputMessage' name='message' rows='9' style='width:100%;' maxlength='250' spellcheck='true' class='form-control'>$input_message_first</textarea>
</div>

    <br>
    <button type='submit' class='btn btn-lg btn-primary btn-block'>{$msg[316]}</button>
</form>

<br><br><hr>
<b>{$msg[317]}:</b>
<ul>
    <li>{$msg[318]} <span style='color:red'>*</span> {$msg[319]}.</li>
    <li>{$msg[320]}.</li>
</ul>";

        // positive Statusausgabe ohne Formularanzeige
        //
        elseif ($success_msg !== ""):

        $output .= "
<br><br><hr><br>
<div><form action='".$_SESSION['main']."' method='POST'>
    <button class='btn btn-lg btn-primary btn-block' type='submit'>{$msg[321]}</button>
</form></div>";

        endif;  # Seite anzeigen

/*
<!-- mit Scroll-Leiste: <iframe src='./kontakt3/kontakt.php' style='border: none; width:100%; height:700px;'></iframe>-->
<!-- ohne Scroll-Leiste: <iframe src='./kontakt3/kontakt.php' id='idIframe' onload='iframeLoaded()' style='border: none; width:100%;' allowfullscreenscrolling='no'> </iframe>-->
<!--<iframe src='./kontakt1/kontakt.temp.php' id='idIframe' onload='iframeLoaded()' style='border: none; width:100%;' allowfullscreenscrolling='no'> </iframe>-->

<!-- <?php #include_once "./kontakt1/kontakt.temp.php"; ?> -->
*/

        $output .= "</div>";
        $output .= "</div>";


        // HTML Ausgabe
        //
        echo $output;
    }
}


// EOF