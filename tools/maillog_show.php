<?php
date_default_timezone_set('Europe/Berlin');
session_start();

require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/mail/Mailcfg.php";
use Dzg\Mailcfg;

// Überprüfen, ob der Benutzer eingeloggt ist
if (isset($_SESSION['loggedin'])
    && $_SESSION['loggedin'] === true):

    // Der Pfad zur Logdatei
    #$logFile = 'log/ip_log.txt';
    $logFile = Mailcfg::$maillogFile;

    // Überprüfen, ob die Datei existiert
    if (file_exists($logFile)) {

        // Lese den Inhalt der Logdatei
        $logData = file_get_contents($logFile);

        // Überprüfen, ob die Logdatei leer ist
        if (trim($logData) === "") {
            echo '
            <div style="font-family: Verdana; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); margin: 20px auto; text-align: center; border: 1px solid #ccc; background-color: #f9f9f9; max-width: 90%; width: 300px;">
            Keine Eintr&auml;ge vorhanden.
            </div>';

        } else {
            // Gib den Inhalt der Logdatei aus
            echo '
            <div style="font-family: Verdana; font-size:15px; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); margin: 20px auto; text-align: center; border: 1px solid #ccc; background-color: #f9f9f9; max-width: 90%; width: 80%;">
            <pre>' . htmlspecialchars($logData) . '</pre>
            </div>';


            // Hole die Dateigröße in Bytes
            $fileSize = filesize($logFile);
            $unit = "Byte";

            // Hier wird die Größe in Kilobytes (KB) berechnet
            $fileSizeKB = $fileSize / 1024;

            // Hier wird die Größe in Megabytes (MB) berechnet
            $fileSizeMB = $fileSize / (1024 * 1024);


            if ($fileSizeMB > 1){
                $fileSize = $fileSizeMB;
                $unit = "MByte";
            } elseif ($fileSizeKB > 1) {
                $fileSize = $fileSizeKB;
                $unit = "kByte";
            }


            // Link zur Datei maillog_delete.php formatieren
            echo '
                <style>
                a:link, a:visited {
                    color: #00598D;
                    text-decoration: none;
                    font-weight: bold;
                }
                a:hover {
                    text-decoration: underline;
                } </style>';

            // Link zu maillog_delete.php setzen
            echo "
            <br />
            <p style='font-family: Verdana; font-size:19px; padding: 10px; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); margin: 20px auto; text-align: center; border: 1px solid #ccc; max-width: 90%; width: 200px;'>
            <a href='maillog_delete' style='color: #007BFF; transition: text-decoration 0.3s; display: inline-block; text-align: center;'>
            Log-Datei leeren
            </a><br />
            <span style='font-size:14px;line-height:30px;'>
            Dateigr&ouml;&szlig;e: {$fileSize} {$unit}</span>
            </p><br />";
        }
    }

    // log-Datei existiert nicht, eine entsprechende Nachricht anzeigen
    else {
        echo '
        <div style="font-family: Verdana; color: red; border: 1px solid red; padding: 10px; border-radius: 5px; margin: 20px auto; text-align: center; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 90%; width: 300px;">
        Die Logdatei existiert nicht.
        </div>';
    }

    echo '
    <br><br>
    <div style="font-family: Verdana; color: green; border: 1px solid black; padding: 10px; border-radius: 5px; margin: 20 auto 20px auto; text-align: center; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 90%; width: 390px;line-height:31px;font-size:22px;">
    <a href="/account/admin" style="color: blue; text-decoration: underline;font-size:17px;">
    Zur&uuml;ck</a></div>';


// nicht angemeldet
else:
    echo '
    <div style="font-family: Verdana; color: red; border: 1px solid black; padding: 10px; border-radius: 5px; margin: 20 auto 20px auto; text-align: center; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 90%; width: 390px;line-height:31px;font-size:22px;">
    Du bist nicht angemeldet.<br><br>
    <a href="/index" style="color: blue; text-decoration: underline;font-size:17px;">
    Zur&uuml;ck</a></div>';

endif;

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

</head>


</html>