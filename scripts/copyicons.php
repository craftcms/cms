<?php

use craft\helpers\Search;

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require(dirname(__DIR__) . '/bootstrap/console.php');

$lightIcons = [
    'earth-africa',
    'earth-americas',
    'earth-asia',
    'earth-europe',
    'earth-oceania',
    'envelope',
    'files',
    'folder-open',
    'globe',
    'image',
    'map-location',
    'newspaper',
    'pen-to-square',
    'plug',
    'signs-post',
    'sitemap',
    'sliders',
    'tags',
    'user-group',
];

$styles = [
    'globe' => 'regular',
    'grip-dots' => 'custom',
];

$kitDir = dirname(__DIR__) . '/node_modules/@awesome.me/kit-ddaed3f5c5';
$kitSvgsDir = "$kitDir/icons/svgs";
$iconsDir = dirname(__DIR__) . '/src/icons';
$metaPath = "$kitDir/icons/metadata/icons.json";
$meta = json_decode(file_get_contents($metaPath), true);
$index = [];
$aliasesPhp = <<<PHP
<?php

PHP;

$skipped = 0;
$wrote = 0;

foreach ($lightIcons as $name) {
    $iconPath = "$iconsDir/light/$name.svg";
    echo "Writing light/$name.svg ... ";
    file_put_contents($iconPath, $meta[$name]['svg']['light']['raw']);
    echo "done\n";
    $wrote++;
}

foreach ($meta as $name => $info) {
    if (isset($info['svg']['custom'])) {
        $style = 'custom';
    } elseif (isset($info['svg']['brands'])) {
        $style = 'brands';
    } else {
        $style = $styles[$name] ?? 'solid';
    }

    $dir = match ($style) {
        'custom' => 'custom-icons',
        default => $style,
    };

    $iconPath = "$iconsDir/$dir/$name.svg";
    echo "Writing $dir/$name.svg ... ";
    file_put_contents($iconPath, $info['svg'][$style]['raw']);
    echo "done\n";
    $wrote++;

    if ($style !== 'custom') {
        $terms = $meta[$name]['search']['terms'] ?? [];
        $index[$name] = [
            'name' => sprintf(" %s ", Search::normalizeKeywords($name)),
            'terms' => sprintf(" %s ", Search::normalizeKeywords($terms)),
            'pro' => empty($meta[$name]['free']),
            'styles' => $meta[$name]['styles'] ?? [],
        ];
    }

    if ($style !== 'solid') {
        $aliasesPhp .= <<<PHP
Craft::setAlias('@appicons/$name.svg', "@craft/icons/$dir/$name.svg");

PHP;
    }
}

echo "Finished writing $wrote icons ($skipped skipped).\n";

echo 'Copying LICENSE.txt ... ';
copy("$kitDir/LICENSE.txt", "$iconsDir/LICENSE.txt");
echo "done\n";

echo 'Writing index ... ';
ksort($index);
$indexPath = "$iconsDir/index.php";
$arr = var_export($index, true);
$indexContents = <<<PHP
<?php
return $arr;
PHP;
file_put_contents($indexPath, $indexContents);
echo "done\n";

echo 'Writing aliases ... ';
file_put_contents("$iconsDir/aliases.php", $aliasesPhp);
echo "done\n";
