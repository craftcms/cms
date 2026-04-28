<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Events\Render;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\HtmlString;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Create a test entry for each test
    $this->entry = EntryModel::factory()->create();

    // Load it from an ElementQuery so all data is properly set
    $this->entry = entryQuery()->id($this->entry->id)->one();

    actingAs(User::findOne());
});

describe('render', function () {
    test('returns markup', function () {
        $markup = $this->entry->render();

        expect($markup)->toBeInstanceOf(HtmlString::class);
    });

    test('Render event allows setting custom output', function () {
        $customOutput = 'Custom Output';

        Event::listen(function (Render $event) use ($customOutput) {
            $event->output = $customOutput;
        });

        $markup = $this->entry->render();

        expect((string) $markup)->toBe($customOutput);
    });

    test('Render event can modify variables and templates', function () {
        $capturedVariables = null;

        Event::listen(function (Render $event) use (&$capturedVariables) {
            $event->variables = array_merge($event->variables, ['foo' => 'bar']);
            $capturedVariables = $event->variables;
        });

        $this->entry->render();

        expect($capturedVariables)->toHaveKey('foo');
        expect($capturedVariables['foo'])->toBe('bar');
    });
});

describe('partialTemplatePathCandidates', function () {
    test('returns correct candidates', function () {
        $reflection = new ReflectionClass($this->entry);
        $method = $reflection->getMethod('partialTemplatePathCandidates');

        $candidates = $method->invoke($this->entry);

        expect($candidates)->toBeArray();

        $refHandle = $this->entry::refHandle();
        if ($refHandle) {
            $hasBaseCandidate = collect($candidates)->contains(fn ($candidate) => str_contains((string) $candidate['template'], Cms::config()->partialTemplatesPath.'/'.$refHandle));
            expect($hasBaseCandidate)->toBeTrue();
        }
    });
});
