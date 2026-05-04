<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


/**
 * Summary of Class RegisterInfo
 */
class RegisterInfo
{
    public static function storeUser($data)
    {
        $stmt =
            "INSERT INTO site_users
                (username, email, `status`, pwcode_endtime, notiz, vorname, nachname)
            VALUES (:username, :email, :status, :pwcode_endtime, :notiz, :vorname, :nachname)";
        Database::sendSQL($stmt, $data);
    }
}


// EOF