<?php
namespace Dzg\PrivateData;

/**
 * Anmelde-Informationen.
 * Datei liegt außerhalb des Verzeichnisbaums $_SERVER['DOCUMENT_ROOT']
 * damit sie nicht von extern zugreifbar ist (chmod 0600)
 *
 * abrufbar: \Dzg\PrivateData\DBASE;
 */

// mysql
const DBASE  = "XXXX";       # Datenbankname
const DBUSER = "XXXX";       # DB-Anmeldename
const DBPW   = "XXXX";       # DB-Anmeldepasswort


// email
const MAILFROM = "XXXX";     # email-from Info für Empfänger
const MAILUSR  = "XXXX";     # Account-Anmeldename
const MAILPWD  = "XXXX";     # Account-Anmeldepasswort

