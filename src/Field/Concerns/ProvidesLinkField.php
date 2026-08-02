<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use Closure;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Cp\Components\CheckboxSelect;
use CraftCms\Cms\Cp\Components\Field;
use CraftCms\Cms\Cp\Components\Group;
use CraftCms\Cms\Cp\Components\Lightswitch;
use CraftCms\Cms\Cp\FormDefinitions\Condition;
use CraftCms\Cms\Cp\FormDefinitions\Contracts\ProjectableFormElement;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Template;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

trait ProvidesLinkField
{
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

                if (array_key_exists($setting, $settings) && ! array_key_exists((string) $attribute, $config)) {
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

    protected function linkAdvancedFieldOptions(?array $fields = null): array
    {
        $fields ??= $this->supportedLinkAdvancedFields();
        $labels = [
            'urlSuffix' => t('URL Suffix'),
            'target' => t('Target'),
            'title' => t('Title Text'),
            'class' => t('Class Name'),
            'id' => t('ID'),
            'rel' => Template::raw(t('Relation ({ex})', ['ex' => '<code>rel</code>'])),
            'ariaLabel' => t('ARIA Label'),
            'download' => t('Download'),
        ];

        $options = [];
        foreach ($fields as $field) {
            if (isset($labels[$field])) {
                $options[] = [
                    'label' => $labels[$field],
                    'value' => $field,
                ];
            }
        }

        return $options;
    }

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

    /** @return list<ProjectableFormElement> */
    protected function linkSettingsFormElements(bool $readOnly): array
    {
        $types = $this->orderedLinkSettingsTypes();
        $configuredTypes = $this->configuredLinkTypesForSettings();
        $typeSettings = $this->{$this->namespacedAttribute('typeSettings')};
        $elements = [];

        foreach ($types as $typeId => $typeClass) {
            $linkType = $configuredTypes[$typeId] ?? ComponentHelper::createComponent([
                'type' => $typeClass,
                'settings' => $typeSettings[$typeId] ?? [],
            ], BaseLinkType::class);
            $definition = $linkType->getSettingsFormDefinition($readOnly);

            if ($definition !== null) {
                $elements[] = Group::fromDefinition($definition, "typeSettings.{$typeId}")
                    ->key("link-type:{$typeId}")
                    ->visibleWhen(Condition::contains('types', $typeId));
            }
        }

        $advancedFields = $this->{$this->namespacedAttribute('advancedFields')};
        $advancedFields = [
            ...$advancedFields,
            ...array_values(array_diff($this->supportedLinkAdvancedFields(), $advancedFields)),
        ];
        $advancedFieldOptions = array_map(
            fn (array $option): array => [
                ...$option,
                'label' => strip_tags((string) $option['label']),
            ],
            $this->linkAdvancedFieldOptions($advancedFields),
        );

        return [
            Field::make(CheckboxSelect::make()
                ->name('types')
                ->options($this->linkTypeOptions($types))
                ->sortable())
                ->label(t('Allowed Link Types'))
                ->instructions(t('The link types that should be available when inserting links.'))
                ->required()
                ->readOnly($readOnly),
            ...$elements,
            Field::make(Lightswitch::make()->name('showLabelField'))
                ->label(t('Show the “Label” field'))
                ->readOnly($readOnly),
            Field::make(CheckboxSelect::make()
                ->name('advancedFields')
                ->options($advancedFieldOptions)
                ->sortable())
                ->label(t('Advanced Fields'))
                ->instructions(t('Choose which advanced fields should be available when inserting links.'))
                ->readOnly($readOnly),
        ];
    }

    protected function orderedLinkSettingsTypes(): iterable
    {
        $allTypes = Link::types();
        /** @var Collection<string, class-string<BaseLinkType>> $selectedTypes */
        $selectedTypes = Collection::make();
        $allowedTypes = $this->{$this->namespacedAttribute('types')};

        foreach ($allowedTypes as $typeId) {
            if (isset($allTypes[$typeId])) {
                $selectedTypes[$typeId] = $allTypes[$typeId];
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

    protected function configuredLinkTypesForSettings(): array
    {
        return [];
    }

    private function namespacedAttribute(string $attribute): string
    {
        $namespace = $this->linkSettingsNamespace();

        return $namespace === null ? $attribute : $namespace.ucfirst($attribute);
    }

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
}
