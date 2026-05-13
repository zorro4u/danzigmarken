<?php
namespace Dzg;

class PWforgetMsg
{
    // prep
    public const MSG1 = [
        110 => "Keine gültige Email-Adresse.",
        111 => "Bitte eine E-Mail-Adresse eintragen.",
        112 => "Keine solche E-Mail-Adresse im System hinterlegt.",
        113 => "Dir wurde soeben eine Email an",
        114 => "zugesandt, mit der du innerhalb der nächsten 48 Stunden (bis zum",
        115 => "dir eine neues Passwort vergeben kannst.",
        116 => "Oh, die Email konnte <b>NICHT</b> gesendet werden :-(",
        117 => "Neues Passwort für www.danzigmarken.de",
        118 => "Neues Passwort für www.danzigmarken.de",
        119 => "für deinen Account auf www.danzigmarken.de wurde nach einem neuen Passwort gefragt.",
        120 => "Um ein neues Passwort zu vergeben, rufe innerhalb der nächsten 48 Stunden (bis",
        121 => "die folgende Website auf",
        122 => "Sollte dir dein Passwort wieder eingefallen sein oder hast du dies nicht angefordert, so ignoriere diese E-Mail.",
        123 => "Herzliche Grüße",
        124 => "Dein Support-Team von www.danzigmarken.de",
    ];

    // form
    public const MSG2 = [];

    // sites
    public const MSG3 = [
        310 => "Passwort vergessen?",
        311 => "Gib deine registrierte E-Mail-Adresse an, um ein neues Passwort anzufordern.",
        312 => "E-Mail",
        313 => "Neues Passwort anfordern",
        314 => "Startseite",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF