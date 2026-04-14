<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class Activate
{
    /**
     * Nutzer mit Aktivierungscode suchen
     */
    public static function getUser($input_code)
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
    public static function setActivated($userid)
    {
        $data = [':userid' => $userid];     # int
        $stmt = "UPDATE site_users
            SET `status`='activated', pwcode_endtime=Null, notiz=Null
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    /**
     * veralteten Eintrag löschen
     */
    public static function deleteOld($userid)
    {
        #$stmt0 = "UPDATE site_users SET status=NULL, pwcode_endtime=NULL, notiz=NULL, pwc=NULL WHERE userid=:userid";
        $data = [':userid' => $userid];     # int
        $stmt = "DELETE FROM site_users WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }
}

