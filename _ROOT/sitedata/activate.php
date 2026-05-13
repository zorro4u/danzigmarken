<?php
namespace Dzg;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class ActivateData
{
    /**
     * Nutzer mit Aktivierungscode suchen
     */
    public static function getUser(string $input_code): array
    {
        $data = [':code' => $input_code];
        $stmt = "SELECT userid, username, pwcode_endtime
            FROM site_users
            WHERE `status`=:code";
        return Database::sendSQL($stmt, $data, 'fetch');
    }


    /**
     * Status auf 'activated' setzen -> zur späteren Auswertung
     */
    public static function setActivated(int $userid): void
    {
        $data = [':userid' => $userid];     // int
        $stmt = "UPDATE site_users
            SET `status`='activated', pwcode_endtime=Null, notiz=Null
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    /**
     * veralteten Eintrag löschen
     */
    public static function deleteOld(int $userid): void
    {
        #$stmt0 = "UPDATE site_users SET status=NULL, pwcode_endtime=NULL, notiz=NULL, pwc=NULL WHERE userid=:userid";
        $data = [':userid' => $userid];     // int
        $stmt = "DELETE FROM site_users WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }
}


// EOF