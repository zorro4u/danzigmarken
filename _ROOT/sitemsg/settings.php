<?php
namespace Dzg;

class SettingsMsg
{
    // prep
    public const MSG1 = [];

    // form
    public const MSG2 = [
        210 => "Bitte korrektes Passwort eingeben.",
        211 => "Name oder Email angeben.",
        212 => "Die Emailangaben müssen übereinstimmen.",
        213 => "Keine gültige Email-Adresse.",
        214 => "Die E-Mail-Adresse ist bereits registriert.",
        215 => "E-Mail-Adressse unverändert.",
        216 => "nur Buchstaben/Zahlen im Anmeldenamen zulässig",
        217 => "Der Benutzername ist schon vergeben.",
        218 => "Benutzername unverändert.",
        219 => "Benutzername und E-Mail-Adresse erfolgreich gespeichert.",
        220 => "E-Mail-Adresse erfolgreich gespeichert.",
        221 => "Benutzername erfolgreich geändert.",
        222 => "Die eingegebenen Passwörter stimmen nicht überein.",
        223 => "Passwort enthält ungültige Zeichen. Nur alphanumerisch und",
        224 => "<LEER>",
        225 => "Passwort erfolgreich gespeichert.",
        226 => "nur Buchstaben im Vornamen zulässig",
        227 => "nur Buchstaben im Nachnamen zulässig",
        228 => "(oder Bindestrich/Leerzeichen bei Doppelnamen)",
        229 => "Persönliche Daten geändert.",
        230 => "alle meine anderen Autologins beendet.",
        231 => "Ein Admin kann sich hier nicht löschen.",
        232 => "Nutzer gelöscht.",
    ];

    // sites
    public const MSG3 = [
        # tabs
        310 => "Einstellungen",
        311 => "Anmeldedaten",
        312 => "Passwort",
        313 => "Persönliche Daten",
        314 => "Autologin",
        315 => "Konto löschen",
        316 => "Download",

        317 => "Zum Ändern deiner Daten gib bitte zur Bestätigung dein aktuelles Passwort ein.",
        318 => "Passwort",
        319 => "Benutzername",
        320 => "E-Mail",
        321 => "E-Mail (wiederholen)",
        322 => "Speichern",
        323 => "Zum Änderen deines Passworts gib bitte zur Bestätigung dein aktuelles Passwort ein",
        324 => "Aktuelles Passwort",
        325 => "Neues Passwort",
        326 => "Neues Passwort (wiederholen)",
        327 => "Vorname",
        328 => "Nachname",
        329 => "Alle meine anderen Anmeldungen geräteübergreifend beenden",
        330 => "Beenden",
        331 => "Keine Autologins vorhanden.",
        332 => "Zum Löschen deines Kontos gib bitte zur Bestätigung dein aktuelles Passwort ein.",
        333 => "Wirklich das Konto  - L Ö S C H E N -  ?",
        334 => "mein Konto löschen",
        335 => "Datenbank-Auszug als PDF-Datei",
        336 => "anzeigen",
        337 => "downloaden",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF