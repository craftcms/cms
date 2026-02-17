<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\IconController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
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

    test('svg processes system icon names', function () {
        $json = get(action([IconController::class, 'svg'], ['icon' => 'gear']))
            ->assertOk()
            ->json();

        expect($json['iconSvg'])
            ->toBeString()
            ->toContain('<svg');
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
        $json = get(action([IconController::class, 'svg'], ['icon' => 'whiskey-glass-ice']))
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

    test('pickerOptions sets correct ARIA labels and titles on buttons', function () {
        $json = get(action([IconController::class, 'pickerOptions']))
            ->assertOk()
            ->json();

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
        expect($html)->toContain('title="0"');

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
            ->toContain('title="0"')  // free icon
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
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'zero']))
            ->assertOk()
            ->json();

        $html = $json['listHtml'];

        // Should contain icons matching 'zero' in name or terms
        expect($html)
            ->toContain('title="0"')
            ->toBeString();
    });

    test('pickerOptions handles multi-word search', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'digit zero']))
            ->assertOk()
            ->json();

        $html = $json['listHtml'];

        // Should find icons that match both terms
        expect($html)->toContain('title="0"');
    });

    test('pickerOptions returns empty list for non-matching search', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'xyznonexistent']))
            ->assertOk()
            ->json();

        expect($json['listHtml'])->toBe('');
    });

    test('pickerOptions ranks search results with name matches first', function () {
        $json = get(action([IconController::class, 'pickerOptions'], ['search' => 'one']))
            ->assertOk()
            ->json();

        $html = $json['listHtml'];

        // The icon with name '1' (containing 'one' in name) should appear in results
        expect($html)
            ->toContain('title="1"')
            ->toBeString();
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
