<?php
namespace Dzg;
require_once __DIR__.'/MailConfig.php';


/***********************
 * Summary of RateLimiting
 */
class RateLimiting
{
    public static function run()
    {
        $cfg = MailConfig::$cfg;
        $maximale_aufrufe = MailConfig::$maximale_aufrufe;

        // Datei zum Speichern der IP-Adressen
        #$logfile = __DIR__.'/log/ip_log.txt';
        $logfile = MailConfig::$mail_logfile;

        if ($cfg['Aufrufe_limitieren']):

        // Hole die IP-Adresse des Besuchers
        $visitor_ip = $_SERVER['REMOTE_ADDR'];

        // Lese die Logdatei, um die bisherigen IP-Einträge zu zählen
        $log_data = file_exists($logfile) ? file_get_contents($logfile) : '';
        $ip_list = explode("\n", trim($log_data));

        // Zähle, wie oft die IP-Adresse bereits eingetragen ist
        $ip_count = 0;
        foreach ($ip_list as $line) {
            if ($line === $visitor_ip) {
                $ip_count++;
            }
        }

        // Wenn die IP-Adresse mehr als 100 Mal aufgerufen wurde, zeige eine Warnung
        if ($ip_count >= $maximale_aufrufe) {
            $output = '
            <style>
                .centered-container {
                    display: flex;
                    justify-content: center;
                    align-items: center;

                    margin: 0;
                    padding: 20px; /* Abstand zum Rand */
                    box-sizing: border-box; /* Einschließen des Paddings in der Höhe und Breite */
                }

                .message-container {
                    background-color: #fff;
                    padding: 10px;
                    border-radius: 10px;
                    box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
                    text-align: center;
                    max-width: 90%;
                    width: 100%;
                    /* Vermeidung von Überschreitungen auf kleinen Bildschirmen */
                    box-sizing: border-box;
                    margin-bottom: 10px; /* Großer Abstand nach unten */
                }

                .message {
                    color: #d9534f; /* Rote Farbe für Warnungen */
                    font-size: 18px;
                    line-height:27px;
                    font-family: Verdana, sans-serif; /* Schriftart Verdana */
                }
            </style>
            <div class="centered-container">
                <div class="message-container">
                    <p><img src="img/failed.png" style="" /></p>
                    <p class="message">Es wurden ungew&ouml;hnliche Aktivit&auml;ten festgestellt.</p>
                    <p class="message">Das Formular steht in K&uuml;rze wieder zur Verf&uuml;gung.</p>
                </div>
            </div>';

            echo $output;
            exit;
        }

        else {
            // Füge die IP-Adresse in die Logdatei ein
            file_put_contents($logfile, $visitor_ip . "\n", FILE_APPEND | LOCK_EX);
        }

        endif;
    }
}