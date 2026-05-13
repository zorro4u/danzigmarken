<?php
namespace Dzg;

class AdminMsg
{
    // prep
    public const MSG1 = [];

    // form
    public const MSG2 = [
        210 => "alle Registrierungslinks gelöscht.",
        211 => "Alle meine anderen aktiven Autologins beendet.",
        212 => "Alle meine ausgeloggten Logins beendet.",
        213 => "Alle meine beendeten Logins gelöscht.",
        214 => "Alle meine abgelaufenen Logins gelöscht.",
        215 => "Alle aktiven Autologins der anderen Nutzer beendet.",
        216 => "Alle ausgeloggten Autologins der anderen Nutzer beendet.",
        217 => "Alle anderen toten Logins gelöscht.",
        218 => "Alle anderen abgelaufenen Logins gelöscht.",
        219 => "Alle Autologins der anderen Nutzer beendet.",
        220 => "Registrierungs-Link erzeugt.",
        221 => "Registrierungslink gelöscht.",
        222 => "Nutzer gelöscht.",
        223 => "Kann mich nicht selbst löschen.",
    ];

    // sites
    public const MSG3 = [
        # tabs
        310 => "erweiterte Einstellungen",
        311 => "Info",
        312 => "Nutzer",
        313 => "Autologin",
        314 => "Reg-Links",
        315 => "Sonstiges",
        316 => "Tools",

        317 => "aktiv",
        318 => "Aktivierung ausstehend",
        319 => "Nutzer",
        320 => "Email",
        321 => "erstellt",
        322 => "geändert",
        323 => "gültig bis",
        324 => "Log-Protokoll",
        325 => "Wirklich den Nutzer  - L Ö S C H E N -  ?",
        326 => "Nutzer löschen",
        327 => "Die automatische Anmeldung beenden für",
        328 => "meine anderen aktiven",
        329 => "meine ausgeloggten",
        330 => "meine beendeten (tot)",
        331 => "meine abgelaufenen (tot)",
        332 => "alle anderen aktiven",
        333 => "alle anderen ausgeloggten",
        334 => "alle anderen beendeten (tot)",
        335 => "alle anderen abgelaufenen (tot)",
        336 => "alle anderen Anmeldungen",
        337 => "alle anderen Nutzer",
        338 => "Beenden",
        339 => "Keine Autologins vorhanden.",
        340 => "Link erzeugen",
        341 => "Registrierungslink",
        342 => "Link öffnen",
        343 => "als Email anzeigen",
        344 => "Link löschen",
        345 => "alle Links löschen",
        346 => "Viewportabmessungen",
        347 => "Breite",
        348 => "Höhe",
        349 => "Geräteabmessungen",
        350 => "import.1",
        351 => "(neue) Dateien in Excel-Liste speichern",
        352 => "import.2",
        353 => "(neue) Daten aus Excel-Liste in DB speichern",
        354 => "import.3",
        355 => "von (neuen) Excel-Daten webpics erstellen",
        356 => "import.4",
        357 => "DB in Excel speichern / Backup",
        358 => "Mail-Log",
        359 => "",
        360 => "Excel_Download",
        361 => "",
        362 => "PDF_Download",
        363 => "",
        364 => ">PDF anzeigen",
        365 => "",
        366 => "DB cleaning",
        367 => "",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF