<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;
use PDO;
use PDOException;

require_once __DIR__.'/../tools/database.php';


class LoginData
{
    public static function searchUser($data): array
    {
        // Nutzerdaten in DB finden & holen
        $stmt = "SELECT * FROM site_users
            WHERE email=:email OR username=:username";
        return Database::sendSQL($stmt, $data, 'fetch');
    }


    public static function storePWhash($data): void
    {
        $stmt = "UPDATE site_users
            SET pw_hash=:pw_hash, chg_ip=:ip, chg_by=:userid
            WHERE userid=:userid";
        Database::sendSQL($stmt, $data);
    }


    /**
     * Autologin: Identifier/Token eintragen
     */
    public static function storeToken($data)
    {
        // Datenbank öffnen
        $pdo = Database::connectMyDB();

        $stmt = "INSERT INTO site_login
            (userid, identifier, token_hash, token_endtime, `login`, autologin, ip)
            VALUES
            (:userid, :identifier, :token_hash, :token_endtime, 1, 1, :ip)";

        // TODO: Funktioniert dann lastinsertId() ?
        #Database::sendSQL($stmt, $data);
        try {
            $qry = $pdo->prepare($stmt);
            foreach ($data AS $k => &$v) {
                if (is_int($v)) {
                    $qry->bindParam($k, $v, PDO::PARAM_INT);
                } else {
                    $qry->bindParam($k, $v);
                }
            }
            $qry->execute();
            $result = (int)$pdo->lastInsertId();

        } catch(PDOException $e) {
            $result = '--- nix geschrieben ---'.$e->getMessage();
        }

        // Datenbank schließen
        $pdo = Null;

        return $result;
    }


    public static function storeLogin($data): void
    {
        // Login speichern
        $stmt = "INSERT INTO site_login (userid, `login`, ip)
                VALUES (:userid, 1, :ip)";
        Database::sendSQL($stmt, $data);
    }
}

