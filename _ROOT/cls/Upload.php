<?php
namespace Dzg;

// IN ARBEIT \\

require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';
use Dzg\{Database, Auth, Tools, Kontakt, Header, Footer};

/***********************
 * Summary of Upload
 * Webseite:
 *
 * __public__
 * show()
 */
class Upload
{
    public static function show()
    {
        self::data_preparation();

        Header::show();
        self::site_output();
        Footer::show("auth");
    }



    /********************************* */
    private static function random_filename()
    {
        /***
        Es existieren zwei Möglichkeiten, um zu vermeiden das Dateien um Upload-Order überschrieben werden. Die erste Möglichkeit ist den Namen der Datei zu erweitern, z.B. indem man eine Zahl anhängt. Dies seht ihr im Script weiter unten, dass eine Zahl anhängt falls die Datei bereits existiert.

        Die andere Möglichkeit ist es,  einen neuen, zufälligen Namen zu vergeben. Den vorherigen Namen kann man beispielsweise in der Datenbank abspeichern und sofern man die Datei per PHP-Script zum Download anbietet, diesen Namen wieder mitsenden. Das Vergeben eines zufälligen Namens hat auch den Vorteil, dass keiner Dateinamen erraten kann und so Bilder/Dateien aus dem Upload-Ordner auslesen kann, die nicht für ihn bestimmt sind. Die Vergabe eines zufälligen Namens kann wie folgt aussehen:
        ***/

        $temporary_name = $_FILES['datei']['tmp_name'];
        $extension = pathinfo($_FILES['datei']['name'], PATHINFO_EXTENSION);

        // Überprüfung der Datei-Endung, MIME-Header Check etc. ...

        function random_string() {
            if(function_exists('random_bytes')) {
                $bytes = random_bytes(16);
                $str = bin2hex($bytes);
            } else if(function_exists('openssl_random_pseudo_bytes')) {
                $bytes = openssl_random_pseudo_bytes(16);
                $str = bin2hex($bytes);
            } else if(function_exists('mcrypt_create_iv')) {
                #$bytes = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
                #$str = bin2hex($bytes);
            } else {
                // Bitte 'euer_geheim_string' durch einen zufälligen String mit >12 Zeichen austauschen
                $str = md5(uniqid('danzigmarken.de_filename_codierung', true));
            }
            return $str;
        }

        $name = random_string(); //random new name
        $new_name = $_SERVER['DOCUMENT_ROOT'].'/upload/files/'.$name.'.'.$extension;
        move_uploaded_file($temporary_name, $new_name);
        echo "Bild hochgeladen nach: $new_name";
    }



    /********************************* */
    private static function anzeigen_1()
    {
        /***
        Die hochgeladenen Dateien sollten in einen speziellen Ordner hochgeladen werden und dieser Ordner sollte zusätzlich geschützt werden. Wir empfehlen euch die Dateien nach z.B. upload/files/ hochzuladen. Im Ordner upload/ solltet ihr eine .htaccess-Datei ablegen, die das Ausführen von Scripts verhindert. Die Lösung mittels dieser zwei Ordern schütz die .htaccess-Datei davor überschrieben zu werden.
        Die sicherste Variante ist per .htaccess-Datei sämtlichen Zugriff auf den Ordner zu verbieten.
        Mittels PHP-Script lassen sich dann erlaubte Dateien zum Download / zur Betrachtung darstellen:
        ***/

        // Aufruf mittels: download.php?file=bild.png
        // Überprüft, ob bild.php im Ordner 'upload' existiert. Zeigt dieses Bild an oder bietet es zum Download an
        if(!isset($_GET['file'])) {
            die("Bitte eine Datei auswählen");
        }

        $filename = basename($_GET['file']);

        // Optional: Nur bestimmte Datei-Endungen erlauben
        $allowed_extensions = array('png', 'jpg', 'jpeg', 'webp');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(!in_array($extension, $allowed_extensions)) {
            die("Ungültige Dateiendung");
        }


        // Überprüfen, ob Datei existiert
        $filepath = "upload/files/".$filename;

        if(!file_exists($filepath)) {
            die("Datei existiert nicht");
        }

        // Variante 1: Datei direkt im Browser anzeigen
        $content_type = mime_content_type($filepath);
        header('Content-type: '.$content_type);
        header('Content-Disposition: inline; filename="'.$filename.'"');
        readfile($filepath);
        exit();


        // Variante 2: Datei zum Download anbieten
        header('Content-Type: application/octet-stream');
        header('Content-Transfer-Encoding: Binary');
        header('Content-disposition: attachment; filename="'.$filename.'"');
        readfile($filepath);
        exit();

        /* Dir-Dir Erstellung
        $uploadpath = '../upload/'.$datum;
        mkdir($uploadpath, 0777, true);
        */
    }



    /*********************************
     * Erzeugen eines neuen Dateinamens
     *
     * Für unseren Anwendungszweck nehmen wir an, dass der Zeitraum zwischen dem Prüfen und dem
     * Verschieben der Datei so klein ist, dass hier mit extrem hoher Wahrscheinlichkeit kein
     * Problem auftritt. Zudem lassen wir den temporären Namen in die Generierung des Dateinamens
     * einfließen, sodass die Kollisionswahrscheinlichkeit weiter sinkt, denn der PHP-Interpreter
     * wird nicht den gleichen temporären Namen für zwei verschiedene Uploadvorgänge vergeben.
     *
     * Die Hash-Funktion MD5 wird zusätzlich benutzt, um einen einheitlich langen String zu
     * erzeugen. Dass sie für kryptografische Zwecke nicht mehr benutzt werden sollte, spielt hier
     * keine Rolle.
     */
    private static function new_name()
    {
        $upload_dir = '/pfad/zum/upload-verzeichnis/';
        do {
            $new_filename = md5(uniqid($_FILES['datei']['tmp_name'], true)).'.'.$allowed_files[$type];
        } while (file_exists($new_filename));
        move_uploaded_file($_FILES['datei']['tmp_name'], $upload_dir.$new_filename);
    }



    /*********************************
     * Datei mit einem PHP-Skript ausliefern
     */
    private static function file_anzeigen()
    {
        if(!empty($_GET['file'])) {
            $file = realpath($upload_dir.$_GET['file']);
            if(strpos($file, realpath($upload_dir)) === 0) {
                header('X-Content-Type-Options: nosniff');
                header('Content-Type: ' . mime_content_type($file));
                header('Content-Length: ' . filesize($file));
                readfile($file);
                die();
            } else {
                http_response_code(404);
                die('Datei nicht gefunden. Prüfen Sie bitte die Adresse.');
            }
        }
    }



    /*********************************
     * Generieren einer Fehlermeldung
     */
    private static function error_message()
    {
        $messages = [];

        switch ($_FILES['datei']['error']) {
            case UPLOAD_ERR_OK:
                // Datei wurde erfoglreich hochgeladen, keine Meldung erzeugen
                break;

            case UPLOAD_ERR_INI_SIZE:
                $messages[] = 'Die Datei überschreitet die maximal erlaubte Größe.';
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $messages[] = 'Die Datei überschreitet die maximal erlaubte Größe.';
                break;

            case UPLOAD_ERR_NO_FILE:
                $messages[] = 'Es wurde keine Datei ausgewählt.';
                break;

            // Weitere Fehlertypen definiert: https://www.php.net/manual/de/features.file-upload.errors.php
            // Allerdings bringt eine Unterscheidung dem Nutzer hier nichts...
            default:
                $messages[] = 'Upload der Datei fehlgeschlagen.';
                break;
        }
        return $messages;

        /*
        // An passender Stelle im Quellcode kann dem Nutzer entweder der Erfolg des Hochladens oder der Grund des Fehlschlagens gemeldet werden:
        <?php
        if(empty($messages)) {
            echo 'Datei wurde erfolgreich hochladen.';
        } else {
        ?>
        <ul>
        <?php
        foreach($messages as $message) {
            <?php
            <li><?= htmlspecialchars($message) ?></li>
            ?>
        }
        ?>
        </ul>
        <?php
        }
        */
    }



    /********************************* */
    private static function dateiname_bereinigen($dateiname)
    {
        // erwünschte Zeichen erhalten bzw. umschreiben
        // aus allen ä wird ae, ü -> ue, ß -> ss (je nach Sprache mehr Aufwand)
        // und sonst noch ein paar Dinge (ist schätzungsweise mein persönlicher Geschmack ;)
        $dateiname = strtolower ( $dateiname );
        $dateiname = str_replace ('"', "-", $dateiname );
        $dateiname = str_replace ("'", "-", $dateiname );
        $dateiname = str_replace ("*", "-", $dateiname );
        $dateiname = str_replace ("ß", "ss", $dateiname );
        $dateiname = str_replace ("ä", "ae", $dateiname );
        $dateiname = str_replace ("ö", "oe", $dateiname );
        $dateiname = str_replace ("ü", "ue", $dateiname );
        $dateiname = str_replace ("Ä", "Ae", $dateiname );
        $dateiname = str_replace ("Ö", "Oe", $dateiname );
        $dateiname = str_replace ("Ü", "Ue", $dateiname );
        $dateiname = htmlentities ( $dateiname );
        $dateiname = str_replace ("&", "und", $dateiname );
        $dateiname = str_replace ("(", "-", $dateiname );
        $dateiname = str_replace (")", "-", $dateiname );
        $dateiname = str_replace (" ", "-", $dateiname );
        $dateiname = str_replace ("'", "-", $dateiname );
        $dateiname = str_replace ("/", "-", $dateiname );
        $dateiname = str_replace ("?", "-", $dateiname );
        $dateiname = str_replace ("!", "-", $dateiname );
        $dateiname = str_replace (":", "-", $dateiname );
        $dateiname = str_replace (";", "-", $dateiname );
        $dateiname = str_replace (",", "-", $dateiname );
        $dateiname = str_replace ("--", "-", $dateiname );

        // und nun jagen wir noch die Heilfunktion darüber
        $dateiname = filter_var($dateiname, FILTER_SANITIZE_URL);
        return ($dateiname);
    }

    /********************************* */
    private static function anzeigen_2()
    {
        /***
        php.ini nach dem Eintrag „max_execution_time = 60“
        Problem bei den Uploads ist nun, dass das Programm den Upload abwarten muss, was bei einer Kombination von großer Datei und langsamer Internetverbindung durchaus länger als die voreingestellte Zeit dauern kann. Also da nicht wundern, wenn es anscheinend nicht funktioniert. Hierbei hilft, die Ausführungszeit von PHP-Programmen in der Apache-Einstellung zu ändern. Da einfach mal in der php.ini nach dem Eintrag „max_execution_time = 60“ suchen. Die 60 sind nur ein Beispiel für 60 Sekunden zulässige Ausführungszeit. Bei einigen Providern kann die Einstellung verändert werden, bei anderen nicht.
        ***/

        echo "<pre>";
        echo "FILES:<br>";
        print_r ($_FILES );
        echo "</pre>";

        if ($_FILES['uploaddatei']['name']  <> "") {
            // Datei wurde durch HTML-Formular hochgeladen
            // und kann nun weiterverarbeitet werden

            // Kontrolle, ob Dateityp zulässig ist
            $zugelassenedateitypen = array("image/png", "image/jpeg", "image/webp");

            if (!in_array( $_FILES['uploaddatei']['type'], $zugelassenedateitypen )) {
                echo "<p>Dateitype ist NICHT zugelassen</p>";
            } else {
                // Test ob Dateiname in Ordnung
                $_FILES['uploaddatei']['name']
                                    = self::dateiname_bereinigen($_FILES['uploaddatei']['name']);

                if ($_FILES['uploaddatei']['name'] <> '') {
                    move_uploaded_file (
                        $_FILES['uploaddatei']['tmp_name'] ,
                        'hochgeladenes/'. $_FILES['uploaddatei']['name'] );

                    echo "<p>Hochladen war erfolgreich: ";
                    echo '<a href="hochgeladenes/'. $_FILES['uploaddatei']['name'] .'">';
                    echo 'hochgeladenes/'. $_FILES['uploaddatei']['name'];
                    echo '</a>';

                } else {
                    echo "<p>Dateiname ist nicht zulässig</p>";
                }
            }
        }
    }



    /********************************* */
    protected static function data_preparation()
    {
        //-------------------------------------------------
        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::is_checked_in() || (int)$_SESSION['su'] !== 1) {
            header("location: /auth/login.php");
            exit;
        }

        //-------------------------------------------------
        // Rücksprung zur Hauptseite für das Navi-Menü setzen
        //

        #$akt_file_id = 1;
        #$_SESSION['fileid'] = $akt_file_id;
        if (!isset($_SESSION['start'])) $_SESSION['start'] = 0;

        if (!empty($_SESSION['main'])) {
            $_SESSION['lastsite'] = ($_SESSION['start'] > 0)
                ? $_SESSION['main']."?start=".$_SESSION['start']
                : $_SESSION['lastsite'] = $_SESSION['main'];
        } else {
            $_SESSION['lastsite'] = "/";
        }


        //-------------------------------------------------
        $error_arr = [];
        $success_msg = "";


        // Plausi-Check okay, Seite starten
        if (empty($error_arr)):


        /*
        Direktiven der php.ini:
        enable-post-data-reading = 1
        post_max_size = 8M
        (--> Bei Apache auch noch LimitRequestBody-Direktive)
        file_uploads = 1
        upload-tmp-dir
        */

        // Datei-Upload-Anfrage empfangen
        if (isset($_FILES['datei']) && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST") {

            $upload_folder = $_SERVER['DOCUMENT_ROOT'].'/upload/files/';       // das Upload-Verzeichnis
            $filename = pathinfo($_FILES['datei']['name'], PATHINFO_FILENAME);
            $extension = strtolower(pathinfo($_FILES['datei']['name'], PATHINFO_EXTENSION));
            $ffn = $upload_folder.$filename.'.'.$extension;     // vollständiger Pfad zur Uploaddatei

            // allgemeiner Uploadfehler
            switch ($_FILES['datei']['error']) {
                case UPLOAD_ERR_OK:
                    // Datei wurde erfoglreich hochgeladen, keine Meldung erzeugen
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    $error_arr[] = 'Die Datei überschreitet die maximal erlaubte Größe.';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error_arr[] = 'Die Datei überschreitet die maximal erlaubte Größe.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_arr[] = 'Es wurde keine Datei ausgewählt.';
                    break;
                default:
                    $error_arr[] = 'Upload der Datei fehlgeschlagen.';
                    break;
                }

            // Überprüfung der Dateiendung
            $allowed_extensions = ['png', 'jpg', 'jpeg', 'webp'];
            if (!in_array($extension, $allowed_extensions)) {
                $error_arr []= "Ungültige Dateiendung. Nur png, jpg, jpeg und webp-Dateien sind erlaubt";
            }

            // Überprüfung des Dateityps
            $zugelassenedateitypen = ["image/png", "image/jpeg", "image/webp"];
            if (!in_array($_FILES['datei']['type'], $zugelassenedateitypen )) {
                $error_arr []= "Dateitype ist NICHT zugelassen";
            }

            // Überprüfung der Dateigröße
            $max_size = 3*1024*1024;   // 3 MB
            if ($_FILES['datei']['size'] > $max_size) {
                $error_arr []= "Bitte keine Dateien größer 3 MB hochladen";
            }

            // Überprüfung, dass das Bild keine Fehler enthält
            // exif_imagetype erfordert die exif-Server.Erweiterung
            if (function_exists('exif_imagetype')) {
                $allowed_types = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP];
                $detected_type = exif_imagetype($_FILES['datei']['tmp_name']);
                if (!in_array($detected_type, $allowed_types)) {
                    $error_arr []= "Nur der Upload von Bilddateien ist gestattet";
                }
            }

            // Test ob Dateiname in Ordnung
            # $_FILES['datei']['name'] = self::dateiname_bereinigen($_FILES['datei']['name']);
            if ($_FILES['datei']['name'] == '') {
                $error_arr []= "Dateiname fehlerhaft konvertiert";
            }

            if (empty($error_arr)) {

                // Neuer Dateiname falls die Datei bereits existiert
                // Falls Datei existiert, hänge eine Zahl an den Dateinamen
                if (file_exists($ffn)) {
                    $id = 1;
                    do {
                        $new_filename = $filename.'_'.$id.'.'.$extension;
                        $ffn = $upload_folder.$new_filename;
                        $id++;
                    } while(file_exists($ffn));
                    $_FILES['datei']['name'] = $new_filename;
                }

                // Neuer anonymisierter, eindeutiger Dateiname, nicht erratbar
                /*
                do {
                    $new_filename = md5(uniqid($_FILES['datei']['tmp_name'], true)).'.'.$extension;
                    $ffn = $upload_folder.$new_filename;
                } while (file_exists($ffn));
                */

                // Alles okay, verschiebe Datei vom Temp- ins Upload-Verzeichnis
                move_uploaded_file($_FILES['datei']['tmp_name'], $ffn);
                $success_msg = 'Bild erfolgreich hochgeladen';
            }


        /*
        // Gruppen-ID
        $gid = (int)$results[0]['gid'];

        // Vorbereitung für Sprungmarken-Ermittlung
        if (!empty($_SESSION['jump'][$gid])) {
            $tmp_fid = array_key_first($_SESSION['jump'][$gid]);
            $tmp_jump = $_SESSION['jump'][$gid][$tmp_fid];  // im Gruppen-Modus (idx2) Sprung zur nächsten Gruppe
        } else {
            // wenn keine Sprungmarken vorhanden, dann auf -1 setzen -> keine Anzeige der Navi-Pfeile
            $tmp_jump = [-1, -1];
        }

        $i = $j = 0;
        $ffn = [];

        foreach ($results as $k=>$v) {

            // für die aktuelle Datei die 5 Fullfilenames zusammensetzen, $ffn[original, large, ...]
            // "original" => "data/original/Lochungen/Dzg.Neufahrwasser_1920-12-14.jpg",
            // "large" => "data/large/Lochungen/l_Dzg.Neufahrwasser_1920-12-14.jpg",
            // "medium" ... , "small" ... , "thumb" ...
            // webroot / sub1 / sub2 / prefix datei suffix
            // data / original / Lochungen / 1_Dzg1_LO_1921-01-15.jpg

            // $ffn['original'=>... , 'large'=> ... , ...]
            $ffn[$v['sub1']] = $v['webroot'].'/'.$v['sub1'].'/'.$v['sub2'].'/'.$v['prefix'].$v['name_orig'].$v['suffix'];

            // nicht weiter benötigt
            unset($v['webroot']);
            unset($v['sub1']);
            unset($v['sub2']);
            unset($v['prefix']);
            unset($v['name_orig']);
            unset($v['suffix']);

            $j++;   // Bildzähler

            // nach 5 Durchläufen pro Datei (idx=4) (original, large, ...) das Summen-Array der ffn speichern
            if ($j % 5 == 0 and $j > 0) {

                // einen der 5 DB-Ergebnisse pro Datei in ein seperates Haupt-Array ($stamps) speichern
                $stamps[$i] = $v;

                // komplette ffn-Liste dem Haupt-Array anhängen
                $stamps[$i] += $ffn;

                // Index-Nr der aktuellen Datei in der Gruppe ermitteln
                if ($v['fid'] == $akt_file_id)
                    $akt_file_idx = $i;

                // im Gruppen-Modus (idx2) erhalten alle Einzeldateien der Gruppe die Sprungmarken zur nächsten Gruppe
                if (!empty($_SESSION['idx2']))
                    $_SESSION['jump'][$gid][$v['fid']] = $tmp_jump;

                $i++;    // Dateizähler
                $ffn = [];
            }
        }

        // Anzahl Dateien/Bilder pro Gruppe
        $max_file = $i;     // count($stamps);


        // Nutzer nicht angemeldet?
        if (!Auth::is_checked_in()) {
            Auth::check_user();
            if (!Auth::is_checked_in()) {

                // wenn nicht angemeldet und nicht von der Hauptseite kommend,
                // und aktuelle Bild nicht der letzten Gruppe angehört,
                // dann unregulärer Seitenaufruf -> Startseite
                if (empty($_SESSION['main'])) $_SESSION['main'] = "/";
                $return2 = ['index', 'index2'];
                if (!isset($_SERVER['HTTP_REFERER']) OR
                    !in_array(pathinfo($_SERVER['HTTP_REFERER'])['filename'], $return2)) {

                    if ($gid <> $_SESSION['groupid']) {
                        header("location: {$_SESSION['main']}");
                        exit;
                    }
                }
            }
        }

        // Gruppen-ID global setzen
        $_SESSION['groupid'] = $gid;


        //-------------------------------------------------
        // Navi-Pfeile: Rück-/Vorsprung Seiten ermitteln
        //

        // Sprungmarken (prev, next) seitenübergreifend per SQL ermitteln
        // site_jump() -> details.func.php
        [$prev, $next] = site_jump($gid);

        $_SESSION['prev'] = ($prev > -1) ? $prev : $akt_file_id;


        # if (empty($_SESSION['mariaDB'])) {} else {}
        // SQLite
        // Sprungmarken (prev, next) per Hauptabfrage ermittelt
        // nur für die jeweilige Seite
        # [$prev, $next] = (!empty($_SESSION['jump'][$gid][$akt_file_id])) ? $_SESSION['jump'][$gid][$akt_file_id] : [-1, -1];

        */



        } // Datei-Upload-Anfrage empfangen

        endif;  // Plausi-Check, no error_arr


        if (!empty($error_arr)) {
            $error_msg = implode("<br>", $error_arr);
        } else {
            $error_msg = "";
        }


        ($error_msg === "")
            ? $showForm = True
            : $showForm = False;

    }



    /********************************* */
    protected static function site_output()
    {

        $ausgabe = Tools::statusmeldung_ausgeben();
        if ($showForm):

        $ausgabe .= '<div class="grid-container-detail">';
        $ausgabe .= '<div class="content detail">';

        $ausgabe .= "<div class='center-detail'>";
        $ausgabe .= "<div class='main-detail'>";

        ///////////////////////////////////////////////////
        // linke Detail-Seite
        //
        $ausgabe .= "<div class='main-detail-left'>";

        $max_size = 3*1024*1024;   // 3 MB
        $ausgabe .= '
        <form action="upload.php" method="post" enctype="multipart/form-data">

        <!-- MAX_FILE_SIZE muss vor dem Datei-Eingabefeld stehen -->
        <input type="hidden" name="MAX_FILE_SIZE" value="'.$max_size.'" />

        <!-- Der Name des Eingabefelds bestimmt den Namen im $_FILES-Array -->
        <label>Wählen Sie ein Bild (*.jpg, *.png oder *.webp) zum Hochladen aus.
        <input name="datei" type="file" accept="image/jpeg,image/png,image/webp"><br>
        </label>

        <input type="submit" value="Hochladen">
        <button>Datei hochladen</button>
        </form>
        ';

        $ausgabe .= "</div>";  # ende linke Seite, < /main-detail-left >


        ///////////////////////////////////////////////////
        // rechte Bild-Seite
        //
        $ausgabe .= "<div class='main-detail-right'>";
        $ausgabe .= "</div>";  # ende rechte Seite, < /main-detail-right >
        $ausgabe .= "</div>";  # ende < /MAIN-DETAIL >


        ///////////////////////////////////////////////////
        // FUSS
        //
        $ausgabe .= "<div class='fuss noprint' style='padding-top:0; padding-bottom:0;'>";
        $ausgabe .= "</div>";   # ende < /fuss >


        $ausgabe .= "</div>";   # ende < /CENTER >
        $ausgabe .= '</div>';   # ende < /CONTENT >
        $ausgabe .= '</div>';   # ende < /GRID-CONTAINER-DETAIL >

        endif;  // Seite anzeigen



        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $ausgabe;

    }

}