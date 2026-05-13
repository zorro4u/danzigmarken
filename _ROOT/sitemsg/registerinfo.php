<?php
namespace Dzg;

class RegisterInfoMsg
{
    // prep
    public const MSG1 = [

    ];

    // form
    public const MSG2 = [
        210 => "nur Buchstaben im Namen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen)",
        211 => "Email angeben.",
        212 => "Keine gültige Email-Adresse.",
        213 => "Die Nachricht verwendet unzulässige Zeichen",
        214 => "keine Email und Nachricht angegeben.",
        215 => "Eine Email mit deiner Anfrage wurde versandt. Du erhälst in Kürze eine Antwort.",
        216 => "Oh, die Email konnte <b>NICHT</b> gesendet werden :-(",
        217 => "Registrierungs-Link für www.danzigmarken.de",
        218 => "Hallo",
        219 => "du kannst dich jetzt auf www.danzigmarken.de registrieren.",
        220 => "Rufe dazu in den nächsten 4 Wochen (bis zum",
        221 => "den folgenden Link auf",
        222 => "Herzliche Grüße",
        223 => "Dein Support-Team von www.danzigmarken.de"
    ];

    // sites
    public const MSG3 = [
        310 => "Registrierung",
        311 => "Du interessierst dich für diese Seiten und willst erweiterten Zugriff auf den Inhalt haben?",
        312 => "Informiere mich kurz darüber und du erhälst Zugang via deiner Email-Adresse.",
        313 => "Dein Name",
        314 => "Deine E-Mail",
        315 => "Deine Nachricht",
        316 => "Anfrage senden",
        317 => "Hinweise",
        318 => "Alle",
        319 => "Felder bitte ausfüllen",
        320 => "Du wirst als nächstes eine Email mit deinem Registrierungs-Link erhalten",
        321 => "Startseite",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF