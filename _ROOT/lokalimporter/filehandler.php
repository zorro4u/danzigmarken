<?php
namespace Dzg\Import;

require_once __DIR__.'/init.php';
require_once __DIR__.'/database.php';
require_once __DIR__.'/toolbox.php';


require_once __DIR__.'/../../_DOCUMENT_ROOT/assets/vendor/autoload.php';
#require_once $_SERVER['DOCUMENT_ROOT'].'/assets/vendor/autoload.php';
use avadim\FastExcelReader\Excel as rExcel;
use avadim\FastExcelWriter\Excel as wExcel;
use avadim\FastExcelWriter\Style\Style;


date_default_timezone_set('Europe/Berlin');

/*
from dataclasses import dataclass
from pathlib import Path
import sys
import time
import subprocess
import concurrent.futures

#-- pip installation notwendig --
import pandas as pd

from .init import Init
#from .database import DataBase as DB
from .toolbox import ToolBox as TB
*/


#FileHandler::readExcel('', true);


/**
 * Funktions-Container zur Dateiverarbeitung
 */
class FileHandler
{
    private const SEP = Init::SEP;    # Pfadtrenner
    private static string $search_path;


    /**
     * stand-alone Dateinamen-Korrektur
     * [arg]:
     * search_path: Pfad zu 'original' Bilder
     */
    public static function
    start_correct_filenames(string|null $search_path=null, bool $update=false): array
    {
        # ---------
        # Ausgabe Infotext
        echo "\nBilder laden ". str_repeat('.', 40) . "\n";
        $starttime = time();

        $search_path ??= Init::$picture_path;

        self::$search_path = $search_path;
        $subdir_list = Init::THEME_DIR;
        $pic_list = [];
        $dat_ct   = [];
        $ren_ct   = [];

        # Bilddateien der versch. Verzeichnisse einlesen,
        $file_list = [];
        foreach ($subdir_list as $directory) {
            $file_list[] = self::dir_reading($directory);
        }

        # Dateinamen korrigieren
        [$pic_list, $dat_ct, $ren_ct] = self::file_processing($file_list);

        # Zählwerte für Infotext
        $pic_counter = count($pic_list);  # gefundene Bilder
        $dat_counter = count($dat_ct);    # neue Bilder
        $ren_counter = count($ren_ct);    # umbenannte Bilder

        # wenn Dateinamen in 'original' geändert wurde,
        # dann auch in allen anderen Web-Verzeichnissen
        if ($ren_counter > 1) {
            self::rename_webpic();
        };


        # ---------
        # Ausgabe Infotext
        $endtime = time() - $starttime;  # sec
        $high = '\33[1A';     # ANSI code für: eine Zeile hoch
        $high = '';
        $txt1 = 'Bilder geladen: ';
        $txt2 = "{$dat_counter}x";
        $txt3 = ToolBox::time2str($endtime);
        if ($update) {
            $txt2 = "$dat_counter / $pic_counter neu ";
        }
        echo "{$high}{$txt1} {$txt2} in {$txt3}";
        #print('{0:,d} Dateien gefunden'.format(len(pic_list)).replace(',', '.'))

        #txt2 = f"{ren_counter}x ({round(ren_counter/len(pic_list)*100)}%)"
        echo "Dateien umbenannt:  {$ren_counter}x";

        return $pic_list;
    }


    /**
     * Verzeichnisinhalt verarbeiten
     */
    private static function dir_reading(string $directory): array
    {
        $sep = self::SEP;
        $cwd = self::$search_path .$sep. $directory;
        $pic_dirlist = [];

        # Verzeichnisse lesen
        foreach (glob($cwd .$sep. '*.*') as $dir_item) {

            if (is_file($dir_item)
                && in_array(strtolower(pathinfo($dir_item, PATHINFO_EXTENSION)), Init::SUFFIX)
                && !str_contains(pathinfo($dir_item, PATHINFO_FILENAME), '_OLD_'))
            {
                $pic_dirlist[] = $dir_item;
            };
        }
        #echo '.';
        return $pic_dirlist;
    }


    /**
     * Datei-Verarbeitung
     */
    private static function file_processing(array $files): array
    {
        $pic_list = [];
        $dat_ct = [];
        $ren_ct = [];
        $count = 0;

        # Dateinamen korrigieren
        foreach ($files as $file) {
            [$file, $count] = self::correct_filenames($file);
            $pic_list[] = $file;
            $dat_ct[] = '1';          # Dateizähler
            if ($count) {
                $ren_ct[] = '1';      # RenamendZähler
            }
        }
        #echo '.';
        return [$pic_list, $dat_ct, $ren_ct];
    }


    /**
     *  generiert aus datarow[23:28] den fullfilename
     */
    public static function make_ffn(array $data): string
    {
        $sep = self::SEP;
        return $data[0] .$sep. $data[1] .$sep. $data[2] .$sep. $data[3].$data[4];
    }


    /* ============================================ */


    /**
     * durch Tippfehler falsch gespeicherte Dateinamen korrigieren
     */
    public static function correct_filenames(string $full_filename): array
    {
        $name = pathinfo($full_filename, PATHINFO_FILENAME);
        $suff = '.' . pathinfo($full_filename, PATHINFO_EXTENSION);  # mit führendem '.'

        $cat_seperator = '_';             # -> Tiefstrich = Kateg.-Trenner
        $spacer = [' ', '-', '.'];        # Eingabe mit fehlerhaftem Kat-Trenner

        $replace_dict = [
            # Sammlung von zu ersetzenden Zeichen; (alt, neu)
            'replacing' =>  [
                1 => [[', ', ',', '&', '+'], '-u.-'],     # 'Komma' / '&' / '+' ersetzen, siehe Info**
                2 => [['dzg-', 'Dzg-', 'DZG-'], 'Dzg.'],  # 'Dzg-' ersetzen
                3 => [['DZG', 'dzg'], 'Dzg'],             # 'DZG' ersetzen
                4 => [['!', ], '1'],                      # '!' ersetzen
                5 => [['Gross', ], 'Groß'],               # 'Gross' ersetzen
                6 => [['_LO', '_-u.-'], $cat_seperator],   # 'LO' löschen
                7 => [[' - Kopie'], '_Kopie'],           # 'Kopie' bearbeiten
                            # Info**: keine Bildanzeige im Web und Probs mit DB Abfrage
            ],

            # nur KopieNr vorhanden '-(2)' -> '_(2) ersetzen
            'copy' => [
                #(' (1)', ' (2)', ' (3)', '-(1)', '-(2)', '-(3)'),
                ['-(1)', '-(2)', '-(3)'],
                $cat_seperator, ],

            # Marker VS/RS
            'vs' => [
                'chr1'    => 'RS',
                'chr2'    => 'VS',
                'old_chr' => $spacer,
                'new_chr' => $cat_seperator,
                'oldpos'  => -2,
                'newpos'  => -3,
            ],

            # Marker Attest
            'attest' => [
                'chr1'    => 'Attest',
                'chr2'    => null,
                'old_chr' => $spacer,
                'new_chr' => $cat_seperator,
                'oldpos'  => -6,
                'newpos'  => -7,
            ],

            # Marker K-BEF
            'befund' => [
                'chr1'    => 'K-BEF',
                'chr2'    => $cat_seperator,
                'old_chr' => $spacer,
                'new_chr' => '_Kurzbefund',
                'oldpos'  => -5,
                'newpos'  => -6,
            ],
        ];


        ## Falscheingaben bei der Namensgebung der Dateien korrigieren
        #
        # Suffix klein schreiben, Leerzeichen löschen
        $suff = strtolower($suff);
        $suff = rtrim($suff);

        # Leerzeichen am Anfang/Ende im Dateinamen löschen
        $name = trim($name);

        # diese Zeichen: ('_' '.') am Namensende löschen
        $name = rtrim($name, '.');
        $name = rtrim($name, $cat_seperator);

        # Replacing Liste alt->neu
        $name = self::replacing($name, $replace_dict['replacing']);

        # '-(2)' -> '_(2)' nur KopienNr vorhanden, Trenner setzen
        [$old_chr, $new_char] = $replace_dict['copy'];
        foreach ($old_chr as $i) {
            if (str_contains($name, $i)) {
                $name = str_replace($i, $new_char . substr($i, 1), $name);
            };
        };


        $name = self::mod_marker_copy($name);

        # RS/VS Endung bearbeiten
        $name = self::mod_marker_vs($name, $replace_dict['vs']);

        # Endung 'Attest' bearbeiten
        $name = self::mod_marker_attest($name, $replace_dict['attest']);

        #  Endung 'K-BEF' bearbeiten
        $name = self::mod_marker_short($name, $replace_dict['befund']);


        # !!! Datei auf Festplatte umbenennen !!!
        # wenn sich der Name geändert hat, Datei umbenennen, Zähler erhöhen
        $sep = self::SEP;
        $ffn_old = $full_filename;
        $ffn_new = dirname($ffn_old) . $sep . $name . $suff;
        $counter = 0;
        if (basename($ffn_old) != basename($ffn_new)) {
            $ffn_new = self::rename_file($ffn_old, $ffn_new);
            $counter++;
        }

        return [$ffn_new, $counter];
    }


    /**
     * Replacing Routine für correct_filenames()
     * Ersetzt die Zeichen einer Reihe von Listen durch ein jeweiliges neues.
     * data.value: (tuple), (str)
     */
    private static function replacing(string $name, array $data)  :string
    {
        foreach (array_values($data) as [$old_chr, $new_chr]) {
            foreach ($old_chr as $i) {
                if (str_contains($name, $i)) {
                    $name = str_replace($i, $new_chr, $name);
                };
            };
        };
        return $name;
    }


    /**
     * ersetze: 'Kopie (1)' -> '(1)'
     */
    private static function mod_marker_copy(string $name): string
    {
        # 'Kopie (1)', ... -> '(1)', ...
        foreach (array_slice(Init::$search_cat22, 2) as $i) {
            if (str_contains($name, $i)) {
                $new_chr = substr($i, -3);
                $name = str_replace($i, $new_chr, $name);
                return $name;
            }
        }

        # 'Kopie' -> '(1)'
        foreach (array_slice(Init::$search_cat22, 0, 2) as $i) {
            if (str_contains($name, $i)) {
                $new_chr = '(1)';
                $name = str_replace($i, $new_chr, $name);
            }
        }

        return $name;
    }


    /**
     * Endung 'RS' oder 'VS' bearbeiten
     * - wenn RS/VS mit Leerzeichen beginnt, dann ersetzen
     * - wenn RS/VS nicht mit Tiefstrich beginnt, Tiefstrich einfügen
     */
    private static function mod_marker_vs(string $name, array $data): string
    {
        $chr1   = $data['chr1'];
        $chr2   = $data['chr2'];
        $oldpos = $data['oldpos'];

        # findet Namen mit RS/VS Endung
        if (strpos($name, $chr1, $oldpos) !== false
            || strpos($name, $chr2, $oldpos) !== false) {
            # wenn drittletztes Zeichen 'Leer' oder anderer ungültiger Trenner, dann ersetzen
            # (String ohne letzten 3) + (die letzten 3 Zeichen, Leer durch Tief ersetzt)
            # 3-letztes kein Tiefstrich, Tiefstrich einfügen
            # (String ohne letzten 2) + Tief + (letzten 2 Zeichen)
            $name = self::rephelper($name, $data);
            };

        ## Liste von zu ersetzenden Zeichen; (alt, neu)
        $repl_list = [
            ['V', '_VS'],    # endet mit 'V', ersetzen mit '_VS'
            ['R', '_RS']];    # endet mit 'R', ersetzen mit '_RS'
        $chr2   = ' ';
        $oldpos = -1;
        $newpos = -2;

        for ($i = 0; $i < count($repl_list); $i++) {
            $old_chr = $repl_list[$i][0];
            $new_chr = $repl_list[$i][1];

            if (strpos($name, $old_chr, $oldpos) === 0) {
                # wenn vorletztes Zeichen Leerzeichen ist, dieses noch löschen
                # sonst einfach anhängen
                $name = (strpos($name, $chr2, $newpos) !== false)
                    ? substr($name, 0, $newpos) . $new_chr
                    : substr($name, 0, $oldpos) . $new_chr;
            };
        };
        return $name;
    }


    /**
     * Endung 'Attest' bearbeiten
     */
    private static function mod_marker_attest(string $name, array $data): string
    {
        $chr1   = $data['chr1'];
        $oldpos = $data['oldpos'];

        if (strpos($name, $chr1, $oldpos) !== false) {
            # wenn 'Attest' mit Leerzeichen beginnt, dann ersetzen
            # wenn 'Attest' nicht mit Tiefstrich beginnt, Tiefstrich einfügen
            $name = self::rephelper($name, $data);
        }
        return $name;
    }


    /**
     * Endung 'K-BEF' bearbeiten
     * - wenn K-BEF mit Leerzeichen beginnt, dann ersetzen
     * - wenn K-BEF nicht mit Tiefstrich beginnt, Tiefstrich einfügen
     */
    private static function mod_marker_short(string $name, array $data): string
    {
        $chr1 = $data['chr1'];         # 'K-BEF'
        $chr2 = $data['chr2'];         # '_'               (-> Tiefstrich = Kat.-Trenner)
        $old_chr = $data['old_chr'];   # ' ', '-', '.'     (falsche Trenner)
        $new_chr = $data['new_chr'];   # '_Kurzbefund'
        $oldpos  = $data['oldpos'];    # -5
        $newpos  = $data['newpos'];    # -6    = oldpos-1

        # wenn ab alter Position alte Bez. (chr1) kommt, ...
        if (strpos($name, $chr1, $oldpos) !== false) {

            # wenn alte Bez. (chr1) mit Leerz. beginnt,
            # dann Leerz. + alte Bez. mit neuer Bez. (inkl. Trenner) überschreiben
            foreach ($old_chr as $i) {
                if (strpos($name, $i, $newpos) === 0) {
                    $name = substr($name, 0, $newpos) . $new_chr;
                };
            };

            # wenn alte Bez. nicht mit Trenner (chr2) beginnt,
            # dann alte Bez. mit neuer Bez. (inkl. Trenner) überschreiben
            if (strpos($name, $chr2, $newpos) === false) {
                $name = substr($name, 0, $oldpos) . $new_chr;
            };
        };
        return $name;
    }


    /**
     * Datei umbenennen
     */
    private static function rename_file(string $ffn_old, string $ffn_new): string
    {
        $sep   = self::SEP;
        $fn0   = pathinfo($ffn_old, PATHINFO_FILENAME);
        $suff0 = pathinfo($ffn_old, PATHINFO_EXTENSION);
        $new   = $ffn_new;
        $path  = dirname($ffn_new);
        $fname = pathinfo($ffn_new, PATHINFO_FILENAME);
        $suff  = pathinfo($ffn_new, PATHINFO_EXTENSION);

        # Was muss geändert werden?
        $chg1 = ($fname != $fn0)   ? true : false;    # Name
        $chg2 = ($suff  != $suff0) ? true : false;  # Suffix

        # wenn Datei schon existiert, diese mit Index umbenennen
        # (Vergleich berücksichtig nicht Groß/Klein im Suffix)
        while (is_file($new)) {

            # Namens- (und Suffix-) Änderung
            if ($chg1) {
                $name_r = strrev($fname);     # reverse, ")3(_tset"
                $pos = strpos($name_r, '(');  # findet die letzte öffnende Klammer

                # wenn kein Index vorhanden, '_(1)' setzen
                # sonst vorh. Index erhöhen
                if ($pos < 0) {
                    $nr = strrev('_(1)');  # '(1)_'
                    $name_r = $nr . $name_r;
                } else {
                    $nr = (string)(int)$name_r[$pos-1] + 1;
                    $name_r = substr($name_r, 0, $pos-1) . $nr . substr($name_r, $pos);
                }
                $fname = strrev($name_r);    # zurückdrehen

                #$old = $path . $sep . basename($new);
                $new = $path . $sep . $fname . $suff;
                $chg1 = is_file($new);
                $chg2 = false;
            }

            # nur Suffix Änderung (chg2)
            else {
                # Marker am Ende setzen, um Schleife zu beenden
                $new = $path . $sep . basename($new) . '-';
            };
        }

        # Marker entfernen, Suffix ändern
        if ($chg2) {
            $new = $path . $sep . rtrim(basename($new), '-');
        }

        # !!! Datei auf Festplatte umbenennen !!!
#        rename($ffn_old, $new);

        return $new;
    }


    /**
     * Replacing Routine für mod_marker_vs() und mod_marker_attest()
     * - wenn Marker mit Leerzeichen beginnt, dann ersetzen
     * - wenn Marker nicht mit Tiefstrich beginnt, Tiefstrich einfügen
     */
    private static function rephelper(string $name, array $data): string
    {
        $old_chr = $data['old_chr'];
        $new_chr = $data['new_chr'];
        $oldpos  = $data['oldpos'];
        $newpos  = $data['newpos'];

        # wenn alt schon an neuer Position,
        # dann auch in neu umbenennen
        foreach ($old_chr as $i) {
            if (strpos($name, $i, $newpos) !== false) {
                $name = substr($name, 0, $newpos) . str_replace($i, $new_chr, substr($name, $newpos));
            }
        }

        # wenn an neuer Position nicht neue Bez.,
        # dann an alter Position neue Bez. einfügen
        if (strpos($name, $new_chr, $newpos) === false) {
            $name = substr($name, 0, $oldpos) . $new_chr . substr($name, $oldpos);
        }

        return $name;
    }


    /**
     * wenn Dateinamen in 'original' geändert wurde,
     * dann auch in allen anderen Web-Verzeichnissen
     *
     * ...\\pic + original + Lochung
     * DATA_PATH + [WEB_SUBDIR] + [picture_dir_list]
     */
    public static function rename_webpic(): void
    {
        $sep = self::SEP;
        $datapath = Init::DATA_PATH;
        $subdir1_list = array_slice(Init::WEB_SUBDIR, 0, -1);    # ['large', 'medium', 'small', 'thumb']
        $subdir2_list = Init::THEME_DIR;         # ['Lochungen',...]
        # $subdir2_list = cls.picture_dir_list;  # ['Lochungen',...]

        foreach ($subdir1_list as $dir1) {
            foreach ($subdir2_list as $dir2) {
                $cwd = $datapath . '/' . $dir1 . '/' . $dir2;
                foreach (glob($cwd .$sep. '*.*') as $dir_item) {
                    if (is_file($dir_item)
                        && in_array(strtolower(pathinfo($dir_item, PATHINFO_EXTENSION)), Init::SUFFIX))
                    {
                        self::correct_filenames($dir_item);
                    };
                };
            };
        };
    }


    ////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\


    /**
     * Lese Excel-Datei via FastExcelReader (Composer)
     * @param string $openFileName
     * @param bool $backup
     * @param int $head_count
     * @return array
     */
    public static function read_excel(string $openFileName = '', bool $backup=false, int $head_count = 1): array
    {
        // Standard Speicherort festlegen, wenn nicht vorhanden
        //
        $sep = self::SEP;           # Pfadtrenner
        $cwd = __DIR__ . $sep;      # aktuelles Verzeichnis
        $excel_file = Init::$fullpath_excelfile;
        if ($backup) {
            $excel_name = 'db_backup';      # Standard-Name für File/Sheet
            $cwd = dirname($excel_file) . $sep;
            $excel_file_name = $excel_name . '.xlsx';
            $excel_file = $cwd . $excel_file_name;
            $sheet_name = $excel_name;
        };

        // Input verarbeiten
        //
        # Leerzeichen vorn/hinten entfernen
        $openFileName = trim($openFileName);

        # keinen Dateinamen empfangen, Standard setzen
        if (empty($openFileName)) {
            $openFileName = $excel_file;
        };

        # Dateiname ohne Pfadangabe, akt. Pfad nutzen
        if (count(explode($sep, $openFileName)) < 2) {
            $openFileName = $cwd . $openFileName;
        };

        # Dateiendung prüfen, ggf. ergänzen
        if (pathinfo($openFileName, PATHINFO_EXTENSION) != 'xlsx') {
            $openFileName = rtrim($openFileName, '.') . '.xlsx';
        };

        # Blattname ggf. gleich Dateiname
        $sheet_name ??= basename($openFileName, '.xlsx');


        // Open XLSX-file
        $excel = rExcel::open($openFileName);

        // Read rows and cols, start index from zero
        $result = [];
        $sheets = (!$backup)
            ? $excel->getSheetNames()
            : array(basename($openFileName, '.xlsx'));
        $sheet_ct = count($sheets);

        foreach ($sheets as $sheet) {
            $sheet_id = ($sheet_ct < 2) ? 0 : $sheet;
            $result[$sheet_id] = $excel
                ->selectSheet($sheet_id)
                ->readRows(false, rExcel::KEYS_ZERO_BASED);
        };

        // Tabellenkopf/-daten separieren
        //
        $head_count = ($backup) ? 2 : $head_count;
        $head = [];
        $data = [];
        # Einblatt-Excel-Datei
        if ($sheet_ct === 1) {
            $head = array_slice($result[0], 0, $head_count);
            $data = array_slice($result[0], $head_count);
        }
        # Mehrblatt-Excel-Datei
        else {};


        /*
        # Excel NULL (NaN) wandeln
        $datalist = [];
        foreach ($data as $row) {
            $temp = [];
            foreach ($row as $i) {
                $temp[] = (strtolower((string)$i) != 'nan') ? $i : '';
            };
            $datalist[] = $temp;
        };
        $data = $datalist;
        */


        # Ergebnis zentral speichern
        Init::$col_name    = $head;  # Spaltennamen
        Init::$stamps_list = $data;  # Werte-Liste

        echo "Daten aus excel geladen: " .count($data). "x", PHP_EOL;

        return $data;
    }



    /**
     * read xlsx-file via panda als dataframe
     */
    public static function OLD_read_excel(bool $backup=false): array
    {
        $excelfile = Init::$fullpath_excelfile;
        if ($backup) {
            $excelfile = dirname($excelfile) . '/DB-backup.xlsx';
        };

        /*
        $starttime = time();
        if (is_file($excelfile)) {
            $df = pd::read_excel(io=$excelfile, dtype=str);
        } else {
            sys.exit('-- Datei laden: excel-Datei nicht gefunden --');
        }

        $header = $df->columns->values->tolist();
        if (!$backup) {
            # ohne Kopfzeile
            foreach ($df->values->tolist() as $row) {
                foreach ($row as $i) {
                    if ((string)$i != 'nan') {
                        $datalist[] = $i;
                    } else {
                        $datalist[] = '';
                    };
                };
            };
        }

        else {
            # ohne 2 Kopfzeilen
            $datalist = [];
            $idlist = [];
            $masterlist = [];
            $masterdict = [];
            $k = 0;
            foreach (substr($df->iloc, 1)->values->tolist() as $row) {
                $temp = [];
                foreach ($row as $i) {
                    if (str($i) != 'nan') {
                        $temp[] = $i;
                    } else {
                        $temp[] = '';
                    };
                };
                # header = header[:38]+[header[-1]]
                # datalist.append((temp[:38]+[temp[-1]]))     # ohne ID's
                $datalist[] = $temp;
                $idlist[] = array_slice($temp, 38, -1);  # oid did sid tid
                $masterlist[] = [$datalist[-1], $idlist[-1]];
                $masterdict[$k] = [[$k => $datalist[-1]], [$k => $idlist[-1]]];
                $k++;
            };
        };
        */

        # [print(z[0].values()) for z in list(masterdict.values())]
        # // keys jeweils gleich
        # masterdict[a][b][c][d]
        # a:keyMainDict b:idxValueArray,0/1 c:keySubDict_0/1 d:idxSubValueArray
        # print(masterdict[100][0][100][-1])      # ffn Nr.101
        # print(masterdict[100][1][100][2])      # sid Nr.101

        /*
        datalist = []
        for z in df.values.tolist():
            temp = []
            for i in z:
                if str(i) != 'nan':
                    temp.append(i)
                else:
                    temp.append('')
            #temp[-1] = FileHandler.make_ffn(z[23:28])     # fullfilename als Letztes ranhängen
            datalist.append(temp)
        */

        # df0 = df.replace('nan', np.nan)    # import nymphy
        # dl2 = [[y for y in x if pd.notna(y)] for x in df0.values.tolist()]
        # print(df[:1].to_string(index=False).replace('NaN',''))

        # Ergebnis zentral speichern
        #Init::$col_name    = $header;    # Spaltennamen
        #Init::$stamps_list = $datalist;  # Werte-Liste

        /*
        # ---------
        # Ausgabe Infotext
        $endtime = time() - $starttime;  # sec
        echo "aus excel Daten geladen: " .count($datalist). "x in " .ToolBox::time2str($endtime);

        if ($backup) {
            return $datalist;
            # return masterdict
        };

        return $datalist;
        */
        return [];
    }


    ////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\


    /**
     * Prepare to write into Excel file
     */
    public static function catlist2excel(array $datalist, array $head = [], string $sheet_name = '', string $outFileName = ''): void
    {
        $outFileName = $outFileName ?: Init::$fullpath_excelfile;
        $header = Init::$db_columns;  # Spaltenbezeichnung
        $header = Init::$csv_head;  # Spaltenbezeichnung

        $starttime = microtime(true);

        #$df->sort_values(['thema', 'kat10', 'datum', 'kat20'], ascending=[True, True, True, False], inplace=True);

        self::write2Excel($datalist, $header, $outFileName, 'dzg', false);

        # ---------
        # Ausgabe Infotext
        $endtime = microtime(true) - $starttime;  # sec
        echo "Daten in excel geschrieben: " .count($datalist). "x in " .ToolBox::time2str($endtime), PHP_EOL;
    }


    /**
     * Schreibe Excel-Datei via FastExcelWriter (Composer)
     * - Datei wird immer neu angelegt
     * - ohne Dateiname --> 'db_backup' + Datum
     * - ohne Pfad --> akt. Script-Verz.
     * - ohne '.xlsx' Endung --> wird gesetzt
     * - ohne Blattname --> Dateiname
     * - Dateiname um Tagesdatum ergänzen
     * ---
     * @param array $datalist     : Datenliste,  [[zeile1], [zeile2]]
     * @param array $head         : Tabellenkopf [head] / [[head1], [head2]]
     * @param string $outFileName : FullFileName der Exceldatei
     * @param string $sheet_name  : excel-Blattname
     * @param bool $add_date      : Tagesdatum (true)
     * @return bool
     */
    public static function write2Excel(array $datalist, array $head = [], string $outFileName = '', string $sheet_name = '', bool $add_date = true): bool
    {
        // Standard Speicherort erstellen, wenn nicht vorhanden
        // akt.Verz./akt.Dateiname_Datum.xlsx
        //
        $sep = self::SEP;           # Pfadtrenner
        $cwd = __DIR__ . $sep;      # aktuelles Verzeichnis
        $today = date('Ymd', time());
        global $excel_file;
        if (empty($excel_file)) {
            $excel_name = 'db_backup';      # Standard-Name für File/Sheet
            $excel_file_name = $excel_name . '_' . $today . '.xlsx';
            $excel_file = $cwd . $excel_file_name;
            $sheet_name = $excel_name;
        };

        // Input verarbeiten
        //
        # keine Datenliste zum Schreiben empfangen, Ende
        if (empty($datalist)) {return false;};

        # Leerzeichen vorn/hinten entfernen
        $outFileName = trim($outFileName);

        # keinen Dateinamen empfangen, Standard setzen
        if (empty($outFileName)) {
            $outFileName = $excel_file;
        };

        # Dateiname ohne Pfadangabe, akt. Pfad nutzen
        if (count(explode($sep, $outFileName)) < 2) {
            $outFileName = $cwd . $outFileName;
        };

        # Dateiendung prüfen, ggf. ergänzen
        if (pathinfo($outFileName, PATHINFO_EXTENSION) != 'xlsx') {
            $outFileName = rtrim($outFileName, '.') . '.xlsx';
        };

        # Blattname ggf. gleich Dateiname
        $sheet_name = $sheet_name ?: basename($outFileName, '.xlsx');

        # Dateiname ggf. um Datum ergänzen
        if ($add_date) {
            $ffn_parts = pathinfo($outFileName);
            $dir = $ffn_parts['dirname'] . $sep;
            $fn = $ffn_parts['filename'] . '_' . $today;
            $ext = '.' . $ffn_parts['extension'];
            $outFileName = $dir . $fn . $ext;
        };

        // Excel-Datei-Erzeugung starten
        //
        $excel = wExcel::create([$sheet_name]);
        $sheet = $excel->getsheet($sheet_name);

        // Header untersuchen
        $head_mode = 0;
        $ct_head = count($head);

        # mehrf. Header-Array
        if (array_key_exists(0, $head) && is_array($head[0])) {
            # mehrz./einz. Header
            $head_mode = ($ct_head > 1) ? 2 : 1;
            $head = ($head_mode === 1) ? $head[0] : $head;
        }

        # einf./leeres Header-Array
        else {
            $head_mode = ($ct_head > 0) ? 1 : 0;
        };

        // Zellformatierung festlegen,
        // alles als Text, Header mit Doppellinie_unten
        //
        $dataStyle = [
            'format' => '@',  # text
        ];

        $headStyle = [
            'format' => '@',  # text
            Style::BORDER => [
                Style::BORDER_BOTTOM => [
                    Style::BORDER_STYLE => Style::BORDER_DOUBLE,
                ]
            ]
        ];

        // Header schreiben
        //
        # einz. Header
        if ($head_mode == 1) {
            $sheet->writeHeader($head, $headStyle);
        }

        # mehrz. Header
        elseif ($head_mode == 2) {
            for ($i = 0; $i < $ct_head-1; $i++) {
                $sheet->writeRow($head[$i], $dataStyle);
            };
            $sheet->writeRow($head[$ct_head-1], $headStyle);
        };

        // Daten schreiben
        $sheet->writeRows($datalist, $dataStyle);

        // Speichern
        $excel->save($outFileName);

        return true;
    }


    ////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\


    /**
     * open excel /w xlsx_file
     */
    public static function open_excel(string $filename=''): void
    {
        $sep = self::SEP;
        $ffn0  = Init::$fullpath_excelfile;
        #ffn0 = dirname($ffn0) . $sep .basename($ffn0, '.xlsx') . '-test.xlsx'
        $ffn1 = dirname($ffn0) . $sep . $filename . '.xlsx';
        $ffn = ($filename) ? $ffn1 : $ffn0;

        #subprocess->run(Init::EXCEL . " " . $ffn, check=false);
    }
}


// EOF