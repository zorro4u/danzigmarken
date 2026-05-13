<?php
namespace Dzg;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class PWresetData
{
    /**
     * PassCode holen
     */
    public static function getPassCode(string $pwcode_hash): array
    {
        $data = [':pwcode_hash' => $pwcode_hash];
        $stmt = "SELECT userid, username, email, vorname, nachname, pwcode_endtime
            FROM site_users WHERE pwcode_hash=:pwcode_hash";
        return Database::sendSQL($stmt, $data, 'fetch');
    }


    /**
     * PassCode deaktivieren
     */
    public static function deletePassCode(int $userid): void
    {
        $data = [':userid' => $userid];     // int
        $stmt = "UPDATE site_users
            SET pwcode_hash=NULL, pwcode_endtime=NULL,
            pwc=NULL, notiz=NULL WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    /**
     * Passwort speichern, PassCode löschen
     */
    public static function
    storeNewPassword(int $userid, string $passwort_hash): void
    {
        // TODO: alle Autologins beenden
        $data = [':userid' => $userid, ':pw_hash' => $passwort_hash];
        $stmt = "UPDATE site_users
            SET pw_hash=:pw_hash, status='activated', pwcode_hash=NULL,
            pwcode_endtime=NULL, pwc=NULL, notiz=NULL
            WHERE userid = :userid";
        Database::sendSQL($stmt, $data);
    }
}


// EOF