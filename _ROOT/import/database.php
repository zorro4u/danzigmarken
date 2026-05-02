<?php
namespace Dzg\Import;

use Dzg\Tools\Database as DB;

require_once __DIR__.'/../tools/database.php';
require_once __DIR__.'/init.php';
require_once __DIR__.'/filehandler.php';
require_once __DIR__.'/toolbox.php';

date_default_timezone_set('Europe/Berlin');


class Database extends DB
{
    /**
     * holt das Datum der jüngsten Datei,
     * als Indikator der letzten DB-Erweiterung
     */
    public static function lastimport() :string
    {
        #$sql = "SELECT MAX(dat_change) FROM dzg_file";
        $sql = "SELECT MAX(dat_create) FROM dzg_file";
        return self::sendSQL($sql, [], 'fetch', 'num')[0];
    }


    ////////////////////\\\\\\\\\\\\\\\\\\\\\\\\\\


    /**
     * prüft, ob die Dateien der übergebenen Liste schon in der DB-Tabelle 'dzg_file' enthalten sind
     * und gibt die neuen Dateien zurück, die noch nicht in der DB enthalten sind
     *
     * vergleicht die Dateiliste mit der DB nach Namen und
     * gibt die nichtgefundenen als Liste zurück
     */
    public static function neue_liste_name(array $file_list) :array
    {
        $starttime = time();

        $notfound   = [];
        $found_list = [];

        # Dateiname/Thema aus DB-Bestand holen
        $sql = "SELECT sub2.sub2, suf.suffix, dat.name
                FROM dzg_file AS dat
                    LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=dat.id_sub2
                    LEFT JOIN dzg_filesuffix AS suf ON suf.id=dat.id_suffix";
        $dblist = self::sendSQL($sql, [], 'fetchall', 'num');

        # testen, ob Dateiname/Thema schon in DB Tabelle enthalten ist
        $found = false;
        foreach ($file_list as $dataset) {
            $file = [$dataset[25], $dataset[26], $dataset[27]];     # (dir2, suffix, name)
            foreach ($dblist as $db_entry) {
                if ($file == $db_entry) {
                    $found = true;
                };
            };

            if (!$found) {
                $notfound[] = $dataset;
            } else {
                $found = false;
                $found_list[] = $dataset;
            };
        };

        # ---------
        # Ausgabe Infotext
        $endtime = time() - $starttime;
        echo "DB Bestand: ".count($dblist)."x", PHP_EOL;
        echo "in DB gefunden: ".count($found_list)."x", PHP_EOL;
        echo "neue Daten gefunden: ".count($notfound)."x", PHP_EOL;

        return $notfound;
    }


    /**
     * prüft, ob die Dateien der übergebenen Liste schon in der DB-Tabelle 'dzg_file' enthalten sind
     * und gibt die neuen Dateien zurück, die noch nicht in der DB enthalten sind
     *
     * vergleicht die Dateiliste mit der DB nach Erstellungsdatum und
     * gibt die nichtgefundenen als Liste zurück
     *
     * die Dateien der übergebenen Liste, die jünger sind als das jüngste Datum der DB, werden als neu erkannt
     *
     * die Funktion ist schneller als die Namensprüfung, da sie nicht jeden Dateinamen mit jedem DB-Eintrag vergleichen muss, sondern nur das Datum der jüngsten Datei in der DB mit dem Erstellungsdatum der Dateien in der Liste vergleicht
     *
     * die Funktion ist aber ungenauer als die Namensprüfung, da sie nur das Erstellungsdatum der Dateien in der Liste mit dem jüngsten Datum der DB vergleicht, aber nicht prüft, ob die Dateien tatsächlich in der DB enthalten sind oder nicht
     *
     * die Funktion könnte auch Dateien als neu erkennen, die zwar jünger sind als das jüngste Datum der DB, aber trotzdem schon in der DB enthalten sind, wenn sie z.B. nachträglich geändert wurden oder wenn das Erstellungsdatum der Datei nicht korrekt ist
     *
     * die Funktion könnte auch Dateien als alt erkennen, die zwar älter sind als das jüngste Datum der DB, aber trotzdem noch nicht in der DB enthalten sind, wenn sie z.B. nachträglich geändert wurden oder wenn das Erstellungsdatum der Datei nicht korrekt ist
     *
     * vergleicht die Dateiliste mit der DB nach Erstellungsdatum und
     * gibt die nichtgefundenen als Liste zurück
     */
    public static function neue_liste_zeit(array $file_list) :array
    {
        $notfound   = [];
        $found_list = [];

        $form = 'Y-m-d H:i';
        #$form = '%Y-%m-%d %H:%M';
        # lastimport = [time.strptime(DataBase.lastimport(), form) if update else time.strptime('0', '%M')][0]
        #$lastimport = time.strptime(self::lastimport(), $form);
        $lastimport = date($form, strtotime(self::lastimport()));
        # print(time.strftime(form, lastimport))

        foreach ($file_list as $dataset) {
            $create   = $dataset[33];
            #change  = $dataset[34];
            #access  = $dataset[35];
            #$filedate = time.strptime($create, $form);
            $filedate = date($form, strtotime($create));
            ($filedate > $lastimport)
                ? $notfound   []= $dataset
                : $found_list []= $dataset;
        }

        return $notfound;
    }


    /**
     * Daten & Thumbs in MariaDB Datenbank schreiben
     *
     * die Funktion könnte auch Dateien als neu erkennen, die zwar jünger sind als das jüngste Datum der DB, aber trotzdem schon in der DB enthalten sind, wenn sie z.B. nachträglich geändert wurden oder wenn das Erstellungsdatum der Datei nicht korrekt ist
     *
     * die Funktion könnte auch Dateien als alt erkennen, die zwar älter sind als das jüngste Datum der DB, aber trotzdem noch nicht in der DB enthalten sind, wenn sie z.B. nachträglich geändert wurden oder wenn das Erstellungsdatum der Datei nicht korrekt ist
     *
     * Daten & Thumbs in MariaDB Datenbank schreiben
     */
    public static function store_list_to_maria(array $file_list) :void
    {
        $starttime = time();
        echo("Daten in MariaDB Datenbank schreiben in ");

        self::add_filelist_to_database($file_list);

        $endtime = time() - $starttime;
        echo ToolBox::time2str($endtime), PHP_EOL;
    }


    /* ============================================ */

    /**
     * Daten & Thumbs in MariaDB Datenbank schreiben
     *
     * die Funktion könnte auch Dateien als neu erkennen, die zwar jünger sind als das jüngste Datum der DB, aber trotzdem schon in der DB enthalten sind, wenn sie z.B. nachträglich geändert wurden oder wenn das Erstellungsdatum der Datei nicht korrekt ist
     *
     * die Funktion könnte auch Dateien als alt erkennen, die zwar älter sind als das jüngste Datum der DB, aber trotzdem noch nicht in der DB enthalten sind, wenn sie z.B. nachträglich geändert wurden oder wenn das Erstellungsdatum der Datei nicht korrekt ist
     *
     * wenn Bild-Datei noch nicht in DB,
     * dann hinzufügen, Web-Bilder erstellen und Thumb in DB eintragen
     */
    private static function add_filelist_to_database(array $file_list) :void
    {
        #$backup  = False

        ## Löschen u. ggf. Tabellen erstellen
        #
        #for i in range(9):  # 9:ohne 10:mit -- dzg_thumbs
            # cls.__sqlite_tabelle_leeren(tabs[i], connection)  # dzg_thumbs, kein Reset von Autoincrement
            # cls.__sqlite_tabelle_LOESCHEN(tabs[i], connection)
        #    pass
        # cls.__NEW_tabellen_erzeugen(connection)

        # testen, ob Dateiname/Thema schon in DB Tabelle enthalten ist
        # newlist = cls.neue_liste_name(file_list, connection)
        # newlist = file_list
        # input(len(newlist))


        ## Stammverzeichnisse / Abhängigkeiten überprüfen, aktualisieren
        #
        if ($file_list) {
            self::root();       # dzg_dirliste
            self::sub1();       # dzg_dirsub1
            self::prefix();     # dzg_fileprefix
        }

        ## Werte eintragen, Abhängigkeiten prüfen
        #
        # Fortschritt-Anzeige
        $i = 0;
        $list_count = count($file_list);
        $step = intdiv($list_count, 5);  # 20% Step
        if ($step < 100) {  # nur für große Mengen ausgeben
            $step = 1000;
        } else {
            echo ' ';
        }

        # 1. Datei-Daten in DB-Tabellen eintragen
        #
        foreach ($file_list as $dataset) {

            # Fortschrittsanzeige
            $i++;
            if ($i == $step) {
                $i = 0;
                echo '*';
            }

            # Liste der IDs der Daten für Fremdschlüsselverknüpfung
            $id = [];

            # Daten in den einzelnen DB-Tabellen suchen und ggf. hinzufügen.
            # ID-Liste wird immer aktualisiert.
            self::sub2($dataset, $id);      # dzg_dirsub2
            self::suffix($dataset, $id);    # dzg_filesuffix
            self::view($dataset, $id);      # dzg_kat20, Ansicht (VS/RS)
            self::cat_group($dataset, $id); # dzg_group
            self::cat_file($dataset, $id);  # dzg_file


            ## 2. Web-Bilder (& Thumbnails) erzeugen
            #
            # ImageChanger.webimages_erstellen(dataset[-1])


            ## 3. thumb in DB eintragen
            #
            /*
            table = pre + 'dzg_thumbs'
            column = cls.DB[table][1:3]  # ('id_ort', 'thumb')
            column_str = ('{},' * (len(column)))[:-1].format(*column)
            placeholder = ('?,' * (len(column)))[:-1]  # '?,?,?,...'
            data = [id_list[5], '']  # id_ort, Platzhalter thb.image
            idx = [None]
            # cls.__sqlite_tabelle_leeren(table, connection)
            # cls.__sqlite_tabelle_LOESCHEN(table, connection)
            # cls.__NEW_tabellen_erzeugen(connection)

            # print(table)
            # print(data)
            # input(column_str)

            if backup:
                # 38:oid did sid tid
                oid_alt = dataset[38]
                tid_alt = dataset[41]
                idx = (int(tid_alt),)

                sql = f"UPDATE {table} SET id_ort={id_list[5]} WHERE id=?"
                cursor.execute(sql, idx)
                connection.commit()
            else:
                # -- Bestand abfragen --
                sql = f"SELECT id FROM thb.dzg_thumbs WHERE id_ort='{data[0]}'"
                #idx = cursor.execute(sql).fetchone()
                cursor.execute(sql)
                idx = cursor.fetchone()

            if not idx and not backup:  # nicht gefunden, hinzufügen
                # cls.store_thumb_to_sqlite(dataset)
                # Thumbnail aus Original-Datei erzeugen, cls.__thumb_eintragen(dataset, connection)
                size, (subdir1, pref) = cls._img_store[-1]  # [(100,75):('thumb','t_')]
                fullfilename = Path(dataset[-1])
                stream = BytesIO()
                with Image.open(fullfilename) as thb_img:
                    thb_img.thumbnail(size)
                    suffix = dataset[26]
                    if suffix == '.jpg':
                        thb_img.save(stream, format='JPEG')
                    elif suffix == '.png':
                        thb_img.save(stream, format='PNG')
                    else:
                        pass
                thb_bytes = stream.getvalue()
                data[1] = thb_bytes

                # Werte eintragen
                sql = f"INSERT INTO {table} ({column_str}) VALUES ({placeholder})"
                cursor.execute(sql, data)
                connection.commit()
                # noch neue id holen
                #idx = cursor.execute("SELECT last_insert_rowid()").fetchone()
                cursor.execute("SELECT last_insert_id()")
                idx = cursor.fetchone()

            # id für weitere Nutzung speichern
            id_list.extend(idx)  # id: 0:sub2, 1:suffix, 2:stamp, 3:dirliste, 4:datei, 5:ort, +6:thumb

            # update der id_thumb in dzg_fileplace
            sql = f"UPDATE dzg_file SET id_thumb='{id_list[6]}' WHERE id='{id_list[5]}'"
            cursor.execute(sql)
            connection.commit()  # Bestätigung der DB-Änderungen

            # if i % 2:
            #    [print(i) for i in alldata_datei]
            #    print()
            #    print(f"{i}-",end='')
            # input(' ++++ ')
            # input('<----->')
            */
        };

        # SQLITE:
        # thumb-DB wieder abtrennen
        #sql = f"DETACH DATABASE {pre[:-1]}"
        #cursor.execute(sql)



        # ---------
        # Ausgabe Infotext
        echo " ".count($file_list)."x", PHP_EOL;


        # input(f"\n-------")
        # print(column)
        # print(data)
        # print(sql)
        # input('dataset 900...')
        # input(f"\n{sql}")
        # cursor.execute(sql)
        # connection.commit()
        # input('-------')

    }


    /* --------------------------------------------- */

    /**
     * Stammverzeichnis in DB-Tabelle 'dzg_dirliste' eintragen
     *
     * die Funktion prüft, ob die Werte für 'localroot' und 'webroot' schon in der DB-Tabelle 'dzg_dirliste' enthalten sind
     * und aktualisiert die Werte entsprechend, wenn sie schon vorhanden sind, oder fügt sie hinzu, wenn sie noch nicht vorhanden sind
     * die Funktion könnte auch beide Werte als neu erkennen, wenn sie z.B. nachträglich geändert wurden oder wenn die Werte in der DB nicht korrekt sind, da sie nur prüft, ob die Werte in der DB vorhanden sind, aber nicht prüft, ob sie tatsächlich die richtigen Werte sind oder nicht
     * die Funktion könnte auch beide Werte als alt erkennen, wenn sie z.B. nachträglich geändert wurden oder wenn die Werte in der DB nicht korrekt sind, da sie nur prüft, ob die Werte in der DB vorhanden sind, aber nicht prüft, ob sie tatsächlich die richtigen Werte sind oder nicht
     *
     * root-verzeichnis
     * < anhängen vs. updaten ?? >
     */
    private static function root() :void
    {
        #cursor = connection.cursor()

        $table  = 'dzg_dirliste';
        $column = array_slice(Init::DB[$table], 1, 2);     # ('localroot', 'webroot')
        # die Liste der Tabellen-Spaltennamen in ein String wandeln,
        $column_str  = implode(',', $column);
        $placeholder = '?, ?';
        $data = [str_replace('\\','\\\\', Init::DATA_PATH), Init::WEBPLACE];  # ('localroot', 'webroot')


        # -- Bestand abfragen --
        #
        # SELECT (SELECT count(1) FROM dzg_dirliste),
        # (SELECT count(1) FROM dzg_dirliste WHERE localroot='c:\\users\\steffen\\documents\\code\\data.nas'),
        # (SELECT count(1) FROM dzg_dirliste WHERE webroot='data')
        #
        # Anz. Datensätze: sollte 1 sein
        # localroot gefunden
        # webroot gefunden
        #
        $txt = "(SELECT count(1) FROM $table), ";
        for ($i = 0; $i < 2; $i++) {
            $txt .= "(SELECT count(1) FROM $table WHERE {$column[$i]}='{$data[$i]}'), ";
        }
        $sql = "SELECT " . substr($txt, 0, -2);
        #$cursor->execute($sql);
        #$chk = $cursor->fetchone();     # one: tuple (1,1,1), all: list [(1,1,1)]
        $chk = self::sendSQL($sql, [], 'fetch', 'num');

        # beide Werte ('localroot', 'webroot') nicht in DB
        if (!$chk[1] && !$chk[2]) {

            # aber eine Datensatz schon vorhanden, updaten !!ändert alle abh. Elemente!!
            if ($chk[0]) {
                $sql = "UPDATE $table  SET {$column[0]}='{$data[0]}', {$column[1]}='{$data[1]}'";
                self::sendSQL($sql, []);
            }

            # noch kein Datensatz vorhanden, einfügen
            else {
                $sql = "INSERT INTO $table ($column_str) VALUES ($placeholder)";
                self::sendSQL($sql, $data);
            };
        }

        # localroot updaten  !!ändert alle abh. Elemente!!
        elseif ($chk[1]) {
            $sql = "UPDATE $table SET {$column[1]}='{$data[1]}' WHERE {$column[0]}='{$data[0]}'";
            self::sendSQL($sql, []);
        }

        # webroot updaten  !!ändert alle abh. Elemente!!
        elseif ($chk[2]) {
            $sql = "UPDATE $table SET {$column[0]}='{$data[0]}' WHERE {$column[1]}='{$data[1]}'";
            self::sendSQL($sql, []);
        }

        else {  # beide Werte schon in DB
        };

        #connection.commit();
    }


    private static function sub1() :void
    {
        #cursor = connection.cursor()

        $table  = 'dzg_dirsub1';
        $column = Init::DB[$table][1];  # 'sub1'
        $placeholder = '?';
        $data = Init::WEB_SUBDIR;       # ['large', 'medium', 'small', 'thumb', 'original']

        # -- Bestand abfragen --
        $txt = '';
        foreach ($data as $value) {
            $txt .= "(SELECT count(1) FROM {$table} WHERE {$column}='{$value}'), ";
        };
        $txt = substr($txt, 0, -2);
        $sql = "SELECT " . $txt;
        #$cursor->execute($sql);
        #$chk = $cursor->fetchone();     # (0,1,1,0,1)
        $chk = self::sendSQL($sql, [], 'fetch', 'num');

        # alles gefunden (1,1,1..)
        if (array_sum($chk) == count($data)) {
        }

        # nix gefunden, alles anhängen (0,0,0..)
        elseif (array_sum($chk) == 0) {
            $sql = "INSERT INTO {$table} ({$column}) VALUES ({$placeholder})";
            self::sendSQL($sql, Init::array_zip($data), 'no', 'num', 'executemany');
        }

        # fehlende anhängen
        else {
            for ($i = 0; $i < count($chk); $i++) {
                if (!$chk[$i]) {
                    $sql = "INSERT INTO {$table} ({$column}) VALUES ('{$data[$i]}')";         # anhängen
                    #sql = "UPDATE {$table} SET {$column}='{$data[$i]}' WHERE id='{$i + 1}'";  # updaten
                    self::sendSQL($sql, []);
                };
            };
        };
        #connection.commit()
    }


    private static function prefix() :void
    {
        /*
        dzg_fileprefix, (subdir1, prefix)
        'prefix' fehlt in globaler Variablen!!, -> verschiebt andere Zugriffe!
        */
        #cursor = connection.cursor()

        $table  = 'dzg_fileprefix';
        $column = array_reverse(array_slice(Init::DB[$table], 1, 2));   # ('id_sub1', 'prefix')
        $column_str  = implode(',', $column);
        #$placeholder = ('?,' * (len($column)))[:-1];  # '?,?,?,...'

        $jointab1 = 'dzg_dirsub1';
        $col_id   = [Init::DB[$jointab1][0], Init::DB[$table][2]];  # ('id', 'id_sub1')
        $column   = [Init::DB[$jointab1][1], Init::DB[$table][1]];  # ('sub1', 'prefix')
        # -> list(zip(Init::_web_subdir, Init::_web_pre))
        # extrahieren aus dataset:
        # ['weboriginal', 'webgroß', 'webmittel', 'webklein', 'webthumb'] ->
        # [('original', ''), ('large', 'l_'), ('medium', 'm_'), ('small', 's_'), ('thumb', 't_')]
        # data = [(Path(dataset[18]).parents[1].name, '')]
        # data += [(Path(col).parents[1].name, Path(col).stem[:2]) for col in dataset[19:-1]]
        # data = list(zip(cls._web_subdir, cls._web_pre))       # Liste von Tuple, n. änderbar
        # [['original', ''], ['large', 'l_'], ['medium', 'm_'], ['small', 's_'], ['thumb', 't_']]
        $data = Init::array_zip(Init::WEB_SUBDIR, Init::WEB_PRE);

        # -- Bestand abfragen --
        #for (s1, w1), (s2, w2) in [list(Init::array_zip($column, $i)) foreach ($data as $i)] {

        # Arbeitliste zusammensetzen, um die SQL-Abfrage zu erstellen:
        # [[sub1, large], [prefix, l_]], [[sub1, medium], [prefix, m_]], ...]
        $tmp = [];
        foreach ($data as $i) {
            $tmp[] = Init::array_zip($column, $i);
        };
        $txt = '';
        foreach ($tmp as $a => [[$s1, $w1], [$s2, $w2]]) {
            # text im heredoc-Format, um die SQL-Abfrage zu erstellen:
            $txt .= <<<EOT
            (SELECT count(1) FROM $table a
            LEFT JOIN $jointab1 b ON b.$col_id[0]=a.$col_id[1]
            WHERE b.$s1='$w1'),
            (SELECT count(1) FROM $table WHERE $s2='$w2'),\n\r
            EOT;
        };
        $sql = "SELECT \n" . substr($txt, 0, -3);   # letztes Komma incl. Zeilenumbruch entfernen

        /*
        (SELECT count(1) FROM dzg_fileprefix a LEFT JOIN dzg_dirsub1 b ON b.id=a.id_sub1 WHERE b.sub1='original'),
        (SELECT count(1) FROM dzg_fileprefix WHERE prefix=''), ...
        */
        #cursor.execute(sql)
        #chk = cursor.fetchone()  # (0,1,1,0,1)
        $chk = self::sendSQL($sql, [], 'fetch', 'num');


        # [(0,1),(1,0),..)
        $tmp = [];
        for ($i = 0; $i < count($chk); $i += 2) {
            $tmp[] = [$chk[$i], $chk[$i + 1]];
        }
        $chk = $tmp;


        for ($i = 0; $i < count($chk); $i++) {
            $count = $chk[$i];

            # alles gefunden (1,1)
            if (array_sum($count) == count($count)) {
            }

            # anhängen (0,0) (0,1) (1,0)
            # --> Abhängige Tabellen! Funktioniert so nicht.
            # Überarbeiten!!
            else {
                # id_sub1 holen
                $sql = "SELECT id FROM dzg_dirsub1 WHERE sub1='{$data[$i][0]}'";

                #cursor.execute(sql)
                #$data[$i][0] = str(cursor.fetchone()[0]);
                $data[$i][0] = (string)self::sendSQL($sql, [], 'fetch', 'num')[0];

                $sql = "INSERT INTO $table ($column_str) VALUES ({$data[$i][0]}, {$data[$i][1]})";
                self::sendSQL($sql, []);
            };
        };
    }


    private static function sub2(array $dataset, array &$id) :array
    {
        $table  = 'dzg_dirsub2';
        $column = Init::DB[$table][1];  # 'sub2'
        $data   = $dataset[0];          # 'sub2'

        # -- Bestand abfragen --
        $sql = "SELECT id FROM $table WHERE $column='$data'";
        $idx = self::sendSQL($sql, [], 'fetch', 'num');  # tuple: (id,)

        if (!$idx) {  # nicht gefunden, anhängen
            $sql = "INSERT INTO $table ($column) VALUES ('$data')";
            self::sendSQL($sql, []);
            # connection.commit()
            # noch neue id holen
            # SQLITE:
            #idx = cursor.execute("SELECT last_insert_rowid()").fetchone()
            # MARIADB
            #cursor.execute("SELECT last_insert_id()")
            $idx = self::sendSQL("SELECT last_insert_id()", [], 'fetch', 'num');
        };

        # id für weitere Nutzung speichern
        $id['sub2'] = $idx;  # id: sub2
        return $id;
    }


    private static function suffix(array $dataset, array &$id) :array
    {
        $table  = 'dzg_filesuffix';
        $column = Init::DB[$table][1];  # 'suffix'
        $data   = $dataset[26];

        # cls.__sqlite_tabelle_leeren(table, connection)
        # cls.__sqlite_tabelle_LOESCHEN(table, connection)
        # cls.__NEW_tabellen_erzeugen(connection)

        # -- Bestand abfragen --
        $sql = "SELECT id FROM $table WHERE $column='$data'";
        $idx = self::sendSQL($sql, [], 'fetch', 'num');

        if (!$idx) {  # nicht gefunden, hinzufügen
            $sql = "INSERT INTO $table ($column) VALUES ('$data')";
            self::sendSQL($sql, []);
            # connection.commit()
            # noch neue id holen
            #idx = cursor.execute("SELECT last_insert_rowid()").fetchone()
            #cursor.execute("SELECT last_insert_id()")
            #idx = cursor.fetchone()
            $idx = self::sendSQL("SELECT last_insert_id()", [], 'fetch', 'num');
        };

        $id['suffix'] = $idx;  # id: sub2,+suffix
        return $id;
    }


    private static function view(array $dataset, array &$id) :array
    {
        $table  = 'dzg_kat20';
        $column = Init::DB[$table][1];  # 'kat20'
        $data   = $dataset[13];         # Position von 'VS/RS' (kat20) im Datensatz

        # -- Bestand abfragen --
        $sql = "SELECT count(1) FROM $table WHERE $column='$data'";
        $chk = self::sendSQL($sql, [], 'fetch', 'num')[0];
        if (!$chk) {  # nicht gefunden, hinzufügen
            $sql = "INSERT INTO $table ($column) VALUES ('$data')";
            self::sendSQL($sql, []);
        }
        return $id;
    }


    private static function cat_group(array $dataset, array &$id, bool $backup=false) :array
    {
        $table  = 'dzg_group';
        # id_thema, datum, kat10..kat13 (PostAmt, AMT, StempNr, Wolff)
        $column = array_merge([Init::DB[$table][1]], array_slice(Init::$db_columns, 1, 5));
        $column_str  = implode(',', $column);
        $tmp = str_repeat('?,', count($column));   # '?,?,?, ...'
        $placeholder = rtrim($tmp, ',');           # '?,?,?,...,?'
        # idsub2, datum, kat10..kat13
        $data = array_merge([$id['sub2']], array_slice($dataset, 1, 5));

        if ($backup) {
            $idx = [(int)$dataset[42],];    # sta_id / id_group
        } else {
            # -- Bestand abfragen --
            $sql = "SELECT id FROM $table
                WHERE $column[0]='$data[0]' AND $column[1]='$data[1]'
                AND $column[2]='$data[2]' AND $column[3]='$data[3]'
                AND $column[4]='$data[4]' AND $column[5]='$data[5]'";
            $idx = self::sendSQL($sql, [], 'fetch', 'num');
        };

        if (!$idx && !$backup) {  # nicht gefunden, hinzufügen
            # Werte eintragen
            $sql = "INSERT INTO $table ($column_str) VALUES ($placeholder)";
            self::sendSQL($sql, $data);
            # noch neue id holen
            #idx = cursor.execute("SELECT last_insert_rowid()").fetchone()
            $idx = self::sendSQL("SELECT last_insert_id()", [], 'fetch', 'num');
        };

        $id['group'] = $idx;  # id: sub2,suffix,+stamp
        return $id;
    }


    private static function cat_file(array $dataset, array &$id) :array
    {
        $table = 'dzg_file';

        $col = Init::DB[$table];
        # id_datei, id_group, id_thema, id_prefix, id_suffix, id_dirliste, id_sub1, id_sub2
        $tmp = array_slice($col, 0, 8);
        # kat20, ..., kat24
        $tmp = array_merge($tmp, array_slice(Init::DB[$table], 10, 5));
        $tmp = array_merge($tmp, array_slice($col, 20, 6)); # 6x namen
        $tmp = array_merge($tmp, array_slice($col, 26, 3)); # 3x datum
        $column = $tmp;

        $column_str = implode(',', $column);
        $tmp = str_repeat('?,', count($column));   # '?,?,?, ...'
        $placeholder = rtrim($tmp, ',');           # '?,?,?,...,?'

        # ID's der FKeys besorgen: id_dirliste
        $tab = 'dzg_dirliste';
        $localroot = str_replace('\\', '\\\\', $dataset[23]);
        $sql = "SELECT id FROM $tab WHERE localroot='$localroot'";
        $id['dirlist'] = self::sendSQL($sql, [], 'fetch', 'num');

        # Werte entspr. der Spaltenreihenfolge sortieren
        # (id_sub1/prefix=1 für 'original')
        $data = [
            $id['file'], $id['group'], $id['sub2'], '1',
            $id['suffix'], $id['dirlist'], '1', $id['sub2']];       # 8x ID's
        $data = array_merge($data, array_slice($dataset, 12, 5));   # 5x kat20-24
        $data = array_merge($data,
            [$dataset[27], $dataset[27], '', '', '', '']);          # 6x namen
        $data = array_merge($data, array_slice($dataset, 33, 3));   # 3x datum

        $sql = "INSERT INTO $table ($column_str) VALUES ($placeholder)";
        self::sendSQL($sql, $data);

        # noch neue id holen
        #idx = cursor.execute("SELECT last_insert_rowid()").fetchone()
        $id['file'] = self::sendSQL("SELECT last_insert_id()", [], 'fetch', 'num');

        return $id;
    }



    /**
     * Datenbank in Excel-Datei speichern
     */
    public static function make_backup(string $outFileName = '', bool $add_date = true) :void
    {
        # Spalten, die ausgelesen werden
        $col = Init::DB_COL;

        # Filter
        $where = "pre.prefix=''";

        # Sortierung
        $sort = "
        the.id DESC, sta.kat10, sta.datum,
        sta.kat11, sta.kat12, sta.kat13, sta.kat14, sta.kat15, sta.kat16, sta.kat17,
        dat.kat20 DESC, dat.kat21, dat.kat22, dat.kat23, sta.id, dat.id";

        # Abfragebefehl
        $sql = "
        SELECT {$col}
        FROM dzg_file AS dat
            LEFT JOIN dzg_group AS sta ON sta.id=dat.id_group
            LEFT JOIN dzg_dirsub2 AS the ON the.id=sta.id_thema
            LEFT JOIN dzg_dirsub2 AS sub2 ON sub2.id=dat.id_sub2
            LEFT JOIN dzg_dirliste AS dir ON dir.id=dat.id_dirliste
            LEFT JOIN dzg_filesuffix AS suf ON suf.id=dat.id_suffix
            LEFT JOIN dzg_kat15 AS k15 ON k15.kat15=sta.kat15
            LEFT JOIN dzg_kat20 AS k20 ON k20.kat20=dat.kat20
            LEFT JOIN dzg_kat21 AS k21 ON k21.kat21=dat.kat21
            LEFT JOIN dzg_dirsub1 AS sub1 ON sub1.id
            LEFT JOIN dzg_fileprefix AS pre ON pre.id_sub1=sub1.id
        WHERE {$where}
        ORDER BY {$sort}
        ";

        # Datenbank-Abfrage senden/empfangen
        # 'num'/Index_Array empfangen,
        # da Spalten ($col) nicht eindeutig angegeben sind.
        # dat.id / sta.id = id
        # eine eindeutige Benennung àla: dat.id did,
        # klappt mit späterer Array-Verarbeitung für Excel-Header nicht so.
        $db_query = DB::sendSQL($sql, [], 'fetchall', 'num');


        # Elemente in string wandeln
        $db_list = [];
        foreach ($db_query as $idx => $data) {
            foreach ($data as $k => $v) {
                #if (!is_string($v)){echo gettype($v).': '. $k.'->'.$v.PHP_EOL;}
                $v ??= '';
                $v = is_bool($v) ? ($v ? '1' : '0') : $v;
                $v = is_int($v) ? (string)$v : $v;
                $db_list[$idx][$k] = $v;
            };
        };


        # Tabellenkopf
        $csv_head = Init::$csv_head;
        $db_head  = Init::$db_head;

        $head = array_merge([$db_head], [$csv_head]);     # 2-zeiliger Header
        $datalist = array_merge([$csv_head], $db_list);   # Daten incl. csv-Header
        $data = array_merge($head, $db_list);             # Daten incl. 2-zeiligem Header


        $outFileName = $outFileName ?: 'db_backup';
        FileHandler::write2Excel($db_list, $head, $outFileName, '', $add_date);


        echo "Datenbank in excel geschrieben: " .count($db_list). "x", PHP_EOL;

        /*
        echo count($csv_head). ' ' .count($db_head) .PHP_EOL;
        for ($i=0; $i<count($csv_head); $i++) {
            $d = $db_head[$i] ?? '---';
            echo $csv_head[$i]. ' <--> ' .$d .PHP_EOL;
        };
        */

        /*
        $tmp = [];
        foreach ($db_list[0] as $k=>$v) {
            $tmp[] = [$k, $v];
        }*/
        /*
        $ct1 = count($db_head);
        $ct2 = count($db_list[0]);
        $ct = max($ct1, $ct2);
        echo $ct1.' '.$ct2.' '.$ct.PHP_EOL;
        */
        /*
        for ($i=0; $i<$ct; $i++) {
            $h = $db_head[$i] ?? ' - ';
            [$k, $v] = $tmp[$i] ?? [' - ', ' - '];
            echo $i.': '.$h. ': ' .$k.'=>'.$v .PHP_EOL;
        };*/
        #exit;



        # open excel
        #try:
        #    #subprocess.run(f"{EXCEL_PROG} {$excel_file}", check=False)
        #    pass
        #except:
        #    pass

    }

}

// EOF