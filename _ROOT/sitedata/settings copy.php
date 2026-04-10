<?php
namespace Dzg\SiteForm;
use Dzg\SitePrep\Settings as Prep;
use Dzg\Tools\{Database, Auth, Tools};

require_once __DIR__.'/../siteprep/settings.php';
require_once __DIR__.'/../tools/loader_tools.php';


/****************************
 * Summary of Settings
 */
class Settings extends Prep
{
    protected static $active;
    protected static $status_message;



    public static function getCounter($data)
    {
        // Zählerangaben für Autologin-Anzeige des aktuellen Nutzers holen
        // alle aktiven Anmeldungen
        $stmt = "WITH
        cte1 AS (
            SELECT userid, COUNT(*) AS count3
            FROM site_login
            WHERE userid = :userid
                AND (`login`=1 && autologin=1)
                AND identifier != :ident)

        SELECT site_users.userid, username, email, vorname, nachname, pw_hash, count3
        FROM site_users
        LEFT JOIN cte1 ON cte1.userid=site_users.userid
        ";

        $data = [':userid' => $userid, ':ident' => $identifier];
        return Database::sendSQL($stmt, $data, 'fetchall');
    }



    public static function changeData($data)
    {
        $stmt = "UPDATE site_users SET email = :email, username = :username  WHERE userid = :userid";
        $data = [
            ':userid'   => $userid,
            ':username' => $input_usr,
            ':email'    => $input_email ];
        Database::sendSQL($stmt, $data);
    }

    public static function changeData2($data)
    {
        $stmt = "UPDATE site_users SET email = :email WHERE userid = :userid";
        $data = [':userid' => $userid, ':email' => $input_email];
        Database::sendSQL($stmt, $data);
    }


    public static function changeData3($data)
    {
        $stmt = "UPDATE site_users SET username = :username WHERE userid = :userid";
        $data = [':userid' => $userid, ':username' => $input_usr];
        Database::sendSQL($stmt, $data);
    }


    public static function storePW($data)
    {
        $stmt = "UPDATE site_users SET pw_hash = :pw_hash WHERE userid = :userid";
        $data = [':userid' => $userid, ':pw_hash' => $passwort_hash];
        Database::sendSQL($stmt, $data);
    }


    public static function changeUser($data)
    {
        $stmt = "UPDATE site_users SET vorname = :vorname, nachname = :nachname WHERE userid = :userid";
        $data = [
            ':userid'   => $userid,
            ':vorname'  => $input_vor,
            ':nachname' => $input_nach ];
        Database::sendSQL($stmt, $data);
        $usr_data['vorname'] = $input_vor;
        $usr_data['nachname'] = $input_nach;
        $success_msg = "Persönliche Daten geändert.";
    }


    public static function deleteMyAutologin($data)
    {
        // Autologins abmelden, log=0
        $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
            WHERE userid = :userid AND (login = 1 && autologin = 1) AND identifier != :ident";
        #$stmt = "DELETE FROM site_login WHERE userid=:userid AND autologin=1";
        $data = [':userid' => $userid, ':ident' => $identifier];
        Database::sendSQL($stmt, $data);
        $usr_data['count3'] = "";
        $success_msg = "alle meine anderen Autologins beendet.";
    }


    public static function deleteUser($data)
    {
        // Konto löschen
        $stmt = "UPDATE site_users SET status = 'deaktiv' WHERE userid = :userid";
        #$stmt = "DELETE FROM site_users WHERE userid=:userid";
        $data = [':userid' => $userid];     # int
        Database::sendSQL($stmt, $data);
    }


    public static function deleteUsersAutologins($data)
    {
        // wenn auf 'deaktiv' gesetzt, dann auch alle Anmeldungen löschen/beenden, (sonst bei DELETE automatisch per Verknüpfung gelöscht).
        #$stmt = "DELETE FROM site_login WHERE userid = :userid";
        $stmt = "UPDATE site_login SET login = NULL, autologin = NULL
            WHERE userid = :userid AND (login = 1 || autologin = 1)";
        $data = [':userid' => $userid];     # int
        Database::sendSQL($stmt, $data);
    }

}