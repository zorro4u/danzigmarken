<?php
namespace Dzg;

class ContactMsg
{
    // prep
    public const MSG1 = [

    ];

    // form
    public const MSG2 = [
        210 => "nur Buchstaben im Namen zulässig (oder Bindestrich/Leerzeichen bei Doppelnamen)",
        211 => "Keine gültige Email-Adresse.",
        212 => "Die Nachricht verwendet unzulässige Zeichen",
        213 => "Keine Name/Email und Nachricht angegeben.",
        214 => "Der <strong>Sicherheitscode</strong> wurde falsch eingegeben.",
        215 => "Bitte die <strong>Sicherheitsfrage</strong> richtig beantworten.",
        216 => "Es besteht Spamverdacht. Bitte überprüfen Sie Ihre Angaben.",
        217 => "Bitte warten Sie einige Sekunden, bevor Sie das Formular erneut absenden.",
        218 => "Sie müssen den Senden-Button mit der Maus anklicken, um das Formular senden zu können.",
        219 => "Ihre Nachricht darf",
        220 => "keine Links",
        221 => "nur einen Link",
        222 => "maximal",
        223 => "Links",
        224 => "enthalten",
        225 => "Folgende Begriffe sind nicht erlaubt",
        226 => "Sie müssen die <strong>Datenschutz&shy;erklärung</strong> akzeptieren.",
        227 => "Bitte überprüfen und korrigieren Sie Ihre Eingaben.",
        228 => "Deine Nachricht wurde versandt. Du erhälst in Kürze eine Antwort.",
        229 => "Oh, die Nachricht konnte <b>NICHT</b> gesendet werden :-(",
    ];

    // sites
    public const MSG3 = [
        310 => "Kontakt",
        311 => "Dein Name",
        312 => "Deine E-Mail-Adresse",
        313 => "Deine Nachricht",
        314 => "Das nachfolgende Feld muss leer bleiben, damit die Nachricht gesendet wird!",
        315 => "Sicherheitscode",
        316 => "Ergebnis Sicherheitsfrage",
        317 => "Sicherheitsfrage",
        318 => "Kopie der Nachricht per E-Mail senden",
        319 => "Ich stimme der Datenschutz&shy;erklärung zu.",
        320 => "Nachricht senden",
        321 => "Felder bitte ausfüllen.",
    ];

    public const MSG = self::MSG1 + self::MSG2 + self::MSG3;
}


// EOF