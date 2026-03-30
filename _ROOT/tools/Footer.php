<?php
namespace Dzg\Tools;

require_once __DIR__.'/Database.php';
require_once __DIR__.'/Logger.php';


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

            case "empty":
                self::empty();
                break;

            case "impressum":
                self::impressum();
                break;

            default:
                self::default();
        }
        #Logger::log();
    }


    /***********************
     * Klassenvariablen / Eigenschaften
     */
    private const PROJECT_DIRS = ['',
        '/', '/account', '/assets/css',
        '/auth'];
    private const PROJECT_EXT = ['php', 'html', 'css'];

    // Textbausteine für Mitteltext
    // allg    (cop0,com,pic,ico)
    // auth    (cop0,com,ico)
    // account (cop0,com,ico)
    // kontakt (cop1,com,ico)



    #<i class='fa-regular fa-address-card'></i>&ensp;
    private const IMPRESSUM =
        "<a class='foot-link' href='/impressum'
        title='Impressum'>Impressum</a>
    ";
    private const ABOUT =
        "<a class='foot-link' href='/about.php'
        title='About'>About</a>
    ";
    private const CC_COPY0 =
        "&copy; 2023 danzigmarken.de
    ";
    private const CC_COPY = "
        &copy; 2023 <a class='foot-link' href='https://www.danzigmarken.de/index.php'
        title='www.danzigmarken.de'>danzigmarken.de</a>
    ";
    private const CC_COPY2 =
        "&copy; 2023 <a class='foot-link' href='/kontakt.php'
        title='Nachricht an danzigmarken.de'>danzigmarken.de</a>
    ";
    private const CC_COM = "
        <a class='foot-link' href='http://creativecommons.org/licenses/by-nc-sa/4.0/deed.de'
        target='_blank' rel='noopener noreferrer nofollow' style='display:inline-block;'
        title='license CC-BY-NC-SA'>
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
        Images licensed by
        <a class='foot-link' href='https://creativecommons.org/licenses/by-nc-sa/4.0/deed.de'
        target='_blank' rel='noopener noreferrer nofollow'
        title='licensed via creativecommons.org'>creativecommons.org</a>
    ";
    private const CC_ICO = "
        <br>
        Icons from <a class='foot-link' href='https://fontawesome.com/'
        target='_blank' rel='noopener noreferrer nofollow'
        title='licensed via fontawesome.com'>fontawesome.com</a>
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
            <script src=\"/assets/bootstrap3/js/bootstrap.min.js\"></script>
            <!-- eigene Scripte -->
            <script src=\"/assets/js/toolbox.js\"></script>
            ";

    // Matomo
    private const MATOMO = "
            <p><img referrerpolicy='no-referrer-when-downgrade'
            src='//matomo.danzigmarken.de/mato.php?idsite=1&amp;rec=1'
            style='border:0;' alt=''></p>";

    // HTML Dokumenten-Ende
    private const HTML_END = "</body></html>";



    private static function mato()
    {
        return (empty($_SESSION['su']))
            ? self::MATOMO
            : "";
    }


    // die neueste/letzte Änderungszeit einer Projektdatei
    private static string $last_changed;
    public static function lastChanged(): string
    {
        if (empty(self::$last_changed)) {
            self::setLastChanged(self::PROJECT_DIRS, self::PROJECT_EXT);
        }
        return self::$last_changed;
    }
    private static function setLastChanged(array $dirs, array $ext)
    {
        self::$last_changed = self::getLastChanged($dirs, $ext);
    }
    private static function getLastChanged(array $dirs, array $file_extensions)
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
        return self::$version
            ?? ($_SESSION['version'] ?? Database::version());
    }


    protected static function default()
    {
        // Standard-Mitteltext
        $txt['mitte'] = self::CC_COPY . self::CC_COM . self::ABOUT . self::CC_PIC . self::CC_ICO;

        // Rechts
        $txt['rechts'] = "
            <a class='foot-link' href='/kontakt' title='Nachricht an danzigmarken.de'>
            <i class='fa-regular fa-envelope'></i>&ensp;Kontakt</a>";

        // die neueste/letzte Änderungszeit einer Kontakt-Projektdatei
        $txt['version'] = self::version();
        $txt['last']    = self::lastChanged();

        $txt['br'] = "";

        $output = self::footer_txt($txt);

        echo $output;
    }


    protected static function empty()
    {
        $output = "<footer class='container'></footer>";
        $output .= self::BOOTSTRP;
        $output .= self::mato();
        $output .= self::HTML_END;

        echo $output;
    }


    protected static function kontakt()
    {
        // Mitteltext
        $txt['mitte'] = self::CC_COPY . self::CC_COM . self::ABOUT . self::CC_ICO;

        // Rechts
        $txt['rechts'] = "
            <a class='foot-link' href='/index' title='Startseite www.danzigmarken.de'>
            <i class='fas fa-home'></i>&ensp;danzigmarken.de</a>";

        // die neueste/letzte Änderungszeit einer Kontakt-Projektdatei
        $project_dirs = ['/assets/css'];
        $txt['last']  = self::getLastChanged($project_dirs, self::PROJECT_EXT);

        $txt['br'] = "";

        $output = self::footer_txt($txt);

        echo $output;
    }


    protected static function auth()
    {
        // Mitteltext
        $txt['mitte'] = self::CC_COPY . self::CC_COM . self::ABOUT . self::CC_ICO;

        // Rechts
        $txt['rechts'] = "
            <a class='foot-link' href='/kontakt' title='Nachricht an danzigmarken.de'>
            <i class='fa-regular fa-envelope'></i>&ensp;Kontakt</a>";

        // die neueste/letzte Änderungszeit einer Auth-Projektdatei
        $project_dirs = ['/assets/css', '/auth'];
        $txt['last']  = self::getLastChanged($project_dirs, self::PROJECT_EXT);

        $txt['br'] = "<br><br><br>";

        $output = self::footer_txt($txt);

        echo $output;
    }


    protected static function account()
    {
        // Mitteltext
        $txt['mitte'] = self::CC_COPY . self::CC_COM . self::IMPRESSUM;

        // Rechts
        $txt['rechts'] = "
            <a class='foot-link' href='/kontakt' title='Nachricht an danzigmarken.de'>
            <i class='fa-regular fa-envelope'></i>&ensp;Kontakt</a>";

        // die neueste/letzte Änderungszeit einer Account-Projektdatei
        $project_dirs = ['/assets/css', '/account'];
        $txt['last']  = self::getLastChanged($project_dirs, self::PROJECT_EXT);

        $txt['br'] = "<br><br><br>";

        $output = self::footer_txt($txt);

        echo $output;
    }


    protected static function impressum()
    {
        // Mitteltext
        $txt['mitte'] = self::CC_COPY . self::CC_COM . self::CC_ICO;

        // Rechts
        $txt['rechts'] = "
            <a class='foot-link' href='/index' title='Startseite www.danzigmarken.de'>
            <i class='fas fa-home'></i>&ensp;danzigmarken.de</a>";

        // die neueste/letzte Änderungszeit einer Account-Projektdatei
        #$project_dirs = ['/about'];
        $project_dirs = ['/'];
        $txt['last']  = self::getLastChanged($project_dirs, self::PROJECT_EXT);

        $txt['br'] = "<br><br><br>";

        $output = self::footer_txt($txt);

        echo $output;
    }


    private static function footer_txt($txt)
    {
        $br     = $txt['br'];
        $mitte  = $txt['mitte'];
        $rechts = $txt['rechts'];
        $links  = self::footer_links($txt);

        $output = "
            <footer class='container'>
            {$br}
            <hr>
            <div class='last'>
                <div class='links kleingrau'>{$links}</div>
                <div class='mitte kleingrau cc'>{$mitte}</div>
                <div class='rechts kleingrau'>{$rechts}</div>
            </div>
            </footer>";
        $output .= self::BOOTSTRP;
        $output .= self::mato();
        $output .= self::HTML_END;
        return $output;
    }


    private static function footer_links($txt)
    {
        $last    = $txt['last'];
        $version = $txt['version'] ?? "";

        $li1 = isset($txt['version'])
            ? "<a class='info' title='Datenstand vom {$version}'>
               <i class='fa fa-database'></i>&emsp;<i>{$version}</i></a>
               <br>"
            : "";
        $li2 = "
            <a class='info' title='Webseite vom {$last}'>
            <i class='fa fa-pencil'></i>&emsp;<i>{$last}</i></a>";

        return $li1.$li2;
    }

}