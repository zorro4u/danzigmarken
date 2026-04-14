<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class RegisterInfo
{
    public static function storeUser($data)
    {
        // Nutzerdaten schon einmal in DB temporär erfassen
        $stmt =
            "INSERT INTO site_users
                (username, email, `status`, pwcode_endtime, notiz, vorname, nachname)
            VALUES (:username, :email, :status, :pwcode_endtime, :notiz, :vorname, :nachname)";
        Database::sendSQL($stmt, $data);
    }
}

