<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Icons;

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
    it('returns custom-icons for a custom icon', function () {
        expect(Icons::resolveIconFamily('graphql'))->toBe('custom-icons');
    });

    it('returns a font-awesome family for a system icon', function () {
        expect(Icons::resolveIconFamily('gear'))->not->toBe('custom-icons')
            ->and(Icons::resolveIconFamily('gear'))->not->toBeEmpty();
    });
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

        expect($path)->toContain('custom-icons/graphql.svg');
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

    // An XML declaration is not valid HTML, and the CP feeds these straight to
    // Vue's runtime template compiler, which throws on it in a production
    // build — taking the surrounding subtree down. Several of the bundled
    // custom icons are authored with one, c-outline (the default system icon)
    // among them.
    it('strips the XML declaration from icons that ship with one', function (string $icon) {
        $svg = Icons::svg($icon);

        expect($svg)->not->toContain('<?xml')
            ->and($svg)->toStartWith('<svg');
    })->with(['c-outline', 'c-debug', 'craft-cms', 'craft-partners', 'default-plugin']);
});
