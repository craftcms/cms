<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementExporterInterface;
use CraftCms\Cms\Element\ElementExporters;
use CraftCms\Cms\Element\Events\RegisterExporters;
use CraftCms\Cms\Element\Exporters\Expanded;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $this->elementExporters = app(ElementExporters::class);
});

it('creates exporters from strings, arrays, and existing instances', function () {
    $fromString = $this->elementExporters->createExporter(Raw::class, Entry::class);
    $fromArray = $this->elementExporters->createExporter([
        'type' => Raw::class,
    ], Entry::class);
    $existing = new Raw;
    $fromInstance = $this->elementExporters->createExporter($existing, Entry::class);

    expect($fromString)->toBeInstanceOf(Raw::class)
        ->and($fromString->getFilename())->toBe('entries')
        ->and($fromArray)->toBeInstanceOf(Raw::class)
        ->and($fromArray->getFilename())->toBe('entries')
        ->and($fromInstance)->toBe($existing)
        ->and($fromInstance->getFilename())->toBe('entries');
});

it('resolves canonical exporters for a source', function () {
    $exporters = $this->elementExporters->availableExporters(Entry::class, '*');
    $types = array_map(fn (ElementExporterInterface $exporter) => $exporter::class, $exporters);

    expect($types)->toBe([
        Raw::class,
        Expanded::class,
    ]);
});

it('serializes exporter configs for the control panel', function () {
    $serialized = $this->elementExporters->serializeExporters(
        $this->elementExporters->availableExporters(Entry::class, '*'),
    );

    expect($serialized)->toBe([
        [
            'type' => Raw::class,
            'name' => Raw::displayName(),
            'formattable' => true,
        ],
        [
            'type' => Expanded::class,
            'name' => Expanded::displayName(),
            'formattable' => true,
        ],
    ]);
});

it('resolves a cloned matching exporter and returns null when missing', function () {
    $exporters = $this->elementExporters->availableExporters(Entry::class, '*');

    $resolved = $this->elementExporters->resolveExporter($exporters, Raw::class);
    $missing = $this->elementExporters->resolveExporter($exporters, 'App\\MissingExporter');

    expect($resolved)->toBeInstanceOf(Raw::class)
        ->and($resolved)->not->toBe($exporters[0])
        ->and($missing)->toBeNull();
});

it('accepts legacy alias class names when resolving exporters', function () {
    $exporters = $this->elementExporters->availableExporters(Entry::class, '*');

    expect($this->elementExporters->resolveExporter($exporters, craft\elements\exporters\Raw::class))
        ->toBeInstanceOf(Raw::class);
});

it('honors register exporters listeners when building available exporters', function () {
    $customExporter = new class extends Raw
    {
        public static function displayName(): string
        {
            return 'Custom';
        }
    };

    Event::listen(function (RegisterExporters $event) use ($customExporter) {
        if ($event->elementType === Entry::class) {
            $event->exporters[] = clone $customExporter;
        }
    });

    $exporters = $this->elementExporters->availableExporters(Entry::class, '*');

    expect(array_map(fn (ElementExporterInterface $exporter) => $exporter::class, $exporters))
        ->toContain($customExporter::class);
});
