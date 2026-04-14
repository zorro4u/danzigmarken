<?php
namespace Dzg\Sites;
use Dzg\SitePrep\Details as Pre;
use Dzg\SitePrep\{Header, Footer};
use Dzg\Tools\Auth;

require_once __DIR__.'/../siteprep/details.php';
require_once __DIR__.'/../siteprep/loader_default.php';
require_once __DIR__.'/../tools/auth.php';

/*
-- MVC Design --
Model,          Data
View,           Ansicht
Controller,     Steuerung: Data <--> Ansicht
*/


/***********************
 * Summary of Details
 * Webseite:
 *
 * __public__
 * show()
 */
class Details extends Pre
{
    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        self::siteEntryCheck();
        self::dataPreparation();

        Header::show();
        self::view();
        Footer::show();
    }


    /***********************
     * Summary of view
     *
     * als HTML ausgeben
     */
    protected static function view()
    {
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $output = "<div class='container'>";
        #$output = "<div class='grid-container-detail'>";
        #$output .= '<div class="content detail">';

        if (!$show_form):
            $output .= $status_message;
        else:

        // Seiten-Check okay, Seite starten
        $spaltennamen = self::$spaltennamen;
        $stamps = self::$stamps;
        $akt_file_id = self::$akt_file_id;
        $akt_file_idx = self::$akt_file_idx;
        $gid = self::$gid;
        $max_file = self::$max_file;
        $prev = self::$prev;
        $next = self::$next;

        $output .= $status_message;
        $output .= "<div class='center-detail'>";
        $output .= "<div class='main-detail'>";


        // linke Seite, Detail-Angaben
        //
        $output .= "
          <div class='main-detail-left'>
          <div class='detail-kat-tab'>
          <table style='padding-top: 6px;'><tbody>";

        $tfoot = 'color:hsl(0, 0%, 80%); font-size: 85%; padding-top: 15px;';
        #style='color:hsl(0,0%,45%); font-style:italic;'

        foreach ($spaltennamen as $spalte_db => $spalte_web) {

            // Fussnoten zeigen, nur wenn angemeldet
            //
            // erstellt
            if ($spalte_db === 'created') {
                // Zeit-String (yyyy-mm-dd hh:mm) in (dd.mm.yyyy hh:mm) wandeln
                $data = ($stamps[$akt_file_idx][$spalte_db])
                    ? date("d.m.Y", strtotime($stamps[$akt_file_idx][$spalte_db]))
                    : '';
                $output .= (Auth::isCheckedIn())
                    ? "</tbody><tfoot>
                        <tr><td class='detail-key' style='".$tfoot."padding-bottom: 0px;'>{$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-bottom: 0px;'>
                        {$data}</td></tr>"
                    : "</tbody><tfoot></tfoot>";

            // bearbeitet
            } elseif ($spalte_db === 'changed') {
                $data = ($stamps[$akt_file_idx][$spalte_db])
                    ? date("d.m.Y", strtotime($stamps[$akt_file_idx][$spalte_db]))
                    : '';
                $output .= ($data && Auth::isCheckedIn())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 0px;'>
                        {$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-top: 0px;'>
                        {$data}</td></tr>"
                    : "";

            // Gruppen-ID
            } elseif ($spalte_db === 'gid') {
                $data = $gid;
                $output .= (Auth::isCheckedIn())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 6px;'>
                        {$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-top: 0px;'>
                        {$data}</td></tr>"
                    : "";

            // Bild-ID
            } elseif ($spalte_db === 'fid') {
                $data = $akt_file_id;
                $output .= (Auth::isCheckedIn())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 0px;'>
                        {$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-top: 6px;'>
                        {$data}</td></tr>"
                    : "";

            // Druckoption
            } elseif ($spalte_db === 'print') {
                $data = $stamps[$akt_file_idx][$spalte_db];
                $data_rev = ($data == 1) ? 0 : 1;      # switchen
                $checked = ($data == 1) ? "checked" : "";
                # onclick='return false;' .. disabled='disabled'

                $output .= (Auth::isCheckedIn())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 0px;'>
                        {$spalte_web}</td>
                        <td class='detail-val' style='".$tfoot."padding-top: 0px;'
                        title='in Druckauswahl ja/nein'>
                        <input type='checkbox' name='{$spalte_db}' id='print' class='chkbx'
                        {$checked} onclick='prn_toogle(".$akt_file_id.",".$data_rev.")' />
                        <label for='print' ></label></td></tr></tfoot>"
                    : "";

            // ab hier die Infos für alle zugänglich
            // Ansicht, mit Linie oberhalb
            } elseif ($spalte_web === 'Ansicht') {
                $data = htmlspecialchars($stamps[$akt_file_idx][$spalte_db]);
                $output .= "
                    <tr><td colspan='2' style='padding-top:6px;
                        border-bottom:1px solid hsl(0,0%,90%);'></td></tr>
                    <tr><td class='detail-key' style='padding-top:6px;'>{$spalte_web}</td>
                    <td class='detail-val'>{$data}</td></tr>";

            } else {
                if ($spalte_web === 'Datum') {
                    $data = ($stamps[$akt_file_idx][$spalte_db])
                        ? date("d.m.Y", strtotime($stamps[$akt_file_idx][$spalte_db]))
                        : '';

                } else {
                    $data = (!empty($stamps[$akt_file_idx][$spalte_db]))
                        ? htmlspecialchars($stamps[$akt_file_idx][$spalte_db])
                        : '';
                }

                $output .= "
                    <tr><td class='detail-key'>{$spalte_web}</td>
                    <td class='detail-val'>{$data}</td></tr>";
            }
        }

        $output .= "</table></div>";  # Tabelle, < /detail-kat-tab >
        $output .= "</div>";  # ende linke Seite, < /main-detail-left >


        // rechte Seite, Bilder
        //
        # <img src='".htmlspecialchars($stamps[$akt_file_idx]['small'])."' width='300' height='' alt='".htmlspecialchars($stamps[$akt_file_idx]['name'])."'></a>

        $output .= "
            <div class='main-detail-right'>
            <table class=detail-pic><tbody><tr><td>
            <div class='detail-pic'>";

            if (!Auth::isCheckedIn()) {
                $output .= "<a href='/".
                    htmlspecialchars($stamps[$akt_file_idx]['medium']).
                    "' title='größere Ansicht'><img src='/".
                    htmlspecialchars($stamps[$akt_file_idx]['small']).
                    "' width='300' height='' alt='".
                    htmlspecialchars($stamps[$akt_file_idx]['name'])."'></a>";

        #    } elseif ($_SESSION['userid']!=3 && empty($_SESSION['su'])) {
            } else {
                $output .= "<a href='/".
                    htmlspecialchars($stamps[$akt_file_idx]['large']).
                    "' title='große Ansicht'><img src='/".
                    htmlspecialchars($stamps[$akt_file_idx]['small']).
                    "' width='300' height='' alt='".
                    htmlspecialchars($stamps[$akt_file_idx]['name'])."'></a>";

            }
        /*
        elseif ($_SESSION['userid']==9 || !empty($_SESSION['suX'])) {
                $output .= "<a href='".
                htmlspecialchars($stamps[$akt_file_idx]['original']).
                "' title='originale Ansicht'><img src='".
                htmlspecialchars($stamps[$akt_file_idx]['small']).
                "' width='300' height='' alt='".
                htmlspecialchars($stamps[$akt_file_idx]['name'])."'></a>";
            }
        */
            $output .= "</div></td></tr></tbody>";

            /*
          <tfoot><tr><td style=''>
          <div class='pic-link'>";

          if (!Auth::isCheckedIn()) {
            #$output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['small']}' title='(800x600)'>klein</button></form>";
            $output .= "<form style='margin:0; margin-top: 4px;'><a class='button btn_pic'
            style='text-decoration:none;' title='- nur angemeldet -'><i class='fas fa-lock'></i>
            mittel</a></form>";
            $output .= "<form style='margin:0; margin-top: 4px;'><a class='button btn_pic'
            style='text-decoration:none;' title='- nur angemeldet -'><i class='fas fa-lock'></i>
            groß</a></form>";

          } elseif (!isset($homechk)) {
            #$output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['small']}' title='(800x600)'>klein</button></form>";
            $output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['medium']}' title='(1280x1024)'>mittel</button></
            form>";
            $output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['large']}' title='(1920x1200)'>groß</button></
            form>";

          } elseif (isset($homechk)) {
            #$output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['medium']}' title='(1280x1024)'>mittel</button></
            form>";
            $output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['large']}' title='(1920x1200)'>groß</button></
            form>";
            $output .= "<form style='margin:0;'><button class='button btn_pic' type='submit'
            formaction='{$stamps[$akt_file_idx]['original']}' title=''>original</button></form>";
          }
        */
          #$output .= "</div></td></tr></tfoot>";
          $output .= "</table>";


        //-------------------------------------------------
        // Thumbnail-Grid
        //
        // durch Laufvariable $i in der Schleife geht keine vorzeitige Variablen-Zuweisung

        // Thumb-Datei
        # '<img src="'.htmlspecialchars($stamps[$i]['webthumb']).'" width="70" height="70"
        # alt="['.htmlspecialchars($stamps[$i]['dateiname']).']">';
        # <img src='".htmlspecialchars($stamps[$akt_file_idx]['thumb'])."' width='70' height='70'
        # alt='#".$stamps[$akt_file_idx]['fid']."'>

        // Thumb-BLOB
        # '<img src="data:image/jpg;charset=utf8;base64,'.base64_encode($stamps[$i]['thumb-blob']).
        # '" width="70" height="70" alt="['.htmlspecialchars($stamps[$i]['dateiname']).']">';
        # <img src='data:image/jpg;charset=utf8;base64,".base64_encode($file['thumb-blob'])."'
        # width='70' height='70' alt='#".$file['fid']."'>

        $output .= "<div class='detail-thumb-grid detail-gal'>";

        if ($max_file > 1)
          foreach ($stamps as $idx => $file)
            if ($idx != $akt_file_idx)
                #$output .= "<div class='detail-thumb' title='#{$file['fid']}'><a href='".
                #$_SERVER['PHP_SELF']."?id={$file['fid']}'><img src='data:image/jpg;charset=utf8;#base64,".base64_encode($file['thumb-blob'])."' width='70' height='70'
                #alt='#{$file['fid']}'></a></div>";
              $output .= "<div class='detail-thumb' title='#{$file['fid']}'>
                <a href='".$_SERVER['PHP_SELF']."?id={$file['fid']}'>
                <img src='/".htmlspecialchars($file['thumb']).
                "' width='70' height='70'alt='#{$file['fid']}'></a></div>";
            else
                # $output .= "<div class='detail-thumb_akt'><img src='data:image/jpg;charset=utf8;
                # base64,".base64_encode($file['thumb-blob'])."' width='70' height='70' alt='#".
                # $file['fid']."'></div>";
              $output .= "<div class='detail-thumb_akt' title='#{$file['fid']}'>
                <img src='/".htmlspecialchars($file['thumb']).
                "' width='70' height='70' alt='#".$file['fid']."'></div>";


        $output .= "</div>";  # ende thumb-grid
        $output .= "</div>";  # ende rechte Seite, </main-detail-right>
        $output .= "</div>";  # ende </main-detail>


        // ZURÜCK .. DRUCKEN .. VOR
        //
        // - Drucken -
        $ziel = "print.php?id=".$akt_file_id;
        $btn_print0 = "<form action='{$ziel}' method='POST' style='display:inline'>".
                    "<button class='btn Xbtn-primary' type='submit' value='print'
                    name='print'><i class='fas fa-print'>&ensp;</i>Drucken</button></form>";

        $btn_print = "<button onclick='window.print();' class='noprint btn print'
                    title='Drucken'><i class='fas fa-print'>&ensp;</i>Drucken</button>";

        $label = (empty($_SESSION['idx2'])) ? "Bild" : "Gruppe";

        $output .= "<div class='fuss noprint' style='padding-top:0; padding-bottom:0;'>";
        // < &lt; > &gt;
        // long-arrow-left angle-double-left chevron-circle-left arrow-circle-left caret-square-left


        if (Auth::isCheckedIn()) {
            ($prev > -1)
                ? $output .= "<div><a class='noprint' style='color:hsl(0, 0%, 45%);
                    background-color:transparent;' href={$_SERVER['PHP_SELF']}?id={$prev}
                    title='{$label} zurück: #{$prev}'><i class='fas fa-long-arrow-left'
                    style='font-size:16px; Xcolor:#9d9d9d;'></i></a></div>"
                : $output .= "<div>&nbsp;</div>";

            $output .= "{$btn_print}";
            #$output .= "{$btn_print0}";

            ($next > -1)
                ? $output .= "<div><a class='noprint' style='color:hsl(0, 0%, 45%);
                    background-color:transparent;' href={$_SERVER['PHP_SELF']}?id={$next}
                    title='{$label} vor: #{$next}'><i class='fas fa-long-arrow-right'
                    style='font-size:16px; Xcolor:#9d9d9d;'></i></a></div>"
                : $output .= "<div>&nbsp;</div>";
        }

        $output .= "</div>";   # ende </fuss>
        $output .= '<div class="onlyprint"><hr></div>';
        $output .= "</div>";   # ende </center-detail>


        ###################################################
        //
        // < BOTTOM >
        //
        /*
        $output .= "
          <div class='bottom'>
          <div style='text-align: right; color: grey; padding-right: 12px;'></div>
          </div>";   # ende < /BOTTOM >
        */

        ###################################################
        //
        // < LAST >
        //
        /*
        $output .= "<div class='last'>
        <div class='links kleingrau cc'></div>
        <div class='mitte kleingrau'></div>
        <div class='rechts kleingrau'></div>
        </div>";   # ende < /LAST >
        */

        endif;                 # showForm
        #$output .= '</div>';   # ende < /content detail >
        $output .= '</div>';   # ende < /grid-container-detail >


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;

    }


}