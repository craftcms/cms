<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\RegisterExporters;
use CraftCms\Cms\Element\Exporters\ElementExporter;
use CraftCms\Cms\Element\Exporters\Expanded;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ExportElementIndexController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::findOne());

    $this->export = fn (array $payload = []) => post(
        action(ExportElementIndexController::class),
        array_merge([
            'context' => 'index',
            'source' => '*',
            'elementType' => Entry::class,
        ], $payload),
    );
});

it('returns 400 for unsupported exporters', function () {
    ($this->export)([
        'type' => 'App\\MissingExporter',
    ])->assertStatus(400);
});

it('exports only the selected ids when criteria include an id filter', function () {
    $included = EntryModel::factory()->createElement(['title' => 'Included']);
    EntryModel::factory()->createElement(['title' => 'Excluded']);

    $response = ($this->export)([
        'type' => Raw::class,
        'format' => 'json',
        'criteria' => [
            'id' => [$included->id],
            'status' => null,
        ],
    ]);

    $response->assertOk();

    $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['id'])->toBe($included->id);
});

it('exports the full query with an explicit limit when no ids are selected', function () {
    EntryModel::factory()->createElement(['title' => 'First']);
    EntryModel::factory()->createElement(['title' => 'Second']);

    $response = ($this->export)([
        'type' => Raw::class,
        'format' => 'json',
        'criteria' => [
            'limit' => 1,
            'status' => null,
        ],
    ]);

    $response->assertOk();

    expect(json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR))->toHaveCount(1);
});

it('returns download responses for each supported formattable format', function (string $format, string $contentType, string $exporterClass) {
    EntryModel::factory()->createElement(['title' => 'Export me']);

    $response = ($this->export)([
        'type' => $exporterClass,
        'format' => $format,
    ]);

    $response->assertOk();

    expect($response->headers->get('content-disposition'))->toContain(".$format")
        ->and($response->headers->get('content-type'))->toContain($contentType)
        ->and($response->getContent())->not->toBe('');
})->with([
    'csv' => ['csv', 'text/csv', Raw::class],
    'xlsx' => ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', Raw::class],
    'json' => ['json', 'application/json', Raw::class],
    'xml' => ['xml', 'application/xml', Expanded::class],
    'yaml' => ['yaml', 'application/x-yaml', Raw::class],
]);

it('uses the entry root tag for xml exports', function () {
    EntryModel::factory()->createElement(['title' => 'Export me']);

    $response = ($this->export)([
        'type' => Raw::class,
        'format' => 'xml',
    ]);

    $response->assertOk();

    expect($response->getContent())->toContain('<entries>');
});

it('returns raw string responses for non formattable exporters', function () {
    $exporter = new class extends ElementExporter
    {
        public static function isFormattable(): bool
        {
            return false;
        }

        public static function displayName(): string
        {
            return 'String export';
        }

        public function export(ElementQueryInterface $query): mixed
        {
            return 'plain export';
        }
    };

    Event::listen(function (RegisterExporters $event) use ($exporter) {
        if ($event->elementType === Entry::class) {
            $event->exporters[] = clone $exporter;
        }
    });

    $response = ($this->export)([
        'type' => $exporter::class,
    ]);

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/octet-stream')
        ->and($response->getContent())->toBe('plain export');
});

it('returns streamed responses for non formattable stream exporters', function () {
    $exporter = new class extends ElementExporter
    {
        public static function isFormattable(): bool
        {
            return false;
        }

        public static function displayName(): string
        {
            return 'Stream export';
        }

        public function export(ElementQueryInterface $query): mixed
        {
            return function (): iterable {
                yield 'streamed';
            };
        }
    };

    Event::listen(function (RegisterExporters $event) use ($exporter) {
        if ($event->elementType === Entry::class) {
            $event->exporters[] = clone $exporter;
        }
    });

    $response = ($this->export)([
        'type' => $exporter::class,
    ]);

    $response->assertOk();

    expect($response->streamedContent())->toBe('streamed');
});
