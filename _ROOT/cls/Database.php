<?php
namespace Dzg;
use PDO, PDOException, Exception;

date_default_timezone_set('Europe/Berlin');

// Unterverzeichnis für debug- / Entwicklungs-Mode / NAS-Server
unset($_SESSION['debug']);
$debug = "/_prepare";
if (strpos(__DIR__, $debug) !== False) {
    $_SESSION['debug'] = $debug;
};


/***********************
 * global: Datenbankverbindung aufbauen
 */
#$pdo = Database::connectMyDB();


function X_version() {return Database::version();}



/***********************
 * Summary of Database
 */
class Database
{
    /***********************
     *  Datenbank-Verbindung, Maria-DB
     * -- alte Bezeichnung, wird noch verwendet --
     */
    public static function connectMyDB() :PDO {
        return self::getPDO();
    }

    public static $pdo;
    public static function getPDO()
    {
        if (!is_object(self::$pdo)) {
            self::setPDO();
        }
        return self::$pdo;
    }

    /***********************
     * Verbindung zur Datenbank herstellen
     */
    protected static function setPDO()
    {
        // Anmeldedaten laden
        #require $_SERVER['DOCUMENT_ROOT']."/db/account_data.php";
        require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/db/account_data.php";

        if (empty($_SESSION['debug'])) {
            $user = $dbuser;
            $password = $dbpw;
            $host = "localhost:3306";
            $database = $dbase;

        } else {
            // debug-Modus, NAS-Server
            $user = $dbuser0;
            $password = $dbpw0;
            $host = "localhost:3307";
            $database = $dbase0;
        }

        $charset = 'utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
            #PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        # vs. PDO::FETCH_BOTH, PDO::FETCH_ASSOC / PDO::FETCH_COLUMN

        $maria_host  = "mysql:host=$host;dbname=$database;charset=$charset";

        try {
            $pdo = new PDO($maria_host, $user, $password, $options);
        } catch(PDOException $e) {die($e->getMessage().': #1.mariaDB.stamps');}

        // PHP-Fehlermeldungen anzeigen
        error_reporting(E_ALL);
        ini_set('display_errors', true);

        self::$pdo = $pdo;
        return $pdo;
    }



    /***********************
     * sql_Befehl an DB senden (exec)
     */
    public static function execDB(string $sql, $pdo=Null) :void
    {
        $pdo_db = (is_object($pdo))
            ? $pdo
            : self::connectMyDB();

        try {
            $pdo_db->exec($sql);
        } catch (PDOException $e) {
            die($e->getMessage().": Error Fetching on MariaDB");
        }
        if (!is_object($pdo)) {$pdo_db = Null;}
    }


    /***********************
     * Summary of executeDB
     */
    public static function executeDB(string $sql, mixed ...$params)
    {
        // TODO klappt so nicht
        return self::fetchDB($sql, $params);
    }


    /***********************
     * sql_Befehl an DB senden (prepare, bindParam, execute, fetch)
     * $data im array-format: [a, b, ...]
     * "all" -> fetchall
     * "many" -> executemany()
     * PDO::FETCH_NUM,     # vs. PDO::FETCH_ASSOC / PDO::FETCH_COLUMN
     */
    public static function fetchDB(string $sql, mixed ...$params)
    {
        $pdo = Null;
        $all = Null;
        $data = Null;
        foreach ($params as $param) {
            if (is_object($param)) {
                $pdo = $param;
            }
            elseif (is_string($param)) {
                $all = $param;
            }
            elseif (is_array($param)) {
                $data = $param;
            }
        }

        if ($all === "many") {
            return self::executemanyDB($sql, $data, $pdo);
        }

        $pdo_db = (is_object($pdo))
            ? $pdo
            : self::connectMyDB();;
        $qry = $pdo_db->prepare($sql);

        if (!empty($data)) {
            for ($i=0; $i < count($data); $i++) {
                if (is_int($i)) {
                    $qry->bindParam($i+1, $data[$i], PDO::PARAM_INT);
                } elseif (is_string($i)) {
                    $qry->bindParam($i+1, $data[$i], PDO::PARAM_STR);
                }
            }
        }
        try {
            $qry->execute();
            $query = ($all === "all")
                ? $qry->fetchAll(PDO::FETCH_NUM)      #(PDO::FETCH_ASSOC)
                : $qry->fetch(PDO::FETCH_NUM);
        } catch (PDOException $e) {
            die($e->getMessage().": Error Fetching on MariaDB");
        }
        if (!is_object($pdo)) {$pdo_db = Null;}
        return $query;
    }


    /***********************
     * sql_Befehl mehrfach an DB senden (prepare, bindParam, execute)
     * $data  im array-format: [[a], [b], ...]
     */
    public static function executemanyDB(string $sql, array $data, $pdo=Null) :bool
    {
        /*
        https://phpdelusions.net/pdo_examples/insert
        make sure that the emulation mode is turned off, as there will be no speed benefit otherwise, however small it is. PDO::ATTR_EMULATE_PREPARES
        it's a good idea to wrap our queries in a transaction. In some circumstances it will greatly speed up the inserts, and it makes sense overall, to make sure that either all data has been added or none.

        PDO error reporting mode should be set to PDO::ERRMODE_EXCEPTION
        you have catch an Exception, not PDOException, as it doesn't matter what particular exception aborted the execution.
        you should re-throw an exception after rollback, to be notified of the problem the usual way.
        also make sure that a table engine supports transactions (i.e. for Mysql it should be InnoDB, not MyISAM)
        there are no Data definition language (DDL) statements that define or modify database schema among queries in your transaction, as such a query will cause an implicit commit
        */

        $pdo_db = (is_object($pdo))
            ? $pdo
            : self::connectMyDB();
        $qry = $pdo_db->prepare($sql);

        try {
            $pdo_db->beginTransaction();
            foreach ($data as $dataset) {
                if (is_array($dataset)) {
                    for ($i=0; $i < count($dataset); ++$i) {
                        if (is_int($dataset[$i])) {
                            $qry->bindParam($i+1, $dataset[$i], PDO::PARAM_INT);
                        } elseif (is_string($i)) {
                            $qry->bindParam($i+1, $dataset[$i], PDO::PARAM_STR);
                        }
                    }
                }
                $qry->execute();
            }
            $pdo_db->commit();
            $success = True;
        } catch (Exception $e) {
            $success = False;
            $pdo_db->rollback();
            die($e->getMessage().": Error Executing_manyData on MariaDB");
            #throw $e;
        }
        if (!is_object($pdo)) {$pdo_db = Null;}
        return $success;
    }


    /***********************
     * Summary of version
     */
    public static function version() {
        // Verbindung zur Datenbank
        $pdo_db = (is_object(self::$pdo))
            ? self::getPDO()
            : self::connectMyDB();

        # neuestes Datum aus created/changed: dzg_fileplace, dzg_file, dzg_group
        # MAX-Werte der einzelnen Spalten holen,
        # die Ergebnis-Zeile in Spalte transponieren
        # und den MAX-Wert ermitteln
        $stmt =
            "WITH
            cte1 AS (
            -- eine Zeile mit den max-Werten der versch. Tabellenspalten
            -- 1Z/6S
                SELECT
                    MAX(ort.changed) m11, MAX(dat.changed) m12, MAX(sta.changed) m13,
                    MAX(ort.created) m21, MAX(dat.created) m22, MAX(sta.created) m23
                FROM dzg_fileplace AS ort
                    LEFT JOIN dzg_file AS dat ON dat.id=ort.id_datei
                    LEFT JOIN dzg_group AS sta ON sta.id=dat.id_stamp
                WHERE ort.deakt=0 ),

            cte2 AS (
            -- Zeilenwerte ('m..') (nebeneinander) als Spaltenwerte ('werte') (untereinander)
            -- mit 'union all' die einzelnen select Abfragen anhängen
            -- pro Zeile: [wert]: (max.Wert)
            -- 6Z/1S
                SELECT m11 wert FROM cte1 UNION ALL
                SELECT m12 wert FROM cte1 UNION ALL
                SELECT m13 wert FROM cte1 UNION ALL
                SELECT m21 wert FROM cte1 UNION ALL
                SELECT m22 wert FROM cte1 UNION ALL
                SELECT m23 wert FROM cte1 )

            -- das jüngste/neueste Datum
            -- SELECT * FROM cte2 ORDER BY wert DESC LIMIT 1
            SELECT MAX(wert) FROM cte2 ";

        try {
            $qry = $pdo_db->query($stmt);
            [$lastdate] = $qry->fetch(PDO::FETCH_NUM);
        } catch(PDOException $e) {die($e->getMessage().': main-data.version()');}

        $version = date("ymd", strtotime($lastdate));
        $_SESSION['version'] = $version;
        return $version;
    }


}