<?php
namespace Dzg\SiteForm;
use Dzg\SitePrep\Change as Pre;
use Dzg\SiteData\ChangeData as Data;
use Dzg\Tools\Tools;

require_once __DIR__.'/../siteprep/change.php';
require_once __DIR__.'/../sitedata/change.php';
require_once __DIR__.'/../tools/tools.php';


/***********************
 * Auswertung der Formulareingabe
 */
class Change extends Pre
{
    /***********************
     * ABBRUCH - Button
     */
    protected static function executeCancelButton()
    {
        $akt_file_id = self::$akt_file_id;
        unset($_POST['cancel']);
        header ("Location: /details.php?id={$akt_file_id}");
        exit;
    }


    /***********************
     * DELETE - Button
     */
    protected static function executeDeleteButton()
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
            'id' => $akt_file_id,  # int
            'ip' => $remaddr,
            'by' => $userid ];     # int
        Data::deleteFile($data);

        // wenn nur 1 Datei, dann auch Gruppeneintrag löschen
        if ($max_file < 2) {
            $data = [
                'id' => $gid,
                'ip' => $remaddr,
                'by' => $userid ];
            Data::deleteGroup($data);
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
    protected static function executeUndeleteButton()
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
            'id' => $akt_file_id,  # int
            'ip' => $remaddr,
            'by' => $userid ];     # int
        Data::undeleteFile($data);

        // wenn nur 1 Datei, dann auch Gruppe wieder aktivieren
        if ($max_file < 2) {
            $data['id'] = $gid;
            Data::undeleteGroup($data);
        }

        self::$stamps[$akt_file_idx]['deakt'] = '0';
        return 'wiederhergestellt';
    }


    /***********************
     * SPLIT - Button
     */
    protected static function executeSplitButton()
    {
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
            $result = Data::newGroup($data1);

            if(is_int($result)){
                $new_gid = $result;

                // Datei mit neuer Marke verknüpfen
                $data = [
                    'id'      => $akt_file_id,    # int
                    'new_gid' => $new_gid,        # int
                    'ip'      => $remaddr,
                    'by'      => $userid ];
                $set = "id_group=:new_gid, chg_ip=:ip, chg_by=:by ";
                Data::updateFile($set, $data);

                $success_msg = 'aus Gruppe gelöst';
                header ("Location: /change.php?id={$akt_file_id}");
            }

            else {
                $error_arr []= $result;
            }


            // Rückgabewerte
            self::$stamps    = $stamps;
            self::$error_arr = $error_arr;

        } # max_file

        return $success_msg;
    }


    /***********************
     * CHANGE - Button
     */
    protected static function executeChangeButton()
    {
        $stamps   = self::$stamps;
        $max_file = self::$max_file;
        $akt_file_id  = self::$akt_file_id;
        $akt_file_idx = self::$akt_file_idx;
        $error_arr    = self::$error_arr;
        $success_msg  = "";
        $gid     = self::$gid;
        $remaddr = $_SERVER['REMOTE_ADDR'];     # abfragende Adresse
        $userid  = $_SESSION['userid'];
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
                $id_theme = Data::getID($input['thema']);
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
                $data += ['id' => $gid];    # int
                Data::updateGroup($set, $data);
                $success_msg = 'Änderung ausgeführt.';
            }

            // update datei
            if (!empty($d)) {
                $d['chg_ip'] = $remaddr;
                $set  = '';
                $data = [];

                foreach ($d AS $spalte => $input_wert) {
                    $set .= $spalte."=:".$spalte.", ";
                    $data[$spalte] = $input_wert;
                }

                // letzten 2 Zeichen (Komma) löschen, Leerz. anhängen
                $set = substr($set, 0, -2)." ";
                $data += ['id' => $akt_file_id];
                Data::updateFile($set, $data);

                // wenn MarkenID geändert wird, und ($max_file < 2) dann auch Marke löschen
                if (isset($input['gid']) && ($max_file < 2)) {
                    $data = [
                        'id' => $gid,
                        'ip' => $remaddr,
                        'by' => $userid ];
                    Data::deleteGroup($data);
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
    protected static function formEvaluation()
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
    }
}
