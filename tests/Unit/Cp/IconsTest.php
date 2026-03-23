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
