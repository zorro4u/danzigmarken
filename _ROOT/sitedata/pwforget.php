<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class PWforget
{
    /**
     * Email in DB suchen
     */
    public static function searchEmail(string $input_email): array
    {
        $data = [':email' => $input_email];
        $stmt = "SELECT userid, email, username, vorname, nachname FROM site_users WHERE email = :email";
        return Database::sendSQL($stmt, $data, 'fetch');
    }


    /**
     * temporären Passwort-Code in DB schreiben,
     * 2 Tage gültig
     */
    public static function setPassCode(array $data): void
    {
        $stmt = "UPDATE site_users
            SET pwcode_hash = :pwcode_hash, pwcode_endtime = :pwcode_endtime, notiz = :notiz
            WHERE userid = :userid";
        Database::sendSQL($stmt, $data);
    }
}


// EOF