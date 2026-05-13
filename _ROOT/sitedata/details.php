<?php
namespace Dzg;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class DetailsData
{
    /**
     * ermittelt Vorgänger und Nachfolger der akt. Seite
     *
     * !!! in PDO\SQLite funktioniert nicht die sql-Funktion: ROW_NUMBER() OVER (ORDER BY ...)
     * bei direktem DB-Zugriff (ohne PDO) funktioniert es
     */
    public static function getJumpData(int $gid)
    {
        //-------------------------------------------------
        // Filter/Sortierung
        //
        $filter = $sort = $sort1 = "";
        $tmp = [];

        $filter = (!empty($_SESSION['filter']))
            ? "{$_SESSION['filter']}"
            : "the.id<>0 AND sta.deakt=0 AND dat.deakt=0";

        $sort = (!empty($_SESSION['sort']))
            ? "{$_SESSION['sort']},"
            : "the.id DESC,";

        // $sort String in Array wandeln, Leerz. und 'print' entfernen, als String rückwandeln
        $sort_arr = explode(",", $sort);
        foreach ($sort_arr as $v) {
            if (!strpos($v, "print")) {
                $tmp []= trim($v);
            }
        }
        $sort = implode(", ", $tmp);

        $sort0 = " sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23";
            #, sta.id, dat.id";
        $sort .= $sort0;

        // $sort String in Array wandeln, Leerz. und die ersten 4 Zeichen entfernen
        $tmp = [];
        $sort_arr = explode(",", $sort);
        foreach ($sort_arr as $v) {
            $tmp []= substr(trim($v), 4);
        }
        // Array in String rückwandeln und am Anfang 'id' ersetzen
        $tmp = implode(", ", $tmp);
        $sort1 = 'thema'.substr($tmp, 2);

        $select = "dat.id fid, sta.id gid, the.id thema,
            sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20, dat.kat21, dat.kat22, dat.kat23";

        //-------------------------------------------------
        // Einzel- oder Gruppen-Liste
        //
        $mainpage2 = isset($_SESSION['idx2'])
            ? $_SESSION['idx2']
            : False;

        if ($mainpage2) {
            // Gruppen-Liste, index2.php
            $current_id = $gid;
            $sql_filler1 = "";
            $sql_filler2 = "-- ";
        }
        else {
            // Einzel-Liste, index.php
            $current_id = (int)$_SESSION['fileid'];
            $sql_filler1 = "-- ";
            $sql_filler2 = "";
        }


        // SQL für [prev, curr, next]
        $stmt = "WITH

        main AS (
        -- Haupttabelle-Abfrage + filter (+ sortierung)
        SELECT {$select}
        FROM dzg_file AS dat
            LEFT JOIN dzg_group AS sta ON sta.id=dat.id_group
            LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
            LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=dat.id_sub2
            LEFT JOIN dzg_dirliste AS list ON list.id=dat.id_dirliste
            LEFT JOIN dzg_filesuffix AS suf ON suf.id=dat.id_suffix
        WHERE {$filter}
        -- Gruppen-Liste
        {$sql_filler1} GROUP BY sta.id
        -- wenn Ausgabe begrenzt werden soll, dann ORDER/LIMIT,
        -- geht dann aber nicht über Seitengrenzen hinweg
        ORDER BY {$sort}
        -- LIMIT :start, :proseite
        ),

        cte AS (
        SELECT
            -- mit SQL-Funktion die Zeilennummer der Haupttabellenabfrage bestimmen,
            -- die Reihenfolge der zu nummerierenden Zeilen durch ORDER festgelegen,
            -- aus 'filter' zuvor die ersten 4 Zeichen entfernen!
            ROW_NUMBER() OVER (ORDER BY {$sort1}) AS row_num,
            fid, gid
        FROM main),

        -- Zeilennummer der aktuellen ID aus 'cte' holen
        current AS (
        SELECT row_num FROM cte
        -- für Gruppen-Liste
        {$sql_filler1} WHERE gid = :id
        -- für Einzel-Liste
        {$sql_filler2} WHERE fid = :id)

        -- Datenabfrage (prev, curr, next)
        SELECT fid, gid FROM cte
        WHERE
            cte.row_num >= (SELECT row_num-1 FROM current) AND
            cte.row_num <= (SELECT row_num+1 FROM current);
        ";
        #  AND pre.prefix='t_'

        // Datenabfrage Einzel-, Gruppen-ID (fid, gid)
        $data = [':id' => $current_id];     # int

        return Database::sendSQL($stmt, $data, 'fetchall', 'num');
    }


    /**
     * Kateg.bezeichnung (kat10-kat29) aus DB holen
     */
    public static function getKatData()
    {
        $stmt = "SELECT * FROM dzg_katbezeichnung";
        return Database::sendSQL($stmt, [], 'fetchall', 'num');
    }


    /**
     * Hauptdatenabfrage
     * alle Dateien mit gleicher Gruppen.ID holen
     */
    public static function getMainData($akt_file_id)
    {
        # LEFT JOIN thb.thumbs AS thb ON thb.id=ort.id_thumb ...
        # --> , thb.thumb thumb: sqlite, thumb-Blob, wird nicht benötigt
        # where AND pre.prefix='t_'

        $sort = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";

        $stmt = "WITH
        dzgfile AS (
            SELECT id_group FROM dzg_file WHERE id=:id)

        SELECT
            dat.id fid, sta.id gid, dat.name, the.thema,
            dat.changed changed, dat.created created, sta.changed s_changed, sta.created s_created,
            list.webroot, sub1.sub1, sub2.sub2, pre.prefix, dat.name, suf.suffix, sta.*, dat.*
        FROM dzg_file AS dat
        LEFT JOIN dzg_group AS sta ON sta.id=dat.id_group
        LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
        LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=dat.id_sub2
        LEFT JOIN dzg_dirliste AS list ON list.id=dat.id_dirliste
        LEFT JOIN dzg_filesuffix AS suf ON suf.id=dat.id_suffix
        LEFT JOIN dzg_dirsub1 AS sub1 ON sub1.id
        LEFT JOIN dzg_fileprefix AS pre ON pre.id_sub1=sub1.id
        WHERE sta.id IN (SELECT * FROM dzgfile)
            AND dat.deakt=0
        ORDER BY {$sort} ";

        $data = [':id' => $akt_file_id];     # int

        return Database::sendSQL($stmt, $data, 'fetchall');
    }
}


// EOF