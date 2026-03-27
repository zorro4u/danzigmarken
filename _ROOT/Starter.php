<?php
namespace Dzg;
use Dzg\Sites;


class Starter
{
    public static function run($site='table')
    {
        $class_site = __DIR__."/sites/".ucfirst($site).".php";
        require_once $class_site;

        switch (strtolower($site)):

            case "admin":
                Sites\Admin::show();
                break;

            case "settings":
                Sites\Settings::show();
                break;

            case "table":
                Sites\Table::show();
                break;

            case "details":
                Sites\Details::show();
                break;

            case "change":
                Sites\Change::show();
                break;

            case "login":
                Sites\Login::show();
                break;

            case "logout":
                Sites\Logout::show();
                break;

            case "pwforget":
                Sites\Pw_forget::show();
                break;

            case "pwreset":
                Sites\Pw_reset::show();
                break;

            case "register_info":
                Sites\Register_info::show();
                break;

            case "register":
                Sites\Register::show();
                break;

            case "activate":
                Sites\Activate::show();
                break;

            case "kontakt":
                Sites\Kontakt::show();
                break;

            case "download":

                break;

            case "upload":
                Sites\Upload::show();
                break;

            case "show_log":
                Sites\Show_log::show();
                break;

            case "impressum":
                Sites\Impressum::show();
                break;

            case "about":
                Sites\About::show();
                break;

            default:

        endswitch;
    }
}