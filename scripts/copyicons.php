<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Search;

require dirname(__DIR__).'/vendor/autoload.php';

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
];

/**
 * Repo-owned icons that must exist in custom-icons/ after every run.
 *
 * The Font Awesome kit is not the source of truth for these — several were
 * removed from the kit after they were committed here (which is why
 * cms-assets/resources/icons/custom-icons is the only icons directory tracked
 * by git). Regenerating the icons directory must never lose them; if the kit
 * stops providing one, the committed copy is authoritative.
 */
$repoCustomIcons = [
    'asterisk-slash',
    'c-debug',
    'c-outline',
    'clone-dashed',
    'craft-cms',
    'craft-partners',
    'craft-stack-exchange',
    'default-plugin',
    'diamond-slash',
    'duplicate',
    'element-card',
    'element-card-slash',
    'element-cards',
    'gear-slash',
    'graphql',
    'grip-dots',
    'image-slash',
    'language',
    'list-flip',
    'list-tree-flip',
    'notification-bottom-left',
    'notification-bottom-right',
    'notification-top-left',
    'notification-top-right',
    'share-flip',
    'slideout-left',
    'slideout-right',
    'thumb-left',
    'thumb-right',
];

$kitDir = dirname(__DIR__).'/node_modules/@awesome.me/kit-ddaed3f5c5';
$kitSvgsDir = "$kitDir/icons/svgs";
$iconsDir = dirname(__DIR__).'/cms-assets/resources/icons';

if (! is_dir($iconsDir)) {
    mkdir($iconsDir, recursive: true);
}

$metaPath = "$kitDir/icons/metadata/icons.json";

if (! is_file($metaPath) || ! is_dir($kitSvgsDir)) {
    fwrite(STDERR, "Font Awesome kit is missing or incomplete at $kitDir.\n".
        "Expected metadata at $metaPath and SVGs at $kitSvgsDir.\n".
        "Try re-running \`vp install\` — this can happen if the private kit registry (npm.fontawesome.com) returned an incomplete package.\n");
    exit(1);
}

$meta = json_decode(file_get_contents($metaPath), true);
$index = [];
$aliasesPhp = <<<PHP
<?php

use Yiisoft\Aliases\Aliases;

\$aliases = app(Aliases::class);

/**
 * We use reflection here as calling ->set every
 * time incurs a high performance cost.
 */
\$reflectionProperty = new ReflectionProperty(\$aliases, 'aliases');
\$reflectionProperty->setValue(\$aliases, array_merge_recursive(\$reflectionProperty->getValue(\$aliases), [
    '@appicons' => [

PHP;

$skipped = 0;
$wrote = 0;

if (! is_dir($iconsDir.'/light')) {
    mkdir($iconsDir.'/light');
}

foreach ($lightIcons as $name) {
    $svg = $meta[$name]['svg']['light']['raw'] ?? '';

    if ($svg === '') {
        echo "Skipping light/$name.svg (kit metadata has no light SVG content)\n";
        $skipped++;

        continue;
    }

    $iconPath = "$iconsDir/light/$name.svg";
    echo "Writing light/$name.svg ... ";
    file_put_contents($iconPath, $svg);
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

    if (! is_dir("$iconsDir/$dir")) {
        mkdir("$iconsDir/$dir");
    }

    $iconPath = "$iconsDir/$dir/$name.svg";
    $svg = $info['svg'][$style]['raw'] ?? '';

    if ($svg === '') {
        echo "Skipping $dir/$name.svg (kit metadata has no $style SVG content)\n";
        $skipped++;

        continue;
    }

    echo "Writing $dir/$name.svg ... ";
    file_put_contents($iconPath, $svg);
    echo "done\n";
    $wrote++;

    if ($style !== 'custom') {
        $terms = $meta[$name]['search']['terms'] ?? [];
        $index[$name] = [
            'name' => sprintf(' %s ', Search::normalizeKeywords((string) $name, language: 'en-US')),
            'terms' => sprintf(' %s ', Search::normalizeKeywords($terms, language: 'en-US')),
            'pro' => empty($meta[$name]['free']),
            'styles' => $meta[$name]['styles'] ?? [],
        ];
    }

    if ($style !== 'solid') {
        $aliasesPhp .= <<<PHP
        '@appicons/$name.svg' => "@icons/$dir/$name.svg",

PHP;
    }
}

$aliasesPhp .= <<<'PHP'
    ]
]));
PHP;

echo "Finished writing $wrote icons ($skipped skipped).\n";

$missing = array_filter(
    $repoCustomIcons,
    fn (string $name): bool => ! is_file("$iconsDir/custom-icons/$name.svg")
        || filesize("$iconsDir/custom-icons/$name.svg") === 0,
);

if ($missing !== []) {
    fwrite(STDERR, "\nError: the following repo-owned custom icons are missing or empty:\n");

    foreach ($missing as $name) {
        fwrite(STDERR, "  - custom-icons/$name.svg\n");
    }

    fwrite(STDERR, "\nThe Font Awesome kit no longer provides these; git is their source of truth.\n");
    fwrite(STDERR, "Restore them with:\n\n");
    fwrite(STDERR, "  git checkout -- cms-assets/resources/icons/custom-icons\n\n");
    exit(1);
}

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
