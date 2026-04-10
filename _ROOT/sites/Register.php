<?php
/* Prozess: RegInfoSeite-->email(Admin)-->email(RegCode)-->dieseSeite:RegSeite-->email(Admin)/email(AktLink)-->ActivateSeite-->Login */

namespace Dzg\Sites;
use Dzg\SiteForm\Register as Prep;
use Dzg\SitePrep\{Header, Footer};

session_start();
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__.'/../siteform/register.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/***********************
 * Summary of Register
 */
class Register extends Prep
{
    /****************************
     * Summary of show
     */
    public static function show()
    {
        self::dataPreparation();

        Header::show();
        self::siteOutput();
        Footer::show("auth");
    }


    /****************************
     * Summary of siteOutput
     */
    private static function siteOutput()
    {
        $show_form = self::$show_form;
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
        if ($show_form):

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
elseif (!$activate_needed && $error_msg === ''):  # positive Statusausgabe ohne Formular
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