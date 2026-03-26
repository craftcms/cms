<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;

describe('fieldHtml', function () {
    it('renders the field container and optional label', function () {
        $html = FormFields::fieldHtml('<input>');
        $labelHtml = FormFields::fieldHtml('<input>', ['label' => 'Label', 'id' => 'id']);
        $blankLabelHtml = FormFields::fieldHtml('<input>', ['label' => '__blank__']);

        expect($html)->toContain('<div class="input ltr"><input></div>')
            ->and($labelHtml)->toContain('<label id="id-label" for="id">Label</label>')
            ->and($blankLabelHtml)->not->toContain('<label');
    });

    it('throws for an invalid site id in multi-site mode', function () {
        if (! Sites::isMultiSite()) {
            expect(true)->toBeTrue();

            return;
        }

        expect(fn () => FormFields::fieldHtml('<input>', ['siteId' => -1]))
            ->toThrow(InvalidArgumentException::class);
    });

    it('supports fieldsets with grouped label semantics', function () {
        $html = FormFields::fieldHtml('<input>', ['fieldset' => true, 'label' => 'Label']);

        expect($html)->toContain('aria-labelledby="')
            ->and($html)->toContain('role="group"');
    });

    it('renders instructions, tip, warning, and errors', function () {
        $withInstructions = FormFields::fieldHtml('<input>', [
            'instructionsId' => 'inst-id',
            'instructions' => '**Test**',
        ]);
        $withTip = FormFields::fieldHtml('<input>', [
            'tipId' => 'tip',
            'tip' => '**Test**',
        ]);
        $withWarning = FormFields::fieldHtml('<input>', [
            'warningId' => 'warning',
            'warning' => '**Test**',
        ]);
        $withErrors = FormFields::fieldHtml('<input>', [
            'errors' => ['Very bad', 'Very, very bad'],
        ]);

        expect($withInstructions)->toContain('id="inst-id"')
            ->and($withInstructions)->toContain('<p><strong>Test</strong></p>')
            ->and($withTip)->toContain('<p id="tip" class="notice has-icon">')
            ->and($withWarning)->toContain('<p id="warning" class="warning has-icon">')
            ->and($withErrors)->toContain('has-errors')
            ->and((bool) preg_match('/<ul id="[\w\-]+" class="errors">/', $withErrors))->toBeTrue();
    });

    it('throws for invalid template paths', function () {
        expect(fn () => FormFields::fieldHtml('template:invalid/template.twig', []))
            ->toThrow(TemplateLoaderException::class);
    });
});

describe('field helper methods', function () {
    it('renders expected markers', function (string $needle, string $method, array $config = []) {
        $html = FormFields::$method($config);

        expect($html)->toContain($needle);
    })->with([
        ['type="checkbox"', 'checkboxFieldHtml'],
        ['color-input', 'colorFieldHtml'],
        ['editable', 'editableTableFieldHtml', ['name' => 'test']],
        ['lightswitch', 'lightswitchFieldHtml'],
        ['<select', 'selectFieldHtml'],
        ['type="text"', 'textFieldHtml'],
        ['<div class="label light" aria-hidden="true">Test unit</div>', 'textFieldHtml', ['unit' => 'Test unit']],
        ['<textarea', 'textareaFieldHtml'],
    ]);
});
