<?php
namespace Dzg\Sites;
use Dzg\SiteForm\Settings as Pre;
use Dzg\SitePrep\{Header, Footer};

require_once __DIR__.'/../siteform/settings.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/**
 * Summary of Class Settings
 */
class Settings extends Pre
{
    public static function show(): void
    {
        self::siteEntryCheck();
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::show_body();
        Footer::show("account");
    }


    /**
     * HTML Ausgabe
     */
    private static function show_body(): void
    {
        $show_form = self::$show_form;
        $status_message = self::$status_message;

        $output = "<div class='container main-container'>";

        if (!$show_form):
            $output .= $status_message;

        # Seiten-Check okay, Seite anzeigen
        else:
            $output .= "<h1>Einstellungen</h1>";
            $output .= $status_message;

            $output .= "<div> ";    // START
            $output .= self::tab_selection();

            $output .= "<div class='tab-content'>";
            $output .= self::tab_user();
            $output .= self::tab_pwd();
            $output .= self::tab_data();
            $output .= self::tab_login();
            $output .= self::tab_account();
            $output .= self::tab_download();

            $output .= "</div> ";   // tab-content
            $output .= "</div> ";   // START

        endif;                      // Seite anzeigen
        $output .= "</div> ";       // container.main-container
        $output .= self::script_show_pw();

        // HTML Ausgabe
        //
        echo $output;
    }



    private static function tab_selection(): string
    {
        $active = self::$active;

        $output = <<<EOT
        <ul class='nav nav-tabs' role='tablist'>
        <li role='presentation' class='{$active['email']}'>
        <a href='#email' aria-controls='profile' role='tab' data-toggle='tab'>
        Anmeldedaten</a></li>

        <li role='presentation' class='{$active['passwort']}'>
        <a href='#passwort' aria-controls='messages' role='tab' data-toggle='tab'>
        Passwort</a></li>

        <li role='presentation' class='{$active['data']}'>
        <a href='#data' aria-controls='home' role='tab' data-toggle='tab'>
        Persönliche Daten</a></li>

        <li role='presentation' class='{$active['autologin']}'>
        <a href='#autologin' aria-controls='autologin' role='tab' data-toggle='tab'>
        Autologin</a></li>

        <li role='presentation' class='{$active['delete']}'>
        <a href='#delete' aria-controls='delete' role='tab' data-toggle='tab'>
        Konto löschen</a></li>
        EOT;

        # wenn angemeldet als 'heinz' oder 'admin'
        if ($_SESSION['userid'] === 3333
            || $_SESSION['su'] === 1111):

        $output .= <<<EOT
        <li role='presentation' class='{$active['download']}'>
        <a href='#download' aria-controls='download' role='tab' data-toggle='tab'>
        Download</a></li>
        EOT;

        endif;

        $output .= "</ul>";

        return $output;
    }


    /**
     * Änderung der E-Mail-Adresse / Benutzername
     * @return string
     */
    private static function tab_user(): string
    {
        $active = self::$active;
        $usr_data = self::$usr_data;
        $name = htmlentities($usr_data['username']);
        $mail = htmlentities($usr_data['email']);

        return <<<EOT
        <div role='tabpanel' class='tab-pane {$active['email']}' id='email'>
        <div class='col-sm-10'>

        <br>
        <p>Zum Ändern deiner Daten gib bitte zur Bestätigung dein aktuelles Passwort ein.</p>

        <form action='?save=email&tab=email' method='POST' class='form-horizontal'>

        <div class='form-group'>
        <label for='inputPW0' class='col-sm-2 control-label'>Passwort</label>
        <div class='col-sm-10 user-box'>
        <input id='inputPW0' type='password' name='passwort'
            class='form-control' autocomplete='current-password'
            spellcheck='false' required />

        <span class='password-toggle-icon' style='right:25px'>
            <i id='pw0' class='fas fa-eye'></i></span>
        </div></div>

        <br>
        <div class='form-group'>
        <label for='inputUser' class='col-sm-2 control-label'>Benutzername</label>
        <div class='col-sm-10'>
        <input class='form-control' id='inputUser' name='username'
            type='text' value='{$name}' autocomplete='off' />
        </div></div>

        <div class='form-group'>
        <label for='inputEmail' class='col-sm-2 control-label'>E-Mail</label>
        <div class='col-sm-10'>
        <input class='form-control' id='inputEmail' name='email'
            type='email' value='{$mail}' autocomplete='off' />
        </div></div>

        <div class='form-group'>
        <label for='inputEmail2' class='col-sm-2 control-label'>E-Mail (wiederholen)</label>
        <div class='col-sm-10'>
        <input class='form-control' id='inputEmail2' name='email2'
            type='email' autocomplete='off' />
        </div></div>

        <br>
        <div class='form-group'>
        <div class='col-sm-offset-2 col-sm-10'>
        <button type='submit' class='btn btn-primary'>Speichern</button>
        </div></div>

        </form></div></div>
        EOT;
    }


    /**
     * Änderung des Passworts
     * @return string
     */
    private static function tab_pwd(): string
    {
        $active = self::$active;
        # placeholder='Buchstaben/Zahlen !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>

        return <<<EOT
        <div role='tabpanel' class='tab-pane {$active['passwort']}' id='passwort'>
        <div class='col-sm-10'>

        <br>
        <p>Zum Änderen deines Passworts gib bitte zur Bestätigung dein aktuelles Passwort ein.</p>

        <form action='?save=passwort&tab=passwort' method='POST' class='form-horizontal'>

        <div class='form-group'>
        <label for='inputPW1' class='col-sm-2 control-label'>Aktuelles Passwort</label>
        <div class='col-sm-10 user-box'>
        <input id='inputPW1' type='password' name='passwortAlt'
            class='form-control' autocomplete='current-password'
            spellcheck='false' required />

        <span class='password-toggle-icon' style='right:25px'>
            <i id='pw1' class='fas fa-eye'></i></span>
        </div></div>

        <br>
        <div class='form-group'>
        <label for='inputPW2' class='col-sm-2 control-label'>Neues Passwort</label>
        <div class='col-sm-10 user-box'>
        <input id='inputPW2' type='password' name='passwortNeu'
            class='form-control' autocomplete='off' spellcheck='false' required />

        <span class='password-toggle-icon' style='right:25px'>
            <i id='pw2' class='fas fa-eye'></i></span>
        </div></div>

        <div class='form-group'>
        <label for='inputPasswortNeu2' class='col-sm-2 control-label'>Neues Passwort (wiederholen)</label>
        <div class='col-sm-10'>
        <input id='inputPasswortNeu2' type='password' name='passwortNeu2'
            class='form-control' autocomplete='off' spellcheck='false' required />

        <span class='password-toggle-icon' style='right:25px'>
            <i id='pw3' class='fas fa-eye'></i></span>
        </div></div>

        <br>
        <div class='form-group'>
        <div class='col-sm-offset-2 col-sm-10'>
        <button type='submit' class='btn btn-primary'>Speichern</button>
        </div></div>

        </form></div></div>
        EOT;
    }


    /**
     * Persönliche Daten
     * @return string
     */
    private static function tab_data(): string
    {
        $active = self::$active;
        $usr_data = self::$usr_data;
        $vor  = htmlentities($usr_data['vorname']);
        $nach = htmlentities($usr_data['nachname']);

        return <<<EOT
        <div role='tabpanel' class='tab-pane {$active['data']}' id='data'>
        <div class='col-sm-10'>

        <form action='?save=data&tab=data' method='POST' class='form-horizontal'>

        <br><br>
        <div class='form-group'>
        <label for='inputVorname' class='col-sm-2 control-label'>Vorname</label>
        <div class='col-sm-10'>
        <input class='form-control' id='inputVorname' name='vorname'
            type='text' value='{$vor}' autocomplete='off' />
        </div></div>

        <div class='form-group'>
        <label for='inputNachname' class='col-sm-2 control-label'>Nachname</label>
        <div class='col-sm-10'>
        <input class='form-control' id='inputNachname' name='nachname'
            type='text' value='{$nach}' autocomplete='off' />
        </div></div>

        <br>
        <div class='form-group'>
        <div class='col-sm-offset-2 col-sm-10'>
        <button type='submit' class='btn btn-primary'>Speichern</button>
        </div></div>

        </form></div></div>
        EOT;
    }



    /**
     * Autologin
     * @return string
     */
    private static function tab_login(): string
    {
        $active = self::$active;
        $usr_data = self::$usr_data;

        $output = <<<EOT
        <div role='tabpanel' class='tab-pane {$active['autologin']}' id='autologin'>
        <div class='col-sm-10'>
        <br>
        EOT;

        if ($usr_data['count3']):

        $output .= <<<EOT
        <p>Alle meine anderen Anmeldungen geräteübergreifend beenden ({$usr_data['count3']}x)</p>

        <div class='col-sm-offset-2 col-sm-10'>
        <div><br>
        <form action='?save=autologin&tab=autologin' method='POST'>
        <button class='btn btn-primary' type='submit'>Beenden</button>
        </form>
        </div></div>
        EOT;

        else:
            $output .= "<p>Keine Autologins vorhanden.</p>";
        endif;

        $output .= "</div></div>";

        return $output;
    }


    /**
     * Konto löschen
     * @return string
     */
    private static function tab_account(): string
    {
        $active = self::$active;

        return <<<EOT
        <div role='tabpanel' class='tab-pane {$active['delete']}' id='delete'>
        <div class='col-sm-10'>

        <br><br>
        <p>Zum Löschen deines Kontos gib bitte zur Bestätigung dein aktuelles Passwort ein.</p><br>

        <form action='?save=delete&tab=delete' method='POST' class='form-horizontal'>
        <div class='form-group'>
        <div class='col-sm-10 user-box'>

        <input id='inputPW3' type='password' name='pw_delete' placeholder='Passwort'
            class='form-control'  style='width:auto;' autocomplete='current-password'
            spellcheck='false' required />
        <label for='inputPW3' class='col-sm-2 control-label sr-only'>Passwort</label>

        <span class='password-toggle-icon' style='left:190px;right:unset'><i id='pw4' class='fas fa-eye'></i></span>
        </div></div>

        <br><br>

        <div class='form-group'>
        <div class='col-sm-offset-2 col-sm-10'>

        <button type='submit' class='btn btn-primary btn-lg' onclick='return confirm('Wirklich das Konto  - L Ö S C H E N -  ?')'>mein Konto löschen</button>

        </div></div>
        </form>

        </div></div>
        EOT;
    }



    /**
     * Downloads
     * @return string
     */
    private static function tab_download(): string
    {
        $active = self::$active;

        $file = "/download/dzg_90.pdf";     #  ./../test/pdf_down.php
        $ffn = $_SERVER["DOCUMENT_ROOT"].$file;
        $size = (file_exists($ffn))         # is_file()
            ? filesize($ffn)
            : 0;
        $filesize = self::humanFileSize($size, 0);

        $output = <<<EOT
        <div role='tabpanel' class='tab-pane {$active['download']}' id='download'>
        <div class='col-sm-10'><br>
        <p>Datenbank-Auszug als PDF-Datei ({$filesize}) ... </p>
        <div class='col-sm-offset-2 col-sm-10'>
        <div><br>

        <form method='POST'>
        <button formaction='{$file}' class='btn btn-primary' type='submit'>
        anzeigen</button>&emsp;&emsp;&emsp;

        <button formaction='/test/pdf_down'class='btn btn-primary' type='submit'>
        downloaden</button>
        </form>
        </div></div></div></div>
        EOT;

        return $output;
    }


    private static function humanFileSize(int $bytes, int $decimals = 2): string
    {
        $factor = floor((strlen($bytes) - 1) / 3);
        #$sz = "BKMGTP";
        #return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$sz[$factor];

        $sz = ($factor > 0) ? "KMGT" : "";
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor))." ".@$sz[(int)$factor - 1]."B";
    }


    /**
     * Passwort-Anzeige
     * @return string
     */
    private static function script_show_pw(): string
    {
        return <<<EOT
        <script>
        // const togglePassword = document.querySelector('.password-toggle-icon i');

        const togglePassword0 = document.getElementById('pw0');
        const togglePassword1 = document.getElementById('pw1');
        const togglePassword2 = document.getElementById('pw2');
        const togglePassword3 = document.getElementById('pw3');
        const togglePassword4 = document.getElementById('pw4');

        const passwordField0 = document.getElementById('inputPW0');
        const passwordField1 = document.getElementById('inputPW1');
        const passwordField2 = document.getElementById('inputPW2');
        const passwordField3 = document.getElementById('inputPasswortNeu2');
        const passwordField4 = document.getElementById('inputPW3');

        togglePassword0.addEventListener('click', function () {
        if (passwordField0.type === 'password') {
            passwordField0.type = 'text';
            togglePassword0.classList.remove('fa-eye');
            togglePassword0.classList.add('fa-eye-slash');

        } else {
            passwordField0.type = 'password';
            togglePassword0.classList.remove('fa-eye-slash');
            togglePassword0.classList.add('fa-eye');
        }
        });

        togglePassword1.addEventListener('click', function () {
        if (passwordField1.type === 'password') {
            passwordField1.type = 'text';
            togglePassword1.classList.remove('fa-eye');
            togglePassword1.classList.add('fa-eye-slash');

        } else {
            passwordField1.type = 'password';
            togglePassword1.classList.remove('fa-eye-slash');
            togglePassword1.classList.add('fa-eye');
        }
        });

        togglePassword2.addEventListener('click', function () {
        if (passwordField2.type === 'password') {
            passwordField2.type = 'text';
            togglePassword2.classList.remove('fa-eye');
            togglePassword2.classList.add('fa-eye-slash');

        } else {
            passwordField2.type = 'password';
            togglePassword2.classList.remove('fa-eye-slash');
            togglePassword2.classList.add('fa-eye');
        }
        });

        togglePassword3.addEventListener('click', function () {
        if (passwordField3.type === 'password') {
            passwordField3.type = 'text';
            togglePassword3.classList.remove('fa-eye');
            togglePassword3.classList.add('fa-eye-slash');

        } else {
            passwordField3.type = 'password';
            togglePassword3.classList.remove('fa-eye-slash');
            togglePassword3.classList.add('fa-eye');
        }
        });

        togglePassword4.addEventListener('click', function () {
        if (passwordField4.type === 'password') {
            passwordField4.type = 'text';
            togglePassword4.classList.remove('fa-eye');
            togglePassword4.classList.add('fa-eye-slash');

        } else {
            passwordField4.type = 'password';
            togglePassword4.classList.remove('fa-eye-slash');
            togglePassword4.classList.add('fa-eye');
        }
        });

        </script>
        EOT;
    }
}


// EOF