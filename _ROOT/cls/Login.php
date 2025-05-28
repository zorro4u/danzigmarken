<?php
#require_once __DIR__.'/includes/login.inc.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';


class Login
{
    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connect_mariadb();
        }

        self::data_preparation();

        Header::show();
        self::site_output();
        Footer::show("auth");

        // Datenbank schließen
        self::$pdo = Null;
    }


    /***********************
     * Klassenvariablen / Eigenschaften
     */
    public static $pdo;
    private static $showForm;
    private static $root_site;
    private static string $status_message;
    private static string $user_value;
    private static string $input_email1;
    private static string $input_usr;


    private static function data_preparation()
    {
        $pdo = self::$pdo;

        $error_msg = "";
        $success_msg = "";
        $input_usr = "";
        $input_email1 = "";
        $input_pwNEU1 = "";
        $ip = remote_addr();

        if (empty($_SESSION['main'])) $_SESSION['main'] = "/";

        // Herkunftsseite speichern
        $return2 = ['index', 'index2', 'details'];
        if (isset($_SERVER['HTTP_REFERER']) && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']) === false)) {
            // wenn VorgängerSeite bekannt und nicht die aufgerufene Seite selbst ist, speichern
            $referer = str_replace("change", "details", $_SERVER['HTTP_REFERER']);
            $fn_referer = pathinfo($referer)['filename'];
            // wenn Herkunft von den target-Seiten, dann zu diesen, ansonsten Standardseite
            $_SESSION['lastsite'] =  (in_array($fn_referer, $return2))
                ? $referer
                : $_SESSION['main'];
        } elseif (empty($_SERVER['HTTP_REFERER']) && empty($_SESSION['lastsite'])) {
            // wenn nix gesetzt ist, auf Standard index.php verweisen
            $_SESSION['lastsite'] = (!empty($_SESSION['main'])) ? $_SESSION['main'] : "/";
        } else {
            $_SESSION['lastsite'] = $_SESSION['main'];
        }

        unset($return2, $referer, $fn_referer);


        # [$usr_data, $securitytoken_row, $error_msg] = Auth::check_user();
        #var_dump($_SESSION['main']);

        // Nutzer schon angemeldet? Dann weg hier ...
        #Auth::check_user();
        if (Auth::is_checked_in()) {
            #Auth::check_user();
            #if (Auth::is_checked_in())
                $success_msg = "Du bist schon angemeldet. Was machst du dann hier? ...";
        }


        // Loginformular empfangen
        if(isset($_GET['login']) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST") {

            // Eingabewerte auf Plausibilität prüfen
            if(isset($_POST['email'], $_POST['passwort'])) {

                // Eingabeformular hat Daten mit $_POST gesendet
                $input_email1 = htmlspecialchars(clean_input($_POST['email']));
                $input_pwNEU1 = $_POST['passwort'];
                $input_usr = "";

                // Passwortlänge prüfen
                if(strlen($input_pwNEU1) < 4 || strlen($input_pwNEU1) > 50) {
                    $error_msg = 'Passwort muss zwischen 4 und 50 Zeichen lang sein!';
                }
                // Passwort-Zeichen prüfen (nur alphanumerisch + ein paar Sonderzeichen (keine sql kritischen), Länge <100 Zeichen
                $regex = "/^[\w<>()?!,.:_=$%&#+*~^ @€µÄÜÖäüöß]{1,100}$/";  // attention: add a slash at the begin and the end
                if (!preg_match($regex, $input_pwNEU1))
                    $error_msg = 'Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>';

                // Email / Name prüfen
                if (!filter_var($input_email1, FILTER_VALIDATE_EMAIL)) {
                    // keine Email -> Eingabe als Benutzername (nur alphanumerisch, 1-50 Zeichen)
                    (!preg_match("/^\w{1,50}$/", $input_email1))
                        ? $error_msg = '- unzulässige Zeichen im Anmeldenamen -'
                        : $input_usr = strtolower($input_email1);
                }
            }
            if($error_msg) {
                unset($_SESSION['userid']);
                $_SESSION['loggedin'] = False;

            // Plausi-Check okay --> einloggen
            } else {

                // Nutzerdaten in DB finden & holen
                $stmt = "SELECT * FROM site_users WHERE email = :email OR username = :username";
                $data = [':email' => $input_email1, ':username' => $input_usr];
                try {
                    $qry = $pdo->prepare($stmt);
                    #$qry->bindParam(':email', $input_email1, PDO::PARAM_STR);
                    #$qry->bindParam(':username', $input_usr, PDO::PARAM_STR);
                    $qry->execute($data);
                    $usr_data = $qry->fetch(PDO::FETCH_ASSOC);
                } catch(PDOException $e) {die($e->getMessage());}

                // Nutzer gefunden und Passwort korrekt
                if($usr_data !== False) {

                    // Nutzer Status aktiv
                    if ($usr_data['status'] === "activated") {

                        // Passwort Vergleich okay
                        if(password_verify($input_pwNEU1, $usr_data['pw_hash'])) {
                            $userid = $usr_data['userid'];

                            // Passwort neu hashen, wenn Algo nicht übereinstimmen
                            // und in der DB den alten Hash durch den neuen ersetzen
                            if(password_needs_rehash($usr_data['pw_hash'], PASSWORD_DEFAULT)) {
                                $pw_hash = password_hash($input_pwNEU1, PASSWORD_DEFAULT);
                                #$pw_hash = password_hash($input_pw, PASSWORD_BCRYPT, ['cost' => 12]);

                                $stmt = "UPDATE site_users SET pw_hash = :pw_hash, ip = :ip WHERE userid = :userid";
                                #$data = [':pw_hash' => $pw_hash, 'ip' => $ip, 'userid' => $userid];
                                #execute_stmt($stmt, $data);
                                try {
                                    $qry = $pdo->prepare($stmt);
                                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                                    $qry->bindParam(':ip', $ip, PDO::PARAM_STR);
                                    $qry->bindParam(':pw_hash', $pw_hash, PDO::PARAM_STR);
                                    $qry->execute();
                                } catch(PDOException $e) {die($e->getMessage().': login.inc_newpwhash');}
                            }

                            // Nutzer möchte angemeldet bleiben (1 Jahr)
                            if(isset($_POST['angemeldet_bleiben'])) {
                                $identifier = random_string();
                                $token_hash = sha1(random_string());
                                $token_timer = time() + 3600*24*365;  // gültig für 1 Jahr
                                $token_endtime = date('Y-m-d H:i:s', $token_timer);

                                // Autologin: Identifier/Token eintragen
                                $stmt = "INSERT INTO site_login (userid, identifier, token_hash, token_endtime, login, autologin, ip)
                                    VALUES (:userid, :identifier, :token_hash, :token_endtime, 1, 1, :ip)";

                                #$data = [':userid' => $userid, ':identifier' => $identifier, ':token_hash' => $token_hash ':token_endtime' => $token_endtime, 'ip'=>$ip,];
                                #execute_stmt($stmt, $data);  // in DB eintragen

                                try {
                                    $qry = $pdo->prepare($stmt);
                                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                                    $qry->bindParam(':identifier', $identifier, PDO::PARAM_STR);
                                    $qry->bindParam(':token_hash', $token_hash, PDO::PARAM_STR);
                                    $qry->bindParam(':token_endtime', $token_endtime, PDO::PARAM_STR);
                                    $qry->bindParam(':ip', $ip, PDO::PARAM_STR);
                                    $qry->execute();
                                } catch(PDOException $e) {die($e->getMessage().': login.inc_storetoken');}

                                // Cookies setzen --- (name, value, expire, path, domain, ...) siehe neu: cookies vs. localStorage
                                setcookie("login_ID", $identifier, $token_timer, "/", "", 1);
                                setcookie("login_token", $token_hash, $token_timer, "/", "", 1);

                                #session_regenerate_id();
                                $_COOKIE['login_ID'] = $identifier;
                                $_COOKIE['login_token'] = $token_hash;
                                $_SESSION['autologin'] = $identifier;

                                // Cookie Variante, LocalStorage
                                // speichert hier aber nix :-(
                                // erst in Auth::check_user()->refresh_token()
                                /*
                                echo "<script>
                                    localStorage.setItem('login_ID', $identifier);
                                    localStorage.setItem('login_token', $token_hash);
                                </script>"; */

                            // Anmelden ohne Autologin
                            } else {
                                // Nutzereintrag ohne Autologin (identifier) finden
                                // dadurch nur ein db-Eintrag pro Einfach-Login
                                /*
                                $stmt = "SELECT userid FROM site_login WHERE userid=:userid AND identifier IS NULL";
                                try {
                                    $qry = $pdo->prepare($stmt);
                                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                                    $qry->execute();
                                    [$usr_id] = $qry->fetch();
                                } catch(PDOException $e) {die($e->getMessage().': login.inc_finduser');}

                                // Login speichern
                                $stmt = ($usr_id == $userid)
                                    ? "UPDATE site_login SET login=1, ip=:ip WHERE userid=:userid AND identifier IS NULL"
                                    : "INSERT INTO site_login (userid, login, ip) VALUES (:userid, 1, :ip)";
                                */

                                // Login speichern
                                $stmt = "INSERT INTO site_login (userid, login, ip) VALUES (:userid, 1, :ip)";

                                try {
                                    $qry = $pdo->prepare($stmt);
                                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                                    $qry->bindParam(':ip', $ip, PDO::PARAM_STR);
                                    $qry->execute();
                                } catch(PDOException $e) {die($e->getMessage().': login.inc_storelogin');}
                            }
                            /*
                            // zentrale "Login"-Wert setzen
                            $_SESSION['loggedin'] = True;
                            $_SESSION['userid'] = $userid;
                            if ((int)$usr_data['su'] === 1) $_SESSION['su'] = True; else unset($_SESSION['su']);
                            if ($usr_data['status'] === "activated") $_SESSION['status'] = "activ"; else unset($_SESSION['status']);
                            */
                            // Hole Datensatz und logge ein
                            [$usr_data, $autologin, $error_msg] = login($userid);
                            # [$usr_data, $securitytoken_row, $error_msg] = Auth::check_user();  // zentrale Werte-Array setzen

                            $success_msg = "Du bist angemeldet";
                            header("location: {$_SESSION['lastsite']}");
                            exit;

                        } else {
                            $error_msg = "Passwort ist falsch.";
                        }

                    } elseif ($usr_data['status'] === "deaktiv") {
                        $error_msg = "Das Konto existiert nicht.";

                    } else {
                        $error_msg = "Das Konto ist nicht aktiviert.";
                    }

                } elseif ($input_email1 !== "") {
                    $error_msg = "Nutzer ist noch nicht registriert.";
                }
            }  // Plausi-Check okay .. einloggen
        }  // $_REQUEST['login']


        // Wert für das Vorausfüllen des Login-Formulars
        $user_value = "";
        if(isset($_GET['usr']) || $input_usr !== "") {
            isset($_GET['usr'])
                ? $user_value = htmlspecialchars(clean_input($_GET['usr']))
                : $user_value = $input_usr;
        } else {
            if($input_email1 !== "")
                $user_value = $input_email1;
        }


        #self::$root_site = $root_site;
        #$error_msg = (!empty($error_arr))
        #    ? implode("<br>", $error_arr)
        #    : "";
        #self::$showForm = $showForm;
        #self::$showForm = ($error_msg === "") ? True : False;
        self::$showForm = ($success_msg === "") ? True : False;
        self::$status_message = status_out($success_msg, $error_msg);
        self::$user_value = $user_value;
        self::$input_email1 = $input_email1;
        self::$input_usr = $input_usr;
    }



    private static function site_output()
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


        $showForm = self::$showForm;
        #$root_site = self::$root_site;
        $status_message = self::$status_message;
        $user_value = self::$user_value;
        $input_email1 = self::$input_email1;
        $input_usr = self::$input_usr;


        $output = "
            <div class='container small-container-330 form-signin'>
            <h2 class='form-signin-heading'>Anmelden</h2>";

        #$output .= statusmeldung_ausgeben();
        $output .= $status_message;

        // Seite anzeigen
        if ($showForm):

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
            */
            $output .= "
                <form action='?login' method='POST'>
                <br>

                <label for='inputEmail' class='sr-only'>E-Mail</label>
                <input type='text' required id='inputEmail' name='email' autocomplete='email'
                    placeholder='Benutzer / E-Mail' value='".$user_value."'
                    class='form-control'".$af1." />
                <br>

                <label for='inputPassword' class='sr-only'>Passwort</label>
                <input type='password' required id='inputPassword' name='passwort'
                    autocomplete='current-password' placeholder='Passwort' class='form-control'
                    spellcheck='false' onfocusin='(this.type='text')'
                    onfocusout='(this.type='password')'".$af2." />

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
                    $reg_link = "./register-info.php?email=".$input_email1;
                elseif ($input_usr != "")
                    $reg_link = "./register-info.php?usr=".$input_usr;
                else
                    $reg_link = "./register-info";

            $output .= "
                <table style='display: block; width: 100%; margin-top: 20px;'><tr>
                <td width='70%' style='padding-right:20px; '>
                    <a href='".$forget_link."'>Passwort vergessen?</a></td>
                <td width='30%' align=right style='padding-left:20px;'>
                    <a href='".$reg_link."'>Registrieren</a></td>
                </tr></table>
                </form>";

        endif;  // Seite anzeigen
        $output .= "</div>";


        echo $output;
    }
}