<?php
namespace Dzg\Sites;
use Dzg\SitePrep\{Header, Footer};

require_once __DIR__.'/../siteprep/loader_default.php';


class Dummy
{
    public static function show(?string $msg=null)
    {
        Header::show();
        self::show_body($msg);
        Footer::show("empty");
    }


    /**
     * HTML Ausgabe
     */
    private static function show_body(?string $msg=null): void
    {
?>

<div class='container main-container registration-form'>
<br><br><p>$msg</p></div>

<?php
    }
}


// EOF