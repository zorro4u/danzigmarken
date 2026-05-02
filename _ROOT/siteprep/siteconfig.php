<?php
namespace Dzg\SitePrep;


class SiteConfig
{
    public const
        HEAD_TEMPLATE = "/assets/inc/html-head-meta.php";

    public const
        ACC_PAGES = ['login.php','logout.php','admin.php','settings.php'];

    public const
        MAIN_PAGES = [1 => 'index.php', 2 => 'index2.php'];


    private const
    META = [
        'cache_no' => "must-revalidate, no-store", #, no-cache, max-age=0, private",
        'cache_0'  => "no-cache, max-age=0, must-revalidate, private",
        'cache_1h' => "max-age=3600, stale-if-error=86400, private",    # 1h+1d
        'cache_1d' => "max-age=604800, stale-if-error=86400, private",  # 1+1Tage
        'cache_1w' => "max-age=604800, stale-if-error=86400, private",  # 7+1Tage

        'expires_0'  => "0",                  # no-cache
        'expires_1h' => "3600",               # 1 Std Cache
        'expires_1d' => "86400",              # 1 Tag
        'expires_1w' => "604800",             # 1 Woche

        'robots_index'  => "index, nofollow,",      # indiziert
        'robots_no'     => "noindex, nofollow,",
        'robots_follow' => "noindex, follow,",      # Seitenlinks folgen

        'google0' => "",
        'google1' => "nopagereadaloud",

        'canonical0' => "",
        'canonical1' => '<link rel="canonical" href="https://www.danzigmarken.de/index">',
        'canonical2' => '<link rel="canonical" href="https://www.danzigmarken.de/details.php?id=10">',
    ];


    public const
    PAGE = [

    'index.php'   => [
        'class_file' => 'Table.php',
        'site_id' => 1,
        'meta'    => [
            'title' => "Briefmarken und Paketkarten der Stadt Danzig (1889-1920-1939-1945)",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_index'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical1'],
            ],
        ],

    'index2.php'  => [
        'class_file' => 'Table.php',
        'site_id' => 2,
        'meta'    => [
            'title' => "Briefmarken und Paketkarten der Stadt Danzig (1889-1920-1939-1945)",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_index'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical1'],
            ],
        ],

    'details.php' => [
        'class_file' => 'Details.php',
        'site_id' => 3,
        'meta'    => [
            'title' => "Detailansicht - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google0'],
            'canonical' => self::META['canonical2'],
            ],
        ],

    'change.php'  => [
        'class_file' => 'Change.php',
        'site_id' => 4,
        'meta'    => [
            'title' => "Bearbeiten - danzigmarken.de",
            'cache' => self::META['cache_1h'],
            'expires' => self::META['expires_1h'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical2'],
            ],
        ],

    'upload.php'  => [
        'class_file' => 'Upload.php',
        'site_id' => 5,
        'meta'    => [
            'title' => "Upload - danzigmarken.de",
            'cache' => self::META['cache_1w'],
            'expires' => self::META['expires_1h'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'impressum.php' => [
        'class_file' => 'Impressum.php',
        'site_id'   => 6,
        'meta'      => [
            'title' => "Impressum - danzigmarken.de",
            'cache' => self::META['cache_1w'],
            'expires' => self::META['expires_1h'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'login.php'   => [
        'class_file' => 'Login.php',
        'site_id' => 7,
        'meta'    => [
            'title' => "Anmelden - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google0'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'pwforget.php' => [
        'class_file' => 'Pwforget.php',
        'site_id'  => 8,
        'meta'     => [
            'title' => "PW-Vergessen - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'pwreset.php' => [
        'class_file' => 'Pwreset.php',
        'site_id' => 9,
        'meta'    => [
            'title' => "PW-Reset - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google0'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'registerinfo.php' => [
        'class_file' => 'Registerinfo.php',
        'site_id'      => 10,
        'meta'         => [
            'title' => "Registrieren-Info - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'register.php' => [
        'class_file' => 'Register.php',
        'site_id'  => 11,
        'meta'     => [
            'title' => "Registrieren - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google0'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'activate.php' => [
        'class_file' => 'Activate.php',
        'site_id'  => 12,
        'meta'     => [
            'title' => "Aktivieren - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'contact.php' => [
        'class_file' => 'Contact.php',
        'site_id' => 13,
        'meta'    => [
            'title' => "Kontakt - danzigmarken.de",
            'cache' => self::META['cache_1w'],
            'expires' => self::META['expires_1h'],
            'robots'  => self::META['robots_follow'],
            'google'  => self::META['google0'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'logout.php'  => [
        'class_file' => 'Logout.php',
        'site_id' => 14,
        'meta'    => [
            'title' => "Abmelden - danzigmarken.de",
            'cache' => self::META['cache_1w'],
            'expires' => self::META['expires_1h'],
            'robots'  => self::META['robots_follow'],
            'google'  => self::META['google0'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'download.php' => [
        'class_file' => 'Download.php',
        'site_id'  => 15,
        'meta'     => [
            'title' => "Download - danzigmarken.de",
            'cache' => self::META['cache_1h'],
            'expires' => self::META['expires_1h'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'about.php'   => [
        'class_file' => 'About.php',
        'site_id' => 16,
        'meta'    => [
            'title' => "About - danzigmarken.de",
            'cache' => self::META['cache_1w'],
            'expires' => self::META['expires_1h'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],


    'settings.php' => [
        'class_file' => 'Settings.php',
        'site_id'  => 100,
        'meta'     => [
            'title' => "Konto - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'admin.php'   => [
        'class_file' => 'Admin.php',
        'site_id' => 101,
        'meta'    => [
            'title' => "erweiterte Einstellungen - danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'showlog.php' => [
        'class_file' => 'Showlog.php',
        'site_id'  => 102,
        'meta'     => [
            'title' => "danzigmarken.de",
            'cache' => self::META['cache_no'],
            'expires' => self::META['expires_0'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],

    'printview.php' => [
        'class_file' => 'Printview.php',
        'site_id'  => 103,
        'meta'     => [
            'title' => "danzigmarken.de",
            'cache' => self::META['cache_1w'],
            'expires' => self::META['expires_1d'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],


    'dummy' => [
        'class_file' => 'Empty.php',
        'site_id' => 404,
        'meta'    => [
            'title' => "danzigmarken.de",
            'cache' => self::META['cache_1h'],
            'expires' => self::META['expires_1h'],
            'robots'  => self::META['robots_no'],
            'google'  => self::META['google1'],
            'canonical' => self::META['canonical0'],
            ],
        ],
    ];
}

// EOF