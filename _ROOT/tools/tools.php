<?php
namespace Dzg\Tools;

require_once __DIR__.'/database.php';


class Tools
{
    /**
     * Stammverzeichnis festlegen, bei Aufruf aus Unterverzeichnis (wie auth/login.php)
     * sonst Probleme zB. mit css Aufruf
     * nötig für Cookies, Header, Footer
     */
    public static function rootDir(): string
    {
        # mögliche root_Dir:
        # "/" bei externem Aufruf,
        # "stamps" bei internem Aufruf,
        # "_prepare" debug-Mode
        $root_dir = ['stamps', '_prepare', 'localhost'];

        # array aus den Dir-Ebenen des Aufrufs
        $act_pth = explode('/', dirname($_SERVER['REQUEST_URI']));

        # Anzahl der Dir-Ebenen
        $pth_len = count($act_pth)-1;

        # root_dir-Elemente in den Dir-Ebenen finden
        for ($i=$pth_len; $i>0; $i--) {
            if (in_array($act_pth[$i], $root_dir))
                break;
        };

        $dir = implode('/', array_slice($act_pth, 0, $i+1));
        if ($dir==='/' || $dir==='\\') {
            $dir = '';
        };

        return $dir;
    }


    /**
     * Herkunftsseite speichern
     *
     * Standard-Rücksprung: "index", "index2", "details"
     * @param array $return2 : zusätzl. Rücksprungseiten
     * - @return string $_SESSION['main']
     * - @return string $_SESSION['lastsite']
     */
    public static function lastSite(array $return2 = []): void
    {
        $return  = ["index", "index2", "details"];
        $return2 =  (!empty($return2))
            ? array_merge($return, $return2)
            : $return;

        #$_SESSION['main'] ??= $_SESSION['rootdir'].'/index.php';
        $_SESSION['main'] ??= "/";

        if (isset($_SERVER['HTTP_REFERER'])
            && (strpos($_SERVER['HTTP_REFERER'], $_SERVER['PHP_SELF']) === false))
        {
            # wenn VorgängerSeite bekannt und
            # nicht die aufgerufene Seite selbst ist, speichern
            $referer = str_replace("change", "details", $_SERVER['HTTP_REFERER']);
            $fn_referer = pathinfo($referer)['filename'];

            # wenn Herkunft von den target-Seiten,
            # dann zu diesen, ansonsten Standardseite
            $_SESSION['lastsite'] = (in_array($fn_referer, $return2))
                ? $referer
                : $_SESSION['main'];
        }

        # wenn nix gesetzt ist, auf Standard index.php verweisen
        elseif (empty($_SERVER['HTTP_REFERER'])
            && empty($_SESSION['lastsite']))
        {
            $_SESSION['lastsite'] = (!empty($_SESSION['main']))
                ? $_SESSION['main']
                : "/";
        }

        #elseif (empty($_SERVER['HTTP_REFERER']) && !empty($_SESSION['lastsite'])) {}

        else {
            $_SESSION['lastsite'] = $_SESSION['main'];
        };
    }


    /**
     * Returns the URL to the site without the script name    (-> passwortvergessen.php)
     */
    public static function getSiteURL(): string
    {
        $myHost = 'www.danzigmarken.de';
        $protocol = (!empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off'
            || $_SERVER['SERVER_PORT'] == 443)
            ? "https://"
            : "http://";
        return $protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
        #return $protocol.$myHost.'/';
    }


    /**
     * Input-Eingabe bereinigen
     */
    public static function cleanInput(string $data): string
    {
        $data = trim($data);            // Leerzeichen anfang/ende entfernen
        $data = stripslashes($data);    // Blackslash entfernen
        $data = strip_tags($data);      // html-tags entfernen
        return $data;
    }


    /**
     * (Error-) Array zu String wandeln
     */
    public static function arr2str(array $array): string
    {
        return implode("<br>", $array);
    }


    /**
     * Statusmeldung auf html ausgeben
     */
    public static function
    statusOut(string $success_msg, string $error_msg, bool $exit=false): string
    {
        $ausgabe = '';

        # id 'status' blendet sich per JS aus -> toolbox.js
        if (isset($success_msg) && $success_msg !== "") {
            $ausgabe =
                "<div id='status' class='alert alert-success'>".
                "<a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>".
                $success_msg."</div>";
        }
        elseif ((isset($error_msg) && $error_msg !== "")
            || !empty($error_msg))
        {
            $exit_info = $exit
                ? "<br>-- <b>< <a href='logout'>Logout</a> in 5sec ></b> --"
                : "";

            $ausgabe =
                "<div id='statusX' class='alert alert-danger' style='display:block;'>".
                $error_msg.$exit_info."</div>";
        };

        return $ausgabe;
    }
}


// EOF