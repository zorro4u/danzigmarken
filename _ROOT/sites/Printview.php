<?php
namespace Dzg\Sites;
use Dzg\SitePrep\PrintView as Pre;
use Dzg\SitePrep\{Header, Footer};
use Dzg\Tools\Auth;

require_once __DIR__.'/../siteprep/printview.php';
require_once __DIR__.'/../siteprep/loader_default.php';
require_once __DIR__.'/../tools/auth.php';


/**
 * die gesamte Datenbank als Druckversion anzeigen
 * dadurch die Möglichkeit, die Ausgaben als PDF-drucken zu speichern
 */
class PrintView extends Pre
{
    public static function show()
    {
        self::siteEntryCheck();

        Header::loadHtmlHead();
        self::show_body();
        Footer::show("empty");
    }


    private static function erzeugeEinzelseite(int $file_id): string
    {
        self::dataPreparation($file_id);

        $output = self::$status_message;
        if (self::$show_form):

        $output .= '<div class="grid-container-detail">';
        $output .= '<div class="content detail">';

        // < CENTRAL >
        $output .= "<div class='center-detail'>";

        // < MAIN >
        $output .= "<div class='main-detail'>";


        // linke Seite, Detail-Angaben
        //
        $output .= "
        <div class='main-detail-left'>
        <div class='detail-kat-tab'>
        <table style='padding-top: 6px;'><tbody>";

        $tfoot = 'color:hsl(0, 0%, 90%); font-size: 80%; font-style: italic; padding-top: 15px;';

        $akt_file_idx = self::$akt_file_idx;
        $stamps = self::$stamps;
        $data = [];

        foreach (self::$spaltennamen as $spalte_db => $spalte_web) {

            // Fussnote zeigen, wenn angemeldet
            if ($spalte_db === 'created') {
                $data = ($stamps[$akt_file_idx][$spalte_db])
                    ? date("d.m.Y", strtotime($stamps[$akt_file_idx][$spalte_db]))
                    : '';
                $output .= (Auth::isCheckedIn())
                    ? "</tbody><tfoot><tr><td class='detail-key' style='".$tfoot.
                        "padding-bottom: 0px;'>{$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-bottom: 0px;'>
                       {$data}</td></tr>"
                    : "</tbody><tfoot></tfoot>";

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

            } elseif ($spalte_db === 'gid') {
                $data = self::$gid;
                $output .= (Auth::isCheckedIn())
                    ? "<tr><td class='detail-key' style='".$tfoot."padding-top: 6px;'>
                        {$spalte_web}</td>
                       <td class='detail-val' style='".$tfoot."padding-top: 6px;'>
                       {$data}</td></tr></tfoot>"
                    : "";

            } elseif ($spalte_db === 'fid') {   #style='".$tfoot."padding-top: 8px;'
                $data0 = self::$akt_file_id;
                $data1 = $stamps[$akt_file_idx]['name'].$stamps[$akt_file_idx]['suffix'];
                $output .= (Auth::isCheckedIn())
                    ? "<tr><td class='detail-key' style='color:hsl(0, 0%, 60%); font-size: 90%; padding-top: 8px;' colspan='2'>[{$data0}]&ensp;{$data1}</td>
                       <!-- <td class='detail-val fid'>{$data}</td> -->
                       </tr></tfoot>"
                    : "";


            // ab hier die Infos für alle
            } elseif ($spalte_web === 'Ansicht') {
                $data = htmlspecialchars($stamps[$akt_file_idx][$spalte_db]);
                $output .= "<tr><td colspan='2'
                    style='padding-top:6px;border-bottom:1px solid hsl(0,0%,90%);'></td></tr>
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

        $output .= "</table></div>";   # Tabelle, < /detail-kat-tab >
        $output .= "</div>";           # ende linke Seite, < /main-detail-left >


        // rechte Seite, Bilder
        //
        $output .= "
            <div class='main-detail-right'>
            <table class=detail-pic><tbody><tr><td>
            <div class='detail-pic'>";

        $output .= "
            <img src='/".htmlspecialchars($stamps[$akt_file_idx]['small']).
            "' width='300' height='' alt='".
            htmlspecialchars($stamps[$akt_file_idx]['name'])."'>";
            // htmlspecialchars($stamps[$akt_file_idx]['small'])."'>";

        $output .= "</div></td></tr></tbody>";
        $output .= "</table>";


        // rechte Seite, Thumbnail-Grid
        /*
        $output .= "<div class='detail-thumb-grid detail-gal'>";

        if (self::$max_file > 1) {
            foreach ($stamps as $idx => $file) {
                if ($idx != $akt_file_idx) {
                    $output .= "<div class='detail-thumb' title='#{$file['fid']}'><img src='/".
                    htmlspecialchars($file['thumb']).
                    "' width='70' height='70' alt='#{$file['fid']}'></div>";
                } else {
                    $output .= "<div class='detail-thumb_akt' title='#{$file['fid']}'><img src='/".
                    htmlspecialchars($file['thumb']).
                    "' width='70' height='70' alt='#{$file['fid']}'></div>";
                }
            }
        }
        $output .= "</div>";  # ende thumb-grid
        */
        $output .= "</div>";  # ende rechte Seite, < /main-detail-right >
        $output .= "</div>";  # ende < /MAIN-DETAIL >

        #$output .= '<div class="onlyprint"><hr></div>';
        $output .= "</div>";   # ende < /CENTER >
        $output .= '</div>';   # ende < /content detail >
        $output .= '</div>';   # ende < /grid-container-detail >

        endif;  # Seite anzeigen

        return $output;
    }
    # class= noprint, onlyprint,


    /**
     * zentrale HTML Ausgabe
     */
    private static function show_body(): void
    {
        // bei Aufruf per Admin-Seite, Filter zurücksetzen
        if (isset($_GET['thema']) && (int)$_GET['thema'] === 100) {
            $_SESSION['thema'] = "- alle -";
            unset($_SESSION['filter']);
        }

        $theme_liste = (empty($_SESSION['thema']) || $_SESSION['thema'] === "- alle -")
            ? self::themeList()
            : $theme_liste = [$_SESSION['thema']];

        // kontinuierliche Einzelseitenausgabe
        foreach ($theme_liste as $thema) {
            $id_liste = self::idListeHolen($thema);
            if (!$id_liste) continue;

            echo "<br><hr><center>{$thema}</center><hr><br>";

            $i = 0;
            foreach ($id_liste as $id) {

                // html-Ausgabe
                echo self::erzeugeEinzelseite($id);

                // Ausgabe begrenzen
                // (komplett: 2.000 Einträge, 1.000 Seiten, 500 Blätter) max: Kork=900
                // auf eine A4 Seite passen ca. 2 Einträge (id)
                // cut = 40 Einträge/Thema --> 20 Seiten (gesamt x5: 100 Seiten, 50 Blätter)
                $id_cut = 40;
                ++$i;
                // if ($i == $id_cut) break;
            }
        }
    }
}


// EOF