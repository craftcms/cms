<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Element\Events\ElementRendering;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->entry = EntryModel::factory()->createElement(['title' => 'Fish & Chips']);

    $this->templatesPath = Aliases::getAll()['@templates'] ?? null;
    $this->tempDir = sys_get_temp_dir().'/craft-renderable-test-'.uniqid();

    File::ensureDirectoryExists($this->tempDir.'/_partials/entry');
    Aliases::set('@templates', $this->tempDir);

    actingAs(User::findOne());
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);

    $this->templatesPath === null
        ? Aliases::remove('@templates')
        : Aliases::set('@templates', $this->templatesPath);
});

describe('render', function () {
    test('renders the element’s partial template with the element and variables', function () {
        File::put($this->tempDir.'/_partials/entry.twig', '{{ entry.id }}|{{ greeting }}');

        expect((string) $this->entry->render(['greeting' => 'Hello']))->toBe("{$this->entry->id}|Hello");
    });

    test('prefers the partial template for the entry’s type', function () {
        File::put($this->tempDir.'/_partials/entry.twig', 'generic');
        File::put($this->tempDir."/_partials/entry/{$this->entry->getType()->handle}.twig", 'type-specific');

        expect((string) $this->entry->render())->toBe('type-specific');
    });

    test('falls back to a paragraph with the encoded element label', function () {
        expect((string) $this->entry->render())->toBe('<p>Fish &amp; Chips</p>');
    });

    test('ElementRendering event allows setting custom output', function () {
        $customOutput = 'Custom Output';

        Event::listen(function (ElementRendering $event) use ($customOutput) {
            $event->output = $customOutput;
        });

        $markup = $this->entry->render();

        expect((string) $markup)->toBe($customOutput);
    });

    test('ElementRendering event can modify variables and templates', function () {
        File::put($this->tempDir.'/_partials/entry.twig', 'default');
        File::put($this->tempDir.'/custom.twig', '{{ greeting }}');

        Event::listen(function (ElementRendering $event) {
            $event->templates = [['template' => 'custom', 'priority' => 1]];
            $event->variables['greeting'] = 'Changed';
        });

        expect((string) $this->entry->render(['greeting' => 'Hello']))->toBe('Changed');
    });
});
