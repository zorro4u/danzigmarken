<?php
namespace Dzg;
use Dzg\Tools\Auth;
use Dzg\PrivateData as My;

require_once __DIR__.'/../sitemsg/impressum.php';
require_once __DIR__.'/../siteprep/loader_default.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../private/account_data.php';


class Impressum
{
    public static function show(): void
    {
        self::siteEntryCheck();

        Header::show();
        self::show_body();
        Footer::show("impressum");
    }


    /**
     * Summary of siteEntryCheck
     */
    private static function siteEntryCheck()
    {
        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::isCheckedIn()) {
            header("location: /about.php");
            exit;
        }
    }


    /**
     * HTML Ausgabe
     */
    private static function show_body()
    {
        $msg = ImpressumMsg::MSG;
?>

<div class='container main-container registration-form'>
<br /><br />

<p><span style='font-style:italic;text-decoration:underline'>
<?= $msg[310] ?>:</span><br />
<?= My\NAME ?><br />
<?= My\STREET ?><br />
<?= My\TOWN ?><br />
<?= $msg[311] ?></p>

<p><span style='font-style:italic;text-decoration:underline'>
<?= $msg[312] ?>:</span><br />
<?= $msg[316] ?>: <?= My\PHONE ?><br />
<?= $msg[313] ?>:
    <a href='/contact' title='Kontaktformular' style='background-color:transparent'>
    <img src='/assets/pic/email_danzigmarken.png'width='180' height='16'></a></p>

<br />
<p><span style='font-style:italic;text-decoration:underline'>
<?= $msg[314] ?>:</span><br />
Zorro4U
<!--
<a href='https://keys.openpgp.org/search?q=viele%40gmx.net'
    title='email/pgp @ keys.openpgp.org' rel='noopener noreferrer nofollow' target='_blank' style='background-color:transparent'>
Zorro4U</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'>
-->

<?php if (Auth::isCheckedIn()): ?>

&emsp;|&emsp;
<a href='https://github.com/zorro4u/danzigmarken'
    title='Code @ github.com' rel='noopener noreferrer nofollow'
    target='_blank' style='background-color:transparent'>
GitHub</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'></p>

<?php endif; ?>

<br />
<p><span style='font-style:italic;text-decoration:underline'>
<?= $msg[315] ?>:</span><br />
<a href='https://www.rainbow-web.com' title='Web-Hoster' rel='noopener noreferrer nofollow'
    target='_blank' style='background-color:transparent'>
www.rainbow-web.com</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'></p>

</div>


<?php
    }
}


// EOF