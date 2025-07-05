<?php
namespace Dzg;

class Starter
{
    public static function run($site='table')
    {
        $class_site = __DIR__."/".ucfirst($site).".php";
        require_once $class_site;

        switch (strtolower($site)):

            case "admin":
                Admin::show();
                break;

            case "settings":
                Settings::show();
                break;

            case "table":
                Table::show();
                break;

            case "details":
                Details::show();
                break;

            case "change":
                Change::show();
                break;

            case "login":
                Login::show();
                break;

            case "logout":
                Logout::show();
                break;

            case "pwforget":
                Pw_forget::show();
                break;

            case "pwreset":
                Pw_reset::show();
                break;

            case "register_info":
                Register_info::show();
                break;

            case "register":
                Register::show();
                break;

            case "activate":
                Activate::show();
                break;

            case "kontakt":
                Kontakt::show();
                break;

            case "download":

                break;

            case "upload":
                Upload::show();
                break;

            case "logger":
                Logger::show();
                break;

            case "impressum":
                Impressum::show();
                break;

            case "about":
                About::show();
                break;

            default:

        endswitch;
    }
}