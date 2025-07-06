<?php
date_default_timezone_set('Europe/Berlin');

// Anmeldedaten laden
require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/db/account_data.php";


// Konfiguration des Mail-Moduls

// SMTP Funktion aktivieren/konfigurieren
$smtp = [];
$smtp['enabled'] = 0;           // Soll das Kontaktformular E-Mails über einen externen SMTP Server versenden? Ja = 1, Nein = 0
#$smtp['encryption'] = 'tls';   // Die Art der Verschlüsselung, die bei der Verbindung mit Ihrem SMTP Server verwendet wird: '', 'ssl' oder 'tls'
#$smtp['port'] = 587;           // Der TCP Port, unter welchem Ihr SMTP Server erreichbar ist. tls-587, ssl-465
#$smtp['encryption'] = 'ssl';   // Die Art der Verschlüsselung, die bei der Verbindung mit Ihrem SMTP Server verwendet wird: '', 'ssl' oder 'tls'
#$smtp['port'] = 465;           // Der TCP Port, unter welchem Ihr SMTP Server erreichbar ist. tls-587, ssl-465
$smtp['debug'] = 0;             // Das Debuglevel (0 - 4)

// SET-0 via rainbow
$smtp['from_name'] = "danzigmarken.de";
$smtp['from_addr'] = $mailfrom0;
$smtp['mail_host'] = 'danzigmarken.de'; // Der Host, unter welchem der SMTP Server erreichbar ist. (bspw. smtp.gmail.com)
$smtp['encryption'] = 'ssl';            // Die Art der Verschlüsselung, die bei der Verbindung mit Ihrem SMTP Server verwendet wird: '', 'ssl' oder 'tls'
$smtp['smtp_port'] = 465;               // Der TCP Port, unter welchem Ihr SMTP Server erreichbar ist. tls-587, ssl-465
$smtp['login_usr'] = $mailusr0;         // Der Benutzername, mit welchem Sie sich bei Ihrem SMTP Server authentifizieren. (kann u.U. die oben genannte E-Mail Adresse sein!)
$smtp['login_pwd'] = $mailpwd0;         // Das Passwort, mit welchem Sie sich bei Ihrem SMTP Server authentifizieren.

// SET-1 via steffen.1
$smtp1['from_name'] = "danzigmarken.de";
$smtp1['from_addr'] = $mailfrom1;
$smtp1['mail_host'] = 'smtp.web.de';
$smtp1['encryption'] = 'tls';
$smtp1['smtp_port'] = 587;
$smtp1['login_usr'] = $mailusr1;
$smtp1['login_pwd'] = $mailpwd1;

// SET-2 via heinz.2
$smtp2['from_name'] = "danzigmarken.de";
$smtp2['from_addr'] = $mailfrom2;
$smtp2['mail_host'] = 'mail.gmx.net';
$smtp2['encryption'] = 'tls';
$smtp2['smtp_port'] = 587;
$smtp2['login_usr'] = $mailusr2;
$smtp2['login_pwd'] = $mailpwd2;


// Weitere Einstellungen //
$cfg['Kopie_senden'] = 0;           // 0 = keine Kopie senden   1 = Kopie nur bei Zustimmung senden   2 = immer eine Kopie senden (ungefragt)
$cfg['HTML5_FEHLERMELDUNGEN'] = 1;  // 0 = Ohne HTML5 Fehlermeldungen    1 = Mit HTML5 Fehlermeldungen


/* ---------------------------------- */

$cfg['Loading_Spinner'] = 0;                    // 0 = Ohne Loading Spinner im Senden-Button    1 = Mit Loading Spinner im Senden-Button
$cfg['Erfolgsmeldung'] = 1;                     // 0 = Ohne Erfolgsmeldung (Weiterleitung zur Datei danke.php)     1 = Mit Erfolgsmeldung (Keine Weiterleitung zur Datei danke.php)
$danke = "danke.php";                           // Pfad zur Danke Seite. "danke.php" kann durch einen Link/URL ersetzt werden. (muss mit "http://www." anfangen!) Die entsprechende Danke Seite kann mit dem nachfolgenden Script auch außerhalb des iFrame angezeigt werden: https://www.kontaktformular.com/integrierung-script-php-kontakt-formular.html#outsideiframe
$cfg['Datenschutz_Erklaerung'] = 0;             // 0 = Ohne Datenschutzerklärung    1 = Mit Datenschutzerklärung
$datenschutzerklaerung = "datenschutz.php";     // Pfad zur Datenschutzerklärung. "datenschutz.php" kann durch einen Link/URL ersetzt werden. (muss mit "http://www." anfangen!)


// Spamschutz - Einstellungen   // (Eine durchdachte IP Blockierung ist ebenso möglich. Siehe: https://www.kontaktformular.com/kontaktformular-spamschutz-captcha-badword-filter-zeitsperre-honeypot.html)
$cfg['Sicherheitscode'] = 1;    // 0 = Ohne Sicherheitscode   1 = Mit Sicherheitscode
$cfg['Sicherheitsfrage'] = 0;   // 0 = Ohne Sicherheitsfrage   1 = Mit Sicherheitsfrage
$cfg['Honeypot'] = 1;           // 0 = Ohne Honeypot   1 = Mit Honeypot
$cfg['Zeitsperre'] = 5;         // Mindest-Anzahl der Sekunden zwischen Anzeigen und Senden des Formulars  	0 = Ohne Zeitsperre
$cfg['Klick-Check'] = 1;        // 0 = Ohne Klick-Check   1 = Mit Klick-Check
$cfg['Links'] = 10;             // Anzahl der maximal erlaubten Links (0 = keine Links erlaubt)
$cfg['Badwordfields'] = 'name, email, message';                 // Die Namen der Felder, die bei dem Bad Word Filter geprüft werden sollen - Groß- und Kleinschreibung beachten!
$cfg['Badwordfilter'] = 'sex%, pussy%, porn%, %.ru, %.ru/%';    // Begriffe für den Bad Word Filter   0 oder leer = Ohne Bad Word Filter
// Funktionsweise des Bad Word Filters:
// badword = matcht, wenn das Bad Word als ganzes Wort enthalten ist
// badword% = matcht, wenn das Bad Word enthalten ist UND wenn ein Wort mit dem Bad Word beginnt
// %badword = matcht, wenn das Bad Word enthalten ist UND wenn ein Wort mit dem Bad Word endet
// %badword% = matcht, wenn das Bad Word enthalten ist UND wenn ein Wort das Bad Word enthält


// Aufrufe des Kontaktformulars (pro IP Adresse) limitieren
// ACHTUNG: Die Datei rate_limiting/log/ip_log.txt benötigt Schreibrechte: chmod 666
$cfg['Aufrufe_limitieren'] = 1;     // 0 = Keine Limitierung der Aufrufe  1 = Aufrufe des Kontaktformulars werden pro IP Adresse limitiert (effektiver DDoS-Schutz!)
$Maximale_Aufrufe = 99;             // Maximale Aufrufe für eine IP Adresse festlegen (Empfehlung: 50 Aufrufe)
$Passwort_fuer_Login_Bereich = 'log-admin'; // Login-Bereich für Log-Datei im Browser öffnen: rate_limiting/login.php. Bitte leeren Sie über den Login-Bereich regelmäßig die Log-Datei.


// Einstellungen für Upload-Funktion
$cfg['NUM_ATTACHMENT_FIELDS'] = 0;	    // Anzahl der Upload-Felder
$cfg['UPLOAD_ACTIVE'] = 1;		        // 1 = Dateianhang wird via Mail gesendet (Standard) 2 = Dateianhang wird in ein Verzeichnis hochgeladen. (ergänzen Sie die unten stehenden Angaben)
$cfg['WHITELIST_EXT'] = 'pdf|png|jpg';	// Erlaubte Dateiendungen - Beispiel: pdf|png|jpg
$cfg['MAX_FILE_SIZE'] = 1024;		    // Maximale Größe von einer Datei in KB. (diese Option ist abhängig von den PHP und Server Einstellungen)
$cfg['MAX_ATTACHMENT_SIZE'] = 2048;	    // Maximale Größe von mehreren Dateien in KB. (bei mehr als 1 Uploadfeld)
$cfg['BLACKLIST_IP'] = ['12.345.67.89'];	// Gesperrte IPs - Beispiel: array('192.168.1.2', '192.168.2.4');


// Vervollständigen Sie die nachfolgenden Angaben, sofern der Dateianhang in ein Verzeichnis hochgeladen werden soll
$cfg['UPLOAD_FOLDER'] = 'upload';	                            // Das Verzeichnis "upload" muss erstellt werden. Dieses benötigt Schreibrechte. (chmod 777)
$cfg['DOWNLOAD_URL'] = 'https://www.danzigmarken.de/download';	// URL zum Kontaktformular (ohne / am Ende!)


// Maximale Zeichenlänge der Felder definieren //
$zeichenlaenge_firma = "50";    // Maximale Zeichen - Feld "Firma" (zwischen den Anführungszeichen)
$zeichenlaenge_vorname = "50";  // Maximale Zeichen - Feld "Vorname" (zwischen den Anführungszeichen)
$zeichenlaenge_name = "50";     // Maximale Zeichen - Feld "Nachname" (zwischen den Anführungszeichen)
$zeichenlaenge_email = "50";    // Maximale Zeichen - Feld "E-Mail" (zwischen den Anführungszeichen)
$zeichenlaenge_telefon = "50";  // Maximale Zeichen - Feld "Telefon" (zwischen den Anführungszeichen)
$zeichenlaenge_betreff = "50";  // Maximale Zeichen - Feld "Betreff" (zwischen den Anführungszeichen)


$mail_logfile = $_SERVER['DOCUMENT_ROOT']."/../data/dzg/mail/maillog.txt";



/* ---------------------------------- */
/*
// Die SMTP Funktion kann im nachfolgenden Abschnitt aktiviert werden. Wichtig: Auf Ihrem Server muss mind. PHP 7.2 oder höher installiert sein. Die aktuelle PHP Version können Sie prüfen, indem Sie die Datei phpinfo.php im Browser aufrufen. //
#$empfaenger = "viele@gmx.net";
$empfaenger = "s.viele@web.de";  // Ihre E-Mail Adresse (idealerweise eine Domain-eigene E-Mail Adresse; z.B. info@ihre-domain.com) Bei Problemen: https://www.kontaktformular.com/faq-script-php-kontakt-formular.html#keine-mail-erhalten
$ihrname = "'Support-Team'";     // Ihr Name

$smtp = array();
$smtp['enabled'] = 1;               // Soll das Kontaktformular E-Mails über einen SMTP Server versenden? Ja = 1, Nein = 0
#$smtp['host'] = 'mail.gmx.net';    // Der Host, unter welchem der SMTP Server erreichbar ist. (bspw. smtp.gmail.com)
$smtp['host'] = 'smtp.web.de';      // Der Host, unter welchem der SMTP Server erreichbar ist. (bspw. smtp.gmail.com)
#$smtp['user'] = 'viele@gmx.net';   // Der Benutzername, mit welchem Sie sich bei Ihrem SMTP Server authentifizieren. (kann u.U. die oben genannte E-Mail Adresse sein!)
$smtp['user'] = 's.viele';          // Der Benutzername, mit welchem Sie sich bei Ihrem SMTP Server authentifizieren. (kann u.U. die oben genannte E-Mail Adresse sein!)
#$smtp['password'] = '******';      // Das Passwort, mit welchem Sie sich bei Ihrem SMTP Server authentifizieren.
$smtp['password'] = '*****';        // Das Passwort, mit welchem Sie sich bei Ihrem SMTP Server authentifizieren.
$smtp['encryption'] = 'tls';        // Die Art der Verschlüsselung, die bei der Verbindung mit Ihrem SMTP Server verwendet wird: '', 'ssl' oder 'tls'
#$smtp['encryption'] = 'ssl';       // Die Art der Verschlüsselung, die bei der Verbindung mit Ihrem SMTP Server verwendet wird: '', 'ssl' oder 'tls'
$smtp['port'] = 587;                // Der TCP Port, unter welchem Ihr SMTP Server erreichbar ist. tls-587, ssl-465
#$smtp['port'] = 465;               // Der TCP Port, unter welchem Ihr SMTP Server erreichbar ist. tls-587, ssl-465
$smtp['debug'] = 0;                 // Das Debuglevel (0 - 4)
*/