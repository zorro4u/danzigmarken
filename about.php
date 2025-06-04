<?php
date_default_timezone_set('Europe/Berlin');
session_start();

#require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Auth.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Header.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/../data/dzg/cls/Footer.php';
use Dzg\Cls\{Auth, Header, Footer};

Header::show();
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

<br />
<p><span style='font-style:italic;text-decoration:underline'>
realisiert:</span><br />
<a href='https://keys.openpgp.org/search?q=viele%40gmx.net'
    title='email/pgp @ keys.openpgp.org' rel='noopener noreferrer nofollow' target='_blank' style='background-color:transparent'>
SteV.it</a>&nbsp;
<img src='/assets/pic/extlink.png' width='12' height='12'>

<?php if (Auth::is_checked_in()): ?>
&emsp;|&emsp;
<a href='https://github.com/zeroby1/danzigmarken'
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
