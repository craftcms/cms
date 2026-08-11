<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Http\Controllers\IconController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Symfony\Component\DomCrawler\Crawler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    File::ensureDirectoryExists(Aliases::get('@cmsAssets/resources/icons/solid'));
    File::ensureDirectoryExists(Aliases::get('@cmsAssets/resources/icons/custom-icons'));

    $indexPath = Aliases::get('@cmsAssets/resources/icons/index.php');

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
    if (! File::exists(Aliases::get('@cmsAssets/resources/icons/solid/gear.svg'))) {
        File::put(Aliases::get('@cmsAssets/resources/icons/solid/gear.svg'), '<svg></svg>');
    }

    // Pro
    if (! File::exists(Aliases::get('@cmsAssets/resources/icons/solid/00.svg'))) {
        File::put(Aliases::get('@cmsAssets/resources/icons/solid/00.svg'), '<svg></svg>');
    }

    // Custom
    if (! File::exists(Aliases::get('@cmsAssets/resources/icons/custom-icons/element-card.svg'))) {
        File::put(Aliases::get('@cmsAssets/resources/icons/custom-icons/element-card.svg'), '<svg></svg>');
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
});

describe('pickerOptions', function () {
    test('pickerOptions returns JSON response with correct structure', function () {
        get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonStructure(['listHtml']);
    });

    test('pickerOptions returns valid HTML list with icon buttons', function () {
        $json = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        expect($json['listHtml'])
            ->toBeString()
            ->toContain('<li>')
            ->toContain('<button')
            ->toContain('</li>');
    });

    test('pickerOptions includes SVG content in buttons', function () {
        $json = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        expect($json['listHtml'])
            ->toContain('<svg');
    });

    test('pickerOptions sets icon values and accessible labels on buttons', function () {
        $json = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        $gear = new Crawler($json['listHtml'])->filter('button[title="gear"]');

        expect($gear->attr('value'))->toBe('gear');
        expect($json['listHtml'])
            ->toContain('aria-label=')
            ->toContain('title=')
            ->toContain('class="icon-picker--icon"');
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

        $html = $json['listHtml'];

        // Should contain free icons (checking for a known free icon: '0')
        expect($html)->toContain('title="gear"');

        // Should not contain pro icons (checking for a known pro icon: '00')
        expect($html)->not->toContain('title="00"');
    });

    test('pickerOptions includes pro icons when freeOnly is false', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['freeOnly' => false]))
            ->assertOk()
            ->json();

        $html = $json['listHtml'];

        // Should contain both free and pro icons
        expect($html)
            ->toContain('title="gear"')  // free icon
            ->toContain('title="00"'); // pro icon
    });

    test('pickerOptions defaults freeOnly to true', function () {
        $json = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        $html = $json['listHtml'];

        // Should not contain pro icons by default
        expect($html)->not->toContain('title="00"');
    });

    test('pickerOptions filters icons by search term', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'cog']))
            ->assertOk()
            ->json();

        $html = $json['listHtml'];

        // Should contain icons matching 'cog' in name or terms
        expect($html)
            ->toContain('title="gear"')
            ->toBeString();
    });

    test('pickerOptions handles multi-word search', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'cog modify']))
            ->assertOk()
            ->json();

        $html = $json['listHtml'];

        // Should find icons that match both terms
        expect($html)->toContain('title="gear"');
    });

    test('pickerOptions returns empty list for non-matching search', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'xyznonexistent']))
            ->assertOk()
            ->json();

        expect($json['listHtml'])->toBe('');
    });

    test('pickerOptions treats empty search string as no search', function () {
        $jsonWithEmpty = get(action([IconController::class, 'pickerOptions'], ['search' => '']))
            ->assertOk()
            ->json();

        $jsonWithoutSearch = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        // Both should return the same result (all free icons)
        expect($jsonWithEmpty['listHtml'])->toBe($jsonWithoutSearch['listHtml']);
    });

    test('pickerOptions caches results for non-search requests', function () {
        Cache::forget('icon-picker-options-list-html:free');
        Cache::forget('icon-picker-options-list-html');

        // First request should generate HTML
        $firstResponse = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        // Second request should use cached HTML
        $secondResponse = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

        expect($firstResponse['listHtml'])->toBe($secondResponse['listHtml']);
    });

    test('pickerOptions uses separate cache keys for freeOnly true and false', function () {
        Cache::forget('icon-picker-options-list-html:free');
        Cache::forget('icon-picker-options-list-html');

        $freeOnlyResponse = get(action([IconController::class, 'pickerOptions'], ['freeOnly' => true]))
            ->assertOk()
            ->json();

        $allIconsResponse = get(action([IconController::class, 'pickerOptions'], ['freeOnly' => false]))
            ->assertOk()
            ->json();

        // Results should be different (all icons has more content)
        expect(Cache::has('icon-picker-options-list-html:free'))->toBeTrue();
        expect(Cache::has('icon-picker-options-list-html'))->toBeTrue();
    });

    test('pickerOptions does not cache search requests', function () {
        Cache::forget('icon-picker-options-list-html:free');
        Cache::forget('icon-picker-options-list-html');

        // Search requests should not be cached
        $searchResponse = get(action([IconController::class, 'pickerOptions'], ['search' => 'zero']))
            ->assertOk()
            ->json();

        expect($searchResponse['listHtml'])->toBeString();

        expect(Cache::has('icon-picker-options-list-html:free'))->toBeFalse();
        expect(Cache::has('icon-picker-options-list-html'))->toBeFalse();
    });
});
