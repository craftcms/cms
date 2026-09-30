<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementExportersResolving;
use CraftCms\Cms\Element\Exporters\ElementExporter;
use CraftCms\Cms\Element\Exporters\Expanded;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ExportElementIndexController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
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
    EntryModel::factory()->createElement(['title' => 'Zulu']);
    EntryModel::factory()->createElement(['title' => 'Alpha']);

    $response = ($this->export)([
        'type' => Raw::class,
        'format' => 'json',
        'criteria' => [
            'limit' => 1,
            'status' => null,
        ],
        'baseCriteria' => ['orderBy' => 'id asc'],
        'sort' => [['field' => 'id', 'direction' => 'desc']],
    ]);

    $response->assertOk();

    $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($payload)->toHaveCount(1)
        ->and($payload[0]['title'])->toBe('Alpha');
});

it('keeps embedded exports scoped to the requested owner field', function () {
    $nestedType = EntryType::factory()->withFieldLayout()->create(['hasTitleField' => true]);
    $settings = ['entryTypes' => [$nestedType->id], 'viewMode' => Matrix::VIEW_MODE_INDEX];
    $fixture = EntryModel::factory()
        ->withField('matrixField', Matrix::class, $settings)
        ->withField('otherMatrix', Matrix::class, $settings)
        ->createElementWithFields();
    $owner = $fixture->element;

    foreach ([
        ['matrixField', ['First included', 'Second included']],
        ['otherMatrix', ['Wrong field']],
    ] as [$handle, $titles]) {
        $entries = [];
        $sortOrder = [];

        foreach ($titles as $title) {
            $uid = 'uid:'.Str::uuid();
            $entries[$uid] = ['type' => $nestedType->handle, 'title' => $title];
            $sortOrder[] = $uid;
        }

        $owner->setFieldValueFromRequest($handle, [
            'entries' => $entries,
            'sortOrder' => $sortOrder,
        ]);
        expect(Elements::saveElement($owner))->toBeTrue();
    }

    $owner = Entry::find()->id($owner->id)->one();
    $field = Fields::getFieldById($fixture->field('matrixField')->id);
    $manager = $field->formControl(new FieldContext(path: 'matrixField', element: $owner))->props()['manager'];
    $response = ($this->export)([
        ...$manager,
        'context' => 'embeddedIndex',
        'source' => '*',
        'baseCriteria' => ['ownerId' => 999999, 'fieldId' => 999999],
        'criteria' => [
            'ownerId' => 999999,
            'fieldId' => 999999,
            'field' => 'otherMatrix',
            'site' => '*',
            'trashed' => true,
        ],
        'type' => Raw::class,
        'format' => 'json',
    ])->assertOk();

    $rows = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    $titles = array_column($rows, 'title');
    sort($titles);

    expect($titles)->toBe(['First included', 'Second included']);
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

    Event::listen(function (ElementExportersResolving $event) use ($exporter) {
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

    Event::listen(function (ElementExportersResolving $event) use ($exporter) {
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
