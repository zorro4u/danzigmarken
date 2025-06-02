<?php
namespace Dzg\Cls;

class Starter
{
    public static function run($site='table')
    {
        #$dir = $_SERVER['DOCUMENT_ROOT']."/../data/dzg/cls/";
        $class_site = __DIR__."/".ucfirst($site).".php";
        require_once $class_site;

        switch (strtolower($site))
        {
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

                break;

            case "pwreset":

                break;

            case "register-info":

                break;

            case "register":

                break;

            case "activate":

                break;

            case "kontakt":
                Kontakt::show();
                break;

            case "download":

                break;

            case "upload":
                Upload::show();
                break;

            case "impressum":

                break;

            default:

        }
    }
}