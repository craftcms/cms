<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Facades\DB;

class PostgresNumericQueryField extends Field
{
    public static string $columnType = Query::TYPE_INTEGER;

    public static function dbType(): string
    {
        return self::$columnType;
    }
}

test('PostgreSQL custom fields compare and sort by their numeric dbType', function (string $columnType, array $values) {
    if (DB::getDriverName() !== 'pgsql') {
        $this->markTestSkipped('PostgreSQL numeric field regression.');
    }

    PostgresNumericQueryField::$columnType = $columnType;
    app(FieldTypes::class)->register(PostgresNumericQueryField::class);
    $entry = EntryModel::factory()
        ->withField('numericValue', PostgresNumericQueryField::class, value: $values[0])
        ->createElementWithFields()->element;
    $ids = [$entry->id];
    foreach (array_slice($values, 1) as $value) {
        $next = EntryModel::factory()->createElement(['typeId' => $entry->typeId, 'sectionId' => $entry->sectionId]);
        $next->setFieldValue('numericValue', $value);
        expect(Elements::saveElement($next))->toBeTrue();
        $ids[] = $next->id;
    }

    $query = fn () => Entry::find()->id($ids)->orderBy('id');
    $field = $entry->getFieldLayout()->getFieldByHandle('numericValue');

    expect($query()->numericValue('>= 4')->ids())->toBe([$ids[0], $ids[2]])
        ->and($query()->numericValue('< 4')->ids())->toBe([$ids[1], $ids[3]])
        ->and($query()->numericValue((string) $values[0])->ids())->toBe([$ids[0]])
        ->and($query()->numericValue(['not', $values[0], $values[2]])->ids())->toBe([$ids[1], $ids[3]])
        ->and($query()->reorder()->orderBy($field->getValueSql())->ids())->toBe([$ids[1], $ids[3], $ids[2], $ids[0]]);
})->with([
    'tinyint' => [Query::TYPE_TINYINT, [10, -2, 4, 2]],
    'smallint' => [Query::TYPE_SMALLINT, [10, -200, 4, 2]],
    'integer' => [Query::TYPE_INTEGER, [10, -40000, 4, 2]],
    'bigint' => [Query::TYPE_BIGINT, [10, -5000000000, 4, 2]],
    'decimal' => ['decimal(12,3)', [10.5, -2.25, 4.75, 2.125]],
    'float' => [Query::TYPE_FLOAT, [10.5, -2.25, 4.75, 2.125]],
    'double' => [Query::TYPE_DOUBLE, ['10.1234567890123', -2.25, 4.75, 2.125]],
]);

test('Date fields extract nested UTC values for comparisons and sorting', function (bool $showTimeZone) {
    $entry = EntryModel::factory()
        ->withField('dateValue', Date::class, ['showTimeZone' => $showTimeZone], value: new DateTime('2026-02-10 12:00:00', new DateTimeZone('Europe/Brussels')))
        ->createElementWithFields()->element;
    $earlier = EntryModel::factory()->createElement(['typeId' => $entry->typeId, 'sectionId' => $entry->sectionId]);
    $earlier->setFieldValue('dateValue', new DateTime('2026-02-10 09:00:00', new DateTimeZone('UTC')));
    expect(Elements::saveElement($earlier))->toBeTrue();
    $query = fn () => Entry::find()->id([$entry->id, $earlier->id]);
    $field = $entry->getFieldLayout()->getFieldByHandle('dateValue');

    expect($query()->dateValue('>= 2026-02-10 10:00:00 UTC')->ids())->toBe([$entry->id])
        ->and($query()->dateValue('< 2026-02-10 10:00:00 UTC')->ids())->toBe([$earlier->id])
        ->and($query()->dateValue('2026-02-10 11:00:00 UTC')->ids())->toBe([$entry->id])
        ->and($query()->orderBy($field->getValueSql())->ids())->toBe([$earlier->id, $entry->id]);
})->with([false, true]);
