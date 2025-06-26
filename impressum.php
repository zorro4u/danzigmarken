<?php
date_default_timezone_set('Europe/Berlin');
session_start();

require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Footer.php';
use Dzg\{Auth, Header, Footer};

Header::show();
?>

<div class='container main-container registration-form'>
<br /><br />

<p><span style='font-style:italic;text-decoration:underline'>
Herausgeber:</span><br />
Heinz Vierling<br />
Ahornweg 8<br />
15518 Rauen<br />
Deutschland</p>

<p><span style='font-style:italic;text-decoration:underline'>
Kontakt:</span><br />
Telefon: +49 (0) 3361 711306<br />
E-Mail:
    <a href='/kontakt' title='Kontaktformular' style='background-color:transparent'>
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

<?php if (Auth::is_checked_in()): ?>
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

<?php Footer::show("impressum"); ?>
