<?php
namespace Dzg\SiteData;
use Dzg\Tools\Database;
use PDO;
use PDOException;

require_once __DIR__.'/../tools/database.php';


class ChangeData
{
    // DropDown-Bezeichnungen holen für Thema, Frankatur, Ansicht, Attest
    public static function getDropEntry(): array
    {
        $stmt = "WITH
        thema AS (SELECT thema FROM dzg_dirsub2 ORDER BY thema),
        kat15 AS (SELECT kat15 FROM dzg_kat15 ORDER BY kat15),
        kat20 AS (SELECT kat20 FROM dzg_kat20 ORDER BY kat20 DESC),
        kat21 AS (SELECT kat21 FROM dzg_kat21 ORDER BY kat21)
        SELECT * FROM thema, kat15, kat20, kat21 ";

        return Database::sendSQL($stmt, [], 'fetchall', 'num');
    }


    public static function deleteFile($data): void
    {
        $stmt = "UPDATE dzg_file SET deakt=1, chg_ip=:ip, chg_by=:by WHERE id=:id";
        Database::sendSQL($stmt, $data);
    }


    public static function deleteGroup($data): void
    {
        $stmt = "UPDATE dzg_group SET deakt=1, chg_ip=:ip, chg_by=:by WHERE id=:id";
        Database::sendSQL($stmt, $data);
    }


    public static function undeleteFile($data): void
    {
        $stmt = "UPDATE dzg_file SET deakt=0, chg_ip=:ip, chg_by=:by WHERE id=:id";
        Database::sendSQL($stmt, $data);
    }


    public static function undeleteGroup($data): void
    {
        $stmt = "UPDATE dzg_group SET deakt=0, chg_ip=:ip, chg_by=:by WHERE id=:id";
        Database::sendSQL($stmt, $data);
    }


    public static function getID($theme): int
    {
        $data = [':thema' => $theme];
        $stmt = "SELECT id FROM dzg_dirsub2 WHERE thema = :thema";
        return (int)Database::sendSQL($stmt, $data, 'fetch', 'num')[0];
    }


    public static function updateGroup($set, $data): void
    {
        $stmt = "UPDATE dzg_group SET {$set} WHERE id=:id";
        Database::sendSQL($stmt, $data);
    }


    public static function updateFile($set, $data): void
    {
        $stmt = "UPDATE dzg_file SET {$set} WHERE id=:id";
        Database::sendSQL($stmt, $data);

        $stmt3 =
            "UPDATE sqlite_sequence
            SET seq=(SELECT MAX(id) max_id FROM dzg_group)
            WHERE name='dzg_group'";      # -> lastNR
        #$stmt3 = "DELETE FROM sqlite_sequence WHERE name = 'dzg_group'";
        # "UPDATE sqlite_sequence SET seq=0 WHERE name='dzg_group'";    # -> 0
        # "DELETE FROM sqlite_sequence WHERE name = 'dzg_group'";
        # "SELECT * FROM sqlite_sequence ORDER BY name";    # -> view autoincrement
    }


    /**
     * neuen Datensatz 'Marke' anlegen
     */
    public static function newGroup($data)
    {
        // Datenbank öffnen
        $pdo = Database::connectMyDB();

        $stmt = "INSERT INTO dzg_group
            (id_thema, datum, kat10, kat11, kat12, kat13,
                kat14, kat15, kat16, kat17, chg_ip, chg_by, mirror)
            VALUES (:id_thema, :datum, :kat10, :kat11, :kat12, :kat13, :kat14, :kat15,
                    :kat16, :kat17, :ip, :by, 1) ";

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

}

