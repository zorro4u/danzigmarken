<?php
namespace Dzg;

class ImpressumMsg
{
    // prep
    public const MSG1 = [];

    // form
    public const MSG2 = [];

    // sites
    public const MSG3 = [
        310 => "Herausgeber",
        311 => "Deutschland",
        312 => "Kontakt",
        313 => "E-Mail",
        314 => "realisiert von",
        315 => "Web-Hoster",
        316 => "Telefon",
        317 => "verantwortlich",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF