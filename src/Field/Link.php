<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Craft;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry as EntryElement;
use craft\fields\conditions\LinkFieldConditionRule;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\generators\LinkDataType;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\Template;
use craft\validators\StringValidator;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Concerns\RelationalField;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\RelationalFieldInterface;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\Events\RegisterLinkTypes;
use CraftCms\Cms\Field\LinkTypes\Asset;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseTextLinkType;
use CraftCms\Cms\Field\LinkTypes\Category;
use CraftCms\Cms\Field\LinkTypes\Email as EmailType;
use CraftCms\Cms\Field\LinkTypes\Entry;
use CraftCms\Cms\Field\LinkTypes\Phone;
use CraftCms\Cms\Field\LinkTypes\Sms;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use yii\base\InvalidArgumentException;
use yii\db\Schema;

use function CraftCms\Cms\t;

/**
 * Link represents a Link field.
 */
final class Link extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, RelationalFieldInterface
{
    use RelationalField;

    private static array $_types;

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return t('Link');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'link';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function phpType(): string
    {
        return sprintf('\\%s|null', LinkData::class);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function dbType(): array
    {
        return [
            'value' => Schema::TYPE_STRING,
            'type' => Schema::TYPE_STRING,
            'label' => Schema::TYPE_STRING,
            'urlSuffix' => Schema::TYPE_STRING,
            'target' => Schema::TYPE_STRING,
            'title' => Schema::TYPE_STRING,
            'class' => Schema::TYPE_STRING,
            'id' => Schema::TYPE_STRING,
            'rel' => Schema::TYPE_STRING,
            'ariaLabel' => Schema::TYPE_STRING,
            'download' => Schema::TYPE_BOOLEAN,
            'filename' => Schema::TYPE_STRING,
        ];
    }

    /**
     * @return array<string,class-string<BaseLinkType>>
     */
    private static function types(): array
    {
        if (! isset(self::$_types)) {
            /** @var class-string<BaseLinkType>[] $types */
            $types = [
                Asset::class,
                Category::class,
                EmailType::class,
                Entry::class,
                Phone::class,
                Sms::class,
            ];

            // Fire a registerLinkTypes event
            if (Event::hasListeners(RegisterLinkTypes::class)) {
                Event::dispatch($event = new RegisterLinkTypes($types));

                return $event->types;
            }

            // URL *has* to be there
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

    /**
     * {@inheritdoc}
     */
    public function __construct($config = [])
    {
        if (isset($config['types'], $config['typeSettings'])) {
            // Filter out any unneeded type settings
            foreach (array_keys($config['typeSettings']) as $typeId) {
                if (! in_array($typeId, $config['types'])) {
                    unset($config['typeSettings'][$typeId]);
                }
            }
        }

        if (array_key_exists('placeholder', $config)) {
            unset($config['placeholder']);
        }

        $config['advancedFields'] ??= [];

        if (isset($config['showTargetField'])) {
            if ($config['showTargetField'] === true) {
                $config['advancedFields'][] = 'target';
            }
            unset($config['showTargetField']);
        }

        if (isset($config['showUrlSuffixField'])) {
            if ($config['showUrlSuffixField'] === true) {
                $config['advancedFields'][] = 'urlSuffix';
            }
            unset($config['showUrlSuffixField']);
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

    #[\Override]
    public static function getRules(): array
    {
        return array_merge(parent::getRules(), [
            'types' => ['required', 'array'],
            'maxLength' => ['required', 'integer', 'min:10'],
        ]);
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
                    $this->_linkTypes[$typeId] = Component::createComponent([
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
                $linkType = Component::createComponent($type, BaseLinkType::class);
                if ($linkType->supports($value)) {
                    return $linkType::id();
                }
            }
        }

        return UrlType::id();
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsHtml(): string
    {
        return $this->settingsHtml(false);
    }

    /**
     * {@inheritdoc}
     */
    public function getReadOnlySettingsHtml(): string
    {
        return $this->settingsHtml(true);
    }

    private function settingsHtml(bool $readOnly): string
    {
        // Sort types by the order from the config and if anything remains by the label, with URL at the top
        // get only the selected types
        /** @var Collection<string,class-string<BaseLinkType>> $selectedTypes */
        $selectedTypes = Collection::make();
        foreach (self::types() as $typeId => $type) {
            if (in_array($typeId, $this->types)) {
                $selectedTypes[$typeId] = $type;
            }
        }

        // now get the remaining types (if there are any)
        $remainingTypes = Collection::make();
        if ($selectedTypes->count() < count(self::types())) {
            $remainingTypes = Collection::make(self::types())
                ->reject(fn ($value, $key): bool => isset($selectedTypes[$key]))
                // and sort them by label, with URL at the top
                ->sort(function (string $a, string $b) {
                    /** @var class-string<BaseLinkType> $a */
                    /** @var class-string<BaseLinkType> $b */
                    if ($a === UrlType::class) {
                        return -1;
                    }
                    if ($b === UrlType::class) {
                        return 1;
                    }

                    return $a::displayName() <=> $b::displayName();
                });
        }

        // combine both array of types
        $types = $selectedTypes->merge($remainingTypes);

        $linkTypeOptions = $types->map(fn (string $type) => [
            'label' => $type::displayName(),
            'value' => $type::id(),
        ])->all();

        $html = Cp::checkboxSelectFieldHtml([
            'label' => t('Allowed Link Types'),
            'id' => 'types',
            'fieldClass' => 'mb-0',
            'name' => 'types',
            'options' => $linkTypeOptions,
            'values' => $this->types,
            'required' => true,
            'targetPrefix' => 'types-',
            'sortable' => true,
            'disabled' => $readOnly,
        ]);

        $linkTypes = $this->getLinkTypes();
        $view = Craft::$app->getView();

        foreach ($types->all() as $typeId => $typeClass) {
            /** @var BaseLinkType $linkType */
            $linkType = $linkTypes[$typeId] ?? Component::createComponent($typeClass, BaseLinkType::class);
            $typeSettingsHtml = $view->namespaceInputs(
                fn () => $readOnly ? $linkType->getReadOnlySettingsHtml() : $linkType->getSettingsHtml(),
                "typeSettings[$typeId]",
            );
            if ($typeSettingsHtml) {
                $html .=
                    Html::beginTag('div', [
                        'id' => "types-$typeId",
                        'class' => array_keys(array_filter([
                            'pt-xl' => true,
                            'hidden' => ! isset($linkTypes[$typeId]),
                        ])),
                    ]).
                    Html::tag('hr').
                    $typeSettingsHtml.
                    Html::endTag('div');
            }
        }

        $html .=
            Html::tag('hr').
            Cp::lightswitchFieldHtml([
                'label' => t('Show the “Label” field'),
                'id' => 'show-label-field',
                'name' => 'showLabelField',
                'on' => $this->showLabelField,
                'disabled' => $readOnly,
            ]).
            Cp::checkboxSelectFieldHtml([
                'label' => t('Advanced Fields'),
                'id' => 'attribute-fields',
                'name' => 'advancedFields',
                'options' => [
                    ['label' => t('URL Suffix'), 'value' => 'urlSuffix'],
                    ['label' => t('Target'), 'value' => 'target'],
                    ['label' => t('Title Text'), 'value' => 'title'],
                    ['label' => t('Class Name'), 'value' => 'class'],
                    ['label' => t('ID'), 'value' => 'id'],
                    ['label' => Template::raw(t('Relation ({ex})', ['ex' => '<code>rel</code>'])), 'value' => 'rel'],
                    ['label' => t('ARIA Label'), 'value' => 'ariaLabel'],
                    ['label' => t('Download'), 'value' => 'download'],
                ],
                'values' => $this->advancedFields,
                'sortable' => true,
                'disabled' => $readOnly,
            ]).
            Html::tag('hr').
            Html::button(t('Advanced'), attributes: [
                'class' => 'fieldtoggle',
                'data' => ['target' => 'advanced'],
            ]).
            Html::beginTag('div', [
                'id' => 'advanced',
                'class' => 'hidden',
            ]).
            Cp::textFieldHtml([
                'label' => t('Max Length'),
                'instructions' => t('The maximum length (in bytes) the field can hold.'),
                'id' => 'maxLength',
                'name' => 'maxLength',
                'type' => 'number',
                'min' => '10',
                'step' => '10',
                'value' => $this->maxLength,
                'errors' => $this->getErrors('maxLength'),
                'data' => ['error-key' => 'maxLength'],
                'disabled' => $readOnly,
            ]);

        if (Cms::config()->enableGql) {
            $html .=
                Cp::selectFieldHtml([
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

        $html .= Html::endTag('div');

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): ?LinkData
    {
        // if this was set due to propagateAll for a fresh element (as opposed to the translation method),
        // and an element is selected, swap it with the same element in the current site (if it exists)
        if (
            $value instanceof LinkData &&
            $element?->propagating &&
            ($element->propagateAll || ($element->isNewForSite && ! isset($element->duplicateOf))) &&
            isset($element->propagatingFrom) &&
            $this->getTranslationKey($element) !== $this->getTranslationKey($element->propagatingFrom)
        ) {
            $linkedElement = $value->getElement();
            if ($linkedElement && $linkedElement::isLocalized()) {
                $localizedQuery = $linkedElement->getLocalized();
                if (
                    $localizedQuery instanceof ElementQueryInterface &&
                    $localizedQuery->siteId($element->siteId)->exists()
                ) {
                    $type = $value->getType();
                    $value = [
                        'type' => $type,
                        'value' => sprintf('{%s:%s@%s:url}', $linkedElement::refHandle(), $linkedElement->id, $element->siteId),
                    ];
                }
            }
        }

        if ($value instanceof LinkData) {
            return $value;
        }

        $linkTypes = $this->getLinkTypes();
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
                    ? (implode(' ', array_map(fn (string $class) => Html::id($class), explode(' ', (string) $value['class']))))
                    : null,
                'id' => (! empty($value['id']) && in_array('id', $this->advancedFields)) ? Html::id($value['id']) : null,
                'rel' => (! empty($value['rel']) && in_array('rel', $this->advancedFields))
                    ? (implode(' ', array_map(fn (string $rel) => Html::id($rel), explode(' ', (string) $value['rel']))))
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
                throw_unless($type, new InvalidArgumentException("Invalid link type: $typeId"));
                $linkType = Component::createComponent($type, BaseLinkType::class);
            }

            $config['value'] = $linkType->normalizeValue($value);
        } else {
            if (! $value) {
                return null;
            }

            $typeId = $this->resolveType($value);
            $config['linkType'] = $linkTypes[$typeId] ?? Component::createComponent(self::types()[$typeId], BaseLinkType::class);
        }

        return LinkData::from($config);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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
                    $linkTypes[$valueTypeId] = Component::createComponent($type, BaseLinkType::class);
                } else {
                    $value = null;
                }
            }
        }

        if (! $value) {
            $valueTypeId = reset($this->types);
        }

        $id = $this->getInputId();
        $view = Craft::$app->getView();

        $view->registerJsWithVars(fn ($id) => <<<JS
new Craft.LinkField($('#' + $id));
JS, [
            $view->namespaceInputId($id),
        ]);

        $typeInputName = "$this->handle[type]";

        if (count($linkTypes) === 1) {
            $innerHtml = Html::hiddenInput($typeInputName, $valueTypeId);
        } else {
            $namespacedId = $view->namespaceInputId($id);
            $js = <<<JS
$('#$namespacedId-type').on('change', e => {
  const type = $('#$namespacedId-type').val();
  $('#$namespacedId')
    .attr('type', type)
    .attr('inputmode', type);
});
JS;
            $view->registerJs($js);

            $innerHtml = Cp::selectHtml([
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
            $nsContainerId = $view->namespaceInputId($containerId);
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
                $view->namespaceInputs(
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
            $html .= Cp::textFieldHtml([
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
                    'urlSuffix' => Cp::textFieldHtml([
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
                    'target' => Cp::lightswitchFieldHtml([
                        'label' => t('Open in a new tab'),
                        'id' => "$id-target",
                        'name' => "$this->handle[target]",
                        'on' => $value?->target,
                        'value' => '_blank',
                    ]),
                    'title' => Cp::textFieldHtml([
                        'label' => t('Title Text'),
                        'id' => "$id-title",
                        'name' => "$this->handle[title]",
                        'value' => $value?->title,
                    ]),
                    'class' => Cp::textFieldHtml([
                        'fieldClass' => 'info-icon-instructions',
                        'class' => 'code',
                        'label' => t('Class Name'),
                        'instructions' => t('Separate multiple values with spaces.'),
                        'id' => "$id-class",
                        'name' => "$this->handle[class]",
                        'value' => $value?->class,
                    ]),
                    'id' => Cp::textFieldHtml([
                        'class' => 'code',
                        'label' => t('ID'),
                        'id' => "$id-id",
                        'name' => "$this->handle[id]",
                        'value' => $value?->id,
                    ]),
                    'rel' => Cp::textfieldHtml([
                        'fieldClass' => 'info-icon-instructions',
                        'class' => 'code',
                        'label' => t('Relation ({ex})', ['ex' => '<code>rel</code>']),
                        'instructions' => t('Separate multiple values with spaces.'),
                        'id' => "$id-rel",
                        'name' => "$this->handle[rel]",
                        'value' => $value?->rel,
                    ]),
                    'ariaLabel' => Cp::textFieldHtml([
                        'label' => t('ARIA Label'),
                        'id' => "$id-aria-label",
                        'name' => "$this->handle[ariaLabel]",
                        'value' => $value?->ariaLabel,
                    ]),
                    'download' => Cp::lightswitchFieldHtml([
                        'label' => t('Download'),
                        'id' => "$id-download",
                        'name' => "$this->handle[download]",
                        'on' => $value?->download,
                        'toggle' => "$id-filename-field",
                    ]).
                        Cp::textFieldHtml([
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

        $html .= Html::endTag('div');

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getElementValidationRules(): array
    {
        return [
            [
                function (ElementInterface $element) {
                    /** @var LinkData $value */
                    $value = $element->getFieldValue($this->handle);
                    $linkTypes = $this->getLinkTypes();
                    if (! isset($linkTypes[$value->getType()])) {
                        $type = self::types()[$value->getType()] ?? null;
                        $element->addError("field:$this->handle", t('{attribute} no longer allows {type} links.', [
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
                        $element->addError("field:$this->handle", $error ?? t('{attribute} is invalid.', [
                            'attribute' => $this->getUiLabel(),
                        ]));

                        return;
                    }

                    $stringValidator = new StringValidator(['max' => $this->maxLength]);
                    if (! $stringValidator->validate($value, $error)) {
                        $element->addError("field:$this->handle", $error);
                    }
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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

    /**
     * {@inheritdoc}
     */
    public function getElementConditionRuleType(): string
    {
        return LinkFieldConditionRule::class;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var LinkData|null $value */
        return $value?->getLink() ?? '';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (! $value) {
            $url = Sites::getPrimarySite()->getBaseUrl() ?? 'https://craftcms.com/';
            $value = new LinkData($url, new UrlType);
        }

        return $this->getPreviewHtml($value, new EntryElement);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getContentGqlType(): Type|array
    {
        if (! $this->fullGraphqlData) {
            return parent::getContentGqlType();
        }

        return LinkDataType::generateType($this);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
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

    /**
     * {@inheritdoc}
     */
    public function getRelationTargetIds(ElementInterface $element): array
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
}
