<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class PWforgetData
{
    public static function searchEmail($input_email)
    {
        // Email in DB suchen
        $data = [':email' => $input_email];
        $stmt = "SELECT userid, email, username, vorname, nachname FROM site_users WHERE email = :email";
        return Database::sendSQL($stmt, $data, 'fetch');
    }


    public static function setPassCode($data)
    {
        // temporären Passwort-Code in DB schreiben, 2 Tage gültig
        $stmt = "UPDATE site_users
            SET pwcode_hash = :pwcode_hash, pwcode_endtime = :pwcode_endtime, notiz = :notiz
            WHERE userid = :userid";
        Database::sendSQL($stmt, $data);
    }

}