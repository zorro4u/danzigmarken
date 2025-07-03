<?php
session_start();

#require_once __DIR__.'/../functions/database.func.php';     // Datenbank-Verbindung $pdo
require_once __DIR__.'/../auth/includes/auth.func.php';

/*
// Nutzer angemeldet?
if (!isCheckedIn()) checkUser();

// Nutzer nicht angemeldet? Dann weg hier ...
if (!isset($_SESSION['userid'])) {
    header("location: ../auth/login.php");
    exit;
}

// Nutzer kein Admin? Dann auch weg hier ...
if ((int)$_SESSION['su'] !== 1) {
    header("location: {$_SESSION['lastsite']}");
    exit;
}

*/

class Download
{
    public static function pdfDownloadSeite()
    {
        $file_dir = $_SERVER['DOCUMENT_ROOT']."/download";
        $file_name = "dzg_90";
        $file_ext = ".pdf";
        $ffn = $file_dir.'/'.$file_name.$file_ext;
        $file_save_as = basename($ffn);

        if (file_exists($ffn)) {
            header("Content-Type:application/pdf");
            header("Content-Disposition: attachment; filename=\"{$file_save_as}\"");
            header("Cache-Control: max-age=0");
            header("Cache-Control: max-age=1");
            header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
            header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header ('Pragma: public'); // HTTP/1.0
            header("Content-Description: File Transfer");
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: ".filesize($ffn));

            ob_clean();
            flush();
            readfile($ffn);
            #rename($ffn, $file_dir.'/'.$now.$file_ext);      # Datei umbenennen
            #unlink($ffn);                                    # Datei löschen
        }
    }
}


Download::pdfDownloadSeite();

// DB-Verbindung schließen
#require_once __DIR__.'/../includes/close_db.inc.php';
$pdo = null;



