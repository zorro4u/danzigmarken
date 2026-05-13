<?php
/* Prozess: RegInfoSeite-->email(Admin)-->email(RegCode)-->dieseSeite:RegSeite-->email(Admin)/email(AktLink)-->ActivateSeite-->Login */

namespace Dzg;

require_once __DIR__.'/../siteform/register.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/**
 * Summary of Class Register
 */
class Register extends RegisterForm
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
     * HTML Seitenausgabe, Registrierung
     */
    private static function show_body(): void
    {
        $msg = self::MSG;
        $usr_data   = self::$usr_data;
        $input_code = self::$input_code;
        $show_form  = self::$show_form;
        $input_usr  = self::$input_usr;
        $error_msg  = self::$error_msg;
        $status_message  = self::$status_message;
        $activate_needed = self::$activate_needed;


        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='main-container registration-form'>";
        $output .= "<h2>{$msg[310]}</h2><p><br></p>";


        // Seite anzeigen
        //
        if ($show_form):

        $pre_user = (!empty($usr_data['username']))
            ? str_replace("_dummy_", "", $usr_data['username'])
            : '';
        $pre_mail = (!empty($usr_data['email']))
            ? str_replace("_dummy_", "", $usr_data['email'])
            : '';

        $output .= <<<EOT

<form action='./register.php?code=$input_code&regon=1' method='POST' style='margin-top: 30px;'>

<div class='form-group'>
    <label for='inputName'>{$msg[311]}: <span style='color:red'>*</span></label>
    <input type='text' required id='inputName' name='username' autocomplete='name' value='$pre_user' size='40' maxlength='250' class='form-control' />
</div>

<div class='form-group'>
    <label for='inputEmail'>{$msg[312]}: <span style='color:red'>*</span></label>
    <input type='email' required id='inputEmail' name='email' autocomplete='email' value='$pre_mail' size='40' maxlength='250' class='form-control' />
</div>

<div class='form-group'>
    <label for='inputPasswort'>{$msg[313]}: <span style='color:red'>*</span></label>
    <input type='password' required id='inputPasswort' name='passwort' autocomplete='new-password' autofocus size='40'  maxlength='250' class='form-control' spellcheck='false' onfocusin='(this.type='text')' onfocusout='(this.type='password')' />
</div>

<div class='form-group'>
    <label for='inputPasswort2'>{$msg[314]}: <span style='color:red'>*</span></label>
    <input type='password' required id='inputPasswort2' name='passwort2' autocomplete='off' size='40' maxlength='250' class='form-control' spellcheck='false' onfocusin='(this.type='text')' onfocusout='(this.type='password')' />
</div>

    <br>
    <button type='submit' class='btn btn-lg btn-primary btn-block'>{$msg[315]}</button>
</form>

<br><br><hr>
<b>{$msg[316]}:</b>
<ul>
    <li><span style='color:red'>*</span> {$msg[317]}.</li>
    <li><span style='text-decoration:underline;'>{$msg[318]}</span>: {$msg[319]}</li>
    <li><span style='text-decoration:underline;'>{$msg[320]}</span>: {$msg[321]}</li><br>
<!--    <li>Du wirst eine Email mit einem Bestätigungs-Link zur Verifizierung erhalten. Danach ist eine Anmeldung möglich.</li> -->
</ul>
EOT;

        // positive Statusausgabe ohne Formularanzeige
        //
        elseif (!$activate_needed && $error_msg === ''):

        $output .= <<<EOT
<br><br><hr><br>
<div><form action='./login.php?usr=$input_usr' method='POST'>
    <button class='btn btn-lg btn-primary btn-block'>{$msg[322]}</button>
</form></div>

<?php else: // bei Fehler oder Mailbestätigung ?>
<div><form action='/index.php' method='POST'>
    <button class='btn btn-lg btn-primary btn-block'>{$msg[323]}</button>
</form></div>
EOT;

        endif;


        $output .= "</div>";
        $output .= "</div>";


        // HTML Ausgabe
        //
        echo $output;
    }
}


// EOF