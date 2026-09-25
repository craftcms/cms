<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\IconController;
use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    File::ensureDirectoryExists(CmsAssets::resourcesPath('icons/solid'));
    File::ensureDirectoryExists(CmsAssets::resourcesPath('icons/custom-icons'));

    $indexPath = CmsAssets::resourcesPath('icons/index.php');

    if (! File::exists($indexPath)) {
        File::put($indexPath, <<<'php_WRAP'
            <?php
            return [
                'gear' => [
                    'name' => ' gear ',
                    'terms' => ' cog cogwheel configuration gear mechanical modify settings sprocket tool wheel ',
                    'pro' => false,
                    'styles' => ['solid', 'regular', 'light', 'thin', 'duotone'],
               ],
               '00' => [
                    'name' => ' 00 ',
                    'terms' => '',
                    'pro' => true,
                    'styles' => ['solid', 'regular', 'light', 'thin', 'duotone'],
               ],
            ];
        php_WRAP);
    }

    // Free
    if (! File::exists(CmsAssets::resourcesPath('icons/solid/gear.svg'))) {
        File::put(CmsAssets::resourcesPath('icons/solid/gear.svg'), '<svg></svg>');
    }

    // Pro
    if (! File::exists(CmsAssets::resourcesPath('icons/solid/00.svg'))) {
        File::put(CmsAssets::resourcesPath('icons/solid/00.svg'), '<svg></svg>');
    }

    // Custom
    if (! File::exists(CmsAssets::resourcesPath('icons/custom-icons/element-card.svg'))) {
        File::put(CmsAssets::resourcesPath('icons/custom-icons/element-card.svg'), '<svg></svg>');
    }
});

describe('iconSvg', function () {
    test('svg returns JSON response with icon SVG markup', function () {
        get(action([IconController::class, 'svg'], ['icon' => 'gear']))
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['iconSvg']);
    });

    test('svg requires icon parameter', function () {
        postJson(action([IconController::class, 'svg']))
            ->assertJsonValidationErrors(['icon']);
    });

    test('svg validates icon is string', function () {
        postJson(action([IconController::class, 'svg']), [
            'icon' => 123,
        ])->assertJsonValidationErrors(['icon']);
    });

    test('svg processes legacy icon names', function () {
        $json = get(action([IconController::class, 'svg'], ['icon' => 'settings']))
            ->assertOk()
            ->json();

        expect($json['iconSvg'])
            ->toBeString()
            ->toContain('<svg');
    });

    test('svg handles custom icons', function () {
        $json = get(action([IconController::class, 'svg'], ['icon' => 'element-card']))
            ->assertOk()
            ->json();

        expect($json['iconSvg'])
            ->toBeString()
            ->toContain('<svg');
    });

    test('svg returns empty string for invalid icons', function () {
        $json = get(action([IconController::class, 'svg'], ['icon' => 'non-existent-icon-xyz']))
            ->assertOk()
            ->json();

        expect($json['iconSvg'])->toBeString();
    });

    test('svg rejects icon names that could be used for path traversal', function (string $icon) {
        get(action([IconController::class, 'svg'], ['icon' => $icon]))->assertBadRequest();
    })->with([
        '../../../../etc/passwd',
        '@webroot/index.php',
        'foo/bar',
        'foo.svg',
    ]);
});

describe('pickerOptions', function () {
    test('pickerOptions returns JSON response with correct structure', function () {
        get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['icons' => [['name', 'svg']]]);
    });

    test('pickerOptions lists each icon by name with its SVG', function () {
        $icons = collect(get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json('icons'));

        $gear = $icons->firstWhere('name', 'gear');

        expect($gear)->not->toBeNull()
            ->and($gear['svg'])->toContain('<svg');
    });

    test('pickerOptions validates search parameter accepts string', function () {
        get(action([IconController::class, 'pickerOptions'], ['search' => 'gear']))
            ->assertOk();
    });

    test('pickerOptions validates search parameter rejects non-string', function () {
        postJson(action([IconController::class, 'pickerOptions']), [
            'search' => 123,
        ])->assertJsonValidationErrors(['search']);
    });

    test('pickerOptions validates freeOnly parameter accepts boolean', function () {
        get(action([IconController::class, 'pickerOptions'], ['freeOnly' => true]))
            ->assertOk();

        get(action([IconController::class, 'pickerOptions'], ['freeOnly' => false]))
            ->assertOk();
    });

    test('pickerOptions validates freeOnly parameter rejects non-boolean', function () {
        postJson(action([IconController::class, 'pickerOptions']), [
            'freeOnly' => 'yes',
        ])->assertJsonValidationErrors(['freeOnly']);
    });

    test('pickerOptions excludes pro icons when freeOnly is true', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['freeOnly' => true]))
            ->assertOk()
            ->json();

        $names = collect($json['icons'])->pluck('name')->all();

        // Should contain free icons (checking for a known free icon: '0')
        expect($names)->toContain('gear');

        // Should not contain pro icons (checking for a known pro icon: '00')
        expect($names)->not->toContain('00');
    });

    test('pickerOptions includes pro icons when freeOnly is false', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['freeOnly' => false]))
            ->assertOk()
            ->json();

        $names = collect($json['icons'])->pluck('name')->all();

        // Should contain both free and pro icons
        expect($names)
            ->toContain('gear')  // free icon
            ->toContain('00'); // pro icon
    });

    test('pickerOptions defaults freeOnly to true', function () {
        $json = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        $names = collect($json['icons'])->pluck('name')->all();

        // Should not contain pro icons by default
        expect($names)->not->toContain('00');
    });

    test('pickerOptions filters icons by search term', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'cog']))
            ->assertOk()
            ->json();

        $names = collect($json['icons'])->pluck('name')->all();

        // Should contain icons matching 'cog' in name or terms
        expect($names)
            ->toContain('gear');
    });

    test('pickerOptions handles multi-word search', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'cog modify']))
            ->assertOk()
            ->json();

        $names = collect($json['icons'])->pluck('name')->all();

        // Should find icons that match both terms
        expect($names)->toContain('gear');
    });

    test('pickerOptions returns empty list for non-matching search', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'xyznonexistent']))
            ->assertOk()
            ->json();

        expect($json['icons'])->toBe([]);
    });

    test('pickerOptions treats empty search string as no search', function () {
        $jsonWithEmpty = get(action([IconController::class, 'pickerOptions'], ['search' => '']))
            ->assertOk()
            ->json();

        $jsonWithoutSearch = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        // Both should return the same result (all free icons)
        expect($jsonWithEmpty['icons'])->toBe($jsonWithoutSearch['icons']);
    });

    test('pickerOptions caches results for non-search requests', function () {
        Cache::forget('icon-picker-options-icons:free');
        Cache::forget('icon-picker-options-icons');

        // First request should generate HTML
        $firstResponse = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        // Second request should use cached HTML
        $secondResponse = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        expect($firstResponse['icons'])->toBe($secondResponse['icons']);
    });

    test('pickerOptions uses separate cache keys for freeOnly true and false', function () {
        Cache::forget('icon-picker-options-icons:free');
        Cache::forget('icon-picker-options-icons');

        $freeOnlyResponse = get(action([IconController::class, 'pickerOptions'], ['freeOnly' => true]))
            ->assertOk()
            ->json();

        $allIconsResponse = get(action([IconController::class, 'pickerOptions'], ['freeOnly' => false]))
            ->assertOk()
            ->json();

        // Results should be different (all icons has more content)
        expect(Cache::has('icon-picker-options-icons:free'))->toBeTrue();
        expect(Cache::has('icon-picker-options-icons'))->toBeTrue();
    });

    test('pickerOptions does not cache search requests', function () {
        Cache::forget('icon-picker-options-icons:free');
        Cache::forget('icon-picker-options-icons');

        // Search requests should not be cached
        $searchResponse = get(action([IconController::class, 'pickerOptions'], ['search' => 'zero']))
            ->assertOk()
            ->json();

        expect($searchResponse['icons'])->toBeArray();

        expect(Cache::has('icon-picker-options-icons:free'))->toBeFalse();
        expect(Cache::has('icon-picker-options-icons'))->toBeFalse();
    });
});
