<?php
/* Prozess: dieseSeite:RegInfo-->email(Admin)-->email(RegCode)-->RegSeite-->email(Admin)/email(AktLink)-->ActivateSeite-->Login */

namespace Dzg\Sites;
use Dzg\SiteForm\RegisterInfo as Init;
use Dzg\SitePrep\{Header, Footer};

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__.'/../siteform/registerinfo.php';
require_once __DIR__.'/../siteprep/loader_default.php';


#header('Content-type: text/html; charset=utf-8');

/***********************
 * Summary of Register_info
 */
class RegisterInfo extends Init
{
    /****************************
     * Summary of show
     */
    public static function show()
    {
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::siteOutput();
        Footer::show("auth");
    }


    /****************************
     * Summary of siteOutput
     */
    public static function siteOutput()
    {
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
        $output .= "
            <h2>Registrierung</h2>
            <br>";

        // Seite anzeigen
        if ($show_form):
        $output .= "

<p>Du interessierst dich für diese Seiten und willst erweiterten Zugriff auf den Inhalt haben? <br>
Informiere mich kurz darüber und du erhälst Zugang via deiner Email-Adresse.</p>

<form action='?regon' method='POST' style='margin-top: 30px;'>

<div class='form-group'>
    <label for='inputName'>Dein Name:</label>
    <input type='text' id='inputName' name='name' autocomplete='name' value='{$pre_usr}' class='form-control' size='40' maxlength='50' autofocus>
</div>

<div class='form-group'>
    <label for='inputEmail'>Deine E-Mail: <span style='color:red'>*</span></label>
    <input type='email' required id='inputEmail' name='email' autocomplete='email' value='{$pre_email}' class='form-control' size='40' maxlength='100'>
</div>

<div class='form-group'>
    <label for='inputMessage'>Deine Nachricht: <span style='color:red'>*</span></label>
    <textarea  required id='inputMessage' name='message' rows='9' style='width:100%;' maxlength='250' spellcheck='true' class='form-control'>{$input_message_first}</textarea>
</div>

    <br>
    <button type='submit' class='btn btn-lg btn-primary btn-block'>Anfrage senden</button>
</form>

<br><br><hr>
<b>Hinweise:</b>
<ul>
    <li>Alle <span style='color:red'>*</span> Felder bitte ausfüllen.</li>
    <li>Du wirst als nächstes eine Email mit deinem Registrierungs-Link erhalten.</li>
</ul>";

// positive Statusausgabe ohne Formular
elseif ($success_msg !== ""):
    $output .= "
<br><br><hr><br>
<div><form action='".$_SESSION['main']."' method='POST'>
    <button class='btn btn-lg btn-primary btn-block' type='submit'>Startseite</button>
</form></div>";

endif;  // Seite anzeigen

/*
<!-- mit Scroll-Leiste: <iframe src='./kontakt3/kontakt.php' style='border: none; width:100%; height:700px;'></iframe>-->
<!-- ohne Scroll-Leiste: <iframe src='./kontakt3/kontakt.php' id='idIframe' onload='iframeLoaded()' style='border: none; width:100%;' allowfullscreenscrolling='no'> </iframe>-->
<!--<iframe src='./kontakt1/kontakt.temp.php' id='idIframe' onload='iframeLoaded()' style='border: none; width:100%;' allowfullscreenscrolling='no'> </iframe>-->

<!-- <?php #include_once "./kontakt1/kontakt.temp.php"; ?> -->
*/

        $output .= "</div>";
        $output .= "</div>";



        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }
}
