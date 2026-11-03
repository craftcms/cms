<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\SelectColor;

describe('host attributes', function () {
    it('wires external label and description associations', function () {
        $html = SelectColor::make()
            ->labelledBy('a-label')
            ->describedBy('a-description')
            ->toHtml();

        expect($html)->toContain('labelled-by="a-label"')
            ->and($html)->toContain('described-by="a-description"');
    });

    it('leaves the associations off when none are given', function () {
        $html = SelectColor::make()->toHtml();

        expect($html)->not->toContain('labelled-by')
            ->and($html)->not->toContain('described-by');
    });
});
