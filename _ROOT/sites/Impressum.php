<?php
namespace Dzg\Sites;
use Dzg\SitePrep\{Header, Footer};
use Dzg\Tools\Auth;
use Dzg\PrivateData as My;

require_once __DIR__.'/../siteprep/loader_default.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../private/account_data.php';


class Impressum
{
    /**
     * Anzeige der Webseite
     */
    public static function show()
    {
        self::siteEntryCheck();

        Header::show();
        self::siteOutput();
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
     * Summary of siteOutput
     */
    private static function siteOutput()
    {
?>

<div class='container main-container registration-form'>
<br /><br />

<p><span style='font-style:italic;text-decoration:underline'>
Herausgeber:</span><br />
<?= My\NAME ?><br />
<?= My\STREET ?><br />
<?= My\TOWN ?><br />
Deutschland</p>

<p><span style='font-style:italic;text-decoration:underline'>
Kontakt:</span><br />
Telefon: <?= My\PHONE ?><br />
E-Mail:
    <a href='/contact' title='Kontaktformular' style='background-color:transparent'>
    <img src='/assets/pic/email_danzigmarken.png'width='180' height='16'></a></p>

<br />
<p><span style='font-style:italic;text-decoration:underline'>
realisiert von:</span><br />
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
Web-Hoster:</span><br />
<a href='https://www.rainbow-web.com' title='Web-Hoster' rel='noopener noreferrer nofollow'
    target='_blank' style='background-color:transparent'>
www.rainbow-web.com</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'></p>

</div>

<?php
    }
}