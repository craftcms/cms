<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Contracts\Statusable;
use CraftCms\Cms\Cp\Html\StatusHtml;
use CraftCms\Cms\Shared\Enums\Color;

describe('statusIndicatorHtml', function () {
    it('renders the draft icon for draft status', function () {
        $html = app(StatusHtml::class)->statusIndicatorHtml('draft');

        expect($html)->toContain('data-icon="draft"')
            ->and($html)->toContain('role="img"');
    });

    it('renders standard status class and aria label', function () {
        $html = app(StatusHtml::class)->statusIndicatorHtml('enabled', [
            'label' => 'Enabled',
        ]);

        expect($html)->toContain('fill="teal"')
            ->and($html)->toContain('label="Status: Enabled"');
    });
});

describe('component status helpers', function () {
    it('renders status indicator from component statuses', function () {
        $component = new class implements Statusable
        {
            public static function statuses(): array
            {
                return ['pending' => ['label' => 'Pending', 'color' => Color::Yellow]];
            }

            public function getStatus(): string
            {
                return 'pending';
            }
        };

        $html = app(StatusHtml::class)->componentStatusIndicatorHtml($component);

        expect($html)->toContain('fill="yellow"')
            ->and($html)->toContain('label="Status: Pending"');
    });

    it('renders component status label and edited status label', function () {
        $component = new class implements Statusable
        {
            public static function statuses(): array
            {
                return ['pending' => ['label' => 'Pending', 'color' => Color::Yellow]];
            }

            public function getStatus(): string
            {
                return 'pending';
            }
        };

        $statusHtml = app(StatusHtml::class);
        $labelHtml = $statusHtml->componentStatusLabelHtml($component);
        $editedHtml = $statusHtml->editedStatusLabelHtml();

        expect($labelHtml)->toContain('<craft-badge')
            ->and($labelHtml)->toContain('fill="yellow"')
            ->and($labelHtml)->toContain('Pending')
            ->and($editedHtml)->toContain('<craft-badge')
            ->and($editedHtml)->toContain('slot="prefix"')
            ->and($editedHtml)->toContain('Edited');
    });
});
