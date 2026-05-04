<?php
namespace Dzg\SitePrep;
use Dzg\Tools\Tools;

require_once __DIR__.'/../tools/tools.php';


class RegisterInfo
{
    protected static function dataPreparation(): void
    {
        Tools::lastSite();
    }
}


// EOF