<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use Closure;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Form\Contracts\Node;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Control;
use CraftCms\Cms\Form\Controls\Lightswitch;
use CraftCms\Cms\Form\Enums\ChoicePresentation;
use CraftCms\Cms\Form\Nodes\Field as FormField;
use CraftCms\Cms\Form\Nodes\Group;
use CraftCms\Cms\Support\Arr;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

/**
 * @property list<string> $types
 * @property array<string, array<string, mixed>> $typeSettings
 * @property bool $showLabelField
 * @property list<'urlSuffix'|'target'|'title'|'class'|'id'|'rel'|'ariaLabel'|'download'> $advancedFields
 * @property list<string> $linkSettingsTypes
 * @property array<string, array<string, mixed>> $linkSettingsTypeSettings
 * @property bool $linkSettingsShowLabelField
 * @property list<'urlSuffix'|'target'|'title'|'class'|'id'|'rel'|'ariaLabel'|'download'> $linkSettingsAdvancedFields
 */
trait ProvidesLinkField
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function prepareLinkSettingsConfig(
        array $config,
    ): array {
        $attributes = [];
        foreach (['types', 'typeSettings', 'showLabelField', 'advancedFields'] as $setting) {
            $attributes[$setting] = $this->namespacedAttribute($setting);
        }

        $namespace = $this->linkSettingsNamespace();

        if ($namespace !== null) {
            $settings = Arr::pull($config, $namespace, []);
            if (! is_array($settings)) {
                $settings = [];
            }

            foreach ([
                'types',
                'typeSettings',
                'showLabelField',
                'advancedFields',
            ] as $setting) {
                $attribute = $attributes[$setting];

                if (array_key_exists($setting, $settings)) {
                    $config[$attribute] = $settings[$setting];
                }
            }
        }

        if (array_key_exists((string) $attributes['types'], $config)) {
            $config[$attributes['types']] = match (true) {
                is_array($config[$attributes['types']]) => $config[$attributes['types']],
                $config[$attributes['types']] === null,
                $config[$attributes['types']] === '' => [],
                default => [$config[$attributes['types']]],
            };
        }

        if (
            isset($config[$attributes['types']], $config[$attributes['typeSettings']]) &&
            is_array($config[$attributes['types']]) &&
            is_array($config[$attributes['typeSettings']])
        ) {
            foreach (array_keys($config[$attributes['typeSettings']]) as $typeId) {
                if (! in_array($typeId, $config[$attributes['types']], true)) {
                    unset($config[$attributes['typeSettings']][$typeId]);
                }
            }
        }

        if (array_key_exists((string) $attributes['advancedFields'], $config)) {
            $config[$attributes['advancedFields']] = match (true) {
                $config[$attributes['advancedFields']] === null,
                $config[$attributes['advancedFields']] === '' => [],
                is_array($config[$attributes['advancedFields']]) => $config[$attributes['advancedFields']],
                default => [$config[$attributes['advancedFields']]],
            };
        }

        return $config;
    }

    /** @return array<string, list<string|Closure>> */
    protected function linkSettingsRules(): array
    {
        $typesAttribute = $this->namespacedAttribute('types');
        $typeSettingsAttribute = $this->namespacedAttribute('typeSettings');
        $showLabelFieldAttribute = $this->namespacedAttribute('showLabelField');
        $advancedFieldsAttribute = $this->namespacedAttribute('advancedFields');
        $supportedAdvancedFields = $this->supportedLinkAdvancedFields();

        return [
            $typesAttribute => [
                'required',
                'array',
                function ($attribute, mixed $value, Closure $fail) {
                    foreach ($value as $type) {
                        if (! is_string($type) || ! array_key_exists($type, Link::types())) {
                            $fail(t('Invalid link type.'));

                            return;
                        }
                    }
                },
            ],
            $typeSettingsAttribute => ['array'],
            $showLabelFieldAttribute => ['boolean'],
            $advancedFieldsAttribute => [
                'array',
                function ($attribute, mixed $value, Closure $fail) use ($supportedAdvancedFields) {
                    foreach ($value as $field) {
                        if (! is_string($field) || ! in_array($field, $supportedAdvancedFields, true)) {
                            $fail(t('Invalid link advanced field.'));

                            return;
                        }
                    }
                },
            ],
        ];
    }

    /** @return list<'urlSuffix'|'target'|'title'|'class'|'id'|'rel'|'ariaLabel'|'download'> */
    protected function supportedLinkAdvancedFields(): array
    {
        return [
            'urlSuffix',
            'target',
            'title',
            'class',
            'id',
            'rel',
            'ariaLabel',
            'download',
        ];
    }

    /**
     * @param  list<'urlSuffix'|'target'|'title'|'class'|'id'|'rel'|'ariaLabel'|'download'>|null  $fields
     * @return list<array{label:string, labelHtml?:string, value:string}>
     */
    protected function linkAdvancedFieldOptions(?array $fields = null): array
    {
        $fields ??= $this->supportedLinkAdvancedFields();
        $labels = [
            'urlSuffix' => t('URL Suffix'),
            'target' => t('Target'),
            'title' => t('Title Text'),
            'class' => t('Class Name'),
            'id' => t('ID'),
            'rel' => t('Relation ({ex})', ['ex' => 'rel']),
            'ariaLabel' => t('ARIA Label'),
            'download' => t('Download'),
        ];

        $options = [];
        foreach ($fields as $field) {
            if (isset($labels[$field])) {
                $options[] = [
                    'label' => $labels[$field],
                    'value' => $field,
                    ...($field === 'rel' ? [
                        'labelHtml' => t('Relation ({ex})', ['ex' => '<code>rel</code>']),
                    ] : []),
                ];
            }
        }

        return $options;
    }

    /** @return list<Node> */
    protected function linkSettingsNodes(): array
    {
        $types = $this->orderedLinkSettingsTypes();
        $namespace = $this->linkSettingsNamespace();
        $prefix = $namespace === null ? '' : "{$namespace}.";
        $configured = $this->configuredLinkTypesForSettings();
        $typeSettings = $this->{$this->namespacedAttribute('typeSettings')};
        $nodes = [
            FormField::make(t('Allowed Link Types'))
                ->instructions(t('The link types that should be available when inserting links.'))
                ->required()
                ->control(Choice::make("{$prefix}types")
                    ->multiple()
                    ->presentation(ChoicePresentation::Checkboxes)
                    ->options($this->linkTypeOptions($types))
                    ->value($this->{$this->namespacedAttribute('types')})),
        ];

        foreach ($types as $typeId => $typeClass) {
            $linkType = $configured[$typeId] ?? ComponentHelper::createComponent([
                'type' => $typeClass,
                'settings' => $typeSettings[$typeId] ?? [],
            ], BaseLinkType::class);
            $settings = $linkType->settingsNodes("{$prefix}typeSettings.{$typeId}");

            if ($settings !== []) {
                $nodes[] = Group::make("link-type-settings-{$typeId}", $settings)
                    ->label($this->linkTypeSettingsLabel($typeClass))
                    ->collapsible();
            }
        }

        $nodes[] = FormField::make(t('Show the “Label” field'))
            ->control(Lightswitch::make("{$prefix}showLabelField")
                ->value($this->{$this->namespacedAttribute('showLabelField')}));
        $nodes[] = FormField::make(t('Advanced Fields'))
            ->instructions(t('Choose which advanced fields should be available when inserting links.'))
            ->control(Choice::make("{$prefix}advancedFields")
                ->multiple()
                ->presentation(ChoicePresentation::Checkboxes)
                ->options($this->linkAdvancedFieldOptions())
                ->value($this->{$this->namespacedAttribute('advancedFields')}));

        $this->groupLinkSettingsChanges($nodes);

        return $nodes;
    }

    /** @param list<Node> $nodes */
    private function groupLinkSettingsChanges(array $nodes): void
    {
        foreach ($nodes as $node) {
            $control = $node->getControl();
            if ($control instanceof Control) {
                $control->deltaGroupAtNamespace();
            }
            $this->groupLinkSettingsChanges($node->children());
        }
    }

    /** @return list<array<string, mixed>> */
    protected function linkPickerConfig(): array
    {
        $availableTypes = Link::types();
        $typeSettings = $this->{$this->namespacedAttribute('typeSettings')};

        return collect($this->{$this->namespacedAttribute('types')})
            ->filter(fn (string $typeId) => isset($availableTypes[$typeId]))
            ->map(function (string $typeId) use ($availableTypes, $typeSettings): array {
                /** @var BaseLinkType $linkType */
                $linkType = ComponentHelper::createComponent([
                    'type' => $availableTypes[$typeId],
                    'settings' => $typeSettings[$typeId] ?? [],
                ], BaseLinkType::class);

                return $linkType->pickerConfig();
            })
            ->values()
            ->all();
    }

    /** @return Collection<string, class-string<BaseLinkType>> */
    protected function orderedLinkSettingsTypes(): Collection
    {
        $allTypes = Link::types();
        /** @var Collection<string, class-string<BaseLinkType>> $selectedTypes */
        $selectedTypes = Collection::make();
        $allowedTypes = $this->{$this->namespacedAttribute('types')};

        foreach ($allTypes as $typeId => $type) {
            if (in_array($typeId, $allowedTypes, true)) {
                $selectedTypes[$typeId] = $type;
            }
        }

        /** @var Collection<string, class-string<BaseLinkType>> $remainingTypes */
        $remainingTypes = Collection::make();
        if ($selectedTypes->count() < count($allTypes)) {
            $remainingTypes = Collection::make($allTypes)
                ->reject(fn ($value, $key): bool => isset($selectedTypes[$key]))
                ->sort(function (string $a, string $b): int {
                    if ($a === UrlType::class) {
                        return -1;
                    }
                    if ($b === UrlType::class) {
                        return 1;
                    }

                    return $a::displayName() <=> $b::displayName();
                });
        }

        return $selectedTypes->merge($remainingTypes);
    }

    protected function linkSettingsNamespace(): ?string
    {
        return null;
    }

    /** @return array<string, BaseLinkType> */
    protected function configuredLinkTypesForSettings(): array
    {
        return [];
    }

    /**
     * @return ($attribute is 'types' ? 'types'|'linkSettingsTypes' :
     *     ($attribute is 'typeSettings' ? 'typeSettings'|'linkSettingsTypeSettings' :
     *     ($attribute is 'showLabelField' ? 'showLabelField'|'linkSettingsShowLabelField' :
     *     'advancedFields'|'linkSettingsAdvancedFields')))
     */
    private function namespacedAttribute(string $attribute): string
    {
        $namespace = $this->linkSettingsNamespace();

        return $namespace === null ? $attribute : $namespace.ucfirst($attribute);
    }

    /**
     * @param  iterable<string, class-string<BaseLinkType>>  $types
     * @return list<array{label:string, value:string}>
     */
    protected function linkTypeOptions(iterable $types): array
    {
        $options = [];
        foreach ($types as $type) {
            $options[] = [
                'label' => $type::displayName(),
                'value' => $type::id(),
            ];
        }

        return $options;
    }

    private function linkTypeSettingsLabel(string $type): string
    {
        return is_a($type, BaseElementLinkType::class, true)
            ? t('{type} Link settings', ['type' => $type::displayName()])
            : t('{type} settings', ['type' => $type::displayName()]);
    }
}
