<?php
namespace Dzg\Import;

date_default_timezone_set('Europe/Berlin');

Init::start();


class Init
{
    ## Verzeichnis/Dateien Konfig
    #
    public const SEP = DIRECTORY_SEPARATOR;    # Pfadtrenner
    public const DATA_PATH = "c:\\develop\\code\\data.nas"; # ROOT_PATH
    #DATA_PATH = Path(r'\\DISKSTATION\web\stamps\_prepare\data')     # primärer lokaler Datenpfad
    public const PICFILE_PATH = "original";   # Verzeichnis der Original-Bilddateien (unterhalb von DATA_PATH)

    public const EXCELFILE_PATH = ".db";       # Verzeichnis der Exceldatei (unterhalb von DATA_PATH)
    public const EXCELFILE = "stamps.xlsx";
    public const EXCEL = "C:\\Program Files\\LibreOffice\\program\\scalc.exe";   # Excel-Programm-Pfad

    # Webpfad-Liste mit Prefix für die Dateiumbenennung
    public const WEBPLACE = 'data';
    public const WEB_SUBDIR = ['large', 'medium', 'small', 'thumb', 'original'];
    public const WEB_PRE = ['l_', 'm_', 's_', 't_', '',];
    #THEME_DIR  = ['Lochungen', 'Nach Porto', 'Paketkarte', 'Paketkarte Hand', 'Paketkarte Kork']
    public const THEME_DIR = ['Lochung', 'Nachporto', 'Paketkarte', 'Handentwertung', 'Korkstempel'];


    # Bilddatei-Endungen, die berücksichtigt werden
    public const SUFFIX = ['jpg', 'jpeg', 'png', 'webp'];


    # Namen der verwendeten Tabellen der Datenbank
    public const DB = [
        'dzg_dirliste' => ['id', 'localroot', 'webroot', 'deakt', 'changed',],
        'dzg_dirsub1' => ['id', 'sub1', 'deakt', 'changed',],
        'dzg_dirsub2' => ['id', 'sub2', 'thema', 'deakt', 'changed',],
        'dzg_fileprefix' => ['id', 'prefix', 'id_sub1', 'deakt', 'changed',],
        'dzg_filesuffix' => ['id', 'suffix', 'deakt', 'changed',],
        'dzg_file' => [
            'id',
            'id_group',
            'id_thema',
            'id_prefix',
            'id_suffix',
            'id_dirliste',
            'id_sub1',
            'id_sub2',
            'print',
            'deakt',
            'kat20',
            'kat21',
            'kat22',
            'kat23',
            'kat24',
            'kat25',
            'kat26',
            'kat27',
            'kat28',
            'kat29',
            'name',
            'name_orig',
            'name_1',
            'name_2',
            'name_3',
            'name_hash',
            'dat_create',
            'dat_change',
            'dat_access',
            'created',
            'changed',
            'chg_ip',
            'chg_by',
            'ghost',
            'id_thumb',
        ],
        'OLD_dzg_file' => [
            'id',
            'kat20',
            'kat21',
            'kat22',
            'kat23',
            'kat24',
            'id_thema',
            'id_group',
            'deakt',
            'ghost',
            'created',
            'changed',
            'by',
            'kat25',
            'kat26',
            'kat27',
            'kat28',
            'kat29',
        ],
        'OLD_dzg_fileplace' => [
            'id',
            'id_datei',
            'id_prefix',
            'id_suffix',
            'id_dirliste',
            'id_sub1',
            'id_sub2',
            'id_thumb',
            'name',
            'name_orig',
            'name_1',
            'name_2',
            'name_3',
            'name_hash',
            'dat_create',
            'dat_change',
            'dat_access',
            'deakt',
            'ghost',
            'created',
            'changed',
            'by',
        ],
        'dzg_group' => [
            'id',
            'id_thema',
            'datum',
            'kat10',
            'kat11',
            'kat12',
            'kat13',
            'kat14',
            'kat15',
            'kat16',
            'kat17',
            'deakt',
            'mirror',
            'ghost',
            'created',
            'changed',
            'chg_ip',
            'chg_by',
            'kat18',
            'kat19',
        ],
        'dzg_kat15' => ['id', 'kat15', 'deakt', 'changed',],
        'dzg_kat20' => ['id', 'kat20', 'deakt', 'changed',],
        'dzg_kat21' => ['id', 'kat21', 'deakt', 'changed',],
        'dzg_katbezeichnung' => ['id', 'spalte', 'bezeichnung', 'deakt', 'changed',],
        'thb.dzg_thumbs' => ['id', 'id_ort', 'thumb', 'deakt', 'created', 'changed',],
    ];

    # Namen der verwendeten Tabellen der Datenbank, wird nicht weiter verwendet
    public const DB_TABLE = [
        'dzg_fileplace',
        'dzg_fileprefix',
        'dzg_filesuffix',
        'dzg_dirliste',
        'dzg_dirsub1',
        'dzg_dirsub2',
        'dzg_kat15',
        'dzg_kat20',
        'dzg_kat21',
        'dzg_katbezeichnung',
        'dzg_file',
        'dzg_group',
        'dzg_thumbs',
        'thb.dzg_thumbs',
    ];

    # Vordefinierte Spaltennamen der DB-Tabelle,
    # die auch für csv-Datei genutzt werden.
    public const DB_COLUMNS = [
        'thema',
        'datum',
        'webroot',
        'localroot',
        'sub1',
        'sub2',
        'name',
        'suffix',
        'dat_create',
        'dat_change',
        'dat_access',
        'ort_deakt',
        'ort_ghost',
        'dat_deakt',
        'dat_ghost',
        'sta_deakt',
        'sta_ghost',
        'sta_mirror',
        'speicherort',
    ];


    # excel/csv-Spaltenbezeichnung f. Kategorien
    public const CSV_COL_TEXT = '
    Thema, Datum,
    PostAmt, AMT, StempNr, Wolff, MichALT, Frank, Zielort, Notiz1, kat18*, kat19*,
    Ansicht, Attest, Kopie, MichNr, kat24, kat25*, kat26*, kat27*, kat28*, kat29*';

    # DB_äquivalent, 0_2_12-21
    public const CSV_COL_DB = '
    thema, datum,
    kat10, kat11, kat12, kat13, kat14, kat15, kat16, kat17, kat18, kat19,
    kat20, kat21, kat22, kat23, kat24, kat25, kat26, kat27, kat28, kat29';

    # zus. csv-Spaltenbezeichnung, 22_28_33_36_42_50-50
    public const CSV_COL_EXT = '
    webroot, localroot, sub1, sub2, suffix, name,
    name_orig, name_1, name_2, name_3, name_hash,
    dat_create, dat_change, dat_access,
    dat_print, dat_deakt, dat_ghost, sta_deakt, sta_mirror, sta_ghost,
    sta_id, dat_id, sta_id_thema, dat_id_thema, dat_id_sub2, dat_id_prefix, dat_id_suffix, dat_id_sub1,
    speicherort';

    # DB-Spaltenbezeichnung,
    # SQL-gleiche Bezeichn., wie 'dat.deakt' und 'sta.deakt',
    # werden nur in der num. Array-Abfrage geholt!
    public const DB_COL = "
    the.thema, sta.datum,
    sta.kat10, sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15, sta.kat16, sta.kat17, sta.kat18, sta.kat19,
    dat.kat20, dat.kat21, dat.kat22, dat.kat23, dat.kat24, dat.kat25, dat.kat26, dat.kat27, dat.kat28, dat.kat29,
    dir.webroot, dir.localroot, sub1.sub1, sub2.sub2, suf.suffix, dat.name, dat.name_orig, dat.name_1, dat.name_2, dat.name_3, dat.name_hash,
    dat.dat_create, dat.dat_change, dat.dat_access, dat.print, dat.deakt, dat.ghost,
    sta.deakt, sta.mirror, sta.ghost,
    sta.id, dat.id,
    sta.id_thema, dat.id_thema,
    dat.id_sub2, dat.id_prefix, dat.id_suffix, dat.id_sub1 ";


    ## Kategorie Konfig
    #
    # zusätzliche Spalten 'kat10, kat11, …'
    # zwischen den vordefinierten Sp1 ('datum') und Sp2('dateiname') einfügen
    public const CAT_COUNT = 20;      # um diese Zahl werden die Spalten erweitert
    public const CAT_TEXT = 'kat';    # Bezeichner

    public const SEARCH_YEAR = ['187', '188', '189', '190', '191', '192', '193', '194',];
    public const SEARCH_CAT10_0 = 'Dzg.';
    public const SEARCH_CAT10_1 = [
        'Dzg',
        'Altm',
        'Bode',
        'Bohns',
        'Bölk',
        'Brunau',
        'Fisch',
        'Fürst',
        'Geml',
        'Gott',
        'Gütt',
        'Groß',
        'Heub',
        'Hohen',
        'Jungf',
        'Kahl',
        'Kalt',
        'Käse',
        'Klein',
        'Kunz',
        'Lade',
        'Lame',
        'Lang',
        'Liess',
        'Marien',
        'Meisterwalde',
        'Neu',
        'Nick',
        'Ohra',
        'Oliva',
        'Pals',
        'Pasew',
        'Pieck',
        'Prau',
        'Schiew',
        'Schön',
        'Simon',
        'Sobbo',
        'Steegen',
        'Stras',
        'Stripp',
        'Stutt',
        'Tieg',
        'Trute',
        'Werne',
        'Wess',
        'Wotz',
        'Zoppot',
    ];
    public const SEARCH_CAT20 = ['VS', 'RS',];
    public const SEARCH_CAT21 = ['Attest', 'Kurzbefund', 'Befund',];
    public const SEARCH_CAT22_0 = ['(1)', '(2)', '(3)', '(4)', '(5)', '(6)', '(7)'];
    public const SEARCH_CAT22_1 = ['Kopie ', 'Kopie'];


    ## Bild Konfig
    #
    # Bildgröße zuordnen
    public const IMG_SIZES = [
        'large' => [1920, 1200],
        'medium' => [1280, 1024],
        'small' => [800, 600],
        'thumb' => [400, 400],
    ];


    # --------------------------
    # globale Variablen Deklaration
    #
    public static array $stamps_list = [];    # zentrale Ergebnisliste
    public static array $col_name = [];       # Spaltennamen, kommt aus excel_read


    # --------------------------
    # weitere Variablen
    public static string $picture_path;
    public static string $excelpath;
    public static string $fullpath_excelfile;
    public static array $picture_dir_list;
    public static array $webplace_subdir_list;
    public static array $pic_suffix;
    public static array $db_columns;
    public static array $csv_head;
    public static array $db_head;
    public static array $search_cat22;
    public static array $search_cat10;
    public static array $img_store;


    /**
     * class setter
     */
    public static function start()
    {
        // Verzeichnisse
        //
        self::$picture_path = self::DATA_PATH . self::SEP . self::PICFILE_PATH;
        self::$excelpath = self::DATA_PATH . self::SEP . self::EXCELFILE_PATH;
        self::$fullpath_excelfile = self::$excelpath . self::SEP . self::EXCELFILE;


        # Unterverzeichnisse von 'PICFILE_PATH' ("original") als ThemenListe
        # speichern, die aber nicht mit '_' oder '.' beginnen
        #$files = array_diff(scandir(self::$picture_path), ['.', '..']);
        foreach (scandir(self::$picture_path) as $file) {
            if (is_file($file))
                continue;
            if ($file === ".." or $file === ".")
                continue;
            if (in_array($file[0], ['_', '.']))
                continue;
            self::$picture_dir_list[] = $file;
        }


        # Webpfad-Liste mit Prefix für die Dateiumbenennung
        # [('data/large', 'l_'), (...), ('data/original', '')]
        $tmp = [];
        foreach (self::WEB_SUBDIR as $websub) {
            $tmp[] = self::WEBPLACE . '/' . $websub;
        }
        self::$webplace_subdir_list = array_map(null, $tmp, self::WEB_PRE);


        # Punkt vor Suffix
        self::$pic_suffix = [];
        foreach (self::SUFFIX as $suff) {
            self::$pic_suffix []= '.' . $suff;
        }


        // Datenbank, Tabellenkopf, Spaltenbezeichnung
        //
        # aus Bezeichner-String die Leerz. & Zeilenwechsel löschen
        # und als Liste speichern

        # thema, datum, kat10, kat11, kat12, ...
        $temp = explode(',', self::CSV_COL_DB);
        $temp = array_merge($temp, explode(',', self::CSV_COL_EXT)); # Zusatzfelder ranhängen
        self::$db_columns = [];
        foreach ($temp as $v) {
                self::$db_columns[] = trim($v);
        }

        # Thema, Datum, PostAmt, AMT, StempNr, ...
        $temp = explode(',', self::CSV_COL_TEXT);
        $temp = array_merge($temp, explode(',', self::CSV_COL_EXT)); # Zusatzfelder ranhängen
        self::$csv_head = [];
        foreach ($temp as $v) {
                self::$csv_head[] = trim($v);
        }

        # the.thema, sta.datum, sta.kat10, sta.kat11, sta.kat12, ...
        $temp = explode(',', self::DB_COL);
        self::$db_head = [];
        foreach ($temp as $v) {
                self::$db_head[] = trim($v);
        }


        // Kategorien
        //
        # ['Kopie ', 'Kopie', Kopie (1)', ...]
        $a = [];
        foreach (self::SEARCH_CAT22_1 as $copy) {
            foreach (self::SEARCH_CAT22_0 as $number) {
                $a[] = $copy . $number;
            }
        }
        self::$search_cat22 = array_merge(self::SEARCH_CAT22_1, $a);
        self::$search_cat10 = array_merge([self::SEARCH_CAT10_0,], self::SEARCH_CAT10_1);


        // Bilder
        //
        # [(1920,1200):('large','l_'), ...]
        $tmp0 = array_values(self::IMG_SIZES);
        $tmp1 = array_map(null, array_slice(self::WEB_SUBDIR, 0, -1), array_slice(self::WEB_PRE, 0, -1));   # ohne 'original'
        self::$img_store = self::array_zip($tmp0, $tmp1);



#var_dump($data[0]);
#var_dump($placeholder);
#var_dump(Init::DB[$table][1]);
#var_dump(array_slice($col, 1, 11));


    }


    /**
     * python version of zip() function for two arrays
     * cut at shorter array length
     */
    public static function array_zip($a1, ...$args)
    {
        $out = [];
        $a2 = (count($args) > 0)
            ? $args[0]
            : [];

        if (count($a1) > 0 && count($a2) > 0) {
            for ($i = 0; $i < min(count($a1), count($a2)); $i++) {
                $out[$i] = [$a1[$i], $a2[$i]];
            }
        }
        elseif (count($a1) > 0) {
            for ($i = 0; $i < count($a1); $i++) {
                $out[$i] = [$a1[$i]];
            }
        }
        elseif (count($a2) > 0) {
            for ($i = 0; $i < count($a2); $i++) {
                $out[$i] = [$a2[$i]];
            }
        };
        return $out;
    }
}


// EOF