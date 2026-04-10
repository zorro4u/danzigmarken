<?php
namespace Dzg\SitePrep;
use Dzg\SiteData\AdminData as Data;
use Dzg\Tools\{Auth, Tools, CheckIP};

require_once __DIR__.'/../sitedata/admin.php';
require_once __DIR__.'/../tools/auth.php';
require_once __DIR__.'/../tools/tools.php';
require_once __DIR__.'/../tools/checkip.php';


/****************************
 * Summary of Admin
 * class A extends B implements C
 */
class Admin
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */
    protected static $error_msg;
    protected static $usr_data;
    protected static $user_list;
    protected static $login_data;
    protected static $identifier;
    protected static $log_data;
    protected static $reglinks;
    protected static $counter;
    protected static $userid;
    protected static $show_form;
    protected static $new_blocked;


    /****************************
     * Summary of siteEntryCheck
     * CheckIn-Test
     * Plausi-Test: userid, identifier, token_hash
     * set identifier
     * set last_site
     * set showForm
     */
    protected static function siteEntryCheck()
    {
        if (empty($_SESSION['main']))
            $_SESSION['main'] = "/";

        $return2 = ["index", "index2", "details"];
        Tools::lastSite($return2);

        [$usr_data, $login_data, $error_msg] = Auth::checkUser();

        // unberechtigter Seitenaufruf
        $status = (empty($error_msg)) ? true : false;

        // Nutzer nicht angemeldet? Dann weg hier ...
        if (!Auth::isCheckedIn()) {
            header("location: /auth/login.php");
            exit;
        }

        // Nutzer kein Admin? Dann auch weg hier ...
        if ($_SESSION['su'] != 1) {
            header("location: {$_SESSION['lastsite']}");
            exit;
        }

        // globale Variablen setzen
        if ($status) {
            self::$identifier = $login_data['identifier'];
            self::$userid = $usr_data['userid'];
            self::$login_data = $login_data;
            self::$usr_data = $usr_data;
        }
        self::$error_msg = $error_msg;
        self::$show_form = $status;

        self::$new_blocked = CheckIP::write_errorlog_into_DB();
    }


    /****************************
     * Summary of dataPreparation
     */
    protected static function dataPreparation()
    {
        Tools::lastSite();

        // Seiten-Check okay, Seite starten
        if (self::$show_form):

            $userid     = self::$userid;
            $identifier = self::$identifier;

            // --- TAB: Registrierung ---
            $reglinks = Data::getDBregistryLink();


            // --- TAB: Nutzer ---
            $user_list = Data::getDBuserList($userid, $identifier);


            // --- TAB: Autologin / Info ---
            $log_data = Data::get_log_data();


            $ct10 = $ct11 = $ct12 = $ct13 = $ct21 = $ct20 = $ct22 = $ct23 = $ct24 = $ct25 = 0;
            foreach ($user_list as $user) {

                // --- TAB: Info ---
                if ($user['userid'] == $userid) {
                    #$usr_data['username'] = $user['username'];
                    #$usr_data['email'] = $user['email'];
                    #$usr_data['created'] = $user['created'];
                    #$usr_data['changed'] = $user['changed'];
                    #$login_data['created'] = $user['created'];
                    #$login_data['last_seen'] = $user['last_seen'];
                    #$login_data['last_seen'] = $login_data['changed'];
                    #$login_data['ip'] = $user['last_ip'];
                    #$login_data['identifier'] = $identifier;
                    #$login_data['token_hash'] = $login_data['token_hash'];
                    #$login_data['token_endtime'] = $user['token_endtime'];

                    // --- TAB: Autologin ---
                    // meine anderen aktiven
                    if ($ct11 < $user['count11']) $ct11 = $user['count11'];
                    // meine ausgeloggten
                    if ($ct10 < $user['count10']) $ct10 = $user['count10'];
                    // meine beendeten (tot)
                    if ($ct12 < $user['count12']) $ct12 = $user['count12'];
                    // meine abgelaufenen (tot)
                    if ($ct13 < $user['count13']) $ct13 = $user['count13'];
                } else {
                    // alle anderen aktiven
                    if ($ct21 < $user['count21']) $ct21 = $user['count21'];
                    // alle anderen ausgeloggten
                    if ($ct20 < $user['count20']) $ct20 = $user['count20'];
                    // alle anderen beendeten (tot)
                    if ($ct22 < $user['count22']) $ct22 = $user['count22'];
                    // alle anderen abgelaufenen (tot)
                    if ($ct23 < $user['count23']) $ct23 = $user['count23'];
                    // alle anderen Anmeldungen
                    if ($ct24 < $user['count24']) $ct24 = $user['count24'];
                    // alle anderen Nutzer
                    if ($ct25 < $user['count25']) $ct25 = $user['count25'];
                };
            };

            self::$user_list = $user_list;
            self::$log_data = $log_data;
            self::$reglinks = $reglinks;
            self::$counter = [
                10 => $ct10,
                11 => $ct11,
                12 => $ct12,
                13 => $ct13,
                20 => $ct20,
                21 => $ct21,
                22 => $ct22,
                23 => $ct23,
                24 => $ct24,
                25 => $ct25,
            ];

        endif;      # Seiten-Check okay
    }
}

