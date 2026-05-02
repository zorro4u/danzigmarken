<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class Register
{
    public static function getUser(string $input_code)
    {
        // Registrierungs-Link auf Gültigkeit prüfen
        $stmt =
            "SELECT userid, email, pwcode_endtime
            FROM site_users WHERE `status`=:status";
        $data = [':status' => $input_code];
        return Database::sendSQL($stmt, $data, 'fetch');
    }


    public static function deleteOldEntry($userid): void
    {
        // veralteten Eintrag löschen
        $stmt0 = "UPDATE site_users
            SET `status`=NULL, pwcode_endtime=NULL, notiz=NULL, pwc=NULL WHERE userid=:userid";
        $stmt = "DELETE FROM site_users WHERE userid=:userid";
        $data = [':userid' => $userid];     # int
        Database::sendSQL($stmt, $data);
    }


    public static function searchActivatedUser($data): array
    {
        // usernamen/email im Bestand suchen
        $stmt = "SELECT username, email FROM site_users
            WHERE `status`='activated'
            AND (username=:username OR email=:email)";
        return Database::sendSQL($stmt, $data, 'fetchall');
    }


    public static function storeUser($data): void
    {
        // Nutzerdaten in DB eintragen
        $stmt = "UPDATE site_users
            SET username=:username, email=:email, pw_hash=:pw_hash,
                `status`=:status, pwcode_endtime=:pwcode_endtime, notiz=:notiz
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }
}


// EOF