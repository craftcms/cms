<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Support\CmsAssets;

describe('resolveIconName', function () {
    it('maps legacy aliases', function () {
        expect(Icons::resolveIconName('settings'))->toBe('gear');
    });

    it('resolves earth aliases to a concrete earth icon', function () {
        expect(Icons::resolveIconName('earth'))->toStartWith('earth-')
            ->and(Icons::resolveIconName('world'))->toStartWith('earth-');
    });
});

describe('resolveIconFamily', function () {
    it('returns the physical icon family', function (string $icon, string $family) {
        expect(Icons::resolveIconFamily($icon))->toBe($family);
    })->with([
        'custom' => ['graphql', 'custom-icons'],
        'unknown' => ['not-an-icon', 'solid'],
    ]);
});

describe('resolveIconData', function () {
    it('returns name and family keys for a custom icon', function () {
        $data = Icons::resolveIconData('graphql');

        expect($data)->toHaveKeys(['name', 'family'])
            ->and($data['name'])->toBe('graphql')
            ->and($data['family'])->toBe('custom-icons');
    });

    it('returns name and family keys for a system icon', function () {
        $data = Icons::resolveIconData('gear');

        expect($data)->toHaveKeys(['name', 'family'])
            ->and($data['name'])->toBe('gear')
            ->and($data['family'])->not->toBe('custom-icons');
    });
});

describe('resolveIconPath', function () {
    it('returns a custom-icons path for a custom icon', function () {
        $path = Icons::resolveIconPath('graphql');

        expect($path)->toBe(CmsAssets::resourcesPath('icons/custom-icons/graphql.svg'));
    });

    it('resolves legacy icon names', function () {
        expect(Icons::resolveIconPath('move'))
            ->toBe(CmsAssets::resourcesPath('icons/custom-icons/grip-dots.svg'));
    });

    it('returns a path ending in .svg for a system icon', function () {
        $path = Icons::resolveIconPath('gear');

        expect($path)->toEndWith('gear.svg')
            ->and($path)->not->toContain('custom-icons');
    });
});

describe('svg', function () {
    it('returns null for null icon', function () {
        expect(Icons::svg(null))->toBeNull();
    });

    it('returns fallback svg when icon is invalid and fallback label exists', function () {
        $html = Icons::svg('definitely-not-a-real-icon', fallbackLabel: 'Fallback Label');

        expect($html)->toContain('<svg')
            ->and($html)->toContain('Fallback Label');
    });
});
