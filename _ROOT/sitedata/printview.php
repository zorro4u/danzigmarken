<?php
namespace Dzg;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class PrintviewData
{
    public static function getTheme(): array
    {
        $sql = "SELECT thema FROM dzg_dirsub2";
        return Database::sendSQL($sql, [], 'fetchall', 'num');
    }


    public static function getIDlist($theme): array
    {
        $col = "dat.id oid, dat.id did, sta.id sid";
        $col = "dat.id";

        if (!empty($_SESSION['filter'])) {
            $filter = (str_contains($_SESSION['filter'], "the.thema"))
                ? "{$_SESSION['filter']}"
                : "{$_SESSION['filter']} AND the.thema='{$theme}'";
        } else {
            $filter = "the.thema='{$theme}' AND dat.deakt=0";
        }

        $sort = (!empty($_SESSION['sort']))
            ? "{$_SESSION['sort']}, "
            : "the.id DESC, ";
        $sort0 = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";
        $sort .= $sort0;

        #$filter = "the.thema='{$theme}' AND ort.deakt=0";
        #$sort = "the.id, sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14,
        #sta.kat15, sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23,
        #sta.id, dat.id";

        $sql = "SELECT {$col}
            FROM dzg_file dat
            LEFT JOIN dzg_group sta ON sta.id=dat.id_group
            LEFT JOIN dzg_dirsub2 the ON the.id=dat.id_thema
            LEFT JOIN dzg_dirsub2 sub2 ON sub2.id=dat.id_sub2
            LEFT JOIN dzg_dirliste dir ON dir.id=dat.id_dirliste
            LEFT JOIN dzg_filesuffix suf ON suf.id=dat.id_suffix
            LEFT JOIN dzg_dirsub1 sub1 ON sub1.id
            LEFT JOIN dzg_fileprefix pre ON pre.id_sub1=sub1.id
            WHERE {$filter} AND pre.prefix='t_' AND dat.print=1
            ORDER BY {$sort}";

        return Database::sendSQL($sql, [], 'fetchall', 'num');
    }


    /**
     * Kateg.bezeichnung (kat10-kat29) aus DB holen
     */
    public static function getKat(): array
    {
        $stmt = "SELECT * FROM dzg_katbezeichnung";
        return Database::sendSQL($stmt, [], 'fetchall', 'num');
    }


    /**
     * Hauptdatenabfrage
     * alle Dateien der angegebenen Gruppen.ID holen
     */
    public static function getMainData($akt_file_id): array
    {
        $sort = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
            sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";

        $stmt = "WITH
            dzgfile AS (
                SELECT id_group FROM dzg_file WHERE id=:id)

            SELECT
                dat.id fid, sta.id gid, dat.name, the.thema,
                dat.changed changed, dat.created created, sta.changed s_changed, sta.created s_created,
                list.webroot, sub1.sub1, sub2.sub2, pre.prefix, dat.name, suf.suffix,
                sta.*, dat.*
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
            ORDER BY {$sort}";

        $data = [':id' => $akt_file_id];    # int

        return Database::sendSQL($stmt, $data, 'fetchall');
    }
}


// EOF