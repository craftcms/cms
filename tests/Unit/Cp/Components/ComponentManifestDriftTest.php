<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\ComponentRegistry;
use CraftCms\Cms\Cp\Components\ViewComponent;
use Symfony\Component\Process\Process;

/*
|--------------------------------------------------------------------------
| CP component / web-component API drift detector
|--------------------------------------------------------------------------
|
| Keeps the PHP CP component builders (src/Cp/Components/*, mapped in
| ComponentRegistry) in sync with the web components' public attribute API, as
| extracted into the Custom Elements Manifest. Two independent guards:
|
| 1. CONVENTION (manifest-only): every web-component `@property` must declare an
|    explicit kebab-case `attribute:`. A multi-word `@property()` with no
|    explicit attribute silently observes a *lowercased* attribute
|    (`accessibleName` -> `accessiblename`), so a kebab server attribute
|    (`accessible-name`) is ignored — a real, invisible bug. This guard flags
|    that class for ALL manifest elements, needs no PHP counterpart, and is what
|    makes the manifest authoritative (attribute names == the real DOM names).
|
| 2. COVERAGE (PHP <-> manifest): for each registered builder, assert every WC
|    attribute is emitted by the PHP builder, matched by EXACT name (safe now
|    that guard #1 keeps the manifest authoritative). A tripwire tracks the known
|    unresolved gaps so closing one forces the list to be updated.
|
| SCOPING LIMITATION: coverage only covers builders registered in
| ComponentRegistry. Elements rendered by other means (e.g. `craft-action-menu`
| / `craft-chip` via MenuHtml / ElementHtml, which are not ViewComponents) are
| NOT coverage-checked — but the convention guard (#1) still covers them, since
| it walks the whole manifest.
|
| The manifest (packages/craftcms-ui/dist/custom-elements.json) is auto-generated
| by beforeAll() below. In CI an existing file is trusted, because laravel-ci.yml
| restores it from a content-keyed actions/cache (never stale by construction);
| everywhere else it is regenerated on every run so local edits to the web
| components can't be drift-checked against a stale manifest. Manual regen:
| cd packages/craftcms-ui && npm run build:manifest
| The checked-in ROOT packages/craftcms-ui/custom-elements.json is stale; do not use it.
*/

/**
 * Path to the generated Custom Elements Manifest. Resolved relative to this
 * file (repo root is five levels up) rather than base_path(), because tests run
 * against the Testbench skeleton, not the repo root.
 */
function cpDriftManifestPath(): string
{
    return dirname(__FILE__, 5).'/packages/craftcms-ui/dist/custom-elements.json';
}

/** Decodes the manifest, failing loudly (with the regen command) if it is missing. */
function cpDriftManifest(): array
{
    $path = cpDriftManifestPath();

    if (! is_file($path)) {
        throw new RuntimeException(sprintf(
            "Custom Elements Manifest not found at %s.\n".
            'Generate it with:  cd packages/craftcms-ui && npm run build:manifest',
            $path,
        ));
    }

    return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

/** camelCase -> kebab-case (`accessibleName` -> `accessible-name`). */
function cpDriftKebab(string $name): string
{
    return strtolower((string) preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name));
}

/**
 * tagName => [attribute names], straight from the manifest. Post-convention, an
 * attribute name is the real observed DOM attribute (explicit kebab or a
 * genuinely lowercase single word), so these compare exactly against the PHP
 * builders' emitted attribute names.
 *
 * @return array<string, list<string>>
 */
function cpDriftManifestAttributes(): array
{
    $byTag = [];

    foreach (cpDriftManifest()['modules'] ?? [] as $module) {
        foreach ($module['declarations'] ?? [] as $declaration) {
            $tag = $declaration['tagName'] ?? null;

            if ($tag === null) {
                continue;
            }

            $byTag[$tag] = array_values(array_map(
                static fn (array $attr): string => $attr['name'],
                $declaration['attributes'] ?? [],
            ));
        }
    }

    return $byTag;
}

/**
 * The convention violations: manifest attributes whose name is camelCase AND
 * equals the property (`fieldName`) — meaning the `@property` set no explicit
 * `attribute:`, so Lit will lowercase the observed attribute. Walks EVERY tagged
 * element in the manifest, not just the registered builders.
 *
 * @return list<array{tag: string, attr: string, kebab: string}>
 */
function cpDriftCamelCaseImplicitAttributes(): array
{
    $risky = [];

    foreach (cpDriftManifest()['modules'] ?? [] as $module) {
        foreach ($module['declarations'] ?? [] as $declaration) {
            $tag = $declaration['tagName'] ?? null;

            if ($tag === null) {
                continue;
            }

            foreach ($declaration['attributes'] ?? [] as $attr) {
                $name = $attr['name'] ?? '';

                if ($name === ($attr['fieldName'] ?? null) && preg_match('/[A-Z]/', $name)) {
                    $risky[] = ['tag' => $tag, 'attr' => $name, 'kebab' => cpDriftKebab($name)];
                }
            }
        }
    }

    return $risky;
}

/**
 * The registry's name => class map. Read via reflection so the test is driven
 * by the actual registered set (and any additions to it), not a hardcoded list.
 *
 * @return array<string, class-string<ViewComponent>>
 */
function cpDriftRegistryMap(): array
{
    $registry = app(ComponentRegistry::class);
    $property = new ReflectionProperty(ComponentRegistry::class, 'components');

    return $property->getValue($registry);
}

/** Reflects a protected method's return value off a fresh builder instance. */
function cpDriftInvokeProtected(ViewComponent $component, string $method): mixed
{
    $reflection = new ReflectionMethod($component, $method);

    return $reflection->invoke($component);
}

/*
|--------------------------------------------------------------------------
| Allowlists — the spec of intentional differences
|--------------------------------------------------------------------------
*/

/**
 * WC attributes intentionally NOT emitted by the PHP builder because they are
 * internal / reflected-state rather than a public server-set input.
 *
 * Keyed by tagName; values are manifest attribute names (exact).
 *
 * Currently EMPTY: none of the covered elements expose a WC attribute that is
 * purely internal reflected state and absent from PHP. The one set of WC-only
 * attributes that exists (craft-button `rel`/`download`) is a genuine gap, not
 * internal state, so it is tracked separately in cpDriftKnownGaps().
 *
 * @return array<string, list<string>>
 */
function cpDriftWcOnlyAllowlist(): array
{
    return [
        // <craft-tabs> writes `collapsed` itself in updated(), derived from
        // selectedIndex, so a surrounding layout can style on it. Readable
        // state, not a knob — Tabs::collapsible() is what a caller sets.
        'craft-tabs' => ['collapsed'],
    ];
}

/**
 * PHP hostAttributes keys that are expected to have NO web-component attribute
 * counterpart — genuine server-side / structural concerns. Used only to label
 * the informational "PHP-only" section of the report; it does not gate any
 * assertion.
 *
 * Keyed by tagName; values are PHP hostAttributes keys.
 *
 * @return array<string, list<string>>
 */
function cpDriftExpectedPhpOnly(): array
{
    return [
        // Global / structural HTML attributes the WC does not declare in the manifest.
        '*' => ['id', 'class', 'style', 'role', 'aria', 'data', 'slot'],

        // craft-button: `type`/`disabled` are native/global; `active` and `command`
        // are PHP conveniences (pressed state, Invoker Commands API).
        'craft-button' => ['type', 'active', 'disabled', 'command'],

        // craft-field: `label` is a WC slot; `readonly`/`disabled` are native.
        'craft-field' => ['label', 'readonly', 'disabled'],

        // craft-switch: native input state / a slot, not declared manifest attributes.
        'craft-switch' => ['checked', 'disabled', 'label'],

        // craft-input / craft-textarea: Lion-inherited control properties
        // (not in the manifest, which only lists own declarations). They must
        // live on the host because Lion pushes them onto the slotted control
        // on upgrade (LionInput/LionTextarea `updated()`), clobbering
        // server-rendered attributes with its defaults otherwise.
        'craft-input' => ['type', 'placeholder', 'name', 'disabled', 'readonly'],
        // craft-input-copy extends craft-input, inheriting the same
        // Lion-pushed control properties.
        'craft-input-copy' => ['type', 'placeholder', 'name', 'disabled', 'readonly'],
        'craft-input-money' => ['type', 'placeholder', 'name', 'disabled', 'readonly'],
        'craft-select' => ['label', 'label-sr-only', 'name', 'disabled', 'required'],
        // craft-input-password / craft-input-color extend LionInput directly (no
        // craft-input host props), so they only carry the Lion-pushed control
        // props; `type` is owned by the component (password reveal / color
        // picker), not server-set.
        'craft-input-password' => ['placeholder', 'name', 'disabled', 'readonly'],
        'craft-input-color' => ['placeholder', 'name', 'disabled', 'readonly'],
        'craft-textarea' => ['placeholder', 'name', 'disabled', 'readonly', 'rows'],

        // craft-icon: `data-color` is the global palette-scoping attribute
        // (colorable.css), not a declared component property.
        'craft-icon' => ['data-color'],

        // craft-tabs: `selected-index` is declared by LionTabs, so it is a real
        // public input but absent from the manifest, which only lists own
        // declarations.
        'craft-tabs' => ['selected-index'],
    ];
}

/**
 * Genuine, currently-UNRESOLVED coverage drift: WC attributes that are real
 * public inputs the PHP builder does not yet emit. Deliberately NOT waved
 * through the allowlist — surfacing them is the point. A tripwire test guards
 * that they still exist, so closing a gap forces this list to be updated.
 *
 * Keyed by tagName; values are manifest attribute names (exact).
 *
 * @return array<string, list<string>>
 */
function cpDriftKnownGaps(): array
{
    return [
        // <craft-button> accepts `rel` and `download` (link-mode companions to
        // href/target), but Button::hostAttributes() emits neither. Either add
        // them to Button, or (if intentionally unsupported server-side) move them
        // to cpDriftWcOnlyAllowlist() with a rationale.
        'craft-button' => ['rel', 'download'],
    ];
}

/**
 * Builds the full coverage analysis, one row per registry entry. All comparisons
 * are EXACT — the convention guard keeps manifest attribute names authoritative.
 *
 * @return array<string, array{
 *     name: string, class: class-string<ViewComponent>, tag: string,
 *     tagInManifest: bool, error: ?string,
 *     wcAttrs: list<string>, phpKeys: list<string>,
 *     uncovered: list<string>, knownGaps: list<string>, phpOnly: list<string>,
 * }>
 */
function cpDriftAnalyze(): array
{
    $manifest = cpDriftManifestAttributes();
    $wcAllow = cpDriftWcOnlyAllowlist();
    $knownGaps = cpDriftKnownGaps();

    $rows = [];

    foreach (cpDriftRegistryMap() as $name => $class) {
        $row = [
            'name' => $name,
            'class' => $class,
            'tag' => '',
            'tagInManifest' => false,
            'error' => null,
            'wcAttrs' => [],
            'phpKeys' => [],
            'uncovered' => [],
            'knownGaps' => [],
            'phpOnly' => [],
        ];

        try {
            $component = $class::make();
            $tag = (string) cpDriftInvokeProtected($component, 'tagName');
            $phpKeys = array_keys((array) cpDriftInvokeProtected($component, 'hostAttributes'));

            $row['tag'] = $tag;
            $row['phpKeys'] = $phpKeys;
            $row['tagInManifest'] = array_key_exists($tag, $manifest);
            $row['wcAttrs'] = $manifest[$tag] ?? [];

            $allow = $wcAllow[$tag] ?? [];
            $gaps = $knownGaps[$tag] ?? [];

            // WC attrs the PHP builder does not cover (exact), minus the
            // intentional internal-state allowlist. Split into known (tracked)
            // gaps and any NEW, undocumented drift.
            foreach ($row['wcAttrs'] as $attr) {
                if (in_array($attr, $phpKeys, true)) {
                    continue;
                }
                if (in_array($attr, $allow, true)) {
                    continue;
                }
                if (in_array($attr, $gaps, true)) {
                    $row['knownGaps'][] = $attr;
                } else {
                    $row['uncovered'][] = $attr;
                }
            }

            // PHP keys with no WC attribute counterpart (informational).
            foreach ($phpKeys as $key) {
                if (! in_array($key, $row['wcAttrs'], true)) {
                    $row['phpOnly'][] = $key;
                }
            }
        } catch (Throwable $e) {
            $row['error'] = $e::class.': '.$e->getMessage();
        }

        $rows[$name] = $row;
    }

    return $rows;
}

beforeAll(function () {
    // In CI an existing manifest was restored by a content-keyed actions/cache
    // step (see laravel-ci.yml), so it can't be stale — trust it. Locally,
    // regenerate unconditionally: a stale manifest silently drift-checks
    // against an outdated attribute API.
    if (getenv('CI') !== false && is_file(cpDriftManifestPath())) {
        return;
    }

    // The pinned npx invocation matches the package's @custom-elements-manifest/analyzer
    // devDependency and needs neither node_modules nor an npm install, so it
    // also works in fresh worktrees (and as a CI cache-miss fallback).
    $process = Process::fromShellCommandline(
        'npx --yes @custom-elements-manifest/analyzer@0.11.0 analyze',
        dirname(__FILE__, 5).'/packages/craftcms-ui',
        timeout: 300,
    );
    $process->run();

    expect($process->isSuccessful() && is_file(cpDriftManifestPath()))->toBeTrue(
        "Failed to generate the Custom Elements Manifest:\n"
        .trim($process->getErrorOutput()."\n".$process->getOutput())
        ."\nGenerate it manually with:  cd packages/craftcms-ui && npm run build:manifest",
    );
});

it('has a manifest with the expected tagged-element count', function () {
    // Sanity check we loaded the real (dist) manifest, not the stale root stub.
    expect(count(cpDriftManifestAttributes()))->toBeGreaterThan(40);
});

describe('convention: every @property declares an explicit attribute', function () {
    it('has no camelCase property silently observed as a lowercased attribute', function () {
        $risky = cpDriftCamelCaseImplicitAttributes();

        $detail = implode("\n  ", array_map(
            static fn (array $r): string => "<{$r['tag']}> {$r['attr']}  →  add  attribute: '{$r['kebab']}'",
            $risky,
        ));

        expect($risky)->toBe([], sprintf(
            '%d web-component propert%s a camelCase name and no explicit `attribute:` option, so Lit '.
            'observes a *lowercased* attribute (e.g. accessibleName → accessiblename) — a kebab-case '.
            'server-emitted attribute (accessible-name) is then silently ignored. Declare an explicit '.
            "kebab attribute on each:\n  %s",
            count($risky),
            count($risky) === 1 ? 'y has' : 'ies have',
            $detail,
        ));
    });
});

describe('coverage: every WC attribute is emitted by the PHP builder', function () {
    it('covers all public WC attributes for each registered component', function (string $name) {
        $row = cpDriftAnalyze()[$name];

        expect($row['error'])->toBeNull("Builder for \"$name\" threw while reflecting: {$row['error']}");

        if (! $row['tagInManifest']) {
            // The builder's tag is not a manifest custom element (e.g.
            // checkbox-select => <fieldset>). Nothing to compare against; this is
            // reported as structural friction, not a coverage failure.
            expect($row['wcAttrs'])->toBe([]);

            return;
        }

        expect($row['uncovered'])->toBe(
            [],
            sprintf(
                'UNDOCUMENTED DRIFT: <%s> declares attribute(s) [%s] that %s does not emit and that '.
                'are not in the internal-state allowlist or the known-gaps list. Add them to the '.
                'builder, or (if intentional) to cpDriftWcOnlyAllowlist()/cpDriftKnownGaps().',
                $row['tag'],
                implode(', ', $row['uncovered']),
                $row['class'],
            ),
        );
    })->with(array_keys(cpDriftRegistryMap()));
});

describe('known unresolved gaps (tripwire)', function () {
    it('confirms documented WC-only gaps still exist (fails when a gap is closed)', function () {
        $rows = cpDriftAnalyze();

        foreach (cpDriftKnownGaps() as $tag => $attrs) {
            $row = collect($rows)->firstWhere('tag', $tag);

            expect($row)->not->toBeNull("No registered builder renders <$tag>; stale known-gap entry?");

            foreach ($attrs as $attr) {
                // Still declared by the WC…
                expect(in_array($attr, $row['wcAttrs'], true))->toBeTrue(
                    "Known-gap attribute \"$attr\" is no longer declared by <$tag>; remove it from cpDriftKnownGaps().",
                );

                // …and still absent from the PHP builder. If this fails, the gap
                // was fixed — great! Remove the entry from cpDriftKnownGaps().
                expect(in_array($attr, $row['phpKeys'], true))->toBeFalse(
                    "Known-gap attribute \"$attr\" is now emitted by the <$tag> builder — the drift is resolved. ".
                    'Remove it from cpDriftKnownGaps() so this tripwire stays meaningful.',
                );
            }
        }
    });
});

it('prints a full drift report', function () {
    $rows = cpDriftAnalyze();

    $expectedPhpOnly = cpDriftExpectedPhpOnly();
    $globalPhpOnly = $expectedPhpOnly['*'] ?? [];

    $lines = [];
    $lines[] = '';
    $lines[] = '=========================================================================';
    $lines[] = ' CP component <-> web-component attribute drift report';
    $lines[] = ' manifest: packages/craftcms-ui/dist/custom-elements.json';
    $lines[] = '=========================================================================';

    $risky = cpDriftCamelCaseImplicitAttributes();
    $lines[] = ' convention (implicit-attribute properties): '
        .($risky ? implode(', ', array_map(fn ($r) => "<{$r['tag']}> {$r['attr']}", $risky)) : '(none — clean)');
    $lines[] = '=========================================================================';

    foreach ($rows as $name => $row) {
        $lines[] = '';
        $lines[] = sprintf('• %s  =>  %s  (<%s>)', $name, class_basename($row['class']), $row['tag']);

        if ($row['error'] !== null) {
            $lines[] = '    ERROR: '.$row['error'];

            continue;
        }

        if (! $row['tagInManifest']) {
            $lines[] = '    FRICTION: tag is not a manifest custom element — no WC API to compare against.';

            continue;
        }

        $lines[] = '    WC attrs   : '.($row['wcAttrs'] ? implode(', ', $row['wcAttrs']) : '(none)');
        $lines[] = '    PHP keys   : '.($row['phpKeys'] ? implode(', ', $row['phpKeys']) : '(none)');

        $lines[] = '    WC missing from PHP (undocumented): '
            .($row['uncovered'] ? implode(', ', $row['uncovered']) : '(none)');

        $lines[] = '    WC missing from PHP (KNOWN GAP)   : '
            .($row['knownGaps'] ? implode(', ', $row['knownGaps']) : '(none)');

        // Split PHP-only into expected (structural/native) vs. worth-a-look.
        $expected = array_merge($globalPhpOnly, $expectedPhpOnly[$row['tag']] ?? []);
        $unexpectedPhpOnly = array_values(array_diff($row['phpOnly'], $expected));
        $lines[] = '    PHP-only (expected): '
            .($row['phpOnly'] ? implode(', ', array_intersect($row['phpOnly'], $expected)) : '(none)');
        $lines[] = '    PHP-only (review)  : '
            .($unexpectedPhpOnly ? implode(', ', $unexpectedPhpOnly) : '(none)');
    }

    $lines[] = '';
    $lines[] = '=========================================================================';

    fwrite(STDOUT, implode("\n", $lines)."\n");

    // Always passes — this test exists to emit the report.
    expect(true)->toBeTrue();
});
