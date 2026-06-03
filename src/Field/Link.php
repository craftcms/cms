<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Concerns\ProvidesLinkField;
use CraftCms\Cms\Field\Conditions\LinkFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Events\LinkTypesResolving;
use CraftCms\Cms\Field\LinkTypes\Asset;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseTextLinkType;
use CraftCms\Cms\Field\LinkTypes\Email as EmailType;
use CraftCms\Cms\Field\LinkTypes\Entry;
use CraftCms\Cms\Field\LinkTypes\Phone;
use CraftCms\Cms\Field\LinkTypes\Sms;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Gql\GqlEntityRegistry;
use CraftCms\Cms\Gql\Types\Generators\LinkDataType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * Link represents a Link field.
 */
class Link extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, TracksReferencesFieldInterface
{
    use ProvidesLinkField;

    private static array $_types;

    #[Override]
    public static function displayName(): string
    {
        return t('Link');
    }

    #[Override]
    public static function icon(): string
    {
        return 'link';
    }

    #[Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', LinkData::class);
    }

    #[Override]
    public static function dbType(): array
    {
        return [
            'value' => Query::TYPE_STRING,
            'type' => Query::TYPE_STRING,
            'label' => Query::TYPE_STRING,
            'urlSuffix' => Query::TYPE_STRING,
            'target' => Query::TYPE_STRING,
            'title' => Query::TYPE_STRING,
            'class' => Query::TYPE_STRING,
            'id' => Query::TYPE_STRING,
            'rel' => Query::TYPE_STRING,
            'ariaLabel' => Query::TYPE_STRING,
            'download' => Query::TYPE_BOOLEAN,
            'filename' => Query::TYPE_STRING,
        ];
    }

    /**
     * @return array<string,class-string<BaseLinkType>>
     */
    public static function types(): array
    {
        if (! isset(self::$_types)) {
            /** @var class-string<BaseLinkType>[] $types */
            $types = [
                Asset::class,
                EmailType::class,
                Entry::class,
                Phone::class,
                Sms::class,
            ];

            // Fire a registerLinkTypes event
            event($event = new LinkTypesResolving($types));

            $types = $event->types;

            // URL *has* to be there
            /** @var class-string<BaseLinkType>[] $types */
            $types[] = UrlType::class;

            self::$_types = array_combine(
                array_map(fn (string $type) => $type::id(), $types),
                $types,
            );
        }

        return self::$_types;
    }

    /**
     * @var bool Whether the Label field should be shown.
     */
    public bool $showLabelField = false;

    /**
     * @var string[] Attribute fields to show.
     *
     * @phpstan-var array<'urlSuffix'|'target'|'title'|'class'|'id'|'rel'|'ariaLabel'|'download'>
     */
    public array $advancedFields = [];

    /**
     * @var array<string,BaseLinkType>
     *
     * @see getLinkTypes()
     */
    private array $_linkTypes;

    /**
     * @var string[] Allowed link types
     */
    public array $types = [
        'entry',
        'url',
    ];

    /**
     * @var array<string,array> Settings for the allowed types
     */
    public array $typeSettings = [];

    /**
     * @var int The maximum length (in bytes) the field can hold
     */
    public int $maxLength = 255;

    /**
     * @var bool Whether GraphQL values should be returned as objects with `type`,
     *           `value`, `label`, `urlSuffix`, and `url` keys.
     */
    public bool $fullGraphqlData = true;

    public function __construct($config = [])
    {
        $config = $this->prepareLegacyAdvancedFieldConfig($config);
        $config = $this->prepareLinkSettingsConfig($config);

        if (array_key_exists('placeholder', $config)) {
            unset($config['placeholder']);
        }

        if (isset($config['graphqlMode'])) {
            $config['fullGraphqlData'] = Arr::pull($config, 'graphqlMode') === 'full';
        }

        // Default fullGraphqlData to false for existing fields
        if (isset($config['id']) && ! isset($config['fullGraphqlData'])) {
            $config['fullGraphqlData'] = false;
        }

        parent::__construct($config);
    }

    #[Override]
    public function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'maxLength' => ['required', 'integer', 'min:10'],
        ], $this->linkSettingsRules());
    }

    /**
     * Returns the link types available to the field.
     *
     * @return array<string,BaseLinkType>
     */
    public function getLinkTypes(): array
    {
        if (! isset($this->_linkTypes)) {
            $this->_linkTypes = [];
            $types = self::types();

            foreach ($this->types as $typeId) {
                if (isset($types[$typeId])) {
                    $this->_linkTypes[$typeId] = ComponentHelper::createComponent([
                        'type' => $types[$typeId],
                        'settings' => $this->typeSettings[$typeId] ?? [],
                    ], BaseLinkType::class);
                }
            }
        }

        return $this->_linkTypes;
    }

    private function resolveType(string $value): string
    {
        $linkTypes = $this->getLinkTypes();

        // check URL last, if it's selected
        $urlType = Arr::pull($linkTypes, UrlType::id());
        if ($urlType) {
            $linkTypes[UrlType::id()] = $urlType;
        }

        foreach ($linkTypes as $id => $linkType) {
            if ($linkType->supports($value)) {
                return $id;
            }
        }

        // See if any unselected types support it
        foreach (self::types() as $typeId => $type) {
            if (! isset($linkTypes[$typeId]) && $type !== UrlType::class) {
                $linkType = ComponentHelper::createComponent($type, BaseLinkType::class);
                if ($linkType->supports($value)) {
                    return $linkType::id();
                }
            }
        }

        return UrlType::id();
    }

    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    #[Override]
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        $html = template('_components/fieldtypes/Link/link-settings', $this->linkSettingsProps($readOnly));

        $html .=
            Html::tag('hr').
            Html::button(t('Advanced'), attributes: [
                'class' => 'fieldtoggle',
                'data' => ['target' => 'advanced'],
            ]).
            Html::beginTag('div', [
                'id' => 'advanced',
                'class' => 'hidden',
            ]).
            FormFields::textFieldHtml([
                'label' => t('Max Length'),
                'instructions' => t('The maximum length (in bytes) the field can hold.'),
                'id' => 'maxLength',
                'name' => 'maxLength',
                'type' => 'number',
                'min' => '10',
                'step' => '10',
                'value' => $this->maxLength,
                'errors' => $this->errors()->get('maxLength'),
                'data' => ['error-key' => 'maxLength'],
                'disabled' => $readOnly,
            ]);

        if (Cms::config()->enableGql) {
            $html .=
                FormFields::selectFieldHtml([
                    'label' => t('GraphQL Mode'),
                    'id' => 'graphql-mode',
                    'name' => 'graphqlMode',
                    'options' => [
                        ['label' => t('Full data'), 'value' => 'full'],
                        ['label' => t('URL only'), 'value' => 'url'],
                    ],
                    'value' => $this->fullGraphqlData ? 'full' : 'url',
                    'disabled' => $readOnly,
                ]);
        }

        return $html.Html::endTag('div');
    }

    private function prepareLegacyAdvancedFieldConfig(array $config): array
    {
        $config['advancedFields'] ??= [];

        if (Arr::pull($config, 'showTargetField') === true) {
            $config['advancedFields'][] = 'target';
        }

        if (Arr::pull($config, 'showUrlSuffixField') === true) {
            $config['advancedFields'][] = 'urlSuffix';
        }

        return $config;
    }

    protected function configuredLinkTypesForSettings(): array
    {
        return $this->getLinkTypes();
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): ?LinkData
    {
        // if this was set due to propagateAll for a fresh element (as opposed to the translation method),
        // and an element is selected, swap it with the same element in the current site (if it exists);
        // this flow will kick in for all cases apart from nested entries when field uses "none" propagation method
        if (
            $value instanceof LinkData &&
            $element?->propagating &&
            ($element->propagateAll || ($element->isNewForSite && ! isset($element->duplicateOf))) &&
            isset($element->propagatingFrom)
        ) {
            // in order to avoid infinite loop when using custom translation format with a translation key containing `include()`
            // we need to prevent `View::renderObjectTemplate()` from trying to normalize this value again and again
            // to do that, we can e.g. set `propagating` to false before getting the translation key
            // see https://github.com/craftcms/cms/issues/18363 for more details
            if ($this->translationMethod === TranslationMethod::Custom->value) {
                $element->propagating = false;
            }

            if ($this->getTranslationKey($element) !== $this->getTranslationKey($element->propagatingFrom)) {
                $value = $this->localizeLinkValue($value, $element);
            }

            // set $propagating back to true
            $element->propagating = true;
        }

        // as above but specifically for nested entries when field uses "none" propagation method
        if (
            $value instanceof LinkData &&
            ! $element?->propagating &&
            isset($element->duplicateOf) &&
            ($element->propagateAll || $element->isNewForSite)
        ) {
            if ($this->getTranslationKey($element) !== $this->getTranslationKey($element->duplicateOf)) {
                $value = $this->localizeLinkValue($value, $element);
            }
        }

        if ($value instanceof LinkData) {
            return $value;
        }

        $linkTypes = $this->getLinkTypes();
        $linkType = null;
        $config = [
            'value' => $value,
        ];

        if (is_array($value)) {
            $typeId = $value['type'] ?? UrlType::id();
            $config = array_filter([
                'label' => (! empty($value['label']) && $this->showLabelField) ? $value['label'] : null,
                'urlSuffix' => (! empty($value['urlSuffix']) && in_array('urlSuffix', $this->advancedFields)) ? $value['urlSuffix'] : null,
                'target' => (! empty($value['target']) && in_array('target', $this->advancedFields)) ? $value['target'] : null,
                'title' => (! empty($value['title']) && in_array('title', $this->advancedFields)) ? $value['title'] : null,
                'class' => (! empty($value['class']) && in_array('class', $this->advancedFields))
                    ? (implode(' ', array_map(Html::id(...), explode(' ', (string) $value['class']))))
                    : null,
                'id' => (! empty($value['id']) && in_array('id', $this->advancedFields)) ? Html::id($value['id']) : null,
                'rel' => (! empty($value['rel']) && in_array('rel', $this->advancedFields))
                    ? (implode(' ', array_map(Html::id(...), explode(' ', (string) $value['rel']))))
                    : null,
                'ariaLabel' => (! empty($value['ariaLabel']) && in_array('ariaLabel', $this->advancedFields)) ? $value['ariaLabel'] : null,
                'download' => (! empty($value['download']) && in_array('download', $this->advancedFields)) ? (bool) $value['download'] : null,
                'filename' => (! empty($value['filename']) && in_array('download', $this->advancedFields)) ? $value['filename'] : null,
            ]);

            $value = $value['value'] ?? $value[$typeId]['value'] ?? '';

            if (is_string($value)) {
                $value = trim($value);
            }

            if (! $value) {
                return null;
            }

            if (isset($config['urlSuffix']) && ! str_starts_with((string) $config['urlSuffix'], '#')) {
                $config['urlSuffix'] = Str::start($config['urlSuffix'], '?');
            }

            if (isset($linkTypes[$typeId])) {
                $linkType = $linkTypes[$typeId];
            } else {
                $type = self::types()[$typeId] ?? null;
                if (! $type) {
                    throw new InvalidArgumentException("Invalid link type: $typeId");
                }
                $linkType = ComponentHelper::createComponent($type, BaseLinkType::class);
            }

            $config['value'] = $linkType->normalizeValue($value);
        } else {
            if (! $value) {
                return null;
            }

            $typeId = $this->resolveType($value);
            $linkType = $linkTypes[$typeId] ?? ComponentHelper::createComponent(self::types()[$typeId], BaseLinkType::class);
        }

        return new LinkData($config['value'], $linkType);
    }

    /**
     * Localize the value of the link field when linking to an element.
     */
    private function localizeLinkValue(LinkData $value, ElementInterface $element): LinkData|array
    {
        $linkedElement = $value->getElement();
        if ($linkedElement && $linkedElement::isLocalized()) {
            $localizedQuery = $linkedElement->getLocalized();
            if (
                $localizedQuery instanceof ElementQueryInterface &&
                $localizedQuery->siteId($element->siteId)->exists()
            ) {
                $type = $value->getType();

                return [
                    'type' => $type,
                    'value' => sprintf('{%s:%s@%s:url}', $linkedElement::refHandle(), $linkedElement->id, $element->siteId),
                ];
            }
        }

        return $value;
    }

    #[Override]
    public function useFieldset(): bool
    {
        return true;
    }

    #[Override]
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $linkTypes = $this->getLinkTypes();
        $valueTypeId = null;

        /** @var LinkData|null $value */
        if ($value) {
            $valueTypeId = $value->getType();

            if (! isset($linkTypes[$valueTypeId])) {
                $type = self::types()[$valueTypeId] ?? null;
                if ($type) {
                    $linkTypes[$valueTypeId] = ComponentHelper::createComponent($type, BaseLinkType::class);
                } else {
                    $value = null;
                }
            }
        }

        if (! $value) {
            $valueTypeId = reset($this->types);
        }

        $id = $this->getInputId();

        HtmlStack::jsWithVars(fn ($id) => <<<JS
new Craft.LinkField($('#' + $id));
JS, [
            InputNamespace::namespaceId($id),
        ]);

        $typeInputName = "$this->handle[type]";

        if (count($linkTypes) === 1) {
            $innerHtml = Html::hiddenInput($typeInputName, $valueTypeId);
        } else {
            $namespacedId = InputNamespace::namespaceId($id);
            $js = <<<JS
$('#$namespacedId-type').on('change', e => {
  const type = $('#$namespacedId-type').val();
  $('#$namespacedId')
    .attr('type', type)
    .attr('inputmode', type);
});
JS;
            HtmlStack::js($js);

            $innerHtml = FormFields::selectHtml([
                'id' => "$id-type",
                'describedBy' => $this->describedBy,
                'name' => $typeInputName,
                'options' => array_map(fn (BaseLinkType $linkType) => [
                    'label' => $linkType::displayName(),
                    'value' => $linkType::id(),
                ], $linkTypes),
                'value' => $valueTypeId,
                'inputAttributes' => [
                    'aria' => [
                        'label' => t('URL type'),
                    ],
                ],
                'toggle' => true,
                'targetPrefix' => "$id-",
            ]);
        }

        foreach ($linkTypes as $typeId => $linkType) {
            $containerId = "$id-$typeId";
            $nsContainerId = InputNamespace::namespaceId($containerId);
            $selected = $typeId === $valueTypeId;
            $typeValue = $selected ? $value?->serialize()['value'] : null;
            $isTextLink = is_subclass_of($linkType, BaseTextLinkType::class);
            $innerHtml .=
                Html::beginTag('div', [
                    'id' => $containerId,
                    'class' => array_keys(array_filter([
                        'flex-grow' => true,
                        'hidden' => ! $selected,
                        'text-link' => $isTextLink,
                    ])),
                    'data' => ['link-type' => $typeId],
                ]).
                InputNamespace::namespaceInputs(
                    fn () => $linkType->inputHtml($this, $typeValue, $nsContainerId),
                    "$this->handle[$typeId]",
                ).
                Html::endTag('div');
        }

        $pane = $this->showLabelField || ! empty($this->advancedFields);
        $html =
            Html::beginTag('div', [
                'id' => $id,
                'class' => $pane ? ['pane', 'hairline', 'padding-m'] : null,
            ]).
            Html::beginTag('div', [
                'class' => 'link-input',
                'data' => ['link-field' => true],
            ]).
            Html::tag('div', $innerHtml, [
                'class' => ['flex', 'flex-nowrap'],
            ]).
            Html::endTag('div');

        if ($this->showLabelField) {
            $html .= FormFields::textFieldHtml([
                'fieldClass' => 'my-m',
                'fieldAttributes' => [
                    'data' => ['label-field' => true],
                ],
                'label' => t('Label'),
                'id' => "$id-label",
                'name' => "$this->handle[label]",
                'value' => $value?->getLabel(true),
                'placeholder' => $value?->getLabel(false),
            ]);
        }

        if (! empty($this->advancedFields)) {
            $html .=
                Html::button(t('Advanced'), attributes: [
                    'class' => ['fieldtoggle', 'mb-0'],
                    'data' => ['target' => "$id-advanced"],
                ]).
                Html::beginTag('div', [
                    'id' => "$id-advanced",
                    'class' => ['hidden', 'meta', 'pane', 'hairline'],
                ]);

            foreach ($this->advancedFields as $field) {
                $html .= match ($field) {
                    'urlSuffix' => FormFields::textFieldHtml([
                        'fieldClass' => 'info-icon-instructions',
                        'label' => t('URL Suffix'),
                        'instructions' => t('Query params (e.g. {ex1}) or a URI fragment (e.g. {ex2}) that should be appended to the URL.', [
                            'ex1' => '`?p1=foo&p2=bar`',
                            'ex2' => '`#anchor`',
                        ]),
                        'id' => "$id-url-suffix",
                        'name' => "$this->handle[urlSuffix]",
                        'value' => $value?->urlSuffix,
                    ]),
                    'target' => FormFields::lightswitchFieldHtml([
                        'label' => t('Open in a new tab'),
                        'id' => "$id-target",
                        'name' => "$this->handle[target]",
                        'on' => $value?->target,
                        'value' => '_blank',
                    ]),
                    'title' => FormFields::textFieldHtml([
                        'label' => t('Title Text'),
                        'id' => "$id-title",
                        'name' => "$this->handle[title]",
                        'value' => $value?->title,
                    ]),
                    'class' => FormFields::textFieldHtml([
                        'fieldClass' => 'info-icon-instructions',
                        'class' => 'code',
                        'label' => t('Class Name'),
                        'instructions' => t('Separate multiple values with spaces.'),
                        'id' => "$id-class",
                        'name' => "$this->handle[class]",
                        'value' => $value?->class,
                    ]),
                    'id' => FormFields::textFieldHtml([
                        'class' => 'code',
                        'label' => t('ID'),
                        'id' => "$id-id",
                        'name' => "$this->handle[id]",
                        'value' => $value?->id,
                    ]),
                    'rel' => FormFields::textFieldHtml([
                        'fieldClass' => 'info-icon-instructions',
                        'class' => 'code',
                        'label' => t('Relation ({ex})', ['ex' => '<code>rel</code>']),
                        'instructions' => t('Separate multiple values with spaces.'),
                        'id' => "$id-rel",
                        'name' => "$this->handle[rel]",
                        'value' => $value?->rel,
                    ]),
                    'ariaLabel' => FormFields::textFieldHtml([
                        'label' => t('ARIA Label'),
                        'id' => "$id-aria-label",
                        'name' => "$this->handle[ariaLabel]",
                        'value' => $value?->ariaLabel,
                    ]),
                    'download' => FormFields::lightswitchFieldHtml([
                        'label' => t('Download'),
                        'id' => "$id-download",
                        'name' => "$this->handle[download]",
                        'on' => $value?->download,
                        'toggle' => "$id-filename-field",
                    ]).
                        FormFields::textFieldHtml([
                            'fieldClass' => ! $value?->download ? 'hidden' : null,
                            'fieldAttributes' => [
                                'data' => ['filename-field' => true],
                            ],
                            'label' => t('Filename'),
                            'id' => "$id-filename",
                            'name' => "$this->handle[filename]",
                            'value' => $value?->getFilename(),
                            'placeholder' => $value?->getFilename(false),
                        ]),
                };
            }

            $html .= Html::endTag('div');
        }

        return $html.Html::endTag('div');
    }

    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            function (string $attribute, LinkData $value, Closure $fail, Validator $validator) {
                $linkTypes = $this->getLinkTypes();

                if (! isset($linkTypes[$value->getType()])) {
                    $type = self::types()[$value->getType()] ?? null;
                    $fail(t('{attribute} no longer allows {type} links.', [
                        'attribute' => $this->getUiLabel(),
                        'type' => is_subclass_of($type, BaseLinkType::class) ? $type::displayName() : $type,
                    ]));

                    return;
                }

                $linkType = $linkTypes[$value->getType()];
                $value = $value->serialize()['value'];
                $error = null;
                if (! $linkType->validateValue($value, $error)) {
                    /** @var string|null $error */
                    $fail($error ?? t('{attribute} is invalid.', [
                        'attribute' => $this->getUiLabel(),
                    ]));

                    return;
                }

                if (! $validator->validateMax($attribute, $value, [$this->maxLength])) {
                    $fail(t('{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.', [
                        'attribute' => $this->getUiLabel(),
                        'max' => $this->maxLength,
                    ]));
                }
            },
        ];
    }

    #[Override]
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        if (parent::isValueEmpty($value, $element)) {
            return true;
        }

        /** @var LinkData $value */
        $value = $element->getFieldValue($this->handle);
        $linkTypes = $this->getLinkTypes();
        $linkType = $linkTypes[$value->getType()] ?? $linkTypes[UrlType::id()] ?? new UrlType;
        $value = $value->serialize()['value'];

        return $linkType->isValueEmpty($value);
    }

    public function getElementConditionRuleType(): string
    {
        return LinkFieldConditionRule::class;
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var LinkData|null $value */
        return $value?->getLink() ?? '';
    }

    #[Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $url = Sites::getPrimarySite()->getBaseUrl() ?? 'https://craftcms.com/';
            $value = new LinkData($url, new UrlType);
        }

        return $this->getPreviewHtml($value, new EntryElement);
    }

    #[Override]
    public function getContentGqlType(): Type|array
    {
        if (! $this->fullGraphqlData) {
            return parent::getContentGqlType();
        }

        return LinkDataType::generateType($this);
    }

    #[Override]
    public function getContentGqlMutationArgumentType(): Type|array
    {
        if (! $this->fullGraphqlData) {
            return parent::getContentGqlMutationArgumentType();
        }

        $typeName = 'LinkDataInput';

        return GqlEntityRegistry::getOrCreate($typeName, fn () => new InputObjectType([
            'name' => $typeName,
            'fields' => [
                'type' => Type::string(),
                'value' => Type::string(),
                'label' => Type::string(),
                'urlSuffix' => Type::string(),
            ],
        ]));
    }

    public function getReferenceTargetIds(ElementInterface $element): array
    {
        $targetIds = [];
        /** @var LinkData|null $value */
        $value = $element->getFieldValue($this->handle);
        $element = $value?->getElement();
        if ($element) {
            $targetIds[] = $element->id;
        }

        return $targetIds;
    }

    public function replaceReferences(ElementInterface $element, array $oldTargetIds, int $newTargetId): bool
    {
        /** @var LinkData|null $value */
        $value = $element->getFieldValue($this->handle);
        $element = $value?->getElement();

        if (in_array(! $element?->id, $oldTargetIds)) {
            return false;
        }

        $element->setFieldvalue($this->handle, [
            'type' => $value->getType(),
            'value' => sprintf('{%s:%s@%s:url}', $element::refHandle(), $newTargetId, $element->siteId),
        ]);

        return true;
    }
}
