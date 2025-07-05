composer.phar in ein Verzeichnis kopieren (/assets)
composer.json mit Editor erstellen
console:
cd assets
php composer.phar install (--ignore-platform-req=ext-gd)
(/assets/vendor wird erstellt, aus github-sync ausschließen)

php composer.phar update

php composer.phar remove phpauth/phpauth --ignore-platform-req=ext-gd


composer require phpauth/phpauth --ignore-platform-req=ext-gd

$config = new \PHPAuth\Config($dbh, null, 'sql', 'fr_FR');
$auth   = new \PHPAuth\Auth($dbh, $config);

# NEU: Sprachdatei z.B. 'de_DE' aktivieren
composer require phpauth/phpauth.l10n --ignore-platform-req=ext-gd

$config = new \PHPAuth\Config($dbh, null, \PHPAuth\Config::CONFIG_TYPE_SQL);
$config = $config->setLocalization( (new \PHPAuth\PHPAuthLocalization('de_DE'))->use() );
$auth   = new \PHPAuth\Auth($dbh, $config);