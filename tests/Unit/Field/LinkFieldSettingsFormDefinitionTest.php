<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\Lightswitch;
use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\FormDefinition;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes;
use CraftCms\Cms\Field\LinkTypes\Asset;
use CraftCms\Cms\Field\LinkTypes\Email;
use CraftCms\Cms\Field\LinkTypes\Entry;
use CraftCms\Cms\Field\LinkTypes\Phone;
use CraftCms\Cms\Field\LinkTypes\Sms;
use CraftCms\Cms\Field\LinkTypes\Url;

it('projects core link type settings under stable local input names', function () {
    $entry = new class extends Entry
    {
        protected function availableSources(): array
        {
            return [
                ['key' => 'section:news', 'label' => 'News'],
                ['key' => 'section:pages', 'label' => 'Pages'],
            ];
        }
    };
    $asset = new class extends Asset
    {
        protected function availableSources(): array
        {
            return [
                ['key' => 'volume:images', 'label' => 'Images'],
                ['key' => 'volume:documents', 'label' => 'Documents'],
            ];
        }
    };

    $entryDefinition = $entry->getSettingsFormDefinition(false)?->toArray();
    $assetDefinition = $asset->getSettingsFormDefinition(false)?->toArray();

    expect(linkInputNames($entryDefinition))->toBe([
        'sources',
        'showUnpermittedSections',
        'showUnpermittedEntries',
    ])->and(linkProjectedInputs($entryDefinition)['sources']['props']['options'][0])->toBe([
        'label' => 'All',
        'value' => '*',
    ])->and(linkInputNames($assetDefinition))->toBe([
        'sources',
        'allowedKinds',
        'showUnpermittedVolumes',
        'showUnpermittedFiles',
    ])->and(linkProjectedInputs($assetDefinition)['allowedKinds']['props']['options'][0])->toBe([
        'label' => 'All',
        'value' => '*',
    ])->and(linkInputNames(new Url()->getSettingsFormDefinition(false)?->toArray()))->toBe([
        'allowRootRelativeUrls',
        'allowAnchors',
        'allowCustomSchemes',
    ])->and(new Email()->getSettingsFormDefinition(false))->toBeNull()
        ->and(new Phone()->getSettingsFormDefinition(false))->toBeNull()
        ->and(new Sms()->getSettingsFormDefinition(false))->toBeNull();
});

it('composes ordered link type settings without adding a binding scope', function () {
    $registry = app(LinkTypes::class);
    $registry->remove(Asset::class);
    $registry->remove(Entry::class);
    $registry->register(SettingsEntryLinkType::class);
    $registry->register(PublicDefinitionLinkType::class);

    try {
        Cms::config()->enableGql();
        $field = new Link([
            'types' => ['url', 'settings-entry', 'sms', 'public-definition'],
            'typeSettings' => [
                'url' => ['allowAnchors' => true],
                'settings-entry' => [
                    'sources' => ['section:news'],
                    'showUnpermittedEntries' => true,
                ],
            ],
            'showLabelField' => true,
            'advancedFields' => ['rel', 'target'],
            'maxLength' => 512,
            'fullGraphqlData' => false,
        ]);

        $definition = $field->getSettingsFormDefinition(false)?->toArray();
        $inputs = linkProjectedInputs($definition);
        $urlSettings = array_find(
            $definition['elements'] ?? [],
            fn (array $element): bool => ($element['key'] ?? null) === 'link-type:url',
        );

        expect(array_keys($inputs))->toBe([
            'types',
            'typeSettings.url.allowRootRelativeUrls',
            'typeSettings.url.allowAnchors',
            'typeSettings.url.allowCustomSchemes',
            'typeSettings.settings-entry.sources',
            'typeSettings.settings-entry.showUnpermittedSections',
            'typeSettings.settings-entry.showUnpermittedEntries',
            'typeSettings.public-definition.customSetting',
            'typeSettings.public-definition.dependentSetting',
            'showLabelField',
            'advancedFields',
            'maxLength',
            'fullGraphqlData',
        ])->and(array_slice(array_column($inputs['types']['props']['options'], 'value'), 0, 4))
            ->toBe(['url', 'settings-entry', 'sms', 'public-definition'])
            ->and(array_slice(array_column($inputs['advancedFields']['props']['options'], 'value'), 0, 2))
            ->toBe(['rel', 'target'])
            ->and($inputs['types']['props']['sortable'])->toBeTrue()
            ->and($inputs['advancedFields']['props']['sortable'])->toBeTrue()
            ->and($urlSettings['visibleWhen'])->toBe([
                'name' => 'types',
                'operator' => 'contains',
                'value' => 'url',
            ])
            ->and(json_encode($definition, JSON_THROW_ON_ERROR))->not->toContain('bindingScope')
            ->and($field->getSettings())->toMatchArray([
                'types' => ['url', 'settings-entry', 'sms', 'public-definition'],
                'typeSettings' => [
                    'url' => ['allowAnchors' => true],
                    'settings-entry' => [
                        'sources' => ['section:news'],
                        'showUnpermittedEntries' => true,
                    ],
                ],
            ]);
    } finally {
        $registry->remove(SettingsEntryLinkType::class);
    }
});

function linkInputNames(?array $definition): array
{
    return array_keys(linkProjectedInputs($definition));
}

function linkProjectedInputs(?array $definition): array
{
    $inputs = [];
    $visit = function (array $elements) use (&$inputs, &$visit): void {
        foreach ($elements as $element) {
            if (isset($element['name'])) {
                $inputs[$element['name']] = $element;
            }

            $visit($element['children'] ?? []);
        }
    };
    $visit($definition['elements'] ?? []);

    return $inputs;
}

class SettingsEntryLinkType extends Entry
{
    #[Override]
    public static function id(): string
    {
        return 'settings-entry';
    }

    #[Override]
    protected function availableSources(): array
    {
        return [
            ['key' => 'section:news', 'label' => 'News'],
            ['key' => 'section:pages', 'label' => 'Pages'],
        ];
    }
}

class PublicDefinitionLinkType extends Url
{
    #[Override]
    public static function id(): string
    {
        return 'public-definition';
    }

    #[Override]
    public function getSettingsFormDefinition(bool $readOnly): ?FormDefinition
    {
        return FormDefinition::make([
            Field::make(Lightswitch::make()->name('customSetting'))
                ->readOnly($readOnly),
            Field::make(Lightswitch::make()->name('dependentSetting'))
                ->visibleWhen(Condition::equals('customSetting', true))
                ->readOnly($readOnly),
        ]);
    }
}
