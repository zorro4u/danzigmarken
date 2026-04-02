<?php
/* Prozess: RegInfoSeite-->email(Admin)-->email(RegCode)-->RegSeite-->email(AktLink)-->dieseSeite:ActivateSeite-->Login */

namespace Dzg\Sites;
use Dzg\SitePrep\ActivatePrep;
use Dzg\SitePrep\{Header, Footer};

session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/../siteprep/activate.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/***********************
 * Summary of Activate
 */
class Activate extends ActivatePrep
{

    /****************************
     * Summary of show
     */
    public static function show()
    {
        self::dataPreparation();

        Header::show();
        self::siteOutput();
        Footer::show("auth");
    }


    /****************************
     * Summary of siteOutput
     */
    private static function siteOutput()
    {
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $usr_data = self::$usr_data;

        $output = "<div class='container'>";
        $output .= $status_message;

        $output .= "<div class='small-container-330 form-signin'>";
        $output .= "
            <h2 class='form-signin-heading'>Aktivierung</h2>
            <br>";

        // Seite anzeigen
        if ($show_form):
        $output .= "
        <form action='./login.php?usr=".$usr_data['username']."' method='POST' style='margin-top: 30px;'>
            <button class='btn btn-lg btn-primary btn-block' type='submit'>Anmelden</button>
        </form>";

        endif;

        $output .= "</div>";
        $output .= "</div>";


        ///////////////////////////////////////////////////
        // html Ausgabe
        //
        echo $output;
    }

}