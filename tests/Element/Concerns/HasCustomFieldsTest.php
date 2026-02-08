<?php

declare(strict_types=1);

use Carbon\Carbon;
use craft\base\ElementInterface;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $field = Field::factory()->create([
        'handle' => 'testField',
        'type' => PlainText::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    $entryModel = Entry::factory()->create();
    $entryModel->element->update(['fieldLayoutId' => $fieldLayout->id]);
    $entryModel->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

    Fields::invalidateCaches();
    Fields::refreshFields();

    $this->entry = entryQuery()->id($entryModel->id)->one();
    $this->field = $field;

    $this->createDraft = fn () => app(Drafts::class)->createDraft(
        canonical: $this->entry,
        creatorId: User::findOne()->id,
    );

    $this->insertChangedField = function (ElementInterface $draft, ?Carbon $dateUpdated = null): void {
        $layoutElementUid = $draft->getFieldLayout()->getCustomFieldElements()[0]->uid;

        DB::table(Table::CHANGEDFIELDS)->insert([
            'elementId' => $draft->id,
            'siteId' => $draft->siteId,
            'fieldId' => $this->field->id,
            'layoutElementUid' => $layoutElementUid,
            'dateUpdated' => $dateUpdated ?? now(),
            'propagated' => true,
        ]);
    };

    $this->reloadDraft = fn (ElementInterface $draft) => entryQuery()
        ->id($draft->id)
        ->draftId($draft->draftId)
        ->one();
});

describe('field values', function () {
    test('getFieldValues returns array', function () {
        expect($this->entry->getFieldValues())->toBeArray();
    });

    test('getFieldValues returns only specified field handles', function () {
        $this->entry->setFieldValue('testField', 'test value');
        $values = $this->entry->getFieldValues(['testField']);

        expect($values)->toHaveKey('testField')
            ->and($values['testField'])->toBe('test value');
    });

    test('getFieldValues filters out unspecified fields', function () {
        $this->entry->setFieldValue('testField', 'test value');
        $values = $this->entry->getFieldValues(['nonExistentField']);

        expect($values)->not->toHaveKey('testField');
    });

    test('getSerializedFieldValues returns array', function () {
        expect($this->entry->getSerializedFieldValues())->toBeArray();
    });

    test('getSerializedFieldValues serializes field values', function () {
        $this->entry->setFieldValue('testField', 'test value');
        $values = $this->entry->getSerializedFieldValues();

        expect($values)->toHaveKey('testField')
            ->and($values['testField'])->toBe('test value');
    });

    test('getSerializedFieldValuesForDb returns array', function () {
        expect($this->entry->getSerializedFieldValuesForDb())->toBeArray();
    });
});

describe('get and set field values', function () {
    test('setFieldValue and getFieldValue roundtrip', function () {
        $this->entry->setFieldValue('testField', 'test value');

        expect($this->entry->getFieldValue('testField'))->toBe('test value');
    });

    test('setFieldValues sets multiple values', function () {
        $this->entry->setFieldValues([
            'testField' => 'value1',
        ]);

        expect($this->entry->getFieldValue('testField'))->toBe('value1');
    });

    test('setFieldValue marks field as dirty', function () {
        $this->entry->setFieldValue('testField', 'new value');

        expect($this->entry->isFieldDirty('testField'))->toBeTrue();
    });

    test('getFieldValue returns normalized value', function () {
        $this->entry->setFieldValue('testField', 'test value');
        $value = $this->entry->getFieldValue('testField');

        expect($value)->toBe('test value');
    });
});

describe('dirty fields', function () {
    test('getDirtyFields returns array', function () {
        expect($this->entry->getDirtyFields())->toBeArray();
    });

    test('setDirtyFields marks fields as dirty', function () {
        $this->entry->setDirtyFields(['testField']);

        expect($this->entry->isFieldDirty('testField'))->toBeTrue();
    });

    test('setDirtyFields with merge=false replaces dirty fields', function () {
        $this->entry->setDirtyFields(['testField']);
        $this->entry->setDirtyFields(['anotherField'], false);

        expect($this->entry->isFieldDirty('testField'))->toBeFalse()
            ->and($this->entry->isFieldDirty('anotherField'))->toBeTrue();
    });

    test('setDirtyFields with merge=true combines dirty fields', function () {
        $this->entry->setDirtyFields(['testField']);
        $this->entry->setDirtyFields(['anotherField'], true);

        expect($this->entry->isFieldDirty('testField'))->toBeTrue()
            ->and($this->entry->isFieldDirty('anotherField'))->toBeTrue();
    });

    test('getDirtyFields returns all dirty field handles', function () {
        $this->entry->setDirtyFields(['testField']);

        expect($this->entry->getDirtyFields())->toContain('testField');
    });

    test('setDirtyFields with empty array clears dirty fields', function () {
        $this->entry->setDirtyFields(['testField']);
        $this->entry->setDirtyFields([], false);

        expect($this->entry->getDirtyFields())->toBeEmpty();
    });
});

describe('generated field values', function () {
    test('getGeneratedFieldValues returns array by default', function () {
        expect($this->entry->getGeneratedFieldValues())->toBeArray();
    });

    test('setGeneratedFieldValues stores values', function () {
        $this->entry->setGeneratedFieldValues(['test' => 'value']);

        expect($this->entry->getGeneratedFieldValues())->toBe(['test' => 'value']);
    });

    test('getGeneratedFieldValues returns empty array when not set', function () {
        expect($this->entry->getGeneratedFieldValues())->toBe([]);
    });

    test('setGeneratedFieldValues overwrites previous values', function () {
        $this->entry->setGeneratedFieldValues(['first' => 'value1']);
        $this->entry->setGeneratedFieldValues(['second' => 'value2']);

        expect($this->entry->getGeneratedFieldValues())->toBe(['second' => 'value2']);
    });
});

describe('field param namespace', function () {
    test('getFieldParamNamespace returns null by default', function () {
        expect($this->entry->getFieldParamNamespace())->toBeNull();
    });

    test('setFieldParamNamespace stores namespace', function () {
        $this->entry->setFieldParamNamespace('fields');

        expect($this->entry->getFieldParamNamespace())->toBe('fields');
    });

    test('setFieldParamNamespace with empty string sets null', function () {
        $this->entry->setFieldParamNamespace('fields');
        $this->entry->setFieldParamNamespace('');

        expect($this->entry->getFieldParamNamespace())->toBeNull();
    });

    test('setFieldParamNamespace stores non-empty namespace', function () {
        $this->entry->setFieldParamNamespace('custom.namespace');

        expect($this->entry->getFieldParamNamespace())->toBe('custom.namespace');
    });
});

describe('outdated fields (_outdatedFields)', function () {
    test('returns empty array for canonical elements', function () {
        expect($this->entry->getOutdatedFields())->toBeEmpty();
    });

    test('isFieldOutdated returns false for canonical elements', function () {
        expect($this->entry->isFieldOutdated('testField'))->toBeFalse();
    });

    test('queries CHANGEDFIELDS table for drafts', function () {
        $draft = ($this->createDraft)();
        ($this->insertChangedField)($draft);
        $draft = ($this->reloadDraft)($draft);

        expect($draft->getOutdatedFields())->toContain('testField');
    });

    test('uses dateLastMerged when set', function () {
        $draft = ($this->createDraft)();

        DB::table(Table::DRAFTS)
            ->where('id', $draft->draftId)
            ->update(['dateLastMerged' => now()->addDay()]);

        ($this->insertChangedField)($draft, now()->subDay());
        $draft = ($this->reloadDraft)($draft);

        expect($draft->getOutdatedFields())->toBeEmpty();
    });

    test('filters by dateCreated when dateLastMerged is null', function () {
        $draft = ($this->createDraft)();
        ($this->insertChangedField)($draft, now()->subDays(100));
        $draft = ($this->reloadDraft)($draft);

        expect($draft->getOutdatedFields())->toBeEmpty();
    });

    test('isFieldOutdated returns true for outdated field', function () {
        $draft = ($this->createDraft)();
        ($this->insertChangedField)($draft);
        $draft = ($this->reloadDraft)($draft);

        expect($draft->isFieldOutdated('testField'))->toBeTrue();
    });

    test('caches results', function () {
        $draft = ($this->createDraft)();
        ($this->insertChangedField)($draft);
        $draft = ($this->reloadDraft)($draft);

        $outdatedFields1 = $draft->getOutdatedFields();

        DB::table(Table::CHANGEDFIELDS)->where('elementId', $draft->id)->delete();

        $outdatedFields2 = $draft->getOutdatedFields();

        expect($outdatedFields1)->toBe($outdatedFields2)
            ->and($outdatedFields2)->toContain('testField');
    });
});

describe('modified fields (_modifiedFields)', function () {
    test('returns empty array for canonical elements', function () {
        expect($this->entry->getModifiedFields())->toBeEmpty();
    });

    test('isFieldModified returns false for canonical elements', function () {
        expect($this->entry->isFieldModified('testField'))->toBeFalse();
    });

    test('queries CHANGEDFIELDS table for drafts', function () {
        $draft = ($this->createDraft)();
        ($this->insertChangedField)($draft);
        $draft = ($this->reloadDraft)($draft);

        expect($draft->getModifiedFields())->toContain('testField');
    });

    test('does not filter by date', function () {
        $draft = ($this->createDraft)();
        ($this->insertChangedField)($draft, now()->subYears(10));
        $draft = ($this->reloadDraft)($draft);

        expect($draft->getModifiedFields())->toContain('testField');
    });

    test('isFieldModified returns true for modified field', function () {
        $draft = ($this->createDraft)();
        ($this->insertChangedField)($draft);
        $draft = ($this->reloadDraft)($draft);

        expect($draft->isFieldModified('testField'))->toBeTrue();
    });
});
