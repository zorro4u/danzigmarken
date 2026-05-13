<?php
namespace Dzg;
use Dzg\Tools\Database;

require_once __DIR__.'/../tools/database.php';


class LogoutData
{
    public static function setLogout(int $userid, string $identifier): void
    {
        // alle anderen Autologins beenden, wenn mit Autologin auch angemeldet
        $stmt0 = "UPDATE site_login
            SET login = NULL, autologin = NULL
            WHERE userid = :userid AND (login = 1 && autologin = 1)
            AND identifier != :ident";

        // alle anderen Logins beenden, wenn mit Autologin angemeldet
        $stmt = "UPDATE site_login
            SET login = NULL, autologin = NULL
            WHERE userid = :userid AND (login = 1)
            AND identifier != :ident";

        // alle Autologins beenden
        $stmt1 = "UPDATE site_login
            SET login = NULL, autologin = NULL
            WHERE userid = :userid AND (login = 1 && autologin = 1)";

        // alle Logins beenden
        $stmt2 = "UPDATE site_login
            SET login = NULL, autologin = NULL
            WHERE userid = :userid AND (login = 1)";

        $data = [':userid' => $userid, ':ident' => $identifier];

        Database::sendSQL($stmt, $data);
    }
}


// EOF