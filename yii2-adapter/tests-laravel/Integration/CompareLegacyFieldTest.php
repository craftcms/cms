<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Integration;

use CraftCms\Cms\Element\Conditions\ElementCondition;
use CraftCms\Cms\Element\Conditions\TitleConditionRule;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Field\Field as LegacyField;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Inertia\Testing\AssertableInertia;
use Override;

class CompareLegacyNotesField extends LegacyField
{
    #[Override]
    public function getInputHtml(mixed $value, ?ElementInterface $element): string
    {
        return sprintf('<input id="%s" name="%s" value="">', $this->getInputId(), $this->handle);
    }
}

uses(DatabaseTestCase::class);

beforeEach(function() {
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * Two entries of one type with the field, which a condition shows only on tours, as A is when `$aIsTour`.
 *
 * @return array{EntryModel, EntryModel}
 */
function comparedTours(string $fieldType, bool $aIsTour): array
{
    $field = Field::factory()->create(['name' => 'Notes', 'handle' => 'notes', 'type' => $fieldType]);
    $layout = FieldLayout::factory()->withContentTab([
        new EntryTitleField(),
        new CustomField(config: [
            'fieldUid' => $field->uid,
            'elementCondition' => [
                'class' => ElementCondition::class,
                'elementType' => Entry::class,
                'conditionRules' => [[
                    'class' => TitleConditionRule::class,
                    'uid' => (string)Str::uuid(),
                    'operator' => '**',
                    'value' => 'Tour',
                ]],
            ],
        ]),
    ]);
    $a = EntryModel::factory()->withFieldLayout($layout)->create();
    $b = EntryModel::factory()->forSection($a->section)->forEntryType($a->entryType)->create();

    foreach ([[$a, $aIsTour ? 'Tour of Line' : 'Light and Line'], [$b, 'Tour of Light']] as [$model, $title]) {
        $entry = Entry::find()->id($model->id)->status(null)->one();
        $entry->title = $title;
        expect(Elements::saveElement($entry))->toBeTrue();
    }

    return [$a, $b];
}

it('edits only B’s side of two entries whose legacy fields would share their ids', function() {
    $notes = Field::factory()->create(['name' => 'Notes', 'handle' => 'notes', 'type' => CompareLegacyNotesField::class]);
    [$a, $b] = [
        EntryModel::factory()->withFieldLayout(FieldLayout::factory()->forField($notes))->create(),
        EntryModel::factory()->withFieldLayout(FieldLayout::factory()->forField($notes))->create(),
    ];

    $this->get(route('craft.cp.elements.compare-elements', ['fromId' => $a->id, 'toId' => $b->id], false))
        ->assertOk()
        ->assertInertia(fn(AssertableInertia $page) => $page
            ->where('from.editor', null)
            ->where('to.editor.canonicalId', $b->id));
});

it('edits only B’s side when A’s legacy field is one a condition shows only once it’s edited', function() {
    [$a, $b] = comparedTours(CompareLegacyNotesField::class, aIsTour: false);

    $this->get(route('craft.cp.elements.compare-elements', ['fromId' => $a->id, 'toId' => $b->id], false))
        ->assertOk()
        ->assertInertia(fn(AssertableInertia $page) => $page
            ->where('from.editor', null)
            ->where('to.editor.canonicalId', $b->id));
});

it('edits both sides of two entries without legacy fields', function() {
    [$a, $b] = comparedTours(PlainText::class, aIsTour: false);

    $this->get(route('craft.cp.elements.compare-elements', ['fromId' => $a->id, 'toId' => $b->id], false))
        ->assertOk()
        ->assertInertia(fn(AssertableInertia $page) => $page
            ->where('from.editor.canonicalId', $a->id)
            ->where('to.editor.canonicalId', $b->id));
});
