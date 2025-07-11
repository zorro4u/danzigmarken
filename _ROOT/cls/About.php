<?php
namespace Dzg;
require_once __DIR__.'/Header.php';
require_once __DIR__.'/Footer.php';


class About
{
    /***********************
     * Anzeige der Webseite
     */
    public static function show()
    {
        Header::show();
        self::siteOutput();
        Footer::show("impressum");
    }


    /****************************
     * Summary of siteOutput
     */
    private static function siteOutput()
    {
?>

<div class='container main-container registration-form'>
<br /><br />

<p><span style='font-style:italic;text-decoration:underline'>
verantwortlich:</span><br />
Heinz Vierling<br />
15518 Rauen<br />
Deutschland</p>

<p><span style='font-style:italic;text-decoration:underline'>
Kontakt:</span><br />
E-Mail:
    <a href='/kontakt' title='Kontaktformular' style='background-color:transparent'>
    <img src='/assets/pic/email_danzigmarken.png'width='180' height='16'></a></p>


<?php if (Auth::isCheckedIn()): ?>

<br />
<p><span style='font-style:italic;text-decoration:underline'>
realisiert von:</span><br />
Zorro4U
&emsp;|&emsp;
<a href='https://github.com/zorro4u/danzigmarken'
    title='Code @ github.com' rel='noopener noreferrer nofollow'
    target='_blank' style='background-color:transparent'>
GitHub</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'></p>

<br />
<p><span style='font-style:italic;text-decoration:underline'>
Web-Hoster:</span><br />
<a href='https://www.rainbow-web.com' title='Web-Hoster' rel='noopener noreferrer nofollow'
    target='_blank' style='background-color:transparent'>
www.rainbow-web.com</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'></p>

<?php endif; ?>

</div>

<?php
    }
}