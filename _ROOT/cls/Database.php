<?php
namespace Dzg;
use PDO, PDOException, Exception;

date_default_timezone_set('Europe/Berlin');


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
        require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/db/account_data.php";

        $user = $dbuser;
        $password = $dbpw;
        $host = "localhost:3306";
        $database = $dbase;
        $charset = 'utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
            #PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        # vs. PDO::FETCH_BOTH, PDO::FETCH_ASSOC / PDO::FETCH_COLUMN, PDO::FETCH_KEY_PAIR

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


    /****************************
     * Summary of sendSQL
     * zentrale DB-Abruffunktion,
     * vereint: prepare, bindParam, execute, (fetch) - Abfolge
     *
     * @param string $sql .. sql-statement like "select * from table_1"
     * @param array $data_array .. like [':id' => $id, ':name' => $name] / []
     * @param string $fetch_mode .. 'fetchall/all', 'fetch/yes/true', default: no/false
     * @param string $pdo_mode .. 'num', 'both', 'column', default: 'assoc'
     * @param bool $many .. execute_many, default: false
     */
    public static function sendSQL(
        string $sql,
        array $data_array,
        string|true $fetch_mode='no',
        string $pdo_mode='assoc',
        bool $many=false )
    {
        // in 'execute_many' Routine springen
        if ($many) {
            return self::executemanyDB($sql, $data_array);
        }

        // Datenbank-Handle / Verbindung
        $dbh = self::getPDO();

        // leeren SQL-Befehl empfangen, Abbruch
        if (empty(trim($sql))) return;

        // SQL-Befehl übergeben
        $qry = $dbh->prepare(trim($sql));

        // DB-Abfrage-Format festlegen
        switch (strtolower($pdo_mode)) {
            case "num":
                $qry->setFetchMode(PDO::FETCH_NUM);
                break;
            case "both":
                $qry->setFetchMode(PDO::FETCH_BOTH);
                break;
            case "column":
                $qry->setFetchMode(PDO::FETCH_COLUMN);
                break;
            case "assoc":
            default:
                $qry->setFetchMode(PDO::FETCH_ASSOC);

            // PDO::FETCH_NAMED PDO::FETCH_CLASS PDO::FETCH_OBJ PDO::FETCH_BOUND PDO::FETCH_LAZY PDO::FETCH_KEY_PAIR
        }

        // die zu sendenden Daten an ihr Format binden und dem SQL-Befehl hinzufügen
        if (!empty($data_array)) {

            // Werte an entspr. Typus binden .. gettype($v)
            // !!! auf &-Zeichen bei $value achten (PDO-Eigenart) !!!
            foreach ($data_array as $k => &$v) {
                switch (true) {

                    case (is_int($v)):
                        $qry->bindParam($k, $v, PDO::PARAM_INT);
                        break;

                    case (is_string($v)):
                        $qry->bindParam($k, $v, PDO::PARAM_STR);
                        break;

                    case (is_null($v)):
                        $qry->bindParam($k, $v, PDO::PARAM_NULL);
                        break;

                    case (is_bool($v)):
                        $qry->bindParam($k, $v, PDO::PARAM_BOOL);
                        break;

                    case (is_array($v)):
                    case (is_object($v)):
                    default:
                        var_dump($v);
                        exit("Error_bindParam: nicht unterstützte Datenstruktur - ".gettype($v));

                    // PDO::PARAM_LOB - large_object, image_data
                }
            }
        }

        try {
             // SQL-Befehl ausführen, an DB senden
            $qry->execute();

            // DB-Antwort empfangen
            switch (strtolower($fetch_mode)) {

                case "fetchall":
                case "all":
                    $query = $qry->fetchAll();
                    break;

                case "fetch":
                case "yes":
                case "true":
                case true:
                    $query = $qry->fetch();
                    break;

                default:
                    return;     # ohne DB-Antwort zurück
            }

        } catch (PDOException $e) {
            die($e->getMessage()." *** ".$sql);
        }

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
            : self::connectMyDB();
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

        $lastdate = self::sendSQL($stmt, [], 'fetch', 'num')[0];

        $version = date("ymd", strtotime($lastdate));
        $_SESSION['version'] = $version;
        return $version;
    }


}