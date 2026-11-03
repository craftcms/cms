<?php

declare(strict_types=1);

use craft\fields\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\File;

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::findOne());
});

function legacyRelationInputHtml(Users $field): string
{
    return (fn() => $this->inputHtml($this->normalizeValue(null, null), null, false))->call($field);
}

it('renders the core element select without an input template', function() {
    expect(legacyRelationInputHtml(new Users(['handle' => 'reviewers'])))
        ->toContain('<craft-element-select-input ');
});

it('renders an input template declared by a subclass', function() {
    $dir = sys_get_temp_dir() . '/relation-input-' . uniqid();
    mkdir($dir);
    file_put_contents("$dir/input.twig", 'custom input for {{ name }}');
    app(TemplateRoots::class)->register(TemplateMode::Cp, 'relation-input-test', $dir);

    $field = new class(['handle' => 'reviewers']) extends Users {
        protected string $inputTemplate = 'relation-input-test/input.twig';
    };

    expect(legacyRelationInputHtml($field))->toBe('custom input for reviewers');

    File::deleteDirectory($dir);
});

it('still serves the legacy element select template for plugin inputs that extend it', function() {
    $field = new class(['handle' => 'reviewers']) extends Users {
        protected string $inputTemplate = '_includes/forms/elementSelect.twig';
    };

    expect(legacyRelationInputHtml($field))
        ->toContain('class="elementselect"')
        ->not->toContain('<craft-element-select-input');
});
