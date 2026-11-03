<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\SelectColor;

describe('host attributes', function () {
    it('wires external label and description associations', function () {
        $html = SelectColor::make()
            ->labelledBy('a-label')
            ->describedBy('a-description')
            ->toHtml();

        expect($html)->toContain('aria-labelledby="a-label"')
            ->and($html)->toContain('aria-describedby="a-description"');
    });

    it('leaves the associations off when none are given', function () {
        $html = SelectColor::make()->toHtml();

        expect($html)->not->toContain('aria-labelledby')
            ->and($html)->not->toContain('aria-describedby');
    });
});
