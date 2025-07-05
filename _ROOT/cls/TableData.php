<?php
namespace Dzg;
require_once __DIR__.'/Table.php';


/***********************
 * Summary of TableData
 * Funktionen für Datenbank-Abfragen
 *
 * __public__
 * getMaxID($filter)
 * getData()
 * getThumbPath($gid)
 * getThumbBlob($gid)
 * kategorieBezeichnungen()
 */
class TableData
{
    /***********************
     * Klassenvariablen / Eigenschaften
     */


    /***********************
     * Summary of getMaxID
     * Anzahl der Einträge der Datenbank ermitteln,
     * 'maxID' wird für die Seiten-Navigation benötigt.
     *
     * (php):
     * '->' Klassenzuweiser, entspricht den '.' in anderen Sprachen
     * '$' kennzeichnet eine Variable
     *
     * @param mixed $filter
     * @return int
     */
    public static function getMaxID(string $filter): int
    {
        $idx2 = Table::$_session['idx2'];

        //-------------------------------------------------
        // Einzel- oder Gruppen-Liste
        //
        $sql_comment1 = ($idx2)
            ? ""        # Gruppen-Liste, index2.php
            : "-- ";    # Einzel-Liste, index.php

        $stmt = "SELECT COUNT(*) FROM (
            SELECT sta.id
            FROM dzg_file AS dat
                LEFT JOIN dzg_fileplace AS ort ON ort.id_datei=dat.id
                LEFT JOIN dzg_group AS sta ON sta.id=dat.id_stamp
                LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
                LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=ort.id_sub2
                LEFT JOIN dzg_filesuffix AS suf ON suf.id=ort.id_suffix
            WHERE {$filter}
            {$sql_comment1} GROUP BY sta.id
            ) AS summary";

        $maxID = Database::sendSQL($stmt, [], 'fetch', 'num')[0];

        return (int)$maxID;
    }


    /***********************
     * Summary of getData
     * Zentral-Werte aus Datenbank auslesen
     *
     * IN:  $_SESSION['start'], $_SESSION['proseite'], $_SESSION['sort'],
     *      $_SESSION['filter'], $_SESSION['idx2'];
     * OUT: $_SESSION['jump']
     *
     * @return array
     */
    public static function getData(): array
    {
        $start = Table::$_session['start'];
        $proseite = Table::$_session['proseite'];
        $sort = Table::$_session['sort'];
        $filter = Table::$_session['filter'];
        $idx2 = Table::$_session['idx2'];

        $start = (!empty($start)) ? $start : 0;
        $proseite = (!empty($proseite)) ? $proseite : 5;

        $sort = (!empty($sort))
            ? "{$sort}, "
            : "the.id DESC, ";

        $sort0 = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
                sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23,
                sta.id, dat.id";
        $sort .= $sort0;


        //-------------------------------------------------
        // Einzel- oder Gruppen-Liste
        //
        $sql_comment1 = ($idx2)
            ? ""        # Gruppen-Liste, index2.php
            : "-- ";    # Einzel-Liste, index.php

        // Gruppen-Liste
        #, thb.id tid

        // Einzel-Liste
        #, thb.id tid ,thb.thumb thumb
        # LEFT JOIN thb.thumbs AS thb ON thb.id=ort.id_thumb  ... ('thb.' spez. SQLite !!)
        # LEFT JOIN thumbs AS thb ON thb.id=ort.id_thumb  ... (MariaDB)

        $stmt = "SELECT
                    dat.id AS fid, sta.id AS gid, the.sub2 AS thema,
                    list.webroot, sub1.sub1, sub2.sub2, pre.prefix, ort.name_orig, suf.suffix,
                    sta.*, dat.*
                FROM dzg_file AS dat
                    LEFT JOIN dzg_fileplace AS ort ON ort.id_datei=dat.id
                    LEFT JOIN dzg_group AS sta ON sta.id=dat.id_stamp
                    LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
                    LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=ort.id_sub2
                    LEFT JOIN dzg_dirliste AS list ON list.id=ort.id_dirliste
                    LEFT JOIN dzg_filesuffix AS suf ON suf.id=ort.id_suffix
                    LEFT JOIN dzg_dirsub1 AS sub1 ON sub1.id
                    LEFT JOIN dzg_fileprefix AS pre ON pre.id_sub1=sub1.id
                WHERE {$filter} AND pre.prefix='t_'
                {$sql_comment1} GROUP BY sta.id
                ORDER BY {$sort}
                LIMIT :start, :proseite";

        $data = [':start' => $start, ':proseite' => $proseite];
        $stamps_db = Database::sendSQL($stmt, $data, 'fetchall');

        // Abfrage verarbeiten
        //
        $jumplist = [];

        foreach ($stamps_db as $idx => $stamp) {

            // erzeugt den Fullfilename der Thumb-Datei aus den einzelnen Komponenten
            $stamps_db[$idx]['thumb'] =
                $stamp['webroot'].'/'.$stamp['sub1'].'/'.$stamp['sub2'].'/'.
                $stamp['prefix'].$stamp['name_orig'].$stamp['suffix'];

            unset($stamps_db[$idx]['webroot']);
            unset($stamps_db[$idx]['sub1']);
            unset($stamps_db[$idx]['sub2']);
            unset($stamps_db[$idx]['prefix']);
            unset($stamps_db[$idx]['name_orig']);
            unset($stamps_db[$idx]['suffix']);

            // Sprungmarken zur vorherigen und nächsten Datei/Gruppe
            $prev = ($idx > 0) ? $stamps_db[$idx-1]['fid'] : -1;
            $next = ($idx < count($stamps_db)-1) ? $stamps_db[$idx+1]['fid'] : -1;

            // $jumplist[Gruppen-ID][Datei-ID] = [vorherige, nächste]
            $jumplist[(int)$stamp['gid']][(int)$stamp['fid']] = [(int)$prev, (int)$next];
        }

        // Sprungmarken-Liste global speichern (-> für Detail-Seite)
        #$_SESSION['jump'] = Table::$_session['jump'] = $jumplist;
        #$_SESSION['jump'] = Table::$_session['jump'] = [];
        unset($_SESSION['jump']);

        Table::$stamps_db = $stamps_db;
        return $stamps_db;
    }


    /***********************
     * Summary of getThumbPath
     * alle Verzeichnispfade der Thumb-Bilder einer Gruppe holen
     * und zum Fullfilename zusammensetzen
     *
     * @param mixed $gid
     * @return array[]
     */
    public static function getThumbPath(int $gid): array
    {
        $sort = "sta.kat10, sta.datum, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15,
                sta.kat16, sta.kat17, dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23,
                sta.id, dat.id";

        $stmt =
            "SELECT dat.id AS fid, list.webroot, sub1.sub1, sub2.sub2, pre.prefix,
                ort.name_orig, suf.suffix
            FROM dzg_file AS dat
                LEFT JOIN dzg_fileplace AS ort ON ort.id_datei=dat.id
                LEFT JOIN dzg_group AS sta ON sta.id=dat.id_stamp
                LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
                LEFT JOIN dzg_dirliste AS list ON list.id=ort.id_dirliste
                LEFT JOIN dzg_dirsub1 AS sub1 ON sub1.id
                LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=ort.id_sub2
                LEFT JOIN dzg_fileprefix AS pre ON pre.id_sub1=sub1.id
                LEFT JOIN dzg_filesuffix AS suf ON suf.id=ort.id_suffix
            WHERE sta.id=:id AND pre.prefix='t_' AND dat.deakt=0
            ORDER BY {$sort}";

        $data = [":id" => $gid];      # int
        $stamps = Database::sendSQL($stmt, $data, 'fetchall');

        foreach ($stamps as $k => $v) {
            $thb_ffn[$k]['fid'] = $v['fid'];
            $thb_ffn[$k]['thumb'] = $v['webroot'].'/'.$v['sub1'].'/'.$v['sub2'].'/'.
                                    $v['prefix'].$v['name_orig'].$v['suffix'];

            // alle Einzeldateien der Gruppe erhalten Sprungmarke zur nächsten Gruppe
            #$_SESSION['jump'][$gid][$v['fid']] = $tmp;
        }

        return $thb_ffn;
    }


    /***********************
     * Summary of getThumbBlob
     * alle Thumb-BLOB-Bilder einer Gruppe holen,
     * Thumbs der Einzeldateien der Gruppe abrufen,
     * die ganzen JOINs wegen dem Filter
     *
     * IN: $filter;
     * @param mixed $gid
     * @return array
     */
    public static function getThumbBlob(int $gid): array
    {
        $filter = Table::$_session['filter'];

        $stmt =
            "SELECT dat.id AS fid, thb.thumb AS thumb
            FROM dzg_file AS dat
                LEFT JOIN dzg_fileplace AS ort ON ort.id_datei=dat.id
                LEFT JOIN dzg_group AS sta ON sta.id=dat.id_stamp
                LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
                LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=ort.id_sub2
                LEFT JOIN dzg_filesuffix AS suf ON suf.id=ort.id_suffix
                LEFT JOIN thb.thumbs AS thb ON thb.id=ort.id_thumb
            WHERE sta.id=:gid AND {$filter} AND dat.deakt=0
            ORDER BY dat.kat20 DESC";

        $data = [':gid' => $gid];     # int
        $thumb_liste = Database::sendSQL($stmt, $data, 'fetchall');

        return $thumb_liste;
    }


    /***********************
     * Summary of kategorieBezeichnungen
     *
     * @return array
     */
    public static function kategorieBezeichnungen()
    {
        $stmt = "SELECT * FROM dzg_katbezeichnung";
        $result = Database::sendSQL($stmt, [], 'fetchall', 'num');
        return $result;
    }

}