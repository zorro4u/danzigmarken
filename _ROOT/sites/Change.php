<?php
namespace Dzg\Sites;
use Dzg\SiteForm\Change as Pre;
use Dzg\SitePrep\{Header, Footer};

require_once __DIR__.'/../siteform/change.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/**
 * Summary of Class Change
 */
class Change extends Pre
{
    public static function show(): void
    {
        self::siteEntryCheck();
        self::dataPreparation();
        self::formEvaluation();

        Header::show();
        self::show_body();
        Footer::show();
    }


    /**
     * HTML Ausgabe
     */
    private static function show_body(): void
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
                            style='border:none; background-color:transparent' /></td></tr>";
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


        // HTML Ausgabe
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


// EOF