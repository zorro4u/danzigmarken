<?php
namespace Dzg\Account;

/**
 * Anmelde-Informationen.
 * Datei liegt außerhalb des Verzeichnisbaums $_SERVER['DOCUMENT_ROOT']
 * damit sie nicht von extern zugreifbar ist (chmod 0600)
 *
 * abrufbar: \Dzg\Account\MyData::DBASE;
 */
class MyData
{
    // mysql
    public const DBASE  = "XXXX";       # Datenbankname
    public const DBUSER = "XXXX";       # DB-Anmeldename
    public const DBPW   = "XXXX";       # DB-Anmeldepasswort


    // email
    public const MAILFROM = "XXXX";     # email-from Info für Empfänger
    public const MAILUSR  = "XXXX";     # Account-Anmeldename
    public const MAILPWD  = "XXXX";     # Account-Anmeldepasswort

}