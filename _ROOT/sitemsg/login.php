<?php
namespace Dzg;

class LoginMsg
{
    // prep
    public const MSG1 = [
        110 => "Du bist schon angemeldet. Was machst du dann hier? ...",
    ];

    // form
    public const MSG2 = [
        210 => "#Login: Passwort muss zwischen 4 und 50 Zeichen lang sein!",
        211 => "#Login: Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>",
        212 => "#Login: unzulässige Zeichen im Anmeldenamen",
        213 => "Du bist angemeldet",
        214 => "Passwort ist falsch.",
        215 => "Das Konto existiert nicht.",
        216 => "Das Konto ist nicht aktiviert.",
        217 => "Nutzer ist noch nicht registriert.",
    ];

    // sites
    public const MSG3 = [
        310 => "Anmelden",
        311 => "E-Mail",
        312 => "Benutzer / E-Mail",
        313 => "Passwort",
        314 => "Angemeldet bleiben",
        315 => "Passwort vergessen?",
        316 => "Registrieren",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF