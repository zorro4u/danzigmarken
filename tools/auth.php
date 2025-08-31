<?php
namespace Test;
date_default_timezone_set('Europe/Berlin');
session_start();

require $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls//Database.php";
use Dzg\Database as DB;

require $_SERVER['DOCUMENT_ROOT']."/assets/vendor/autoload.php";

use PDO;
use RuntimeException;



Auth::run();


class Auth {
    public static array $cfg_array;
    public static function getCfgArray()
    {
        if (empty(self::$cfg_array)):
            require __DIR__."/PHPAuth_config_array.php";
            self::$cfg_array = $PHPAuth_config_array;
        endif;

        return self::$cfg_array;
    }


    public static function run()
    {
        $dbh = DB::getPDO();
        $cfg = new \PHPAuth\Config($dbh, null, \PHPAuth\Config::CONFIG_TYPE_SQL);
        #$cfg = new \PHPAuth\Config($dbh, self::getCfgArray(), 'array');
        #$cfg = new \PHPAuth\Config($dbh, __DIR__.'/PHPAuth_config_ini.ini', 'ini');
        #$cfg = new \PHPAuth\Config($dbh, '$/PHPAuth_config_ini.ini', 'ini');
        $cfg = $cfg->setLocalization( (new \PHPAuth\PHPAuthLocalization('de_DE'))->use() );
        $auth   = new \PHPAuth\Auth($dbh, $cfg);

        self::storeConfigToDB($dbh, self::getCfgArray(), 'array');
        #self::storeConfigToDB($dbh, __DIR__.'/PHPAuth_config_ini.ini', 'ini');

        #var_dump($auth->getUID("s.viele@web.de"));
        #var_dump($auth->deleteUser($auth->getUID("s.viele@web.de"), "test"));echo'<br>';
        #var_dump($auth->register("s.viele@web.de", "test", "test", [], '', true) );


        #var_dump($cfg->getAll());

/*
        if (!$auth->isLogged()) {
            header('HTTP/1.0 403 Forbidden');
            echo "Forbidden";

            exit();
        }
*/
    }

    public static function storeConfigToDB($dbh, $config_source, $cfg_type)
    {
        $config = [];
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $cfg_type = strtolower($cfg_type);

        switch($cfg_type):
            case 'ini':
                // ini
                if (!is_readable($config_source)) {
                    throw new RuntimeException("PHPAuth: config type is FILE, declared as {$config_source}, but file not readable or not exist");
                }

                // load configuration
                $config = parse_ini_file($config_source);
                break;

            case 'array':
                // array
                if (empty($config_source)) {
                    throw new RuntimeException('PHPAuth: config type is ARRAY, but source config is EMPTY');
                }

                // get configuration from given array
                $config = $config_source;
                break;
        endswitch;

        $sql = "UPDATE phpauth_config SET value=:value WHERE setting=:setting";
        foreach ($config as $setting=>$value) {
            $data = [':setting' => $setting, ':value' => $value];
            DB::sendSQL($sql, $data);
        }

    }
}