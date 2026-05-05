<?php
/* Prozess: RegInfoSeite-->email(Admin)-->email(RegCode)-->RegSeite-->email(AktLink)-->dieseSeite:ActivateSeite-->Login */

namespace Dzg\Sites;
use Dzg\SitePrep\Activate as Pre;
use Dzg\SitePrep\{Header, Footer};

session_start();
date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/../siteprep/activate.php';
require_once __DIR__.'/../siteprep/loader_default.php';


/**
 * Summary of Class Activate
 */
class Activate extends Pre
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
        $show_form = self::$show_form;
        $status_message = self::$status_message;
        $usr_data = self::$usr_data;
?>

<div class='container'>
<?= $status_message ?>
<div class='small-container-330 form-signin'>
<h2 class='form-signin-heading'>Aktivierung</h2>
<br>

<?php if ($show_form): ?>
<form action='./login.php?usr=<?= $usr_data['username'] ?>' method='POST' style='margin-top: 30px;'>
<button class='btn btn-lg btn-primary btn-block' type='submit'>Anmelden</button>
</form>
<?php endif; ?>

</div></div>

<?php
    }
}


// EOF