<?php
namespace Dzg;

class DetailsMsg
{
    // prep
    public const MSG1 = [
        110 => "Seite ohne ID-Angabe funktioniert nicht.",
        111 => "uups, a wrong ID ... u'r out",
        112 => "ID not found",
    ];

    // form
    public const MSG2 = [];

    // sites
    public const MSG3 = [
        310 => "in Druckauswahl ja/nein",
        311 => "größere Ansicht",
        312 => "große Ansicht",
        313 => "Drucken",
        314 => "zurück",
        315 => "vor",
    ];

    // prep.change
    public const MSG4 = [
        410 => "druckbar",
    ];

    // form.change
    public const MSG5 = [
        520 => "wiederhergestellt",
        521 => "aus Gruppe gelöst",
        522 => "unzulässige Zeichen eingegeben",
        523 => "Änderung ausgeführt.",
    ];

    // sites.change
    public const MSG6 = [
        620 => "Wirklich von der Bildgruppe  - L Ö S E N -  ?",
        621 => "aus Gruppe lösen",
        622 => "Wirklich das Bild  - L Ö S C H E N -  ?",
        623 => "aus Bestand <b>löschen</b>",
        624 => "Wiederherstellen",
        625 => "Abbrechen",
        626 => "Wirklich Eintrag  - Ä N D E R N -  ?",
        627 => "Ändern",
    ];

    public const MSG =
        self::MSG1 + self::MSG2 + self::MSG3 +
        self::MSG4 + self::MSG5 + self::MSG6;
}


// EOF