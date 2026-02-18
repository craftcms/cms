<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Rebrand;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('rebrand');
    $this->rebrand = app(Rebrand::class);
});

describe('getImage', function () {
    it('rejects unsupported types', function () {
        expect($this->rebrand->getImage('invalid-type'))->toBe(null);
    });

    it('returns a file if it exists', function () {
        // Create a fake file in the icon directory
        Storage::disk('rebrand')->put('icon/test-icon.png', 'fake-content');

        $result = $this->rebrand->getImage('icon');

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['name', 'url'])
            ->and($result['name'])->toBe('test-icon.png')
            ->and($result['url'])->toContain('icon/test-icon.png');
    });

    it('returns null if no file exits', function () {
        $result = $this->rebrand->getImage('icon');

        expect($result)->toBeNull();
    });
});
