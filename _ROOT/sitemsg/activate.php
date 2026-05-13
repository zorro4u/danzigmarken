<?php
namespace Dzg;

class ActivateMsg
{
    // prep
    public const MSG1 = [
        110 => "Code enthält ungültige Zeichen",
        111 => "Kein Code übermittelt.",
        112 => "Dein Konto ist aktiviert. Du kannst dich jetzt",
        113 => "anmelden",
        114 => "Die Aktivierungsfrist von 4 Wochen ist am",
        115 => "abgelaufen.",
        116 => "Das Konto ist bereits aktiviert oder existiert nicht.",
    ];

    // form
    public const MSG2 = [];

    // sites
    public const MSG3 = [
        310 => "Aktivierung",
        311 => "Anmelden",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF