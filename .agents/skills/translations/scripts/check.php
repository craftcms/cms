<?php
// Checks a branch's translation files against its English source file: keys
// that aren't in the source, messages that aren't valid ICU, and placeholders
// that the translation dropped.
//
// Usage: php check.php <translations-dir> [--missing]
// Exits with 1 when it finds a problem. --missing also lists untranslated keys.

$dir = rtrim($argv[1] ?? '', '/');
$listMissing = in_array('--missing', $argv, true);
if (!is_file("$dir/en/app.php")) {
    fwrite(STDERR, "Usage: php check.php <translations-dir> [--missing]\n");
    exit(2);
}

/**
 * Returns the argument names in an ICU message. By default, only top-level
 * arguments other than plural and select arguments count, since a translation
 * may drop those or the placeholders in their branches when its language
 * doesn't need them. With $all, every argument counts, including ones inside
 * branches, where a translation may move a placeholder.
 */
function icuArgs(string $s, bool $all = false, int &$i = 0, bool $nested = false): array
{
    $args = [];
    $len = strlen($s);
    while ($i < $len) {
        $c = $s[$i++];
        if ($c === '}' && $nested) {
            return $args;
        }
        if ($c !== '{') {
            continue;
        }
        preg_match('/\G\s*(\w+)\s*(?:,\s*(\w+)\s*)?/', $s, $m, 0, $i);
        $i += strlen($m[0] ?? '');
        $type = $m[2] ?? null;
        if ($all || (!$nested && !in_array($type, ['plural', 'select', 'selectordinal'], true))) {
            $args[] = $m[1] ?? '';
        }
        if (in_array($type, ['plural', 'select', 'selectordinal'], true)) {
            $i++; // the comma before the branches
            while ($i < $len && preg_match('/\G\s*(?:offset:\d+\s*)?([=\w]+)\s*\{/', $s, $b, 0, $i)) {
                $i += strlen($b[0]);
                $args = array_merge($args, icuArgs($s, $all, $i, true));
            }
        }
        // Skip the rest of the argument, such as its number or date format.
        for ($depth = 1; $i < $len && $depth > 0; $i++) {
            $depth += ['{' => 1, '}' => -1][$s[$i]] ?? 0;
        }
    }
    return $args;
}

$en = require "$dir/en/app.php";
$problems = 0;
foreach (glob("$dir/*/app.php") as $file) {
    $lang = basename(dirname($file));
    if ($lang === 'en') {
        continue;
    }
    $translations = require $file;
    $missing = array_diff_key($en, $translations);
    printf("%-6s %d of %d translated\n", $lang, count($en) - count($missing), count($en));
    if ($listMissing) {
        foreach (array_keys($missing) as $key) {
            echo "  missing: $key\n";
        }
    }
    foreach ($translations as $key => $value) {
        $issue = null;
        if (!isset($en[$key])) {
            $issue = 'not in en/app.php';
        } elseif (msgfmt_create('en', $en[$key]) === null) {
            continue; // The source isn't an ICU message, such as an email body.
        } elseif (msgfmt_create($lang, $value) === null) {
            $issue = 'invalid ICU message';
        } elseif ($lost = array_diff(icuArgs($key), icuArgs($value, true))) {
            $issue = 'missing placeholder {' . implode('}, {', array_unique($lost)) . '}';
        }
        if ($issue) {
            $problems++;
            echo "  $issue: $key\n";
        }
    }
}
exit($problems ? 1 : 0);
