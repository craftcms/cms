<?php

use Composer\Autoload\ClassLoader;

// Load Composer’s autoloader
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// See if Composer has an optimized autoloader
// h/t https://stackoverflow.com/a/46435124/1688568
$autoloadClass = null;
foreach (get_declared_classes() as $class) {
    if (str_starts_with($class, 'ComposerAutoloaderInit')) {
        $autoloadClass = $class;
        break;
    }
}
if ($autoloadClass === null) {
    echo "No optimized Composer autoloader found.\n";
    die(1);
}

$classes = array_filter(require(__DIR__ . '/composer-classes.php'), function($class) {
    return !str_starts_with($class, 'Composer\\');
});

/** @var ClassLoader $classLoader */
$classLoader = $autoloadClass::getLoader();
foreach ($classLoader->getClassMap() as $class => $file) {
    if (
        str_starts_with($class, 'Composer\\') &&
        !str_starts_with($class, 'Composer\\Command\\') &&
        !str_starts_with($class, 'Composer\\Console\\') &&
        !str_starts_with($class, 'Composer\\XdebugHandler\\')
    ) {
        $classes[] = $class;
    }
}

sort($classes);


$content = <<<PHP
<?php

return [

PHP;

foreach ($classes as $class) {
    $content .= <<<PHP
    $class::class,

PHP;
}

$content .= <<<PHP
];

PHP;

if (file_put_contents(__DIR__ . '/composer-classes.php', $content) === false) {
    echo "Unable to write to composer-classes.php\n";
    die(1);
}

echo "Updated composer-classes.php\n";
