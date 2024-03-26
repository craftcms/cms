<?php

use craft\helpers\Json;
use craft\helpers\Search;

if (!isset($argv[1])) {
    exit("No icons.json path provided.\n");
}

if (!file_exists($argv[1])) {
    exit("No file exists at $argv[1].\n");
}

require dirname(__DIR__, 2) . "/vendor/autoload.php";
$app = require(dirname(__DIR__, 2) . '/bootstrap/console.php');

$icons = Json::decodeFromFile($argv[1]);
$index = [];

$regularIconsPath = __DIR__ . '/regular';
$solidIconsPath = __DIR__ . '/solid';

foreach ($icons as $name => $icon) {
    if (file_exists("$regularIconsPath/$name.svg") || file_exists("$solidIconsPath/$name.svg")) {
        $terms = $icon['search']['terms'] ?? [];
        $index[$name] = [
            'name' => sprintf(" %s ", Search::normalizeKeywords($name)),
            'terms' => sprintf(" %s ", Search::normalizeKeywords($terms)),
        ];
    }
}

$file = __DIR__ . '/index.php';
$arr = var_export($index, true);
$contents = <<<PHP
<?php
return $arr;
PHP;

file_put_contents($file, $contents);
exit("done\n");
