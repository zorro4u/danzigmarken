<?php
namespace Dzg\Test;

date_default_timezone_set('Europe/Berlin');
session_start();

require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/tools/Database.php";
use Dzg\Tools\Database as DB;
use Exception;


Delete::delete_deaktiv_files();


/**
 * Löscht Einträge dat.deaktiv=1 aus DB & als Datei
 */
class Delete
{
    // dir1 Verzeichnisse
    public static array $pic_dirlist = [
        'large'    => 'l_',
        'medium'   => 'm_',
        'small'    => 's_',
        'thumb'    => 't_',
        'original' => '',
        'export.medium' => 'm_',
    ];


    public static function delete_deaktiv_files(): void
    {
        // als 'gelöscht' markierte Einträge aus DB holen
        $sql =
            "SELECT sub2.sub2, dat.name, suf.suffix, dat.id
            FROM dzg_file AS dat
            LEFT JOIN dzg_group AS sta ON sta.id=dat.id_group
            LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=dat.id_sub2
            LEFT JOIN dzg_filesuffix AS suf ON suf.id=dat.id_suffix
            WHERE dat.deakt=1";

        // [['Lochung', 'xxx', '.jpg', id], [...]]
        $dblist = DB::sendSQL($sql, [], 'fetchall', 'num');

        // Dateien löschen
        if (!empty($dblist)) {
            $result = self::deleting_files($dblist);

            echo(
                count($dblist).'x'.count(self::$pic_dirlist).'='.
                count($dblist)*count(self::$pic_dirlist).
                ' löschen'); echo('<br>');
            foreach ($dblist AS $d) {echo $d[3].': '.$d[0].'/'.$d[1].$d[2], '<br>';}; echo"<br>";

            echo 'verwaist: ', count($result['sta_idlist']), '<br>';
            foreach($result['sta_idlist'] AS $v){echo $v, '<br';} echo"<br>";

            echo 'Fehler: ', count($result['error_id']), '<br>';
            foreach($result['error_id'] AS $v){echo $v, '<br';};

        } else {
            echo 'nix zu bereinigen...';
        }
    }


    private static function deleting_files($dblist)
    {
        // Bilder-Verzeichnis
        $search_path = $_SERVER['DOCUMENT_ROOT']."/data";

        // dir1 Verzeichnisse
        $pic_dirlist = self::$pic_dirlist;

        // Dateiliste mit vollständigem lokalen Pfad bilden,
        // für alle Bildvarianten (original, large, medium, ...)
        $filelist = [];
        foreach ($dblist as $d0) {
            $dir2 = $d0[0];
            $name = $d0[1];
            $suff = $d0[2];
            $idx  = (int)$d0[3];
            foreach ($pic_dirlist as $dir1=>$pre) {
                $suffix = (!str_contains($dir1, 'export.big'))
                    ? $suff
                    : '.png';

                $path = $search_path.'/'.$dir1.'/'.$dir2;
                $fn   = $pre.$name.$suffix;

                $filelist []= [$path, $fn, $idx];
            }
        }

        // Dateien löschen
        $result = self::deleting_local_files($filelist);
        $result = self::deleting_db_files($result);

        return $result;
    }


    private static function deleting_local_files($filelist)
    {
        // Dateien auf Festplatte löschen
        // bei Fehler müssen die später per Hand gelöscht werden, da dann schon aus DB ausgetragen
        $result = [
            'deleted_list'=> [],
            'deleted_id'  => [],
            'error_list'  => [],
            'error_id'    => [],
            ];

        foreach ($filelist as $d) {
            $path = $d[0];
            $fn   = $d[1];
            $idx  = $d[2];
            $file = $path.'/'.$fn;

            if (file_exists($file)) {
                if (is_writeable($file)) {
                    // Umbenennen -> _OLD_
                    $fn_new   = '_OLD_'.$fn;
                    $file_new = $path.'/'.$fn_new;
                    #rename($file, $file_new);

                    // Datei löschen !!!
                    unlink($file);

                    $result['deleted_id'] []= $idx;
                    #$result['deleted_list'] []= [$path, $fn];

                } else {
                    // PermissionError
                    echo("<br>Berechtigung verweigert: {$file} konnte nicht gelöscht werden.");
                    $result['error_id'] []= $idx;
                    #$result['error_list'] []= $file;
                }
            } else {
                // FileNotFoundError
                // Die Datei {file} existiert nicht, egal
                $result['deleted_id'] []= $idx;
                #result['deleted_list'] []= [file]
            }
        }

        // Duplikate entfernen
        $result['deleted_id'] = array_unique($result['deleted_id']);

        return $result;
    }


    private static function deleting_db_files($result)
    {
        // Dateien aus DB (dzg_file) löschen

        if (!empty($result['deleted_id'])) {
            // für executemany vorbereiten, Liste in Liste
            $data = [];
            foreach ($result['deleted_id'] as $v) {
                $data []= [$v];
            }
            $sql = "DELETE FROM dzg_file WHERE id=?";
            DB::sendSQL($sql, $data, 'no', 'num', true);
        }

        // verwaiste Einträge aus DB (dzg_group) zählen und löschen,
        // (... wenn keine Dateien mehr verknüpft sind)
        $sql0 =
            "SELECT sta.id
            FROM dzg_group AS sta
            LEFT JOIN dzg_file AS dat ON sta.id=dat.id_group
            WHERE dat.id IS Null
                AND sta.deakt=1
            GROUP BY sta.id";

        $result['sta_idlist'] = DB::sendSQL($sql0, [], 'fetchall', 'num');

        $sql = "DELETE FROM dzg_group WHERE id in ({$sql0})";
        DB::sendSQL($sql);

        return $result;
    }
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>

</head>


</html>