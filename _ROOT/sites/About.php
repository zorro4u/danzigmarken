<?php
namespace Dzg;
use Dzg\PrivateData as My;

require_once __DIR__.'/../sitemsg/impressum.php';
require_once __DIR__.'/../siteprep/loader_default.php';
require_once __DIR__.'/../private/account_data.php';


class About
{
    public static function show(): void
    {
        Header::show();
        self::show_body();
        Footer::show("impressum");
    }


    /**
     * HTML Ausgabe
     */
    private static function show_body(): void
    {
        $msg = ImpressumMsg::MSG;
?>

<div class='container main-container registration-form'>
<br /><br />

<p><span style='font-style:italic;text-decoration:underline'>
<?= $msg[317] ?>:</span><br />
<?= My\NAME ?><br />
<?= My\TOWN ?><br />
<?= $msg[311] ?></p>

<p><span style='font-style:italic;text-decoration:underline'>
<?= $msg[312] ?>:</span><br />
<?= $msg[313] ?>:
    <a href='/contact' title='Kontaktformular' style='background-color:transparent'>
    <img src='/assets/pic/email_danzigmarken.png'width='180' height='16'></a></p>


<?php // if (Auth::isCheckedIn()): ?>

<br />
<p><span style='font-style:italic;text-decoration:underline'>
<?= $msg[314] ?>:</span><br />
Zorro4U
&emsp;|&emsp;
<a href='https://github.com/zorro4u/danzigmarken'
    title='Code @ github.com' rel='noopener noreferrer nofollow'
    target='_blank' style='background-color:transparent'>
GitHub</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'></p>

<br />
<p><span style='font-style:italic;text-decoration:underline'>
<?= $msg[315] ?>:</span><br />
<a href='https://www.rainbow-web.com' title='Web-Hoster' rel='noopener noreferrer nofollow'
    target='_blank' style='background-color:transparent'>
www.rainbow-web.com</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'></p>

<?php // endif; ?>

</div>

<?php
    }
}


// EOF