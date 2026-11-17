<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Search;

require dirname(__DIR__).'/vendor/autoload.php';

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
    'language',
    'notification-bottom-left',
    'notification-bottom-right',
    'notification-top-left',
    'notification-top-right',
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

/**
 * Font Awesome 7 draws parts of some icons outside the 512-high viewBox (its icon canvas grew from 16 to 20px
 * tall but the viewBox didn't), so let them overflow it, as Font Awesome's own web implementations do.
 */
$withVisibleOverflow = fn (string $svg): string => preg_replace('/^<svg\b/', '<svg overflow="visible"', $svg, 1);
$index = [];
$skipped = 0;
$wrote = 0;

if (! is_dir($iconsDir.'/light')) {
    mkdir($iconsDir.'/light');
}

// Settings icons are drawn in the light style when one exists, so plugins can name any icon.
foreach ($meta as $name => $info) {
    $svg = $info['svg']['light']['raw'] ?? '';

    if ($svg === '') {
        continue;
    }

    $iconPath = "$iconsDir/light/$name.svg";
    echo "Writing light/$name.svg ... ";
    file_put_contents($iconPath, $withVisibleOverflow($svg));
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
    file_put_contents($iconPath, $withVisibleOverflow($svg));
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

}

// The metadata lists a custom icon under the official icon's entry when they share a name (e.g. grip-dots),
// and that entry only has the official styles, so take those custom icons from the kit's SVGs instead.
foreach (glob("$kitSvgsDir/custom-icons/*.svg") ?: [] as $kitPath) {
    $name = basename($kitPath, '.svg');

    if (isset($meta[$name]['svg']['custom'])) {
        continue;
    }

    echo "Writing custom-icons/$name.svg ... ";
    file_put_contents("$iconsDir/custom-icons/$name.svg", $withVisibleOverflow(file_get_contents($kitPath)));
    echo "done\n";
    $wrote++;
}

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
