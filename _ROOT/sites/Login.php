<?php
namespace Dzg\Sites;
use Dzg\SiteForm\Login as Pre;
use Dzg\SitePrep\{Header, Footer};

require_once __DIR__.'/../siteform/login.php';
require_once __DIR__.'/../siteprep/loader_default.php';


class Login extends Pre
{
    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        self::siteEntryCheck();
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::view();
        Footer::show("auth");
    }


    private static function view()
    {
        /**
         * Passwort-Eingabe sichtbar machen,
         */

        /*
        <input id="check" type="checkbox" >
        <input id="pw" type="password" >
        <script>
            var check = document.getElementById("check"),
                pw = document.getElementById("pw");
            check.onclick = function() {
                pw.type = this.checked ? "text" : "password";
            };
        </script>

        function machText(chk,frm){
        var p=frm.newpass;
        var p2=frm.newpass2;
        try{
        var val=p.value;
        var val=p2.value;
        p.type=chk?'text':'password';
        p2.type=chk?'text':'password';
        p.value=val;//benötigt z. B. in Opera
        p2.value=val;//benötigt z. B. in Opera
        }
        catch(e){
        var neuInp=document.createElement('input');
        var neuInp2=document.createElement('input');
        neuInp.type=chk?'text':'password';
        neuInp2.type=chk?'text':'password';
        neuInp.value=p.value;
        neuInp2.value=p2.value;
        neuInp.name=neuInp.id='newpass';
        neuInp2.name=neuInp2.id='newpass';
        p.parentNode.replaceChild(neuInp,p);
        p2.parentNode.replaceChild(neuInp2,p2);
        }
        }

        <input type="checkbox" onclick="machText(this.checked,this.form)">

        */


        $status_message = self::$status_message;
        $user_value   = self::$user_value;
        $input_email1 = self::$input_email1;
        $input_usr    = self::$input_usr;
        $show_form = self::$show_form;
        $cookie    = self::$cookie;


        $output = "
            <div class='container small-container-330 form-signin'>
            <h2 class='form-signin-heading'>Anmelden</h2>";

        $output .= $status_message;
        if (!empty($cookie)) $output .= $cookie;

        // Seite anzeigen
        if ($show_form):

            if ($user_value != "") {
                $af1 = "";
                $af2 = "autofocus";
            } else {
                $af1 = "autofocus";
                $af2 = "";
            }
            /*
            action="test_form.php"
            test_form.php/%22%3E%3Cscript%3Ealert('hacked')%3C/script%3E
            action="test_form.php/"><script>alert('hacked')</script>

            onfocusin=\"(this.type='text')\" onfocusout=\"(this.type='password')\"

            <div style="position: absolute; display: flex; align-items: flex-start; width: 0px; height: 0px; border: none; padding: 0px; margin: 0px; background: no-repeat; visibility: visible; user-select: none; pointer-events: none; z-index: 3; opacity: 1;">
            <img title="Sticky Password" src="" style="position: relative; border: none; display: inline; cursor: default; padding: 0px; margin: 0px; pointer-events: auto; left: 267.2px; top: 6.8px; width: 20px; height: 20px; min-width: 20px; max-width: 20px; min-height: 20px; max-height: 20px;">
            </div>

            $output .= "
                <input id='pwd' type='password' name='passwort' placeholder='Passwort'
                    class='form-control' autocomplete='current-password' spellcheck='false'
                    required {$af2} />
                <label for='pwd' class='sr-only'>Passwort</label>
                <input id='toggle_pwd' type='checkbox' onclick=\"pwd.type = this.checked ? 'text' : 'password'\" />";

            <input type="password" name="" id="password" required="" />
            <label>Password</label>
            <span class="password-toggle-icon"><i class="fas fa-eye"></i></span>

            */

            $output .= "
                <form action='?login' method='POST'>
                <br>
                <label for='inputEmail' class='sr-only'>E-Mail</label>
                <input id='inputEmail' type='text' name='email' value='{$user_value}'
                    placeholder='Benutzer / E-Mail'
                    class='form-control' autocomplete='email' required {$af1} />
                <br>";


            $output .= "
                <div class='user-box'>
                <input id='pwd' type='password' name='passwort' placeholder='Passwort'
                    class='form-control' autocomplete='current-password' spellcheck='false'
                    required {$af2} />
                <label for='pwd' class='sr-only'>Passwort</label>
                <span class='password-toggle-icon'><i class='fas fa-eye'></i></span>
                </div>";


            $output .= "
                <div class='checkbox' style='padding-top: 15px;'>
                <label>
                <input type='checkbox' name='angemeldet_bleiben' value='1'
                    autocomplete='off' checked /> Angemeldet bleiben
                </label>
                </div>

                <button class='btn btn-lg btn-primary btn-block'
                    style='margin-top: 20px;' type='submit'>Login</button>";

                $forget_link = ($input_email1 != "" && $input_usr === "")
                    ? "./pwforget.php?email=".$input_email1
                    : "./pwforget";
                    #: $_SESSION['rootdir']."/auth/pwforget.php";

                if ($input_email1 != "" && $input_usr === "")
                    $reg_link = "./registerinfo.php?email=".$input_email1;
                elseif ($input_usr != "")
                    $reg_link = "./registerinfo.php?usr=".$input_usr;
                else
                    $reg_link = "./registerinfo";

            $output .= "
                <table style='display: block; width: 100%; margin-top: 20px;'><tr>
                <td width='70%' style='padding-right:20px; '>
                    <a href='{$forget_link}'>Passwort vergessen?</a></td>
                <td width='30%' align=right style='padding-left:20px;'>
                    <a href='{$reg_link}'>Registrieren</a></td>
                </tr></table>
                </form>";

        endif;  // Seite anzeigen
        $output .= "</div>";


        $output .= "
        <script>
        const passwordField = document.getElementById('pwd');
        const togglePassword = document.querySelector('.password-toggle-icon i');

        togglePassword.addEventListener('click', function () {
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            togglePassword.classList.remove('fa-eye');
            togglePassword.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            togglePassword.classList.remove('fa-eye-slash');
            togglePassword.classList.add('fa-eye');
        }
        });
        </script>
        ";

        echo $output;
    }
}