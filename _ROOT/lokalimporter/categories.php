<?php
namespace Dzg\Importer;

require_once __DIR__.'/init.php';
require_once __DIR__.'/filehandler.php';
require_once __DIR__.'/database.php';
require_once __DIR__.'/toolbox.php';

date_default_timezone_set('Europe/Berlin');

/*
from dataclasses import dataclass
from pathlib import Path
import platform
import time
from datetime import datetime
import concurrent.futures

from .init import Init
from .filehandler import FileHandler as File
from .database import DataBase
from .toolbox import ToolBox as tb
*/


// Categories::make_list();


/**
 * Funktions-Container für die Zuordnung
 * von Dateinamenbestandteile zu Kategorien in der Datenbank
 */
class Categories
{
    private const SEP = Init::SEP;       # Pfadtrenner
    private const FORM = 'Y-m-d H:i';   # Datumsformat

    # Klassen-Variablen deklarieren
    private static array $pic_list  = [];     # zentrale Bildliste
    private static array $file_list = [];     # alle gefundenen Bilddateien
    private static array $file_neg  = [];     # nicht erfasste Dateien
    private static array $filenotfound = [];  # Fehler bei der Namenskorrektur
    private static array $dat_ct = [];        # Zähler Bildliste
    private static array $ren_ct = [];        # Zähler Namenskorrektur
    private static array $qry10 = [];         # Kürzel-Liste
    private static array $qry11 = [];         # Kürzel-Liste
    private static string $search_path = '';  # Suchpfad
    private static string $lastimport;        # letzter Import



    /**
     * verschiebt gefundenes Listenelement an angegeb. Platz
     */
    private static function placing(array $cat_list, int $place, array $search, string $add_str = '', bool $search_equal = true): array
    {
        $found = false;
        $ct_cat = count($cat_list);
        $ct_search = count($search);

        for ($i = 0; $i < $ct_cat; $i++) {
            $cat = $cat_list[$i];

            for ($j = 0; $j < $ct_search; $j++) {
                $s = $search[$j];
                if ($search_equal) {
                    # wenn Element der Suchliste gleich einem Listenelement ist
                    $search_found = ($cat == $s) ? true : false;
                } else {
                    # wenn Element der Suchliste in Listenelement enthalten
                    $search_found = str_contains($cat, $s);
                };

                if ($search_found) {
                    if ($i != $place) {
                        # tauschen
                        $temp = $cat_list[$place];
                        if (strlen($add_str) > 0) {
                            $cat_list[$i] = str_replace($s, $add_str . ' ' . $s, $cat_list[$i]);
                        };
                        $cat_list[$place] = $cat_list[$i];
                        $cat_list[$i] = $temp;
                    };
                    $found = true;
                };

                # nach erstem Fund Search-Schleife beenden
                if ($found) {break 1;}
            };
            if ($found) {break 1;}
        };
        return $cat_list;
    }


    /**
     * tauscht einzelne Listenelemente (nochmal umsortieren)
     */
    private static function resort(array $cat_list): array
    {
        # [1]->[8]: 'Postamt' beginnt mit '(' , kat10 -> kat17
        $plc1 = 1;
        $plc2 = 8;
        # wenn 1.Element '('
        if ($cat_list[$plc1] && $cat_list[$plc1][0] == '(') {
            $temp = $cat_list[$plc2];
            $cat_list[$plc2] = $cat_list[$plc1];
            $cat_list[$plc1] = $temp;
        };

        # [1]->[8]: 'Postamt' beginnt mit Zahl, kat10 -> kat17
        $plc1 = 1;
        $plc2 = 8;
        # wenn 1.Element Zahl
        if ($cat_list[$plc1] && is_numeric($cat_list[$plc1][0])) {
            $temp = $cat_list[$plc2];
            $cat_list[$plc2] = $cat_list[$plc1];
            $cat_list[$plc1] = $temp;
        };

        # [2]->[5]: 'AMT' beginnt mit '(' , kat11 -> kat13
        $plc1 = 2;
        $plc2 = 5;
        # wenn 1.Element '('
        if ($cat_list[$plc1] && strpos($cat_list[$plc1], '(') === 0) {
            $temp = $cat_list[$plc2];
            $cat_list[$plc2] = $cat_list[$plc1];
            $cat_list[$plc1] = $temp;
        };

        /*
        # -> kat13 [4/5] aufräumen <-> kat11 [2/3]
        plc1 = 4
        plc2 = 2
        found = False
        if cat_list[plc1] and cat_list[plc1][0].isalpha():  # wenn Element mit Buchstabe beginnt
            temp = cat_list[plc2]
            cat_list[plc2] = cat_list[plc1]
            cat_list[plc1] = temp
            found = True

        # -> kat12 [3/4] aufräumen <-> kat11 [2/3]
        plc1 = 3
        plc2 = 2
        found = False
        if cat_list[plc1] and cat_list[plc1][0].isalpha():  # wenn Element mit Buchstabe beginnt
            temp = cat_list[plc2]
            cat_list[plc2] = cat_list[plc1]
            cat_list[plc1] = temp
            found = True

        # -> kat11 [2/3] aufräumen <-> kat17 [8/9]
        plc1 = 2
        plc2 = 8
        found = False
        if cat_list[plc1] and len(cat_list[plc1]) > 5:  # wenn Element mit Buchstabe beginnt
            temp = cat_list[plc2]
            cat_list[plc2] = cat_list[plc1]
            cat_list[plc1] = temp
            found = True

        # -> kat10 [1/2] aufräumen <-> kat12 [3/4]
        plc1 = 1
        plc2 = 2
        found = False
        if cat_list[plc1] and cat_list[plc1][0].isdigit():  # wenn Element mit Zahl beginnt
            temp = cat_list[plc2]
            cat_list[plc2] = cat_list[plc1]
            cat_list[plc1] = temp
            found = True

        # -> kat12 [3/4] aufräumen <-> kat13 [4/5]
        search = ['.']
        plc1 = 3
        plc2 = 4
        found = False
        if cat_list[plc1].find(s) >= 0:  # wenn Element der Searchliste in Listenelement enthalten
            temp = cat_list[plc2]
            cat_list[plc2] = cat_list[plc1]
            cat_list[plc1] = temp
            found = True
        # ----
        */

        return $cat_list;
    }


    /**
     * extrahiert Kategorien aus Dateinamen und speichert sie als Liste
     * Kategorien entspr. eines Schemas erkennen und sortieren
     * Kateg.-Trenner im Dateinamen: Tiefstrich
     * Kategorie-Liste (datum, kat10...29)
     *
     */
    private static function make_catlist(string $fullfilename): array
    {
        # da Dateibenennung nicht konsequent im 20-Kat-Schema,
        # ist die Zuordnung erst später manuell möglich
        $cat_list = [];
        $fname = pathinfo($fullfilename, PATHINFO_FILENAME);
        foreach (explode('_', $fname) as $cat) {
            $cat_list[] = trim($cat);
        };

        # Liste noch bis 20 Kateg. und (+1) fürs Datum auffüllen
        $end = Init::CAT_COUNT - count($cat_list) + 1;
        for ($i = 0; $i < $end; $i++) {
            $cat_list[] = '';
        }


        # [0] : datum
        # 'Datum' finden und an Anfang von cat_list bringen,
        $place = 0;
        $search = Init::SEARCH_YEAR;
        $cat_list = self::placing($cat_list, $place, $search, '', false);

        # wenn kein Datum vorhanden, dann Leerspalte f. Datum am Anfang
        $found = false;
        foreach ($search as $s) {
            $found = str_contains($cat_list[0], $s);
            if ($found) {break;};
        };
        if (!$found) {
            $cat_list = array_merge([''], array_slice($cat_list, 0, -1));
        };


        # [1], [2], [11], [12] : kat10, kat11, kat20, kat21
        # PostAmt, AMT, Ansicht, Attest
        # [idx => (search_list, search_mode)]
        $working_dict = [
            1 =>  [array_merge(self::$qry10, Init::$search_cat10), false,],   # [1]:  'Postamt' ('Dzg.') -> kat10
            2 =>  [self::$qry11, true,],                       # [2]:  'Amt' ('D1')       -> kat11
            11 => [Init::SEARCH_CAT20, true,],                 # [11]: 'Ansicht' (VS)     -> kat20
            12 => [Init::SEARCH_CAT21, true,],                 # [12]: 'Attest' finden    -> kat21
        ];

        foreach ($working_dict as $place => [$search, $mode]) {
            $cat_list = self::placing($cat_list, $place, $search, '', $mode);
        };


        # [13] : kat22
        # '(Nr)'->'Kopie (Nr)' finden, war urspr. 'Kopie (Nr)'->'_(Nr)'
        $place = 13;
        $search = Init::SEARCH_CAT22_0;
        $cat_list = self::placing($cat_list, $place, $search, 'Kopie');

        # [13] : kat22
        # 'Kopie (1)' finden, (sollte aber nicht mehr im Dateinamen sein)
        $place = 13;
        $search = Init::$search_cat22;
        $cat_list = self::placing($cat_list, $place, $search);


        # [1] : kat10
        # 'Dzg.' -> '' löschen
        $repl = Init::SEARCH_CAT10_0;
        if (str_contains($cat_list[1], $repl)) {
            $cat_list[1] = str_replace($repl, '', $cat_list[1]);
        };


        # abschließend nochmals umsortieren
        return self::resort($cat_list);
    }


    /**
     * erzeugt für eine Bilddatei den vollst. Datensatz
     */
    private static function make_datarow(string $fullfilename): array
    {
        # [0]: 'thema' / subdir2
        $sep = self::SEP;
        $ffn = explode($sep, $fullfilename);
        $subdir2 = trim($ffn[count($ffn)-2]);
        $datarow = [$subdir2];

        # [1..21]: datum, kat10..kat29 (21x)
        $datarow = array_merge($datarow, self::make_catlist($fullfilename));

        # [22,23]: webroot, localroot (2x)
        $datarow = array_merge($datarow, [Init::WEBPLACE, Init::DATA_PATH]);

        # [24..27]: subdir1, sub2, suffix, dateiname (4x)
        $datarow = array_merge($datarow,
            [Init::PICFILE_PATH,
            $subdir2,
            '.'.strtolower(pathinfo($fullfilename, PATHINFO_EXTENSION)),
            trim(pathinfo($fullfilename, PATHINFO_FILENAME))]);

        # [28..49] (22x Platzhalter)
        # -- [28..32]: 5x Namen
        # -- [33..35]: 3x Datei-Zeitstempel create,change,access
        # -- [36..41]: 6x Status-Flags
        # -- [42..49]: 8x IDs
        for ($i = 0; $i < 22; $i++) {
            $datarow[] = '';
        };

        # [50]: 'Speicherort' als letztes ranhängen
        $datarow[] = $fullfilename;

        return $datarow;
    }


    /**
     *
     */
    private static function ctime(string $path_to_file)
    {
        /*
        Try to get the date that a file was created, falling back to when it was
        last modified if that isn't possible.
        See http://stackoverflow.com/a/39501288/1709587 for explanation.
        */
        // getlastmod()
        // touch()

        # Zeitstempel, Verwendung ist systemabhängig
        $stat = stat($path_to_file);
        $create = filemtime($path_to_file);  # Änderg. Inhalt, Modified
        $change = filectime($path_to_file);  # Erstellg./Änderg. Metadaten, Creation
        $access = fileatime($path_to_file);  # Zugriff, Access

        #if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        #if (strncasecmp(PHP_OS, 'WIN', 3) == 0) {
        #if (stripos(PHP_OS, 'win') === 0) {
        #if (DIRECTORY_SEPARATOR === '\\') {
        if (PHP_OS_FAMILY === "Windows") {
            $cr_time = $stat['ctime'];
        } else {
            #try:
                # $cr_time = $stat->st_birthtime;
            #except AttributeError:
                # We're probably on Linux. No easy way to get creation dates here,
                # so we'll settle for when its content was last modified.
                $cr_time = $stat['mtime'];;
        };
        return $cr_time;
    }


    /**
     * Bilddateien der versch. Verzeichnisse lesen
     */
    private static function dir_reading(array $dir_list): array
    {
        $sep = self::SEP;
        $pic_dirlist = [];

        foreach ($dir_list as $dir) {
            $cwd = self::$search_path . $sep . $dir;
            $pic_list = [];

            foreach (glob($cwd . $sep . '*.*') as $dir_item) {
                if (is_file($dir_item)
                    && in_array(strtolower(pathinfo($dir_item, PATHINFO_EXTENSION)), Init::SUFFIX)
                    && !str_contains(pathinfo($dir_item, PATHINFO_FILENAME), '_OLD_'))
                {
                    $pic_list[] = $dir_item;
                };
            };
            $pic_dirlist[] = $pic_list;
        };

        # globale Gesamtbilddateiliste
        self::$file_list = $pic_dirlist;

        return $pic_dirlist;
    }


    /**
     * aus Dateinamen Datensatz erstellen,
     * Dateiname überprüfen/ändern
     */
    private static function file_processing(array $dir_list): array
    {
        #lastimport = cls._lastimport
        $form     = self::FORM;
        $notfound = [];
        $dataset  = [];
        $datarow  = [];
        $dat_ct   = [];
        $ren_ct   = [];
        $count    = 0;

        foreach ($dir_list as $dir) {
            foreach ($dir as $file) {
                #create_time = datetime->fromtimestamp(self::creation_date($file));
                #create_time = $create_time->strftime($form);
                #filedate = time.strptime(create_time, form);
                #if filedate > lastimport:
                if (1==1) {

                    # Dateinamen korrigieren
                    [$file, $count] = FileHandler::correct_filenames($file);

                    # Kategorien-Liste anhand des Namensplits erzeugen
                    $datarow = self::make_datarow($file);

                    # Datei Zeitstempel (create,change,access) speichern
                    #try {
                        $create = date($form, self::ctime($file));
                        $change = date($form, filemtime($file));
                        $access = date($form, fileatime($file));
                        $datarow[28] = $create;
                        $datarow[29] = $change;
                        $datarow[30] = $access;
                    #};
                    /*
                    except FileNotFoundError:
                        # Fehler bei Dateinamen-Korrektur
                        $notfound[] = $file;
                    */

                    #self::$pic_list[] = $datarow;    # Datenliste der Dateien
                    $dataset[] = $datarow;    # Datensatz der Datei zentral speichern
                    $dat_ct[]  = '1';         # Dateizähler
                    if ($count) {
                        $ren_ct[] = '1';      # RenamendZähler
                    };
                };
            };
        };

        #self::$filenotfound[] = $notfound;       # Dateifehler
        #echo '.';
        return [$dataset, $dat_ct, $ren_ct];
    }


    /**
     * erstellt eine Liste aus Verzeichnis- und Dateinamen der ersten Subdir-Ebene;
     *
     * durch Tippfehler falsch gespeicherte Dateinamen korrigieren;
     * eine Liste aus Teilen des Dateinamens erstellen,
     * die später als Kategorien genutzt werden können
     *
     * search_path=Init.picture_path,
     * update=True  // es werden nur Dateien seit dem letzten Update berücksichtigt
     * update=False // es werden alle Bild-Dateien in den Verzeichnissen berücksichtigt
     */
    public static function make_list(string|null $search_path=null, bool $update=false): array
    {
        # ---------
        # Ausgabe Infotext
        #print('\nVerzeichnisse lesen ', end='', flush=True)
#        echo "\nBilder laden ";
        $starttime = time();

        $search_path ??= Init::$picture_path;

        self::$pic_list = [];
        self::$search_path = $search_path;   # -> cls.__dir_reading()
        $theme_list = Init::THEME_DIR;


        # wird genutzt in cls.__file_processing()
        self::$lastimport = ($update)
            ? date(self::FORM, Database::lastimport())
            : date('%M', 0);

        #echo self::$lastimport;

        # Kürzel-Liste aus DB holen
        # wird genutzt in cls.__make_catlist()
        $sql1 = "SELECT kat10 FROM dzg_group WHERE kat10 <> '' GROUP BY kat10 ORDER BY kat10";
        $sql2 = "SELECT kat11 FROM dzg_group WHERE kat11 <> '' GROUP BY kat11 ORDER BY kat11";
        $qry1 = Database::sendSQL($sql1, [], 'all');   # [(x,), (y,), (z,), ...]
        $qry2 = Database::sendSQL($sql2, [], 'all');

        # set() entfernt doppelte Einträge aus der Liste
        # list() macht daraus wieder eine sortierbare Liste
        # sorted() sortiert die Liste
        # tuple() macht daraus eine unveränderbare Liste
        #
        #self::$qry10 = sorted(list(set([$i for $t in $qry1 for $i in $t])));
        $tmp = [];
        foreach ($qry1 as $t) {
            foreach ($t as $i) {
                $tmp[] = $i;
            };
        };
        sort($tmp);
        self::$qry10 = array_unique($tmp);

        #self::$qry11 = tuple(sorted(list(set([$i for $t in $qry2 for $i in $t]))));
        $tmp = [];
        foreach ($qry2 as $t) {
            foreach ($t as $i) {
                $tmp[] = $i;
            };
        };
        sort($tmp);
        self::$qry11 = array_unique($tmp);


        # Bilddateien der versch. Verzeichnisse lesen
        $dir_list = self::dir_reading($theme_list);

        # aus Dateinamen eine Datensatz-/Kategorieliste bilden
        [$pic_list, $dat_ct, $ren_ct] = self::file_processing($dir_list);


        # Zählwerte für Infotext
        $pic_counter = count($pic_list);  # gefundene Bilder
        $dat_counter = count($dat_ct);    # neue Bilder
        $ren_counter = count($ren_ct);    # umbenannte Bilder

        # wenn Dateinamen in 'original' geändert wurde,
        # dann auch in allen anderen Web-Verzeichnissen
        if ($ren_counter > 1) {
            FileHandler::rename_webpic();
        };

        # Datenliste zentral speichern
        Init::$stamps_list = $pic_list;


        # ---------
        # Ausgabe Infotext
        $endtime = time() - $starttime;  # sec
        $high = '\33[1A';     # ANSI code für: eine Zeile hoch
        $high = '';
        $txt1 = 'Bilder geladen:';
        $txt2 = $dat_counter.'x';
        $txt3 = ToolBox::time2str($endtime);
        if ($update) {
            $txt2 = $dat_counter." / ".count(self::$pic_list)." neu ";
        };
        echo "{$high}{$txt1} {$txt2} in {$txt3}".PHP_EOL;
        #print('{0:,d} Dateien gefunden'.format(len(pic_list)).replace(',', '.'))

        #txt2 = f"{ren_counter}x ({round(ren_counter/len(pic_list)*100)}%)"
        echo "Dateien umbenannt: {$ren_counter}x".PHP_EOL;

        return $pic_list;
    }
}


// EOF