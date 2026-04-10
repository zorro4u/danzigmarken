<?php
namespace Dzg\Sites;
use Dzg\SitePrep\Logout as Pre;
use Dzg\SitePrep\{Header, Footer};

require_once __DIR__.'/../siteprep/logout.php';
require_once __DIR__.'/../siteprep/loader_default.php';


class Logout extends Pre
{
    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        self::dataPreparation();

        Header::show();
        self::siteOutput();
        Footer::show("auth");
    }


    private static function siteOutput()
    {
        $show_form = self::$show_form;
        $root_site = self::$root_site;
        $status_message = self::$status_message;

        $output = "
            <div class='container small-container-330 form-signin'>
            <h2 class='form-signin-heading'>Abmelden</h2>";

        #$output .= statusmeldungAusgeben();
        $output .= $status_message;

        // Seite anzeigen
        if ($show_form):
            $output .= "
                <form action='?logout' method='POST'>
                <br>";

            if (!empty($_SESSION['autologin'])):
                $output .= "
                    <div class='checkbox' style='padding-top: 15px;'>
                    <label>
                    <input type='checkbox' name='logout_all' value='1' autocomplete='off'> geräteübergreifend alle meine Anmeldungen beenden (Grand Logout)
                    </label>
                    </div>";
            endif;
            $output .= "
                <button class='btn btn-lg btn-primary btn-block' style='margin-top: 20px;' type='submit'>Logout</button>
                </form>";
        else:
            $output .= "
                <br><br><hr><br>
                <div><form action=".$root_site." method='POST'>
                <button class='btn btn-lg btn-primary btn-block' type='submit'>Startseite</button>
                </form></div>";
        endif;
        $output .= "</div>";

        echo $output;
    }
}