<?php
namespace Dzg\SitePrep;
use Dzg\Tools\Tools;
use Dzg\Mail\{MailConfig, AntiSpam};

require_once __DIR__.'/../tools/tools.php';
#require_once __DIR__.'/../mail/Mail.php';
require_once __DIR__.'/../mail/MailConfig.php';
require_once __DIR__.'/../mail/AntiSpam.php';
#require_once __DIR__.'/../mail/RateLimiting.php';
#require_once __DIR__.'/../mail/Captcha.php';

#require $_SERVER['DOCUMENT_ROOT']."/assets/vendor/autoload.php";
#use Gregwar\Captcha\PhraseBuilder;


/**
 * Webseite: Kontaktformular
 */
class Contact
{
    protected static $question;

    protected static function siteClose()
    {
        if (!empty($_POST)) {
        unset($_POST, $_GET, $_REQUEST, $_SESSION['captcha'], $_SESSION['captcha_code'], $_SESSION['captcha_frage']);
        }
    }


    /**
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
        $cfg = MailConfig::$cfg;
        $question = [[],[]];

        if ($cfg['Sicherheitsfrage']) {
            $question = AntiSpam::getRandomQuestion();  // [id, question]

            # Bei Erstaufruf 'frage_id' setzen (statt per Formular zu senden)
            # Abfrage, ob existiert oder nicht leer.
            # Da richtige Antwort nie '0', ist alles okay.
            if (empty($_POST['answer']))
                $_SESSION['Sicherheitsfrage'] = $question[0];
        }

        # ggf. übrig gebliebene Werte löschen
        if ($cfg['Sicherheitscode'] && empty($_POST['sicherheitscode'])) {
            unset($_SESSION['captcha_code'], $_SESSION['captcha'], $_SESSION['phrase']);
        }

        self::$question = $question;
    }
}




######################################
#unset($_POST, $_GET, $_REQUEST);
#var_dump($_POST);
#foreach ($_POST AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_GET AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_REQUEST AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_COOKIE AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_SERVER AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";
#foreach ($_SESSION AS $k=>$v) {echo $k, ": ", $v, "<br>";};echo"<br>";

#if (!empty($_SESSION['su'])) var_dump($_SESSION['idx2'], $_SESSION['siteid']);

#print_r('ident: '.$_COOKIE['identifier'].'<br>');
#print_r('token: '.$_COOKIE['securitytoken'].'<br>');
#print_r('token: '.sha1($_COOKIE['securitytoken']).'<br>');
#print_r(pathinfo($_SESSION['main']));
