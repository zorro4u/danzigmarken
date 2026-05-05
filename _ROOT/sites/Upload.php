<?php
namespace Dzg\Sites;
use Dzg\SitePrep\Upload as Pre;
use Dzg\SitePrep\{Header, Footer};
use Dzg\Tools\Tools;

// IN ARBEIT \\

require_once __DIR__.'/../siteprep/upload.php';
require_once __DIR__.'/../siteprep/loader_default.php';
require_once __DIR__.'/../tools/tools.php';


/**
 * Summary of Class Upload
 */
class Upload extends Pre
{
    public static function show(): void
    {
        self::dataPreparation();

        Header::show();
        self::show_body();
        Footer::show("auth");
    }


    /**
     * HTML Ausgabe
     */
    private static function show_body(): void
    {

        $ausgabe = Tools::statusmeldungAusgeben();
        if (self::$show_form):

        $ausgabe .= '<div class="grid-container-detail">';
        $ausgabe .= '<div class="content detail">';

        $ausgabe .= "<div class='center-detail'>";
        $ausgabe .= "<div class='main-detail'>";

        ///////////////////////////////////////////////////
        // linke Detail-Seite
        //
        $ausgabe .= "<div class='main-detail-left'>";

        $max_size = 3*1024*1024;   // 3 MB
        $ausgabe .= '
        <form action="upload.php" method="post" enctype="multipart/form-data">

        <!-- MAX_FILE_SIZE muss vor dem Datei-Eingabefeld stehen -->
        <input type="hidden" name="MAX_FILE_SIZE" value="'.$max_size.'" />

        <!-- Der Name des Eingabefelds bestimmt den Namen im $_FILES-Array -->
        <label>Wählen Sie ein Bild (*.jpg, *.png oder *.webp) zum Hochladen aus.
        <input name="datei" type="file" accept="image/jpeg,image/png,image/webp"><br>
        </label>

        <input type="submit" value="Hochladen">
        <button>Datei hochladen</button>
        </form>
        ';

        $ausgabe .= "</div>";  # ende linke Seite, < /main-detail-left >


        ///////////////////////////////////////////////////
        // rechte Bild-Seite
        //
        $ausgabe .= "<div class='main-detail-right'>";
        $ausgabe .= "</div>";  # ende rechte Seite, < /main-detail-right >
        $ausgabe .= "</div>";  # ende < /MAIN-DETAIL >


        ///////////////////////////////////////////////////
        // FUSS
        //
        $ausgabe .= "<div class='fuss noprint' style='padding-top:0; padding-bottom:0;'>";
        $ausgabe .= "</div>";   # ende < /fuss >


        $ausgabe .= "</div>";   # ende < /CENTER >
        $ausgabe .= '</div>';   # ende < /CONTENT >
        $ausgabe .= '</div>';   # ende < /GRID-CONTAINER-DETAIL >

        endif;  // Seite anzeigen


        // HTML Ausgabe
        //
        echo $ausgabe;
    }
}


// EOF