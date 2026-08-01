<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Concerns;

use Closure;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Html;
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

    protected function linkSettingsProps(bool $readOnly): array
    {
        $types = $this->orderedLinkSettingsTypes();
        $namespace = $this->linkSettingsNamespace();
        $typeSettingsNamespace = $namespace === null
            ? 'typeSettings'
            : sprintf('%s[typeSettings]', $namespace);

        return [
            'advancedFieldOptions' => $this->linkAdvancedFieldOptions(),
            'advancedFields' => $this->{$this->namespacedAttribute('advancedFields')},
            'allowedTypes' => $this->{$this->namespacedAttribute('types')},
            'field' => $this,
            'linkTypeOptions' => $this->linkTypeOptions($types),
            'linkTypeSettings' => $this->linkTypeSettingsHtml(
                $types,
                $this->configuredLinkTypesForSettings(),
                $this->{$this->namespacedAttribute('typeSettings')},
                $readOnly,
                $typeSettingsNamespace,
            ),
            'namespace' => $namespace,
            'readOnly' => $readOnly,
            'showLabelField' => $this->{$this->namespacedAttribute('showLabelField')},
        ];
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

    protected function linkTypeSettingsHtml(
        iterable $types,
        array $configuredLinkTypes,
        array $typeSettings,
        bool $readOnly,
        string $namespace = 'typeSettings',
    ): array {
        $settings = [];

        foreach ($types as $typeId => $typeClass) {
            /** @var BaseLinkType $linkType */
            $linkType = $configuredLinkTypes[$typeId] ?? ComponentHelper::createComponent([
                'type' => $typeClass,
                'settings' => $typeSettings[$typeId] ?? [],
            ], BaseLinkType::class);
            $html = InputNamespace::namespaceInputs(
                fn () => $readOnly ? $linkType->getReadOnlySettingsHtml() : $linkType->getSettingsHtml(),
                sprintf('%s[%s]', $namespace, $typeId),
            );

            if ($html !== null && $html !== '') {
                $settings[$typeId] = [
                    'html' => Html::tag('craft-field-group', $html),
                    'label' => $this->linkTypeSettingsLabel($typeClass),
                ];
            }
        }

        return $settings;
    }

    private function linkTypeSettingsLabel(string $type): string
    {
        return is_a($type, BaseElementLinkType::class, true)
            ? t('{type} Link settings', ['type' => $type::displayName()])
            : t('{type} settings', ['type' => $type::displayName()]);
    }
}
