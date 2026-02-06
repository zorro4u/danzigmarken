<?php
namespace Dzg;
require_once __DIR__.'/Details.php';
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';
use PDO, PDOException;

/***********************
 * Summary of Change
 * Webseite:
 *
 * __public__
 * show()
 */
class Change extends Details
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $pdo;
    protected static array $abfrage_db;


    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connectMyDB();
        }

        self::siteEntryCheck();
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::siteOutput();
        Footer::show();

        // Datenbank schließen
        self::$pdo = Null;
    }


    protected static function siteEntryCheck()
    {
        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::isCheckedIn()) {
            header("location: /auth/login.php");
            exit;
        }

        // PlausiCheck Seiten-ID
        parent::siteEntryCheck();
    }


    public static function dataPreparation()
    {
        // Details Hauptroutine ausführen
        parent::dataPreparation();

        // Seiten-Check bisher okay
        if (self::$show_form):

        # self:: kommt hier von Details
        $max_file = self::$max_file;
        $spaltennamen = self::$spaltennamen;
        $abfrage_db = [];

        // diese Einträge nicht anzeigen
        unset($spaltennamen['created']);
        unset($spaltennamen['changed']);

        // noch zusätzliche Spalte (im Vgl. zur Detail-Seite) zur Ausgabe anhängen
        #$spaltennamen += ['kat23' => 'Bildursprung'];
        $spaltennamen += ['print' => 'druckbar'];

        // die Spalte(n) am Schluss ausgeben
        $tmp_prn['print'] = $spaltennamen['print'];
        unset($spaltennamen['print']);
        $spaltennamen += $tmp_prn;


        // wenn eine Bildgruppe besteht, dann IDs anzeigen, um Gruppe ändern zu können
        if ($max_file > 1) {
            #$spaltennamen['fid'] = 'Bild.ID';
            #$spaltennamen['gid'] = 'Marken.ID';
        }

        // zusätzliche Vorbereitungen wegen Formularverwendung
        //
        // DropDown-Bezeichnungen holen für Thema, Frankatur, Ansicht, Attest
        $stmt = "SELECT * FROM (
            SELECT thema FROM dzg_dirsub2 ORDER BY thema) AS s1, (
            SELECT kat15 FROM dzg_kat15 ORDER BY kat15) AS s2, (
            SELECT kat20 FROM dzg_kat20 ORDER BY kat20 DESC) AS s3, (
            SELECT kat21 FROM dzg_kat21 ORDER BY kat21) AS s4";

        $results = Database::sendSQL($stmt, [], 'fetchall', 'num');

        $qry_arr = [];
        // abfrage_array nach Spalten (select Statement) aufsplitten
        foreach ($results AS $entry) {
            foreach($entry AS $k=>$v) {
                $qry_arr[$k] []= $v; {
                }
            }
        }

        // doppelte Einträge in den Spalten aufgrund der kombinerten DB-Abfrage löschen
        // [$theme_db, $franka_db, $ansicht_db, $attest_db] = $abfrage_db;
        foreach ($qry_arr AS $col) {
            $abfrage_db []= array_values(array_unique($col));
        }

        // temp. Hilfs-Variablen löschen
        unset($stmt, $results, $qry_arr);

        // globale Variablen setzen
        self::$spaltennamen = $spaltennamen;
        self::$abfrage_db = $abfrage_db;

        endif;      # Seiten-Check okay
    }


    /***********************
     * Formular-Eingabe verarbeiten
     * und an DB schicken
     */
    public static function formEvaluation()
    {
        // kat10-kat17, datum -> dzg_group
        // kat21-24           -> dzg_file
        // kat20              -> kat20(kat20)
        // kat21              -> kat21(kat21)
        // kat15              -> kat15(kat15)
        // thema              -> dzg_dirsub2(thema)

        $userid = $_SESSION['userid'];
        $stamps = self::$stamps;
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $error_arr = self::$error_arr;
        $success_msg = "";
        $error2 = False;


        // Seiten-Check bisher okay, Formularauswertung starten
        if ($show_form):

        $pdo_db = self::$pdo;
        $max_file = self::$max_file;
        $akt_file_id = self::$akt_file_id;
        $akt_file_idx = self::$akt_file_idx;
        $gid = self::$gid;
        $remaddr = $_SERVER['REMOTE_ADDR'];     # abfragende Adresse


        // ABBRUCH - Button
        //
        if (isset($_POST['cancel']) &&
            $_POST['cancel'] === "Cancel" &&
            strtoupper($_SERVER["REQUEST_METHOD"] === "POST"))
        {
            unset($_POST['cancel']);
            header ("Location: /details.php?id={$akt_file_id}");
            exit;
        }


        // DELETE - Button
        //
        if (isset($_POST['delete']) &&
            $_POST['delete'] == "Delete" &&
            strtoupper($_SERVER["REQUEST_METHOD"] === "POST"))
        {
            // Soft-Delete durch setzen von: flag 'deakt'
            // wenn ($max_file < 2)
            // dann auch Marke löschen und
            // nach Änderung zur Übersicht wechseln oder leere Datenseite anzeigen
            //
            unset($_POST['delete']);
            unset($_POST['restore']);
            $_POST = [];

            $data = [
                ':by' => $userid,       # int
                ':id' => $akt_file_id,  # int
                ':ip' => $remaddr ];
            $stmt1 = "UPDATE dzg_file SET deakt=1, chg_ip=:ip, chg_by=:by WHERE id=:id";
            $stmt2 = "UPDATE dzg_fileplace SET deakt=1, chg_ip=:ip, chg_by=:by WHERE id=:id";
            $stmt3 = "UPDATE dzg_group SET deakt=1, chg_ip=:ip, chg_by=:by WHERE id=:id";

            Database::sendSQL($stmt1, $data);
            Database::sendSQL($stmt2, $data);

            // wenn nur 1 Datei, dann auch Gruppeneintrag löschen
            if ($max_file < 2) {
                Database::sendSQL($stmt3, $data);
            }

            // zum vorherigen Element wechseln
            # data-ajax=false
            header("Location: /change.php?id={$_SESSION['prev']}");
            exit;

            // wenn Datei "gelöscht", dann
            // komplett leere Anzeige, statt sofort zur Übersicht zu wechseln
            // Anzeige des Wiederherstellen-Buttons
            // -- wird nicht weiter verwendet / 22.5.25 --
            // Problem mit Rücksprung, Sprungmarken, ...
            foreach ($ffn AS $k=>$v)
                $ffn[$k] = '';      # Bildpfade löschen
            foreach ($stamps[$akt_file_idx] AS $k=>$v)
                $stamps[$akt_file_idx][$k] = '';
            $stamps[$akt_file_idx]['deakt'] = '1';
            $success_msg = 'gelöscht';

        }   # ende Delete-Button


        // WIEDERHERSTELLEN - Button, ('Delete' rückgängig machen)
        // -- wird nicht weiter verwendet / 22.5.25 --
        //
        if (isset($_POST['restore']) &&
            $_POST['restore'] == "Restore" &&
            strtoupper($_SERVER["REQUEST_METHOD"] === "POST"))
        {
            unset($_POST['restore']);
            unset($_POST['delete']);
            $_POST = [];

            $data = [
                ':by' => $userid,       # int
                ':id' => $akt_file_id,  # int
                ':ip' => $remaddr ];
            $stmt1 = "UPDATE dzg_file SET deakt=0, chg_ip=:ip, chg_by=:by WHERE id=:id";
            $stmt2 = "UPDATE dzg_fileplace SET deakt=0, chg_ip=:ip, chg_by=:by WHERE id=:id";
            $stmt3 = "UPDATE dzg_group SET deakt=0, chg_ip=:ip, chg_by=:by WHERE id=:id";

            Database::sendSQL($stmt1, $data);
            Database::sendSQL($stmt2, $data);

            // wenn nur 1 Datei, dann auch Gruppe wieder aktivieren
            if ($max_file < 2) {
                Database::sendSQL($stmt3, $data);
            }

            $stamps[$akt_file_idx]['deakt'] = '0';
            $success_msg = 'wiederhergestellt';

        }   # ende Wiederherstellen-Button


        // TRENNEN - Button
        //
        if (isset($_POST['split']) &&
            $_POST['split'] == "Split" &&
            strtoupper($_SERVER["REQUEST_METHOD"] === "POST"))
        {
            unset($_POST['split']);
            $_POST = [];

            if ($max_file > 1) {
                // zum Separieren entweder Notiz1 hinzufügen
                // (oder DB Unique Anweisung aufheben)
                $stamps[$akt_file_idx]['kat17'] .= "_ALT: {$gid}_";

                // neuen Datensatz 'Marke' anlegen
                $stmt1 = "INSERT INTO dzg_group (id_thema, datum, kat10, kat11, kat12, kat13,
                            kat14, kat15, kat16, kat17, chg_ip, chg_by, mirror)
                    VALUES (:id_thema, :datum, :kat10, :kat11, :kat12, :kat13, :kat14, :kat15,
                            :kat16, :kat17, :ip, :by, 1)";

                $data1 = [
                    ':id_thema' => (int)$stamps[$akt_file_idx]['id_thema'],  # int
                    ':datum'    => $stamps[$akt_file_idx]['datum'],
                    ':kat10'    => $stamps[$akt_file_idx]['kat10'],
                    ':kat11'    => $stamps[$akt_file_idx]['kat11'],
                    ':kat12'    => $stamps[$akt_file_idx]['kat12'],
                    ':kat13'    => $stamps[$akt_file_idx]['kat13'],
                    ':kat14'    => $stamps[$akt_file_idx]['kat14'],
                    ':kat15'    => $stamps[$akt_file_idx]['kat15'],
                    ':kat16'    => $stamps[$akt_file_idx]['kat16'],
                    ':kat17'    => $stamps[$akt_file_idx]['kat17'],
                    ':ip'       => $remaddr,
                    ':by'       => $userid ];

                // TODO: Funktioniert dann lastinsertId() ?
                #Database::sendSQL($stmt1, $data1);

                try {
                    $qry = $pdo_db->prepare($stmt1);
                    foreach ($data1 AS $k => &$v) {
                        if (is_int($v)) {
                            $qry->bindParam($k, $v, PDO::PARAM_INT);
                        } else {
                            $qry->bindParam($k, $v);
                        }
                    }
                    $qry->execute();

                    $new_gid = (int)$pdo_db->lastInsertId();

                } catch(PDOException $e) {
                    $error_arr []= '--- nix geschrieben ---'.$e->getMessage();
                }

                // Datei mit neuer Marke verknüpfen
                $stmt2 = "UPDATE dzg_file SET id_stamp=:new_gid, chg_ip=:ip, chg_by=:by
                            WHERE id=:akt_file_id";
                $data2 = [
                    ':akt_file_id'  => $akt_file_id,    # int
                    ':new_gid'      => $new_gid,  # int
                    ':ip'           => $remaddr,
                    ':by'           => $userid ];
                Database::sendSQL($stmt2, $data2);

                $success_msg = 'aus Gruppe gelöst';
                header ("Location: /change.php?id={$akt_file_id}");
            } # max_file
        }     # ende Trennen-Buttton


        // ÄNDERN - Button
        //
        if (isset($_POST['change']) &&
            $_POST['change'] == "Change" &&
            strtoupper($_SERVER["REQUEST_METHOD"] === "POST"))
        {
            unset($_POST['change']);

            if (!isset($_POST['print'])) $_POST['print'] = 'XXX';

            // Liste mit [Spalte => Änderg.werten] erstellen
            // (Hinweis: mit Leerz. kann DB-Eintrag überschrieben/gelöscht werden)
            foreach ($_POST AS $spalte => $input_wert) {

                if (!empty($input_wert)) {
                    // Plausi-Check des Eingabewerts, bevor an DB gesendet wird !!!
                    // erlaubte Zeichen definieren,
                    // SQL-kritische: [%;´`\'\"\-\{\}\[\]\*\/\\\\ (AND)(OR)]
                    $regex_wert = "/^[\w\s ×÷=<>:,!@#€&§~£¥₩₽° \^\$\.\|\?\*\+\-\(\)\[\]]{0,100}$/u";
                    $regex_wert_no = "/[^\w\s ×÷=<>:,!@#€&§~£¥₩₽° \^\$\.\|\?\*\+\-\(\)\[\]]/mu";
                    $regex_digi    = "/^\d{1,1000000}$/";
                    $regex_digi_no = "/\D/";
                    $regex_wrapper = "/\r\r|\r\n|\n\r|\n\n|\n|\r|<br>/";  # Zeilenumbrüche

                    // html-tags, Blackslash, Leerzeichen anfangs/ende entfernen
                    // auth.func.php: cleanInput($data) = strip_tags(stripslashes(trim($data)));

                    $input_wert = Tools::cleanInput($input_wert);
                    $input_wert = preg_replace($regex_wrapper, "", $input_wert);

                    $regex = ($spalte === "gid") ? $regex_digi_no : $regex_wert_no;
                    if ($input_wert !== "" &&
                        preg_match_all($regex, $input_wert, $match))
                    {
                        $error_arr []= 'unzulässige Zeichen eingegeben: " '.
                            htmlentities(implode(" ", $match[0])).' "';
                    }

                    #$input_wert = htmlspecialchars($input_wert);

                    // Sucheingabe okay
                    if (empty($error_arr)) {

                        // wenn Leerfeld (= 'XXX'-code)
                        // dann nicht in $input_arr schreiben u.
                        // weiter mit nächstem Element in Schleife machen
                        if (empty($stamps[$akt_file_idx][$spalte]) && $input_wert == 'XXX') {
                            continue;
                        }

                        if ($input_wert == 'XXX')
                            $input_wert = ($spalte == 'print') ? 0 : '';

                        // Datum umformatieren wie in DB (deutsch -> englisch)
                        if ($spalte == 'datum')
                            $input_wert = date('Y-m-d', strtotime($input_wert));

                        // keine Veränderung
                        if ($stamps[$akt_file_idx][$spalte] == $input_wert) continue;

                        // wenn Veränderung, dann in $input_arr schreiben
                        if ($stamps[$akt_file_idx][$spalte] != $input_wert) {
                            $input[$spalte] = htmlspecialchars($input_wert);
                            $stamps[$akt_file_idx][$spalte] = $input_wert; # für Anzeige im Formular
                        }
                    }
                }
            }

            if (!empty($input)) {
                /*
                Update der Spalten entspr. der Abhängigkeiten!!
                thema              -> dzg_dirsub2(thema)
                kat20              -> kat20(kat20)
                kat10-kat14, datum -> dzg_group, fk:thema
                kat21-24           -> dzg_file, fk:thema,kat20,stamp,(suff,liste)
                */

                if (isset($input['kat10'])) $s['kat10'] = $input['kat10'];
                if (isset($input['kat11'])) $s['kat11'] = $input['kat11'];
                if (isset($input['kat12'])) $s['kat12'] = $input['kat12'];
                if (isset($input['kat13'])) $s['kat13'] = $input['kat13'];
                if (isset($input['kat14'])) $s['kat14'] = $input['kat14'];
                if (isset($input['kat15'])) $s['kat15'] = $input['kat15'];
                if (isset($input['kat16'])) $s['kat16'] = $input['kat16'];
                if (isset($input['kat17'])) $s['kat17'] = $input['kat17'];

                if (isset($input['kat20'])) $d['kat20'] = $input['kat20'];
                if (isset($input['kat21'])) $d['kat21'] = $input['kat21'];
                if (isset($input['kat22'])) $d['kat22'] = $input['kat22'];
                if (isset($input['kat23'])) $d['kat23'] = $input['kat23'];
                if (isset($input['kat24'])) $d['kat24'] = $input['kat24'];
                if (isset($input['deakt'])) $d['deakt'] = $input['deakt'];
                if (isset($input['datum'])) $s['datum'] = $input['datum'];
                if (isset($input['gid'])) $d['id_stamp'] = (int)$input['gid'];
                if (isset($input['print'])) $d['print'] = (int)$input['print'];
/*
                if (isset($input['kat20'])) {
                    $d['kat20'] = $input['kat20'];
                    if ($d['kat20'] == 'XXX') $d['kat20'] = '';
                }

                if (isset($input['kat21'])) {
                    $d['kat21'] = $input['kat21'];
                    if ($d['kat21'] == 'XXX') $d['kat21'] = '';
                }

                if (isset($input['kat15'])) {
                    $s['kat15'] = $input['kat15'];
                    if ($s['kat15'] == 'XXX') $s['kat15'] = '';
                }
*/
                if (isset($input['thema'])) {
                    // id holen
                    $stmt = "SELECT id FROM dzg_dirsub2 WHERE thema = :thema";
                    $data = [':thema' => $input['thema']];
                    $id_theme = (int)Database::sendSQL($stmt, $data, 'fetch', 'num')[0];
                    $s['id_thema'] = $id_theme;
                    $d['id_thema'] = $id_theme;
                }


                // update gruppe
                if (!empty($s)) {
                    $s['chg_ip'] = $remaddr;
                    $set = '';
                    $data = [];

                    foreach ($s AS $spalte => $input_wert) {
                        $set .= $spalte."=:".$spalte.", ";
                        $data[$spalte] = $input_wert;
                    }

                    // letzten 2 Zeichen (Komma) löschen, Leerz. anhängen
                    $set = substr($set, 0, -2)." ";
                    $data += [':id' => $gid];    # int
                    $stmt = "UPDATE dzg_group SET {$set} WHERE id=:id";
                    Database::sendSQL($stmt, $data);
                    $success_msg = 'Änderung ausgeführt.';
                }

                // update datei
                if (!empty($d)) {
                    $d['ip'] = $remaddr;
                    $set1 = '';
                    $data1 = [];
                    $data2 = [];

                    foreach ($d AS $spalte => $input_wert) {
                        $set1 .= $spalte."=:".$spalte.", ";
                        $data1[$spalte] = $input_wert;
                    }

                    // letzten 2 Zeichen (Komma) löschen, Leerz. anhängen
                    $set1 = substr($set1, 0, -2)." ";
                    $data1 += [':id' => $akt_file_id];
                    $stmt1 = "UPDATE dzg_file SET {$set1} WHERE id=:id";

                    # trouble mit UNIQUE key
                    $stmt2 = "UPDATE dzg_group SET deakt=1, mirror=1 WHERE id=:id";
                    #$stmt2 = "DELETE FROM dzg_group WHERE id = :id";
                    #$stmt2 = "DELETE FROM dzg_group WHERE id = :id AND kat17 LIKE '%_ALT:%';";
                    $data2 = [':id' => $gid];

                    $stmt3 = "UPDATE sqlite_sequence SET seq=(SELECT MAX(id) FROM dzg_group)
                              WHERE name='dzg_group'";      # -> lastNR
                    #$stmt3 = "DELETE FROM sqlite_sequence WHERE name = 'dzg_group'";
                    # "UPDATE sqlite_sequence SET seq=0 WHERE name='dzg_group'";    # -> 0
                    # "DELETE FROM sqlite_sequence WHERE name = 'dzg_group'";
                    # "SELECT * FROM sqlite_sequence ORDER BY name";    # -> view autoincrement

                    Database::sendSQL($stmt1, $data1);

                    // wenn MarkenID geändert wird, und ($max_file < 2) dann auch Marke löschen
                    if (isset($input['gid']) && ($max_file < 2)) {
                        Database::sendSQL($stmt2, $data2);
                    }

                    $success_msg = 'Änderung ausgeführt.';
                }
            }
        } # ende Ändern-Button


        // Formularseite trotz Fehler bei der Änderungseingabe anzeigen
        $error2 = !empty($error_arr) ? true : false;

        endif;  // Plausi-Check okay


        $error_msg = !empty($error_arr)
            ? implode("<br>", $error_arr)
            : "";

        /*---
        if (empty($_SESSION['su'])) {
            $error_msg = "Diese Seite wird überarbeitet und funktioniert im Augenblick nicht.";
        } ---*/

        $show_form = ($error_msg === "" OR $error2) ? true : false;
        $status_message = Tools::statusOut($success_msg, $error_msg);

        // globale Variablen setzen
        self::$stamps = $stamps;
        self::$show_form = $show_form;
        self::$status_message = $status_message;
    }


    /***********************
     * HTML erzeugen
     */
    protected static function siteOutput()
    {
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $output = "<div class='container'>";
        if (!$show_form):
            $output .= $status_message;
        else:

        // Seiten-Check okay, Seite starten
        $spaltennamen = self::$spaltennamen;
        $stamps = self::$stamps;
        $akt_file_idx = self::$akt_file_idx;
        $max_file = self::$max_file;
        $prev = self::$prev;
        $next = self::$next;
        [$theme_db, $franka_db, $ansicht_db, $attest_db] = self::$abfrage_db;

        $output .= $status_message;
        $output .= "<div class='center-detail'>";
        $output .= "<div class='main-detail'>";

        // Formular-Eingabe-Beginn
        $output .= "<form class='detail-kat-grid' method='POST' onsubmit=''>";
        #$output .= "<form class='main-detail' method='POST' onsubmit=''>";


        // LINKE SEITE
        //
        $output .= "<div class='main-detail-left'>";
        $output .= "<div class='detail-kat-tab'>";
        #$output .= "<form class='detail-kat-tab' method='POST' onsubmit=''>";
        $output .= "<table style='border-spacing: 0px 10px; border-collapse: separate;'><tbody>";

        foreach ($spaltennamen as $spalte_db => $spalte_web) {

            // Daten-Generierung
            $data = (!empty($stamps[$akt_file_idx][$spalte_db]))
                ? htmlspecialchars($stamps[$akt_file_idx][$spalte_db])
                : '';

            //  angepasste Daten-Generierung, Ausgabe-Formatierung
            //
            // Bildspeicherpfad (nicht genutzt)
            if ($spalte_web == 'Bildpfad') {
                $data = htmlspecialchars($stamps[$akt_file_idx]['original']);

                $output .= "<tr><td class='detail-key'>{$spalte_web}</td>";
                $output .= "<td class='detail-val' style='border: 1px solid black;
                            padding-left:12px; color:hsl(0,0%,45%);font-size: 95%;'>
                            {$data}</td></tr>";

            // "Dropdown Thema"
            } elseif ($spalte_web == 'Thema') {
                $output .= "<tr><td class='detail-key'>{$spalte_web}</td>";
                $output .= "<td class='detail-val' style='border: 1px solid black;
                            background-color:hsl(54,73%,97%)'>";
                $output .= "<select class='filter-option' name='thema' onchange=''
                            style='background-color:transparent;'>";

                foreach ($theme_db as $key => $opt_val) {
                    $value = ($opt_val == $stamps[$akt_file_idx]['thema'])
                        ? "'{$opt_val}' selected"
                        : "'{$opt_val}'";
                    $output .= "<option value={$value}>{$opt_val}</option>";
                }
                $output .= "</select></td></tr>";

            // "Dropdown Frankatur"
            } elseif ($spalte_web == 'Frankatur') {
                $data = (!empty($stamps[$akt_file_idx][$spalte_db]))
                    ? htmlspecialchars($stamps[$akt_file_idx][$spalte_db])
                    : '';

                $output .= "<tr><td class='detail-key'>{$spalte_web}</td>";
                $output .= "<td class='detail-val' style='border: 1px solid black;
                            background-color:hsl(54,73%,97%)'>";
                $output .= "<select class='filter-option' name='kat15' onchange=''
                            style='background-color:transparent;'>";

                foreach ($franka_db as $key => $opt_val) {
                    if ($opt_val == '') $opt_val = 'XXX';

                    $chk = !empty($stamps[$akt_file_idx]['kat15'])
                        ? $stamps[$akt_file_idx]['kat15']
                        : 'XXX';

                    $value = ($opt_val == $chk)
                        ? "'{$opt_val}' selected"
                        : "'{$opt_val}'";

                    if ($opt_val == 'XXX') $opt_val = '';

                    $output .= "<option value={$value}>{$opt_val}</option>";
                }
                $output .= "</select></td></tr>";

            // "Dropdown Ansicht"
            } elseif ($spalte_web == 'Ansicht') {
                $output .= "<tr><td colspan='2' style='padding-top:0px;
                            border-top:1px solid hsl(0,0%,90%);'></td></tr>";
                $output .= "<tr><td class='detail-key'>{$spalte_web}</td>";
                $output .= "<td class='detail-val' style='border: 1px solid black;
                            background-color:hsl(54,73%,97%)'>";
                $output .= "<select class='filter-option' name='kat20' onchange=''
                            style='background-color:transparent;'>";

                foreach ($ansicht_db as $key => $opt_val) {
                    if ($opt_val == '') $opt_val = 'XXX';

                    $chk = !empty($stamps[$akt_file_idx]['kat20'])
                        ? $stamps[$akt_file_idx]['kat20']
                        : 'XXX';

                    $value = ($opt_val == $chk)
                        ? "'{$opt_val}' selected"
                        : "'{$opt_val}'";

                    if ($opt_val == 'XXX') $opt_val = '';

                    $output .= "<option value={$value}>{$opt_val}</option>";
                }
                $output .= "</select></td></tr>";

            // "Dropdown Attest"
            } elseif ($spalte_web == 'Attest') {
                $output .= "<tr><td class='detail-key'>{$spalte_web}</td>";
                $output .= "<td class='detail-val' style='border: 1px solid black;
                            background-color:hsl(54,73%,97%)'>";
                $output .= "<select class='filter-option' name='kat21' onchange=''
                            style='background-color:transparent;'>";

                foreach ($attest_db as $key => $opt_val) {
                    if ($opt_val == '') $opt_val = 'XXX';

                    $chk = !empty($stamps[$akt_file_idx]['kat21'])
                        ? $stamps[$akt_file_idx]['kat21']
                        : 'XXX';

                    $value = ($opt_val == $chk)
                        ? "'{$opt_val}' selected"
                        : "'{$opt_val}'";

                    if ($opt_val == 'XXX') $opt_val = '';

                    $output .= "<option value={$value}>{$opt_val}</option>";
                }
                $output .= "</select></td></tr>";

            // Bild-ID
            } elseif ($spalte_db == 'fid') {
                $output .= "<tr><td class='detail-key' style='color:hsl(0,0%,45%);'>
                            <i>{$spalte_web}</i></td>";
                $output .= "<td class='detail-val' style='border: 1px solid black;
                            padding-left:12px;color:hsl(0,0%,45%);font-size: 95%;'>
                            <i>#{$data}</i></td></tr>";

            // Druckoption
            } elseif ($spalte_db == 'print') {
                $output .= "<tr><td class='detail-key' style='color:hsl(0,0%,45%);'>
                            {$spalte_web}</td>";

                $checked = ((int)$data === 1) ? "checked" : "";
                $output .= "<td class='detail-val' style='border: 0px solid black;
                            padding-left:12px;background-colorX:hsl(54,73%,97%)'>
                            <input type='checkbox' name='{$spalte_db}' value='1'
                            id='print' class='' {$checked} /><label for='print'></label></td></tr>";

            // gleiche Ausgabe-Formatierung, angepasste Daten-Generierung
            } else {
                // Datum
                if ($spalte_web == 'Datum') {
                    $data = ($stamps[$akt_file_idx][$spalte_db])
                        ? date("d.m.Y",strtotime($stamps[$akt_file_idx][$spalte_db]))
                        : '';
                }

                $output .= "<tr><td class='detail-key'>{$spalte_web}</td>";
                $output .= "<td class='detail-val' style='border: 1px solid black;
                            background-color:hsl(54,73%,97%)'>
                            <input type='text' name='{$spalte_db}' value='{$data}' placeholder=''
                            style='border: none;background-color:transparent'></td></tr>";
            }
        }  # foreach $spaltennamen

        $output .= "</table>";
        $output .= "</div>";  # < /detail-kat-tab >
        $output .= "</div>";  # ende linke Seite


        //-------------------------------------------------
        // RECHTE SEITE - Bild
        //
        $output .= "<div class='main-detail-right'>";

        # https://css3generator.com/
        # https://www.mediaevent.de/css/farbrechner.html
        # https://www.w3schools.com/colors/colors_hsl.asp
        #style='box-shadow: 2px 2px 3px silver;
        #border: 1px solid hsl(220, 100%, 60%)

        if (!$stamps[$akt_file_idx]['deakt']) {
            $output .= "<table class=detail-pic>";
            $output .= "<tbody><tr><td><div class='detail-pic'><a href='/".
                htmlspecialchars($stamps[$akt_file_idx]['original']).
                "' title='große Ansicht'><img src='/".
                htmlspecialchars($stamps[$akt_file_idx]['small'])."' width='300' height=''
                alt='/".htmlspecialchars($stamps[$akt_file_idx]['name'])."'>
                </a></div></td></tr></tbody>";

        // Modus 'Gelöscht'
        } else {
            $output .= "<table class='detail-pic' width='300' height='300'
                        style='background-color:hsl(0,0%,97%);border:none;box-shadow:none;'>";
        }
        $output .= "</table>";


        //-------------------------------------------------
        // Thumbnail-Grid - Navigation rechte Seite
        //
        // durch Laufvariable $i in der Schleife geht keine vorzeitige Variablen-Zuweisung

        // Thumb-Datei
        # '<img src="'.htmlspecialchars($stamps[$i]['webthumb']).'" width="70" height="70"
        # alt="['.htmlspecialchars($stamps[$i]['dateiname']).']">';

        // Thumb-BLOB
        # '<img src="data:image/jpg;charset=utf8;base64,'.base64_encode($stamps[$i]['thumb-blob']).
        # '" width="70" height="70" alt="['.htmlspecialchars($stamps[$i]['dateiname']).']">';

        $output .= "<div class='detail-thumb-grid detail-gal'>";

        if ($max_file > 1) {
            foreach ($stamps as $idx => $file) {

                if ($idx != $akt_file_idx AND !$file['deakt']) {
                    $output .= "<div class='detail-thumb-blob' title='#{$file['fid']}'>
                        <a href='".$_SERVER['PHP_SELF']."?id={$file['fid']}'>
                        <img src='/".htmlspecialchars($file['thumb'])."' width='70'
                        height='70' alt='#".$file['fid']."'></a></div>";

                } elseif ($idx == $akt_file_idx AND !$file['deakt']) {
                    $output .= "<div class='detail-thumb_akt' title='#{$file['fid']}'>
                        <img src='/".htmlspecialchars($file['thumb'])."' width='70'
                        height='70' alt='#".$file['fid']."'></div>";

                } elseif ($idx != $akt_file_idx AND $file['deakt']) {
                    $output .= "<div class='detail-thumb_akt' title='#{$file['fid']}'
                        style='border:1px solid hsl(0, 0%, 95%);box-shadow:0 0 0'>
                        <a href='".$_SERVER['PHP_SELF']."?id={$file['fid']}'>
                        <table width='70' height='70'></table></a></div>";

                } else {
                    $output .= "<div class='detail-thumb_akt'
                        style='border:1px solid hsl(0, 0%, 95%);box-shadow:0 0 0''>
                        <table width='70' height='70'></table></div>";
                }
            }
        }
        $output .= "</div>";  # ende thumb-grid

        //-------------------------------------------------
        // Button-Bereich rechte Seite
        //
        $btn_split = $btn_delete = '';
        if (!$stamps[$akt_file_idx]['deakt']) {

            // - Trennen -
            $btn_split = ($max_file > 1)
                #class='button btn_chg_delete' "&emsp;&emsp;&emsp;
                ? "<formX method='POST' style='display:inline'>".
                  "<button class='btn Xbtn-primary' type='' name='split' value='Split'
                    onclick='return confirm(\"Wirklich von der Bildgruppe  - L Ö S E N -  ?\")'>
                    aus Gruppe lösen</button></formX>"
                : "";

            // - Löschen -
            #class='button btn_chg_delete  Xbtn-primary'  <formX method='POST'></formX>
            #onclick='return confirm(\"Wirklich das Bild  - L Ö S C H E N -  ?\")'
            #<input type='hidden' name='fid' value='{$akt_file_id}' />
            #<button formaction='{$_SERVER['PHP_SELF']}' class='btn' type='' name='delete'  value='Delete'>
            $akt_file_id = self::$akt_file_id;
            $btn_delete = "
                <button class='btn' type='' name='delete' value='Delete'
                onclick='return confirm(\"Wirklich das Bild  - L Ö S C H E N -  ?\")'>
                aus Bestand <b>löschen</b></button>";
        }
        $output .= "<div class='fuss'>";

        if (!empty($btn_split)) {
            $output .= "<div class='links noprint'>".$btn_split."</div>";
            $output .= "<div class='mitte'>"."</div>";
            $output .= "<div class='rechts noprint'>".$btn_delete."</div>";
        } else {
            $output .= "<div class='links'>"."</div>";
            $output .= "<div class='mitte noprint'>".$btn_delete."</div>";
            $output .= "<div class='rechts'>"."</div>";
        }

        #echo "<a class='kleingrau deaktiv2' Xhref='upload.php'>
        # [ <i class='fas fa-lock'></i> Hinzufügen ]</a>";

        $output .= "</div>";  # ende </fuss>, Button-Bereich rechte Seite

        //-------------------------------------------------
        // Button-Bereich re Seite
        //
        /*
        btn btn-primary
        button btn_chg_cancel
        button btn_chg_transmit
        button btn_chg_restore
        */
        #$output .= "<tfoot><tr><td class='detail-val' style='padding-top:15px; text-align:left'>";
        $output .= "<div class='fuss'>";

        $btn_restore = "<formX method='POST'>
            <button class='btn btn-primary' type='' name='restore' value='Restore'>
                Wiederherstellen</button></formX>";
        $btn_cancel = "<formX method='POST'>
            <button class='btn Xbtn-primary' type='' name='cancel' value='Cancel'>
                Abbrechen</button></formX>";
        $btn_okay ="
            <button class='btn btn-primary' type='submit'name='change' value='Change'
                onclick_X='return confirm(\"Wirklich Eintrag  - Ä N D E R N -  ?\")'>
                Ändern</button>";
        $btn_okayX = "";

        // - Abbrechen / Ändern -
        if (!$stamps[$akt_file_idx]['deakt']) {
            $output .= "<div class='links noprint'>".$btn_cancel."</div>";
            $output .= "<div class='mitte'>"."</div>";
            $output .= "<div class='rechts noprint'>".$btn_okay."</div>";

        // - Wiederherstellen -
        } else {
            $output .= "<div class='links noprint'></div>";
            $output .= "<div class='mitte'>".$btn_restore."</div>";
            $output .= "<div class='rechts noprint'></div>";
        }
        $output .= "</div>";  # ende </fuss>, Button-Bereich rechte Seite


        // ZURÜCK .. VOR
        //
        $label = (empty($_SESSION['idx2'])) ? "Bild" : "Gruppe";

        $output .= "<br><div class='fuss noprint' style='padding-top:0; padding-bottom:0;'>";
        // < &lt; > &gt;
        // long-arrow-left angle-double-left chevron-circle-left arrow-circle-left caret-square-left

        #<form method='POST' action='{$_SERVER['PHP_SELF']}' class='change'>
        #<input type='hidden' name='fid' value='{$prev}' />
        ($prev > -1)
            ? $output .= "
                <div>
                <a class='noprint' style='color:hsl(0, 0%, 45%); background-color:transparent;'>
                <button formaction='{$_SERVER['PHP_SELF']}?id={$prev}' class='lnk' title='{$label} zurück: #{$prev}'>
                <i class='fas fa-long-arrow-left' style='font-size:16px;'></i></button>
                </a></div>"

            : $output .= "<div>&nbsp;</div>";

        $output .= "&nbsp;";

        ($next > -1)
            ? $output .= "
                <div>
                <a class='noprint' style='color:hsl(0, 0%, 45%); background-color:transparent;'>
                <button formaction='{$_SERVER['PHP_SELF']}?id={$next}' class='lnk' title='{$label} vor: #{$next}'>
                <i class='fas fa-long-arrow-right' style='font-size:16px;'></i></button>
                </a></div>"

            : $output .= "<div>&nbsp;</div>";


        $output .= "</div>";   # ende </fuss>
        $output .= '<div class="onlyprint"><hr></div>';




        $output .= "</div>";  # ende rechte Seite
        $output .= "</form>";
        $output .= "</div>";  # ende main-detail



        $output .= "</div>";  # ende center-detail

        #echo "</div>";  # ende content detail
        #echo "</div>";  # ende grid-container-detail

        endif;  # Seite anzeigen

        $output .= "</div>";  # ende container

        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }
}


/*
$_SESSION:

array(21) {
["rootdir"]=> string(0) ""
["loggedin"]=> bool(true)
["userid"]=> int(2)
["su"]=> bool(true)
["status"]=> string(5) "activ"

["sort"]=> string(34) " the.id DESC, sta.kat10, sta.datum"
["dir"]=> string(4) " ASC" ["col"]=> string(10) " sta.kat10"
["filter"]=> string(66) "the.id IS NOT NULL AND sta.deakt=0 AND dat.deakt=0 AND ort.deakt=0"
["version"]=> string(6) "200525"

["siteid"]=> int(3)
["idx2"]=> bool(false)
["main"]=> string(11) "/index2.php"
["lastsite"]=> string(24) "/index2.php?start=45#740"

["start"]=> int(45)
["proseite"]=> int(5)
["groupid"]=> int(652)
["fileid"]=> int(741)
["prev"]=> int(740)
["next"]=> int(742)

["jump"]=> array(5) {
[982]=> array(1) { [736]=> array(2) { [0]=> int(-1) [1]=> int(737) } }
[647]=> array(1) { [737]=> array(2) { [0]=> int(736) [1]=> int(738) } }
[1062]=> array(1) { [738]=> array(2) { [0]=> int(737) [1]=> int(741) } }
[652]=> array(1) { [741]=> array(2) { [0]=> int(738) [1]=> int(742) } }
[1094]=> array(1) { [742]=> array(2) { [0]=> int(741) [1]=> int(-1) } }
}

}
*/