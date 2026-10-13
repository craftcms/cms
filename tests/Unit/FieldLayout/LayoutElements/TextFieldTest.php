<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\TextField;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use Symfony\Component\DomCrawler\Crawler;

it('renders native field direction and only marks translation on multisite installs', function (bool $multisite) {
    Sites::shouldReceive('isMultiSite')->andReturn($multisite);

    $field = new TextField([
        'attribute' => 'title',
        'label' => 'Title',
        'orientation' => 'rtl',
        'translatable' => true,
    ]);
    $context = new UiContext;
    $node = $field->uiNode(new FieldLayoutElementContext(new Entry(['title' => 'Example']), $context));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $rendered = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('craft-field');

    expect($rendered->attr('orientation'))->toBe('rtl')
        ->and($rendered->attr('translatable') !== null)->toBe($multisite);
})->with([
    'single site' => false,
    'multiple sites' => true,
]);

it('preserves field grouping and passes static mode to field presentation hooks', function (ControlMode $mode, bool $global) {
    $field = new class(['attribute' => 'title', 'label' => 'Title', 'required' => true, 'orientation' => 'ltr']) extends TextField
    {
        protected function useFieldset(): bool
        {
            return true;
        }

        protected function showStatus(): bool
        {
            return false;
        }

        protected function instructionsText(?ElementInterface $element = null, bool $static = false): string
        {
            return $static ? 'Read the value.' : 'Edit the value.';
        }

        protected function tipText(?ElementInterface $element = null, bool $static = false): string
        {
            return $static ? 'Read-only tip.' : 'Editing tip.';
        }

        protected function warningText(?ElementInterface $element = null, bool $static = false): string
        {
            return $static ? 'Read-only warning.' : 'Editing warning.';
        }
    };
    $context = new UiContext(mode: $global ? $mode : ControlMode::Editable);
    $node = $field->uiNode(new FieldLayoutElementContext(null, $context, $global ? ControlMode::Editable : $mode));
    $payload = app(UiResolver::class)->resolve(Ui::make([$node]), $context);
    $rendered = new Crawler(app(UiHtmlRenderer::class)->render($payload))->filter('craft-field');
    $static = $mode !== ControlMode::Editable;

    expect($rendered->attr('fieldset'))->not->toBeNull()
        ->and($rendered->attr('required') !== null)->toBe(! $static)
        ->and($rendered->filter('[slot="help-text"]')->text())->toBe($static ? 'Read the value.' : 'Edit the value.')
        ->and($rendered->filter('[slot="tip"]')->text())->toBe($static ? 'Read-only tip.' : 'Editing tip.')
        ->and($rendered->filter('[slot="warning"]')->text())->toBe($static ? 'Read-only warning.' : 'Editing warning.')
        ->and($payload->nodes[0]->props['showStatus'])->toBeFalse();
})->with([
    'editable' => [ControlMode::Editable, false],
    'read-only field' => [ControlMode::ReadOnly, false],
    'disabled field' => [ControlMode::Disabled, false],
    'read-only UI' => [ControlMode::ReadOnly, true],
]);
