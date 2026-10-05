<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements as ElementsFacade;
use CraftCms\Cms\Support\Facades\EntryTypes as EntryTypesFacade;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Collection;

class MatrixControllerFixture
{
    public static function create(): array
    {
        $innerField = Field::factory()->create([
            'name' => 'Inner Text',
            'handle' => 'innerText',
            'type' => PlainText::class,
        ]);

        $entryType = EntryType::factory()
            ->withField($innerField)
            ->create([
                'name' => 'Matrix Block',
                'handle' => 'matrixBlock',
                'hasTitleField' => true,
            ]);

        $matrixField = Field::factory()->create([
            'name' => 'Matrix Field',
            'handle' => 'matrixField',
            'type' => Matrix::class,
            'settings' => ['entryTypes' => [$entryType->id]],
        ]);

        $ownerType = EntryType::factory()
            ->withField($matrixField)
            ->create([
                'name' => 'Owner',
                'handle' => 'owner',
                'hasTitleField' => true,
            ]);

        $section = Section::factory()
            ->withEntryTypes($ownerType)
            ->create([
                'handle' => 'owners',
            ]);

        $owner = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($ownerType)
            ->createElement([
                'title' => 'Owner Entry',
                'slug' => Str::slug('Owner Entry '.Str::random(6)),
            ]);

        EntryTypesFacade::refreshEntryTypes();
        FieldsFacade::invalidateCaches();
        FieldsFacade::refreshFields();

        return [
            'entryType' => $entryType,
            'field' => FieldsFacade::getFieldById($matrixField->id),
            'innerField' => $innerField,
            'owner' => EntryElement::find()->id($owner->id)->status(null)->one(),
            'ownerType' => $ownerType,
            'section' => $section,
            'siteId' => Site::firstOrFail()->id,
        ];
    }

    public static function payload(array $fixture, array $overrides = []): array
    {
        return array_merge([
            'fieldId' => $fixture['field']->id,
            'entryTypeId' => $fixture['entryType']->id,
            'ownerId' => $fixture['owner']->id,
            'ownerElementType' => EntryElement::class,
            'siteId' => $fixture['siteId'],
            'path' => ['fields', 'matrixField'],
        ], $overrides);
    }

    public static function saveBlocks(array $fixture, array $blocks): EntryElement
    {
        $entries = [];
        $sortOrder = [];

        foreach ($blocks as $block) {
            $uid = $block['uid'] ?? Str::uuid()->toString();
            $sortOrder[] = $uid;
            $entries["uid:$uid"] = [
                'type' => $fixture['entryType']->handle,
                'title' => $block['title'],
                'enabled' => $block['enabled'] ?? true,
                'enabledForSite' => $block['enabledForSite'] ?? true,
                'fields' => [
                    'innerText' => $block['innerText'],
                ],
            ];
        }

        $owner = EntryElement::find()->id($fixture['owner']->id)->status(null)->one();
        $owner->setFieldValueFromRequest($fixture['field']->handle, [
            'entries' => $entries,
            'sortOrder' => $sortOrder,
        ]);

        expect(ElementsFacade::saveElement($owner))->toBeTrue();

        return EntryElement::find()->id($owner->id)->status(null)->one();
    }

    public static function entries(array $fixture): Collection
    {
        return collect(EntryElement::find()
            ->fieldId($fixture['field']->id)
            ->ownerId($fixture['owner']->id)
            ->siteId($fixture['siteId'])
            ->drafts(null)
            ->status(null)
            ->all());
    }

    public static function refresh(array $fixture): array
    {
        $fixture['field'] = FieldsFacade::getFieldById($fixture['field']->id);
        $fixture['owner'] = EntryElement::find()->id($fixture['owner']->id)->status(null)->one();

        return $fixture;
    }
}
