<?php
namespace Dzg\Cls;

// Datenbank- & Auth-Funktionen laden
#require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';
use Dzg\Cls\{Database, Auth, Tools, Header, Footer};
use PDO, PDOException, Exception;


/****************************
 * Summary of Settings
 */
class Settings
{
    /****************************
     * Klassenvariablen / Eigenschaften
     */
    public static $pdo;
    public static $active;
    public static $userid;
    public static $usr_data;
    protected static $userliste;
    protected static $identifier;
    protected static $error_msg;
    protected static $showForm;
    protected static $status_message;


    /****************************
     * Summary of show
     */
    public static function show()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connect_mariadb();
        }

        self::site_entry_check();
        self::data_preparation();
        self::form_evaluation();

        Header::show();
        self::site_output();
        Footer::show("account");

        // Datenbank schließen
        self::$pdo = Null;
    }


    /****************************
     * Summary of site_entry_check
     * CheckIn-Test
     * Plausi-Test: userid, identifier, token_hash
     * set identifier
     * set last_site
     * set showForm
     */
    public static function site_entry_check()
    {
        if (empty($_SESSION['main']))
            $_SESSION['main'] = "/";

        $return2 = ["index", "index2", "details"];
        Tools::lastsite($return2);

        [$usr_data, $securitytoken_row, $error_msg] = Auth::check_user();

        // unberechtigter Seitenaufruf
        $status = (empty($error_msg)) ? true : false;

        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::is_checked_in()) {
            header("location: /auth/login.php");
            exit;
        }

        // globale Variablen setzen
        if ($status) {
            self::$identifier = $securitytoken_row['identifier'];
            self::$userid = $usr_data['userid'];
        }
        self::$error_msg = $error_msg;
        self::$showForm = $status;
    }


    /****************************
     * Summary of data_preparation
     * set $usr_data, $userliste
     */
    public static function data_preparation()
    {
        // TODO:
        // Konto löschen per PW legitimieren
        // Konto löschen: logout nicht über die logout-Seite sondern per Funktion und Rücksprung zur Hauptseite
        //

        // globale Variablen holen
        $userid = self::$userid;
        $identifier = self::$identifier;
        $showForm = self::$showForm;
        $pdo = self::$pdo;
        $usr_data = [];
        $userliste = [];

        // Seiten-Check okay, Seite starten
        if ($showForm):

        // Zählerangaben für Autologin-Anzeige des aktuellen Nutzers holen
        // alle aktiven Anmeldungen
        $stmt = "SELECT site_users.userid, username, email, vorname, nachname, pw_hash, count3
            FROM site_users
            LEFT JOIN (
                SELECT userid, COUNT(*) AS count3
                FROM site_login
                WHERE userid = :userid AND login=1 && autologin=1 AND identifier != :ident
            ) AS ct1 ON ct1.userid = site_users.userid
            ";

        try {
            $qry = $pdo->prepare($stmt);
            $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
            $qry->bindParam(':ident', $identifier, PDO::PARAM_STR);
            $qry->execute();
            $results = $qry->fetchALL(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {die($e->getMessage().': settings.inc_ct-autologins');}

        // Daten separieren
        foreach ($results as $user) {

            // aktueller Nutzer (für Formular-Vorbelegung)
            if ($user['userid'] == $userid) $usr_data = $user;

            // die anderen (für Abgleich nach Änderung von name/email)
            else {
                $userliste []= [
                    'username' => $user['username'],
                    'email' => $user['email']
                ];
            }
        }
        endif;      # Seiten-Check okay

        // globale Variablen setzen
        self::$usr_data = $usr_data;
        self::$userliste = $userliste;
    }


    /****************************
     * Summary of form_evaluation
     * Formular-Eingabe verarbeiten
     */
    public static function form_evaluation()
    {
        $pdo = self::$pdo;
        $identifier = self::$identifier;
        $usr_data = self::$usr_data;
        $userliste = self::$userliste;
        $userid = self::$userid;
        $showForm = self::$showForm;
        $error_msg = self::$error_msg;
        $success_msg = "";

        // Seiten-Check okay, Seite starten
        if ($showForm):

        // Änderungsformular empfangen
        if (isset($_GET['save']) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST"):

        $regex_usr = "/^[\wäüößÄÜÖ\-]{3,50}$/";
        $regex_email = "/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix";
        $regex_pw = "/^[\w<>()?!,.:_=$%&#+*~^ @€µäüößÄÜÖ]{1,100}$/";
        // (Doppel-)Name mit Bindestrich/Leerzeichen, ohne damit zu beginnen oder zu enden und ohne doppelte Striche/Leerz., ist 0-50 Zeichen lang
        $regex_name = "/^[a-zA-ZäüößÄÜÖ]+([a-zA-ZäüößÄÜÖ]|[ -](?=[a-zA-ZäüößÄÜÖ])){0,50}$/";
        $regex_name_no = "/^[^a-zA-ZäüößÄÜÖ]+|[^a-zA-ZäüößÄÜÖ -]+|[- ]{2,}|[^a-zA-ZäüößÄÜÖ]+$/";

        $save = htmlspecialchars(Tools::clean_input($_GET['save']));
        switch ($save):

        // Änderung Anmeldedaten
        case 'email':
            $update_username = False;
            $update_email = False;

            $input_usr = htmlspecialchars(Tools::clean_input($_POST['username']));    # strtolower()
            $input_email = htmlspecialchars(Tools::clean_input($_POST['email']));
            $input_email2 = htmlspecialchars(Tools::clean_input($_POST['email2']));
            $input_pw = $_POST['passwort'];


            // Eingabewerte auf Plausibilität prüfen

            // Passwort-Check
            if (!password_verify($input_pw, $usr_data['pw_hash']))
                $error_msg = "Bitte korrektes Passwort eingeben.";


            // Email-Check
            elseif ($input_email === "" && $input_usr === "")
                $error_msg = 'Name oder Email angeben.';

            elseif ($input_email !== "" && ($input_email !== $input_email2))
                $error_msg = 'Die Emailangaben müssen übereinstimmen.';

            elseif ($input_email !== "" &&
                (!filter_var($input_email, FILTER_VALIDATE_EMAIL)))
            {
                $error_msg = 'Keine gültige Email-Adresse.';

            } elseif ($input_email !== "" &&
                ($input_email !== $usr_data['email']))
            {
                foreach ($userliste AS $user_info) {
                    // ist email schon vorhanden?
                    if ($input_email == $user_info['email']) {
                        $error_msg = "Die E-Mail-Adresse ist bereits registriert.";
                        break;
                    } else {
                        $update_email = True;
                    }
                }

            } elseif ($input_email === $usr_data['email'])
                $success_msg = "E-Mail-Adressse unverändert.";


            // Username-Check
            elseif ($input_usr !== "" &&
                preg_match("/\W/", $input_usr, $matches))
            {
                $error_msg = 'nur Buchstaben/Zahlen im Anmeldenamen zulässig: '.
                    htmlspecialchars($matches[0]);

            } elseif ($input_usr !== "" &&
                ($input_usr !== $usr_data['username']))
            {
                foreach ($userliste AS $user_info) {
                    // ist username schon vorhanden?
                    if ($input_usr == $user_info['username']) {
                        $error_msg = "Der Benutzername ist schon vergeben.";
                        break;
                    } else {
                        $update_username = True;
                    }
                }
            } elseif ($input_usr == $usr_data['username'])
                $success_msg = "Benutzername unverändert.";

            else {}  // Namen-Email-Check okay


            // Daten in DB ändern
            if ($error_msg === "" && $success_msg === "") {
                if ($update_email && $update_username) {
                    $stmt = "UPDATE site_users SET email = :email, username = :username  WHERE userid = :userid";
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                        $qry->bindParam(':username', $input_usr, PDO::PARAM_STR);
                        $qry->bindParam(':email', $input_email, PDO::PARAM_STR);
                        $qry->execute();
                    } catch(PDOException $e) {
                        die($e->getMessage().': settings.inc_update-email.name');
                    }
                    $usr_data['username'] = $input_usr;
                    $usr_data['email'] = $input_email;
                    $success_msg = "Benutzername und E-Mail-Adresse erfolgreich gespeichert.";


                } elseif ($update_email) {
                    $stmt = "UPDATE site_users SET email = :email WHERE userid = :userid";
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                        $qry->bindParam(':email', $input_email, PDO::PARAM_STR);
                        $qry->execute();
                    } catch(PDOException $e) {
                        die($e->getMessage().': settings.inc_update-email');
                    }
                    $usr_data['email'] = $input_email;
                    $success_msg = "E-Mail-Adresse erfolgreich gespeichert.";

                } elseif ($update_username) {
                    $stmt = "UPDATE site_users SET username = :username WHERE userid = :userid";
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                        $qry->bindParam(':username', $input_usr, PDO::PARAM_STR);
                        $qry->execute();
                    } catch(PDOException $e) {
                        die($e->getMessage().': settings.inc_update-usrname');
                    }
                    $usr_data['username'] = $input_usr;
                    $success_msg = "Benutzername erfolgreich geändert.";
                }
                else {}   // keine Änderungen
            }
        break;


        // Änderung Passwort
        case 'passwort':
            $input_pwALT = $_POST['passwortAlt'];
            $input_pwNEU1 = $_POST['passwortNeu'];
            $input_pwNEU2 = $_POST['passwortNeu2'];

            // Eingabewerte auf Plausibilität prüfen
            if ($input_pwNEU1 != $input_pwNEU2)
                $error_msg = "Die eingegebenen Passwörter stimmten nicht überein.";

            elseif (!password_verify($input_pwALT, $usr_data['pw_hash']))
                $error_msg = "Bitte korrektes Passwort eingeben.";

            elseif (strlen($input_pwNEU1) < 4 || strlen($input_pwNEU1) > 50)
                $error_msg = "Passwort muss zwischen 4 und 50 Zeichen lang sein!";

            elseif (!preg_match($regex_pw, $input_pwNEU1))
                $error_msg = "Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>";

            else {
                $passwort_hash = password_hash($input_pwNEU1, PASSWORD_DEFAULT);
                $stmt = "UPDATE site_users SET pw_hash = :pw_hash WHERE userid = :userid";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                    $qry->bindParam(':pw_hash', $passwort_hash, PDO::PARAM_STR);
                    $qry->execute();
                } catch(PDOException $e) {
                    die($e->getMessage().': settings.inc_update-pwhash');
                }
                $usr_data['pw_hash'] = $passwort_hash;
                $success_msg = "Passwort erfolgreich gespeichert.";
            }
        break;


        // Änderung Persönl. Daten
        case 'data':
            $error_msg = [];
            $input_vor = isset($_POST['vorname']) ? htmlspecialchars(Tools::clean_input($_POST['vorname'])) : "";
            $input_nach = isset($_POST['nachname']) ? htmlspecialchars(Tools::clean_input($_POST['nachname'])) : "";

            // Plausi-Check
            if ($input_vor !== "" && preg_match_all($regex_name_no, $input_vor, $match))
                $error_msg []= 'nur Buchstaben im Vornamen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen): "'.htmlentities(implode(" ", $match[0])).'"';

            if ($input_nach !== "" && preg_match_all($regex_name_no, $input_nach, $match))
                $error_msg []= 'nur Buchstaben im Nachnamen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen): "'.htmlentities(implode(" ", $match[0])).'"';

            // Eingabe okay
            if (empty($error_msg)){
                if ($input_vor != $usr_data['vorname'] || $input_nach != $usr_data['nachname']) {
                    $stmt = "UPDATE site_users SET vorname = :vorname, nachname = :nachname WHERE userid = :userid";
                    try {
                        $qry = $pdo->prepare($stmt);
                        $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                        $qry->bindParam(':vorname', $input_vor, PDO::PARAM_STR);
                        $qry->bindParam(':nachname', $input_nach, PDO::PARAM_STR);
                        $qry->execute();
                    } catch(PDOException $e) {
                        die($e->getMessage().': settings.inc_update-name');
                    }
                    $usr_data['vorname'] = $input_vor;
                    $usr_data['nachname'] = $input_nach;
                    $success_msg = "Persönliche Daten geändert.";
                }
            }
            $error_msg = implode("", $error_msg);
        break;


        // Autologins abmelden, log=0
        case 'autologin':
            $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
                WHERE userid = :userid AND (login = 1 && autologin = 1) AND identifier != :ident";
            #$stmt = "DELETE FROM site_login WHERE userid=:userid AND autologin=1";
            try {
                $qry = $pdo->prepare($stmt);
                $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                $qry->bindParam(':ident', $identifier, PDO::PARAM_STR);
                $qry->execute();
            } catch(PDOException $e) {
                die($e->getMessage().': settings.inc_del-autologin');
            }
            $usr_data['count3'] = "";
            $success_msg = "alle meine anderen Autologins beendet.";
        break;


        // Konto löschen
        case 'delete':
            $input_pw3 = $_POST['pw_delete'];

            // Passwort-Check
            if (!password_verify($input_pw3, $usr_data['pw_hash']))
                $error_msg = "Bitte korrektes Passwort eingeben.";

            if ((isset($_SESSION['su']) && (int)$_SESSION['su'] === 1) )
                $error_msg = "Ein Admin kann sich hier nicht löschen.";

            if ($error_msg === "") {
                $stmt = "UPDATE site_users SET status = 'deaktiv' WHERE userid = :userid";
                #$stmt = "DELETE FROM site_users WHERE userid=:userid";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                    $qry->execute();
                } catch(PDOException $e) {
                    die($e->getMessage().': settings.inc_del-user-1');
                }

                // wenn auf 'deaktiv' gesetzt, dann auch alle Anmeldungen löschen/beenden, (sonst bei DELETE automatisch per Verknüpfung gelöscht).
                #$stmt = "DELETE FROM site_login WHERE userid = :userid";
                $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
                    WHERE userid = :userid AND (login = 1 || autologin = 1)";
                try {
                    $qry = $pdo->prepare($stmt);
                    $qry->bindParam(':userid', $userid, PDO::PARAM_INT);
                    $qry->execute();
                } catch(PDOException $e) {
                    die($e->getMessage().': settings.inc_del-user-2');
                }
                $usr_data = [];
                $success_msg = "Nutzer gelöscht.";

                Auth::logout();
                #header("location: /auth/logout.php");
                exit;
            }
        break;

        endswitch;  # Speichern-Taste gedrückt
        endif;      # Formular empfangen
        endif;      # Seiten-Check okay


        // Marker setzen, um wieder auf den letzten Tab-Reiter zu springen
        //
        // Liste der #Tab-ID's
        $site_tabs = ['email', 'passwort', 'data', 'autologin', 'delete', 'download'];

        $active = [];
        if (isset($_GET['tab']) && in_array($_GET['tab'], $site_tabs)) {
            foreach ($site_tabs as $tab) {
                if ($_GET['tab'] == $tab) {
                    $active[$tab] = "active";
                } else
                    $active[$tab] = "";
            }

        // irgendwie kein GET erhalten,
        // $active auf Standard (1.Tab = email) setzen
        } else {
            foreach ($site_tabs as $tab) {
                $active[$tab] = "";
            }
            $active[$site_tabs[0]] = "active";
        }

        $status_message = Tools::status_out($success_msg, $error_msg);

        // globale Variablen setzen
        self::$usr_data = $usr_data;
        self::$active = $active;
        self::$error_msg = $error_msg;
        self::$status_message = $status_message;

        unset($_REQUEST, $_POST, $_GET);
    }


    /****************************
     * Summary of site_output
     */
    public static function site_output()
    {
        $showForm = self::$showForm;
        $status_message = self::$status_message;
        $output = "<div class='container main-container'>";
        if (!$showForm):
            $output .= $status_message;
        else:

        // Seiten-Check okay, Seite starten
        $active = self::$active;
        $usr_data = self::$usr_data;

        $output .= "<h1>Einstellungen</h1>";
        $output .= $status_message;

        $output .= "<div> "; # -- START --

        // -- Nav tabs --
        $output .= "
            <ul class='nav nav-tabs' role='tablist'>
            <li role='presentation' class='".$active['email']."'><a href='#email' aria-controls='profile' role='tab' data-toggle='tab'>Anmeldedaten</a></li>
            <li role='presentation' class='".$active['passwort']."'><a href='#passwort' aria-controls='messages' role='tab' data-toggle='tab'>Passwort</a></li>
            <li role='presentation' class='".$active['data']."'><a href='#data' aria-controls='home' role='tab' data-toggle='tab'>Persönliche Daten</a></li>
            <li role='presentation' class='".$active['autologin']."'><a href='#autologin' aria-controls='autologin' role='tab' data-toggle='tab'>Autologin</a></li>
            <li role='presentation' class='".$active['delete']."'><a href='#delete' aria-controls='delete' role='tab' data-toggle='tab'>Konto löschen</a></li>

            ";
            # wenn angemeldet als 'heinz' oder 'admin'
            if ($_SESSION['userid'] === 3333 || $_SESSION['su'] === 1111):
                $output .= "
            <li role='presentation' class='".$active['download']."'><a href='#download' aria-controls='download' role='tab' data-toggle='tab'>Download</a></li>";
            endif;
            $output .= "

        </ul>
        <div class='tab-content'>";

        // -- Änderung der E-Mail-Adresse / Benutzername --
        $output .= "

        <div role='tabpanel' class='tab-pane ".$active['email']."' id='email'>
            <div class='col-sm-10'>

            <br>
            <p>Zum Ändern deiner Daten gib bitte zur Bestätigung dein aktuelles Passwort ein.</p>

            <form action='?save=email&tab=email' method='POST' class='form-horizontal'>
                <div class='form-group'>
                    <label for='inputPW0' class='col-sm-2 control-label'>Passwort</label>
                    <div class='col-sm-10 user-box'>
                        <input id='inputPW0' type='password' name='passwort'
                            class='form-control' autocomplete='current-password' spellcheck='false'
                            required />
                        <span class='password-toggle-icon' style='right:25px'><i id='pw0' class='fas fa-eye'></i></span>
                    </div>
                </div>
                <br>
                <div class='form-group'>
                    <label for='inputUser' class='col-sm-2 control-label'>Benutzername</label>
                    <div class='col-sm-10'>
                        <input class='form-control' id='inputUser' name='username' type='text' value='".htmlentities($usr_data['username'])."' autocomplete='off'>
                    </div>
                </div>

                <div class='form-group'>
                    <label for='inputEmail' class='col-sm-2 control-label'>E-Mail</label>
                    <div class='col-sm-10'>
                        <input class='form-control' id='inputEmail' name='email' type='email' value='".htmlentities($usr_data['email'])."' autocomplete='off'>
                    </div>
                </div>

                <div class='form-group'>
                    <label for='inputEmail2' class='col-sm-2 control-label'>E-Mail (wiederholen)</label>
                    <div class='col-sm-10'>
                        <input class='form-control' id='inputEmail2' name='email2' type='email' autocomplete='off'>
                    </div>
                </div>

                <br>
                <div class='form-group'>
                    <div class='col-sm-offset-2 col-sm-10'>
                        <button type='submit' class='btn btn-primary'>Speichern</button>
                    </div>
                </div>
            </form>
            </div>
        </div>

        ";
        // -- Änderung des Passworts --
        $output .= "

        <div role='tabpanel' class='tab-pane ".$active['passwort']."' id='passwort'>
            <div class='col-sm-10'>

            <br>
            <p>Zum Änderen deines Passworts gib bitte zur Bestätigung dein aktuelles Passwort ein.</p>
            <form action='?save=passwort&tab=passwort' method='POST' class='form-horizontal'>
                <div class='form-group'>
                    <label for='inputPW1' class='col-sm-2 control-label'>Aktuelles Passwort</label>
                    <div class='col-sm-10 user-box'>
                        <input id='inputPW1' type='password' name='passwortAlt'
                            class='form-control' autocomplete='current-password' spellcheck='false'
                            required />
                        <span class='password-toggle-icon' style='right:25px'><i id='pw1' class='fas fa-eye'></i></span>
                    </div>
                </div>
                <br>
                <div class='form-group'>
                    <label for='inputPW2' class='col-sm-2 control-label'>Neues Passwort</label>
                    <div class='col-sm-10 user-box'>
                        <input id='inputPW2' type='password' name='passwortNeu'
                            class='form-control' autocomplete='off' spellcheck='false'
                            required />
                        <span class='password-toggle-icon' style='right:25px'><i id='pw2' class='fas fa-eye'></i></span>
                    </div>
                </div>

                ";
                // placeholder='Buchstaben/Zahlen !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>'
                $output .= "

                <div class='form-group'>
                    <label for='inputPasswortNeu2' class='col-sm-2 control-label'>Neues Passwort (wiederholen)</label>
                    <div class='col-sm-10'>
                        <input id='inputPasswortNeu2' type='password' name='passwortNeu2'
                            class='form-control' autocomplete='off' spellcheck='false'
                            required />
                        <span class='password-toggle-icon' style='right:25px'><i id='pw3' class='fas fa-eye'></i></span>
                    </div>
                </div>

                <br>
                <div class='form-group'>
                    <div class='col-sm-offset-2 col-sm-10'>
                        <button type='submit' class='btn btn-primary'>Speichern</button>
                    </div>
                </div>
            </form>
            </div>
        </div>

        ";
        // -- Persönliche Daten --
        ; $output .= "

        <div role='tabpanel' class='tab-pane ".$active['data']."' id='data'>
            <div class='col-sm-10'>
            <form action='?save=data&tab=data' method='POST' class='form-horizontal'>
            <br><br>
                <div class='form-group'>
                    <label for='inputVorname' class='col-sm-2 control-label'>Vorname</label>
                    <div class='col-sm-10'>
                        <input class='form-control' id='inputVorname' name='vorname' type='text' value='".htmlentities($usr_data['vorname'])."' autocomplete='off'>
                    </div>
                </div>

                <div class='form-group'>
                    <label for='inputNachname' class='col-sm-2 control-label'>Nachname</label>
                    <div class='col-sm-10'>
                        <input class='form-control' id='inputNachname' name='nachname' type='text' value='".htmlentities($usr_data['nachname'])."' autocomplete='off'>
                    </div>
                </div>

                <br>
                <div class='form-group'>
                    <div class='col-sm-offset-2 col-sm-10'>
                        <button type='submit' class='btn btn-primary'>Speichern</button>
                    </div>
                </div>
            </form>
            </div>
        </div>

        ";
        // -- Autologin --
        $output .= "

        <div role='tabpanel' class='tab-pane ".$active['autologin']."' id='autologin'>
            <div class='col-sm-10'>
            <br>

            ";
            if ($usr_data['count3']):
            $output .= "

            <p>Alle meine anderen Anmeldungen geräteübergreifend beenden (".$usr_data['count3']."x)</p>
            <div class='col-sm-offset-2 col-sm-10'>
                <div><br>
                <form action='?save=autologin&tab=autologin' method='POST'>
                    <button class='btn btn-primary' type='submit'>Beenden</button>
                </form>
                </div>
            </div>

            ";
            else:
            $output .= "

            <p>Keine Autologins vorhanden.</p>

            ";
            endif;
            $output .= "

            </div>
        </div>

        ";
        // -- Konto löschen --
        $output .= "

        <div role='tabpanel' class='tab-pane ".$active['delete']."' id='delete'>
            <div class='col-sm-10'>
            <br><br>
            <p>Zum Löschen deines Kontos gib bitte zur Bestätigung dein aktuelles Passwort ein.</p><br>
            <form action='?save=delete&tab=delete' method='POST' class='form-horizontal'>
                <div class='form-group'>
                    <div class='col-sm-10 user-box'>
                        <input id='inputPW3' type='password' name='pw_delete' placeholder='Passwort'
                            class='form-control'  style='width:auto;' autocomplete='current-password' spellcheck='false'
                            required />
                        <label for='inputPW3' class='col-sm-2 control-label sr-only'>Passwort</label>
                        <span class='password-toggle-icon' style='left:190px;right:unset'><i id='pw4' class='fas fa-eye'></i></span>
                    </div>
                </div>
                <br><br>

                <div class='form-group'>
                    <div class='col-sm-offset-2 col-sm-10'>
                        <button type='submit' class='btn btn-primary btn-lg' onclick='return confirm('Wirklich das Konto  - L Ö S C H E N -  ?')'>mein Konto löschen</button>
                    </div>
                </div>
            </form>
            </div>
        </div>";


    // -- Downloads --

    function human_filesize($bytes, $decimals = 2) {
        $factor = floor((strlen($bytes) - 1) / 3);
        #$sz = "BKMGTP";
        #return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)).@$sz[$factor];

        if ($factor > 0) $sz = "KMGT";
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor))." ".@$sz[$factor - 1]."B";
    }
    $file = "/download/dzg_90.pdf";
    $ffn = $_SERVER["DOCUMENT_ROOT"].$file;
    $size = (file_exists($ffn))     # is_file()
        ? filesize($ffn)
        : 0;
    $filesize = human_filesize($size, 0);
    $output .= "

        <div role='tabpanel' class='tab-pane ".$active['download']."' id='download'>
            <div class='col-sm-10'>
            <br>
            <p>Datenbank-Auszug als PDF-Datei (".$filesize.") ... </p>
            <div class='col-sm-offset-2 col-sm-10'>
                <div><br>
                <form action='".$file."' method='POST'>
                    <button class='btn btn-primary' type='submit'>anzeigen</button>&emsp;&emsp;&emsp;
                    <button formaction='/test/pdf_down'class='btn btn-primary' type='submit'>downloaden</button>
                </form>
                </div>";


    $output .= "</div></div></div>";    # -- Downloads

        $output .= "</div> "; # -- tab-content -- ./../test/pdf_down.php
        $output .= "</div> "; # -- ende.START --
        endif;                # showForm

        $output .= "</div> "; # -- container --


        // Passwort-Anzeige
        $output .= "
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
        ";

        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }

}




/*

    <h2>Viewportabmessungen</h2>
    <h3>Breite</h3>
    <p><a href="https://wiki.selfhtml.org/wiki/ClientWidth">Element.clientWidth</a>:
        <span
        id="clientW"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/InnerWidth">Window.innerWidth</a>:
        <span
        id="innerW"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/OuterWidth">Window.outerWidth</a>:
        <span
        id="outerW"></span>px</p>
    <h3>Höhe</h3>
    <p><a href="https://wiki.selfhtml.org/wiki/ClientHeight">Element.clientHeight</a>:
        <span id="clientH"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/InnerHeight">Window.innerHeight</a>:
        <span
        id="innerH"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/OuterHeight">Window.outerHeight</a>:
        <span
        id="outerH"></span>px</p>
    <h2>Geräteabmessungen</h2>
    <h3>Breite</h3>
    <p><a href="https://wiki.selfhtml.org/wiki/JavaScript/Screen/width">Screen.width</a>:
        <span id="screenW"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/availWidth">Screen.availWidth</a>:
        <span
        id="availW"></span>px</p>
    <h3>Höhe</h3>
    <p><a href="https://wiki.selfhtml.org/wiki/JavaScript/Screen/height">Screen.height</a>:
        <span id="screenH"></span>px</p>
    <p><a href="https://wiki.selfhtml.org/wiki/availHeight">Screen.availHeight</a>:
        <span
        id="availH"></span>px</p>



<script>
'use strict';
document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener('resize', messen);
    messen();

    function messen() {
        document.getElementById('clientW')
            .textContent = document.querySelector('html')
            .clientWidth;
        document.getElementById('innerW')
            .textContent = window.innerWidth;
        document.getElementById('outerW')
            .textContent = window.outerWidth;
        document.getElementById('clientH')
            .textContent = document.querySelector('html')
            .clientHeight;
        document.getElementById('innerH')
            .textContent = window.innerHeight;
        document.getElementById('outerH')
            .textContent = window.outerHeight;
        document.getElementById('screenW')
            .textContent = screen.width;
        document.getElementById('availW')
            .textContent = screen.availWidth;
        document.getElementById('screenH')
            .textContent = screen.height;
        document.getElementById('availH')
            .textContent = screen.availHeight;
    }
});
</script>

resizeObserver
Window.matchMedia()
*/