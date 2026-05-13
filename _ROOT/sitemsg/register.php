<?php
namespace Dzg;

class RegisterMsg
{
    // prep
    public const MSG1 = [
        110 => "Die Registrierung funktioniert nur mit dem per Email zugesandten Link. <br>Überprüfe nochmal deinen Posteingang und den Spam-Ordner. Wiederhole ggf. die Registrierung. <br>",
        111 => "Der Registrierungs-Code fehlt. Überprüfe nochmal den Link in deiner Email.",
        112 => "<b>Manipulationsverdacht: </b><br>Es wurden ungültige Zeichen im Registrierungs-Code erkannt.",
        113 => "Der Registrierungs-Link ist nicht gültig. Wiederhole die Registrierung.",
        114 => "Der Registrierungs-Link ist nach 4 Wochen abgelaufen."
    ];

    // form
    public const MSG2 = [
        210 => "Name und Email angeben.",
        211 => "nur Kleinbuchstaben/Zahlen im Anmeldenamen zulässig",
        212 => "Keine gültige Email-Adresse.",
        213 => "Der Benutzername ist schon vergeben.",
        214 => "Die E-Mail-Adresse ist bereits registriert.",
        215 => "Bitte identische Passwörter eingeben",
        216 => "Passwort muss zwischen 4 und 50 Zeichen lang sein!",
        217 => "Passwort enthält ungültige Zeichen. Nur alphanumerisch und !?,.:_=$%&#+*~^(@€µÄÜÖäüöß)<LEER>",
        218 => "Name, Email und Passwort angeben.",
        219 => "Eine Email wurde dir soeben zur Bestätigung zugesandt, in der die Registrierung abschließend noch aktiviert werden muss. Danach kannst du dich anmelden.",
        220 => "Oh, die Email konnte <b>NICHT</b> gesendet werden :-(",
        221 => "Du wurdest erfolgreich registriert und kannst dich jetzt anmelden",
        222 => "Kontoaktivierung auf www.danzigmarken.de",
        223 => "Hallo",
        224 => "dein Konto auf www.danzigmarken.de muss noch bis zum",
        225 => "aktiviert werden.",
        226 => "Rufe dazu folgenden Link auf",
        227 => "Herzliche Grüße",
        228 => "Dein Support-Team von www.danzigmarken.de",
    ];

    // sites
    public const MSG3 = [
        310 => "Registrierung",
        311 => "Benutzername",
        312 => "E-Mail",
        313 => "Passwort",
        314 => "Passwort wiederholen",
        315 => "Registrieren",
        316 => "Hinweise",
        317 => "Felder bitte ausfüllen",
        318 => "Name",
        319 => "Buchstaben, Zahlen oder Bindestriche",
        320 => "Passwort",
        321 => "Buchstaben, Zahlen oder ausgewählte Sonderzeichen, mind. 4 Zeichen",
        322 => "Anmelden",
        323 => "Startseite",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF