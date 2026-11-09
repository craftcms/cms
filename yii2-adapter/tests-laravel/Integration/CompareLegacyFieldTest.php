<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Integration;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
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

it('edits only B’s side of two entries whose legacy fields would share their ids', function() {
    $this->actingAs(User::factory()->admin()->create());
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
