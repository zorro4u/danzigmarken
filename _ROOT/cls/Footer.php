<?php
namespace Dzg\Cls;

include_once __DIR__.'/../inc/logged.inc.php';
require_once __DIR__.'/Database.php';
use Dzg\Cls\Database;

/***********************
 * Summary of Footer
 */
class Footer
{
    public static function show($stile='default')
    {
        switch ($stile)
        {
            case "kontakt":
                self::kontakt();
                break;

            case "auth":
                self::auth();
                break;

            case "account":
                self::account();
                break;

            case "leer":
                self::leer();
                break;

            case "impressum":
                self::impressum();
                break;

            default:
                self::default();
        }
    }


    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private const PROJECT_DIRS = ['',
        '/about', '/account', '/assets/css',
        '/auth'];
    private const PROJECT_EXT = ['php', 'html', 'css'];

    // Textbausteine für Mitteltext
    // allg    (cop0,com,pic,ico)
    // auth    (cop0,com,ico)
    // account (cop0,com,ico)
    // kontakt (cop1,com,ico)



    #<i class='fa-regular fa-address-card'></i>&ensp;
    private const IMPRESSUM =
        "<a class='foot-link' href='/about/impressum'
        title='Impressum'>Impressum</a>
    ";
    private const ABOUT =
        "<a class='foot-link' href='/about/about'
        title='About'>About</a>
    ";
    private const CC_COPY0 =
        "&copy; 2023 danzigmarken.de
    ";
    private const CC_COPY1 = "
        &copy; 2023 <a class='foot-link' href='https://www.danzigmarken.de/index.php'
        title='www.danzigmarken.de'>danzigmarken.de</a>
    ";
    private const CC_COPY2 =
        "&copy; 2023 <a class='foot-link' href='/kontakt.php'
        title='Nachricht an danzigmarken.de'>danzigmarken.de</a>
    ";
    private const CC_COM = "
        <a class='foot-link' href='http://creativecommons.org/licenses/by-nc-sa/4.0/deed.de'
        target='_blank' rel='license noopener noreferrer' style='display:inline-block;'
        title='licenses creativecommons.org'>
        <img style='height:8px!important; margin-left:6px; vertical-align:text-top;'
        src='/assets/pic/cc.svg' alt='cc'>
        <img style='height:8px!important; margin-left:0px; vertical-align:text-top;'
        src='/assets/pic/by.svg' alt='by'>
        <img style='height:8px!important; margin-left:0px; vertical-align:text-top;'
        src='/assets/pic/nc.svg' alt='nc'>
        <img style='height:8px!important; margin-right:6px; vertical-align:text-top;'
        src='/assets/pic/sa.svg' alt='sa'></a>
    ";
    private const CC_PIC = "
        <br>
        Bilder sind lizensiert via
        <a class='foot-link' href='https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de'
        target='_blank' title='licenses creativecommons.org'>Creative&nbsp;Commons</a>
    ";
    private const CC_ICO = "
        <br>
        Icons von <a class='foot-link' href='https://fontawesome.com/' target='_blank' title='licenses fontawesome.com'>Font&nbsp;Awesome</a>
    ";

    // Bootstrap core JavaScript
    // Placed at the end of the document so the pages load faster
    private const BOOTSTRP = "
            <!-- Bootstrap core JavaScript -->
            <script src=\"/assets/bootstrap3/js/jquery.min.js\"></script>
            <script>
            window.jQuery ||
            document.write('<script src=\"/assets/bootstrap3/js/jquery.min.js\"><\/script>')
            </script>
            <script src=\"/assets/bootstrap3/js/bootstrap.min.js\"></script>";

    // Matomo
    private const MATOMO = "
            <p><img referrerpolicy='no-referrer-when-downgrade'
            src='//matomo.danzigmarken.de/mato.php?idsite=1&amp;rec=1'
            style='border:0;' alt=''></p>";

    // HTML Dokumenten-Ende
    private const HTML_END = "</body></html>";


    // die neueste/letzte Änderungszeit einer Projektdatei
    private static string $lastchanged;
    public static function lastchanged(): string
    {
        if (empty(self::$lastchanged)) {
            self::set_lastchanged(self::PROJECT_DIRS, self::PROJECT_EXT);
        }
        return self::$lastchanged;
    }
    private static function set_lastchanged(array $dirs, array $ext)
    {
        self::$lastchanged = self::get_lastchanged($dirs, $ext);
    }

    private static function get_lastchanged(array $dirs, array $file_extensions)
    {
        $times = 0;
        $fullrootpath = $_SERVER['DOCUMENT_ROOT'].$_SESSION['rootdir'];

        foreach ($dirs as $dir) {
            $fullpath = $fullrootpath.$dir;

            foreach (scandir($fullpath) as $file) {
                if ($file === ".." or $file === ".") continue;

                $suffix = pathinfo($file, PATHINFO_EXTENSION);
                $ffn = $fullpath.'/'.$file;

                // nur php Dateien
                if (!in_array($suffix, $file_extensions)) continue;

                // nur die neueste Änderungszeit speichern
                if (filemtime($ffn) > $times) {
                    $times = filemtime($ffn);
                }
            }
        }
        return date('ymd', $times);
    }


    private static string $version;
    private static function version(): string
    {
        if (empty(self::$version)) {
            self::$version = !empty($_SESSION['version']) ? $_SESSION['version'] : Database::version();
        }
        return self::$version;
    }


    protected static function default()
    {
        // Standard-Mitteltext
        $mitte = self::CC_COPY0 . self::CC_COM . self::ABOUT . self::CC_PIC . self::CC_ICO;
        $version = self::version();
        $lastchanged = self::lastchanged();

        $output = "
            <footer class='container'>
            <hr>
            <div class='last'>
                <div class='links kleingrau'>
                    <a class='info' title='Datenstand vom {$version}'>
                    <i class='fa fa-database'></i>&emsp;<i>{$version}</i></a>
                    <br>
                    <a class='info' title='Webseite vom {$lastchanged}'>
                    <i class='fa fa-pencil'></i>&emsp;<i>{$lastchanged}</i></a></div>
                <div class='mitte kleingrau cc'>{$mitte}</div>
                <div class='rechts kleingrau'>
                    <a class='foot-link' href='/kontakt' title='Nachricht an danzigmarken.de'>
                    <i class='fa-regular fa-envelope'></i>&ensp;Kontakt</a></div>
            </div>
            </footer>";

        $output .= self::BOOTSTRP;
        $output .= self::MATOMO;
        $output .= self::HTML_END;

        echo $output;
    }


    protected static function leer()
    {
        $output = "<footer class='container'></footer>";
        $output .= self::BOOTSTRP;
        $output .= self::MATOMO;
        $output .= self::HTML_END;

        echo $output;
    }


    protected static function kontakt()
    {
        // Mitteltext
        $mitte = self::CC_COPY0 . self::CC_COM . self::ABOUT . self::CC_ICO;

        // die neueste/letzte Änderungszeit einer Kontakt-Projektdatei
        $project_dirs = ['/assets/css'];
        $lastchanged = self::get_lastchanged($project_dirs, self::PROJECT_EXT);


        $output = "
            <footer class='container'>
            <hr>
            <div class='last'>
                <div class='links kleingrau'>
                    <a class='info' title='Webseite vom {$lastchanged}'>
                    <i class='fa fa-pencil'></i>&emsp;<i>{$lastchanged}</i></a></div>
                <div class='mitte kleingrau cc'>{$mitte}</div>
                <div class='rechts kleingrau'>
                    <a class='foot-link' href='/index' title='Startseite www.danzigmarken.de'>
                    <i class='fas fa-home'></i>&ensp;danzigmarken.de</a></div>
            </div>
            </footer>";

        $output .= self::BOOTSTRP;
        $output .= self::MATOMO;
        $output .= self::HTML_END;

        echo $output;
    }


    protected static function auth()
    {
        // Mitteltext
        $mitte = self::CC_COPY0 . self::CC_COM . self::ABOUT . self::CC_ICO;

        // die neueste/letzte Änderungszeit einer Auth-Projektdatei
        $project_dirs = ['/assets/css', '/auth'];
        $lastchanged = self::get_lastchanged($project_dirs, self::PROJECT_EXT);

        $output = "
            <footer class='container'>
            <br><br><br>
            <hr>
            <div class='last'>
                <div class='links kleingrau'>
                    <a class='info' title='Webseite vom {$lastchanged}'>
                    <i class='fa fa-pencil'></i>&emsp;<i>{$lastchanged}</i></a></div>
                <div class='mitte kleingrau cc'>{$mitte}</div>
                <div class='rechts kleingrau'>
                    <a class='foot-link' href='/kontakt' title='Nachricht an danzigmarken.de'>
                    <i class='fa-regular fa-envelope'></i>&ensp;Kontakt</a></div>
            </div>
            </footer>";

        $output .= self::BOOTSTRP;
        $output .= self::MATOMO;
        $output .= self::HTML_END;

        echo $output;
    }

    protected static function account()
    {
        // Mitteltext
        $mitte = self::CC_COPY0 . self::CC_COM . self::IMPRESSUM;

        // die neueste/letzte Änderungszeit einer Account-Projektdatei
        $project_dirs = ['/assets/css', '/account'];
        $lastchanged = self::get_lastchanged($project_dirs, self::PROJECT_EXT);

        $output = "
            <footer class='container'>
            <br><br><br>
            <hr>
            <div class='last'>
                <div class='links kleingrau'>
                    <a class='info' title='Webseite vom {$lastchanged}'>
                    <i class='fa fa-pencil'></i>&emsp;<i>{$lastchanged}</i></a></div>
                <div class='mitte kleingrau cc'>{$mitte}</div>
                <div class='rechts kleingrau'>
                    <a class='foot-link' href='/kontakt' title='Nachricht an danzigmarken.de'>
                    <i class='fa-regular fa-envelope'></i>&ensp;Kontakt</a></div>
            </div>
            </footer>";

        $output .= self::BOOTSTRP;
        $output .= self::MATOMO;
        $output .= self::HTML_END;

        echo $output;
    }

    protected static function impressum()
    {
        // Mitteltext
        $mitte = self::CC_COPY0 . self::CC_COM . self::CC_PIC . self::CC_ICO;

        // die neueste/letzte Änderungszeit einer Account-Projektdatei
        $project_dirs = ['/about'];
        $lastchanged = self::get_lastchanged($project_dirs, self::PROJECT_EXT);

        $output = "
            <footer class='container'>
            <br><br><br>
            <hr>
            <div class='last'>
                <div class='links kleingrau'>
                    <a class='info' title='Webseite vom {$lastchanged}'>
                    <i class='fa fa-pencil'></i>&emsp;<i>{$lastchanged}</i></a></div>
                <div class='mitte kleingrau cc'>{$mitte}</div>
                <div class='rechts kleingrau'>
                    <a class='foot-link' href='/kontakt' title='Nachricht an danzigmarken.de'>
                    <i class='fa-regular fa-envelope'></i>&ensp;Kontakt</a></div>
            </div>
            </footer>";

        $output .= self::BOOTSTRP;
        $output .= self::MATOMO;
        $output .= self::HTML_END;

        echo $output;
    }
}