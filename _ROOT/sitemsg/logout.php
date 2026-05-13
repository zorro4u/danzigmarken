<?php
namespace Dzg;

class LogoutMsg
{
    // prep
    public const MSG1 = [
        110 => "Alle meine anderen Autologins beendet.",
        111 => "Du bist abgemeldet",
    ];

    // form
    public const MSG2 = [];

    // sites
    public const MSG3 = [
        310 => "Abmelden",
        311 => "geräteübergreifend alle meine Anmeldungen beenden (Grand Logout)",
        312 => "Logout",
        313 => "Startseite",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF