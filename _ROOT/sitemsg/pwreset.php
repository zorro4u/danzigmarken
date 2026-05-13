<?php
namespace Dzg;

class PWresetMsg
{
    // prep
    public const MSG1 = [
        110 => "Ohne Legitimations-Code kann das Passwort nicht zurückgesetzt werden.",
        111 => "Es wurde kein Legitimations-Code zum Zurücksetzen des Passworts übermittelt.",
        112 => "Der Benutzer wurde nicht gefunden oder hat kein neues Passwort angefordert bzw. der übergebene Code war ungültig.",
        113 => "Stell sicher, dass du den genauen Link in der URL aufgerufen hast. Solltest du mehrmals die Passwortvergessen-Funktion genutzt haben, so ruf den Link in der neuesten E-Mail auf.",
        114 => "Dein Code ist leider am",
        115 => "abgelaufen. Benutze die",
        116 => "Passwortvergessen-Funktion",
        117 => "erneut.",
        118 => "Passwort enthält ungültige Zeichen. Nur alphanumerisch und",
        119 => "<LEER>",
        120 => "Bitte identische Passwörter eingeben",
        121 => "Passwort angeben.",
        122 => "Dein Passwort wurde geändert",
        123 => "Passwort muss zwischen 4 und 50 Zeichen lang sein!",
    ];

    // form
    public const MSG2 = [];

    // sites
    public const MSG3 = [
        310 => "Neues Passwort vergeben",
        311 => "du kannst dir hier ein neues Passwort vergeben",
        312 => "Neues Passwort",
        313 => "Passwort wiederholen",
        314 => "Passwort speichern",
        315 => "Anmelden",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF