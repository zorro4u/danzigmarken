<?php
namespace Dzg\SitePrep;
use Dzg\Tools\{Database, Auth, Tools};
use PDO;
use PDOException;

require_once __DIR__.'/details.php';
require_once __DIR__.'/../tools/loader_tools.php';


/***********************
 * Summary of Change
 * Webseite:
 *
 * __public__
 * show()
 */
class ChangePrep extends DetailsPrep
{
    protected static $pdo;
    protected static array $abfrage_db;


    protected static function siteEntryCheck()
    {
        // Datenbank öffnen
        if (!is_object(self::$pdo)) {
            self::$pdo = Database::connectMyDB();
        }

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
        $stmt = "WITH
        thema AS (SELECT thema FROM dzg_dirsub2 ORDER BY thema),
        kat15 AS (SELECT kat15 FROM dzg_kat15 ORDER BY kat15),
        kat20 AS (SELECT kat20 FROM dzg_kat20 ORDER BY kat20 DESC),
        kat21 AS (SELECT kat21 FROM dzg_kat21 ORDER BY kat21)
        SELECT * FROM thema, kat15, kat20, kat21 ";

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
     * ABBRUCH - Button
     */
    private static function executeCancelButton()
    {
        $akt_file_id = self::$akt_file_id;
        unset($_POST['cancel']);
        header ("Location: /details.php?id={$akt_file_id}");
        exit;
    }


    /***********************
     * DELETE - Button
     */
    private static function executeDeleteButton()
    {
        // Soft-Delete durch setzen von: flag 'deakt'
        // wenn ($max_file < 2)
        // dann auch Marke löschen und
        // nach Änderung zur Übersicht wechseln oder leere Datenseite anzeigen
        //
        $stamps = self::$stamps;
        $akt_file_id = self::$akt_file_id;
        $userid   = $_SESSION['userid'];
        $gid      = self::$gid;
        $max_file = self::$max_file;
        $remaddr  = $_SERVER['REMOTE_ADDR'];     # abfragende Adresse

        unset($_POST['delete']);
        unset($_POST['restore']);
        $_POST = [];

        $data = [
            ':by' => $userid,       # int
            ':id' => $akt_file_id,  # int
            ':ip' => $remaddr ];
        $stmt1 = "UPDATE dzg_file SET deakt=1, chg_ip=:ip, chg_by=:by WHERE id=:id";
        $stmt3 = "UPDATE dzg_group SET deakt=1, chg_ip=:ip, chg_by=:by WHERE id=:id";

        Database::sendSQL($stmt1, $data);

        // wenn nur 1 Datei, dann auch Gruppeneintrag löschen
        if ($max_file < 2) {
            $data[':id'] = $gid;
            Database::sendSQL($stmt3, $data);
        }

        // zum vorherigen Element wechseln
        # data-ajax=false
        #header("Location: /change.php?id={$_SESSION['prev']}");
        header("Location: /details.php?id={$_SESSION['prev']}");
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

        self::$stamps = $stamps;
        return 'gelöscht';
    }


    /***********************
     * UNDELETE - Button
     */
    private static function executeUndeleteButton()
    {
        // WIEDERHERSTELLEN - Button, ('Delete' rückgängig machen)
        // -- wird nicht weiter verwendet / 22.5.25 --
        //
        $akt_file_idx = self::$akt_file_idx;
        $akt_file_id  = self::$akt_file_id;
        $userid   = $_SESSION['userid'];
        $gid      = self::$gid;
        $max_file = self::$max_file;
        $remaddr  = $_SERVER['REMOTE_ADDR'];     # abfragende Adresse

        unset($_POST['restore']);
        unset($_POST['delete']);
        $_POST = [];

        $data = [
            ':by' => $userid,       # int
            ':id' => $akt_file_id,  # int
            ':ip' => $remaddr ];
        $stmt1 = "UPDATE dzg_file SET deakt=0, chg_ip=:ip, chg_by=:by WHERE id=:id";
        $stmt3 = "UPDATE dzg_group SET deakt=0, chg_ip=:ip, chg_by=:by WHERE id=:id";

        Database::sendSQL($stmt1, $data);

        // wenn nur 1 Datei, dann auch Gruppe wieder aktivieren
        if ($max_file < 2) {
            $data[':id'] = $gid;
            Database::sendSQL($stmt3, $data);
        }

        self::$stamps[$akt_file_idx]['deakt'] = '0';
        return 'wiederhergestellt';
    }


    /***********************
     * SPLIT - Button
     */
    private static function executeSplitButton()
    {
        $pdo_db = self::$pdo;
        $stamps = self::$stamps;
        $akt_file_idx = self::$akt_file_idx;
        $akt_file_id  = self::$akt_file_id;
        $error_arr    = self::$error_arr;
        $success_msg  = "";
        $userid   = $_SESSION['userid'];
        $gid      = self::$gid;
        $max_file = self::$max_file;
        $remaddr  = $_SERVER['REMOTE_ADDR'];     # abfragende Adresse

        unset($_POST['split']);
        $_POST = [];

        if ($max_file > 1) {
            // zum Separieren entweder Notiz1 hinzufügen
            // (oder DB Unique Anweisung aufheben)
            $stamps[$akt_file_idx]['kat17'] .= "_ALT: {$gid}_";

            // neuen Datensatz 'Marke' anlegen
            $stmt1 =
                "INSERT INTO dzg_group
                    (id_thema, datum, kat10, kat11, kat12, kat13,
                        kat14, kat15, kat16, kat17, chg_ip, chg_by, mirror)
                VALUES (:id_thema, :datum, :kat10, :kat11, :kat12, :kat13, :kat14, :kat15,
                        :kat16, :kat17, :ip, :by, 1) ";

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
            $stmt2 = "UPDATE dzg_file SET id_group=:new_gid, chg_ip=:ip, chg_by=:by
                        WHERE id=:akt_file_id";
            $data2 = [
                ':akt_file_id'  => $akt_file_id,    # int
                ':new_gid'      => $new_gid,        # int
                ':ip'           => $remaddr,
                ':by'           => $userid ];
            Database::sendSQL($stmt2, $data2);

            $success_msg = 'aus Gruppe gelöst';
            header ("Location: /change.php?id={$akt_file_id}");

            // Rückgabewerte
            self::$stamps    = $stamps;
            self::$error_arr = $error_arr;

        } # max_file

        return $success_msg;
    }


    /***********************
     * CHANGE - Button
     */
    private static function executeChangeButton()
    {
        $stamps   = self::$stamps;
        $max_file = self::$max_file;
        $akt_file_id  = self::$akt_file_id;
        $akt_file_idx = self::$akt_file_idx;
        $error_arr    = self::$error_arr;
        $success_msg  = "";
        $gid     = self::$gid;
        $remaddr = $_SERVER['REMOTE_ADDR'];     # abfragende Adresse
        $input   = [];
        $s       = [];
        $d       = [];

        unset($_POST['change']);

        if (!isset($_POST['print'])) $_POST['print'] = 'XXX';


        // Liste mit [Spalte => Änderg.werten] erstellen
        // (Hinweis: mit Leerz. kann DB-Eintrag überschrieben/gelöscht werden)
        //
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
            if (isset($input['gid'])) $d['id_group'] = (int)$input['gid'];
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
                $d['chg_ip'] = $remaddr;
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

                $stmt3 =
                    "UPDATE sqlite_sequence
                    SET seq=(SELECT MAX(id) max_id FROM dzg_group)
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

        // Rückgabewerte
        self::$stamps    = $stamps;
        self::$error_arr = $error_arr;

        return $success_msg;
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

        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $success_msg = "";
        $error2 = False;

        // Seiten-Check bisher okay, Formularauswertung starten
        if ($show_form &&
            strtoupper($_SERVER["REQUEST_METHOD"] === "POST")) {

            // ABBRUCH - Button
            if (isset($_POST['cancel']) && $_POST['cancel'] === "Cancel" ) {
                self::executeCancelButton();
            }

            // DELETE - Button
            if (isset($_POST['delete']) && $_POST['delete'] == "Delete") {
                self::executeDeleteButton();
            }

            // WIEDERHERSTELLEN - Button, ('Delete' rückgängig machen)
            // -- wird nicht weiter verwendet / 22.5.25 --
            if (isset($_POST['restore']) && $_POST['restore'] == "Restore") {
                $success_msg = self::executeUndeleteButton();
            }

            // TRENNEN - Button
            if (isset($_POST['split']) && $_POST['split'] == "Split") {
                $success_msg = self::executeSplitButton();
            }

            // ÄNDERN - Button
            if (isset($_POST['change']) && $_POST['change'] == "Change") {
                $success_msg = self::executeChangeButton();
            }

            // Formularseite trotz Fehler bei der Änderungseingabe anzeigen
            $error2 = !empty(self::$error_arr) ? true : false;
        }

        $error_msg = !empty(self::$error_arr)
            ? implode("<br>", self::$error_arr)
            : "";

        /*---
        if (empty($_SESSION['su'])) {
            $error_msg = "Diese Seite wird überarbeitet und funktioniert im Augenblick nicht.";
        } ---*/

        $show_form = ($error_msg === "" OR $error2) ? true : false;
        $status_message = Tools::statusOut($success_msg, $error_msg);

        // globale Variablen setzen
        self::$show_form = $show_form;
        self::$status_message = $status_message;

        // Datenbank schließen
        self::$pdo = Null;
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