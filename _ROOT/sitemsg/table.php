<?php
namespace Dzg;

class TableMsg
{
    // navi
    public const MSG1 = [
        110 => "eine Seite rückwärts",
        111 => "erste Seite",
        112 => "aktuelle Seite",
        113 => "Seite",
        114 => "letzte Seite",
        115 => "eine Seite vorwärts",
        116 => "Anzahl pro Seite festlegen",
        117 => "Auswahl löschen",
        118 => "Filter wählen",
        119 => "Thema auswählen",
        120 => "suchen",
        121 => "Suche starten",
        122 => "Suche löschen",
        123 => "Suchwörter eingeben",
    ];

    // body
    public const MSG2 = [
        210 => "absteigend",
        211 => "aufsteigend",
        212 => "in Druckauswahl aufnehmen",
        213 => "druck ja/nein",
    ];

    // sites
    public const MSG3 = [
        310 => "Druckanzeige",
        311 => "zum Seitenanfang",
        312 => "Seitenanfang",
        313 => "Marken",
        314 => "Einträgen",
        315 => "Hier nur eine zufällige",
        316 => "er Auswahl aus",
        317 => "Voller Zugang mit Filter- und Suchfunktion nach dem",
        318 => "Anmelden",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF