<?php
session_start();
require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/mail/Mailcfg.php";

// Überprüfen, ob der Benutzer eingeloggt ist
if (isset($_SESSION['loggedin'])
    && $_SESSION['loggedin'] === true):


// Datei zum Leeren der IP-Adressen
#$logFile = 'log/ip_log.txt';
$logFile = Mailcfg::$maillogFile;

// Überprüfe, ob die Datei existiert
if (file_exists($logFile)) {

    // die Datei leeren
    file_put_contents($logFile, "", LOCK_EX);
    echo '
    <div style="font-family: Verdana; color: green; border: 1px solid black; padding: 10px; border-radius: 5px; margin: 20 auto 20px auto; text-align: center; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 90%; width: 390px;line-height:31px;font-size:22px;">
    Die IP-Logdatei wurde geleert.<br><br>
    <a href="/account/admin" style="color: blue; text-decoration: underline;font-size:17px;">
    Zur&uuml;ck</a></div>';

} else {
    echo '
    <div style="font-family: Verdana; color: red; border: 1px solid black; padding: 10px; border-radius: 5px; margin: 20 auto 20px auto; text-align: center; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); max-width: 90%; width: 390px;line-height:31px;font-size:22px;">
    Die IP-Logdatei existiert nicht.<br><br>
    <a href="maillog_show" style="color: blue; text-decoration: underline;font-size:17px;">
    Zur&uuml;ck</a></div>';
}

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
    <title>Delete Logfile</title>

</head>


</html>