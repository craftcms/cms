<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Field;
use CraftCms\Cms\Field\FieldTypes;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Query;
use Illuminate\Support\Facades\DB;

class NumericQueryField extends Field
{
    public static string $columnType = Query::TYPE_INTEGER;

    public static function dbType(): string
    {
        return self::$columnType;
    }
}

test('numeric custom fields compare and sort by their declared type', function (string $fieldType, array $settings, string $columnType, bool $fractional = false) {
    if (DB::getDriverName() === 'pgsql' && $fieldType === NumericQueryField::class && ! in_array($columnType, [Query::TYPE_INTEGER, 'decimal(12,2)'])) {
        $this->markTestSkipped('PostgreSQL only casts integer and decimal custom fields.');
    }

    NumericQueryField::$columnType = $columnType;
    app(FieldTypes::class)->register(NumericQueryField::class);

    $entry = EntryModel::factory()
        ->withField('numericValue', $fieldType, $settings, value: 10)
        ->createElementWithFields()->element;
    $ids = [];
    $values = $fractional ? ['10.5', '-2.25', '4.75', '2.125', null] : ['10', '-2', '4', '2', null];

    foreach ($values as $value) {
        $next = EntryModel::factory()->createElement(['typeId' => $entry->typeId, 'sectionId' => $entry->sectionId]);
        $next->setFieldValue('numericValue', $value);
        expect(Elements::saveElement($next))->toBeTrue();
        $ids[] = $next->id;
    }

    $field = $entry->getFieldLayout()->getFieldByHandle('numericValue');
    $query = fn () => Entry::find()->id($ids)->orderBy('id');
    $notIds = $fieldType === Number::class ? [$ids[1], $ids[3]] : [$ids[1], $ids[3], $ids[4]];

    expect($query()->numericValue('>= 4')->ids())->toBe([$ids[0], $ids[2]])
        ->and($query()->numericValue('< 4')->ids())->toBe([$ids[1], $ids[3]])
        ->and($query()->numericValue($values[2])->ids())->toBe([$ids[2]])
        ->and($query()->numericValue(['not', $values[2], $values[0]])->ids())->toBe($notIds)
        ->and($query()->numericValue(':empty:')->ids())->toBe([$ids[4]])
        ->and($query()->numericValue(':notempty:')->reorder()->orderBy($field->getValueSql())->ids())
        ->toBe([$ids[1], $ids[3], $ids[2], $ids[0]]);
})->with([
    'native integer' => [Number::class, ['min' => null], Query::TYPE_INTEGER],
    'native decimal' => [Number::class, ['min' => null, 'decimals' => 3], Query::TYPE_DECIMAL, true],
    'plugin tinyint' => [NumericQueryField::class, [], Query::TYPE_TINYINT],
    'plugin smallint' => [NumericQueryField::class, [], Query::TYPE_SMALLINT],
    'plugin integer' => [NumericQueryField::class, [], Query::TYPE_INTEGER],
    'plugin bigint' => [NumericQueryField::class, [], Query::TYPE_BIGINT],
    'plugin decimal' => [NumericQueryField::class, [], 'decimal(12,2)', true],
    'plugin float' => [NumericQueryField::class, [], Query::TYPE_FLOAT, true],
    'plugin double' => [NumericQueryField::class, [], Query::TYPE_DOUBLE, true],
]);

test('numeric field comparisons retain their type across field layouts', function () {
    $result = EntryModel::factory()
        ->withField('numericValue', Number::class, ['decimals' => 2], value: 10.5)
        ->createElementWithFields();
    $other = EntryModel::factory()
        ->withFieldLayout(FieldLayout::factory()->forField($result->fields->get('numericValue')))
        ->createElement();
    $other->setFieldValue('numericValue', 2.75);
    expect(Elements::saveElement($other))->toBeTrue();

    $query = fn () => Entry::find()->id([$result->element->id, $other->id]);

    expect($query()->numericValue('>= 4')->ids())->toBe([$result->element->id])
        ->and($query()->numericValue('< 4')->ids())->toBe([$other->id])
        ->and($query()->numericValue('2.75')->ids())->toBe([$other->id]);
});

test('SQLite bigint fields preserve integers beyond floating point precision', function () {
    if (DB::getDriverName() !== 'sqlite') {
        $this->markTestSkipped('SQLite NUMERIC affinity regression.');
    }

    NumericQueryField::$columnType = Query::TYPE_BIGINT;
    app(FieldTypes::class)->register(NumericQueryField::class);
    $entry = EntryModel::factory()
        ->withField('bigValue', NumericQueryField::class, value: '9007199254740993')
        ->createElementWithFields()->element;
    $other = EntryModel::factory()->createElement(['typeId' => $entry->typeId, 'sectionId' => $entry->sectionId]);
    $other->setFieldValue('bigValue', '9007199254740992');
    expect(Elements::saveElement($other))->toBeTrue();

    expect(Entry::find()->id([$entry->id, $other->id])->bigValue('> 9007199254740992')->ids())->toBe([$entry->id]);
});

test('lightswitch queries distinguish true false and missing values', function () {
    $entry = EntryModel::factory()
        ->withField('switchValue', Lightswitch::class, value: true)
        ->createElementWithFields()->element;
    $off = EntryModel::factory()->createElement(['typeId' => $entry->typeId, 'sectionId' => $entry->sectionId]);
    $off->setFieldValue('switchValue', false);
    expect(Elements::saveElement($off))->toBeTrue();
    $missing = EntryModel::factory()->createElement(['typeId' => $entry->typeId, 'sectionId' => $entry->sectionId]);
    $ids = [$entry->id, $off->id, $missing->id];
    $query = fn () => Entry::find()->id($ids)->orderBy('id');
    $field = $entry->getFieldLayout()->getFieldByHandle('switchValue');

    expect($query()->switchValue(true)->ids())->toBe([$entry->id])
        ->and($query()->switchValue(false)->ids())->toBe([$off->id, $missing->id])
        ->and($query()->switchValue(['value' => false, 'strict' => true])->ids())->toBe([$off->id])
        ->and($query()->switchValue('not 1')->ids())->toBe([$off->id, $missing->id])
        ->and($query()->reorder()->orderBy($field->getValueSql())->ids())->toBe(
            DB::getDriverName() === 'pgsql' ? [$off->id, $entry->id, $missing->id] : [$missing->id, $off->id, $entry->id],
        );
});

test('date custom fields compare and sort chronologically', function () {
    if (DB::getDriverName() !== 'sqlite') {
        $this->markTestSkipped('SQLite JSON path regression.');
    }

    $entry = EntryModel::factory()
        ->withField('dateValue', Date::class, value: new DateTime('2026-02-10 12:00:00', new DateTimeZone('UTC')))
        ->createElementWithFields()->element;
    $earlier = EntryModel::factory()->createElement(['typeId' => $entry->typeId, 'sectionId' => $entry->sectionId]);
    $earlier->setFieldValue('dateValue', new DateTime('2026-02-10 09:00:00', new DateTimeZone('UTC')));
    expect(Elements::saveElement($earlier))->toBeTrue();
    $query = fn () => Entry::find()->id([$entry->id, $earlier->id]);
    $field = $entry->getFieldLayout()->getFieldByHandle('dateValue');

    expect($query()->dateValue('>= 2026-02-10 10:00:00 UTC')->ids())->toBe([$entry->id])
        ->and($query()->dateValue('< 2026-02-10 10:00:00 UTC')->ids())->toBe([$earlier->id])
        ->and($query()->orderBy($field->getValueSql())->ids())->toBe([$earlier->id, $entry->id]);
});
