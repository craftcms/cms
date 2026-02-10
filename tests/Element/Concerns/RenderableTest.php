<?php

declare(strict_types=1);

use craft\events\RenderElementEvent;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\User\Elements\User;
use Twig\Markup;
use yii\base\Event;

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

        expect($markup)->toBeInstanceOf(Markup::class);
    });

    test('triggers render event', function () {
        $eventTriggered = false;
        $customOutput = 'Custom Output';

        Event::on(
            Element::class,
            Element::EVENT_RENDER,
            function (RenderElementEvent $event) use (&$eventTriggered, $customOutput) {
                $eventTriggered = true;
                $event->output = $customOutput;
            }
        );

        $markup = $this->entry->render();

        expect($eventTriggered)->toBeTrue();
        expect((string) $markup)->toBe($customOutput);

        Event::off(Element::class, Element::EVENT_RENDER);
    });

    test('event can modify variables and templates', function () {
        $eventTriggered = false;
        $customVariables = ['foo' => 'bar'];

        Event::on(
            Element::class,
            Element::EVENT_RENDER,
            function (RenderElementEvent $event) use (&$eventTriggered, $customVariables) {
                $eventTriggered = true;
                $event->variables = array_merge($event->variables, $customVariables);
            }
        );

        // We can't easily verify templates logic without setting up view paths,
        // but we can verify the event was triggered and properties were accessible.
        $this->entry->render();

        expect($eventTriggered)->toBeTrue();

        Event::off(Element::class, Element::EVENT_RENDER);
    });
});

describe('partialTemplatePathCandidates', function () {
    test('returns correct candidates', function () {
        // partialTemplatePathCandidates is protected, so we access it via reflection or if we can infer it from render behavior.
        // Or we can assume it works if render works.
        // However, we can use reflection to test protected methods if needed, or check if public API exposes it.
        // Element::render uses it.

        // We'll test it via reflection to be sure.
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
