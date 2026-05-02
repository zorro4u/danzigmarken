<?php
namespace Dzg\Import;

require_once __DIR__.'/lokalimport.php';
require_once __DIR__.'/toolbox.php';

date_default_timezone_set('Europe/Berlin');


main(4);


/**
 * zentraler Startpunkt
 *
 * -1- (neue) Dateien in Excel-Liste speichern
 * -2- (neue) Daten aus Excel-Liste in DB speichern
 * -3- von (neuen) Excel-Daten webpics erstellen
 * -4- DB in Excel speichern / Backup
 */
function main(int $switch = 1)
{
    $time1 = microtime(true);

    if ($switch == 1) {
        LokalImport::step1();

    } elseif ($switch == 2) {
        LokalImport::step2();

    } elseif ($switch == 3) {
        LokalImport::step3();

    } elseif ($switch == 4) {
        LokalImport::step4();
    }

    $time2 = microtime(true) - $time1;
    echo "Gesamtausführungszeit: " . ToolBox::time2str($time2), PHP_EOL;
    echo PHP_EOL, "-- e n d e --", PHP_EOL;
}

// EOF