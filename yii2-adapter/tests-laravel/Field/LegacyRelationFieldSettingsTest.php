<?php

declare(strict_types=1);

use craft\fields\Assets;
use craft\fields\Entries;
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

it('renders the shared relation field settings template through craft.cp', function() {
    $html = (new Users())->getSettingsHtml();

    expect($html)
        ->toContain('<craft-field')
        ->toContain('<craft-switch')
        ->not->toContain('&lt;craft-');
});

it('renders the legacy Entries and Assets settings templates', function(string $class, string $setting) {
    $html = (new $class())->getSettingsHtml();

    expect($html)
        ->toContain("name=\"$setting\"")
        ->not->toContain('showUnpermittedEntries')
        ->not->toContain('showUnpermittedFiles');
})->with([
    'entries' => [Entries::class, 'showUnpermittedSections'],
    'assets' => [Assets::class, 'showUnpermittedVolumes'],
]);

it('renders a settings template declared by a subclass', function() {
    $dir = sys_get_temp_dir() . '/relation-settings-' . uniqid();
    mkdir($dir);
    file_put_contents("$dir/settings.twig", 'custom settings for {{ field.handle }}');
    app(TemplateRoots::class)->register(TemplateMode::Cp, 'relation-test', $dir);

    $field = new class() extends Users {
        protected string $settingsTemplate = 'relation-test/settings.twig';
    };
    $field->handle = 'related';

    expect($field->getSettingsHtml())->toBe('custom settings for related');

    File::deleteDirectory($dir);
});
