<?php
namespace Dzg;

require_once __DIR__.'/../siteprep/logout.php';
require_once __DIR__.'/../siteprep/loader_default.php';


class Logout extends LogoutPrep
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
        $msg = self::MSG;
        $show_form = self::$show_form;
        $root_site = self::$root_site;
        $status_message = self::$status_message;
?>

<div class='container small-container-330 form-signin'>
<h2 class='form-signin-heading'><?= $msg[310] ?></h2>
<?= $status_message ?>

<?php if ($show_form): ?>

<form action='?logout' method='POST'>
<br>

<?php if (!empty($_SESSION['autologin'])): ?>

<div class='checkbox' style='padding-top: 15px;'>
<label>
<input type='checkbox' name='logout_all' value='1' autocomplete='off' />
<?= $msg[311] ?>
</label></div>

<?php endif;   // autologin ?>

<button class='btn btn-lg btn-primary btn-block'
    style='margin-top: 20px;' type='submit'><?= $msg[312] ?></button>
</form>

<?php else: ?>

<br><br><hr><br>
<div><form action={$root_site} method='POST'>
<button class='btn btn-lg btn-primary btn-block'
    type='submit'><?= $msg[313] ?></button>
</form></div>

<?php endif;   // Seite anzeigen ?>
</div>


<?php
    }
}


// EOF