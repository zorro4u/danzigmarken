<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class Settings
{
    public static function getCounter(int $userid, string $identifier): array
    {
        // Zählerangaben für Autologin-Anzeige des aktuellen Nutzers holen
        // alle aktiven Anmeldungen
        $data = [':userid' => $userid, ':ident' => $identifier];

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

        return Database::sendSQL($stmt, $data, 'fetchall');
    }


    public static function changeUser(array $data): void
    {
        $stmt = "UPDATE site_users
            SET email=:email, username=:username
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    public static function changeUserMail(array $data): void
    {
        $stmt = "UPDATE site_users
            SET email=:email
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    public static function changeUserName(array $data): void
    {
        $stmt = "UPDATE site_users
            SET username=:username
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    public static function storePW(array $data): void
    {
        $stmt = "UPDATE site_users
            SET pw_hash=:pw_hash
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    public static function changeUserData(array $data): void
    {
        $stmt = "UPDATE site_users
            SET vorname=:vorname, nachname=:nachname
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    public static function deleteMyAutologin(array $data): void
    {
        // Autologins abmelden, log=0
        $stmt = "UPDATE site_login
            SET `login`=NULL, autologin=NULL
            WHERE userid=:userid
                AND (`login`=1 && autologin=1)
                AND identifier != :ident";
        #$stmt = "DELETE FROM site_login WHERE userid=:userid AND autologin=1";
        Database::sendSQL($stmt, $data);
    }


    public static function deleteUser(int $userid): void
    {
        // Konto löschen
        $data = [':userid' => $userid];     # int
        $stmt = "UPDATE site_users SET `status`='deaktiv' WHERE userid=:userid";
        #$stmt = "DELETE FROM site_users WHERE userid=:userid";
        Database::sendSQL($stmt, $data);

        // wenn auf 'deaktiv' gesetzt, dann auch alle Anmeldungen löschen/beenden, (sonst bei DELETE automatisch per Verknüpfung gelöscht).
        self::deleteUsersAutologins($userid);
    }


    public static function deleteUsersAutologins(int $userid): void
    {
        // wenn auf 'deaktiv' gesetzt, dann auch alle Anmeldungen löschen/beenden, (sonst bei DELETE automatisch per Verknüpfung gelöscht).
        #$stmt = "DELETE FROM site_login WHERE userid = :userid";
        $data = [':userid' => $userid];     # int
        $stmt = "UPDATE site_login SET `login`=NULL, autologin=NULL
            WHERE userid=:userid
                AND (`login`=1 || autologin=1)";
        Database::sendSQL($stmt, $data);
    }
}


// EOF