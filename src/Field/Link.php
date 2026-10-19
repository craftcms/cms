<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Cp\FormFields;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Concerns\ProvidesLinkField;
use CraftCms\Cms\Field\Conditions\LinkFieldConditionRule;
use CraftCms\Cms\Field\Contracts\CrossSiteCopyableFieldInterface;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseTextLinkType;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Gql\Types\Generators\LinkDataType;
use CraftCms\Cms\Gql\Types\Input\LinkData as LinkDataInput;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\InputNamespace;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Ui\Contracts\Control;
use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Nodes\Field as UiField;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use GraphQL\Type\Definition\Type;
use Override;

use function CraftCms\Cms\t;

/**
 * Link represents a Link field.
 *
 * @since 6.0.0
 */
class Link extends Field implements CrossSiteCopyableFieldInterface, InlineEditableFieldInterface, MergeableFieldInterface, TracksReferencesFieldInterface
{
    use ProvidesLinkField;

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
        return app(LinkTypes::class)->typesById()->all();
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
     * @see linkResolver()
     */
    private LinkResolver $_linkResolver;

    /**
     * @var string[] Allowed link types
     */
    public array $types = [
        'entry',
        'url',
    ];

    /**
     * @var array<string, array<string, mixed>> Settings for the allowed types
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

    #[Override]
    public function attributeLabels(): array
    {
        return [
            ...parent::attributeLabels(),
            ...$this->linkSettingsAttributeLabels(),
            'maxLength' => t('Max Length'),
        ];
    }

    #[Override]
    public function settingsUi(UiContext $context = new UiContext): Ui
    {
        return Ui::make($this->linkSettingsNodes())->add(
            UiField::make(t('Max Length'))
                ->instructions(t('The maximum length (in bytes) the field can hold.'))
                ->control(Number::make('maxLength')->min(10)->value($this->maxLength)),
        )->when(
            Cms::config()->enableGql,
            fn (Ui $ui): Ui => $ui->add(
                UiField::make(t('GraphQL Mode'))
                    ->control(Choice::make('graphqlMode')->options([
                        ['label' => t('Full data'), 'value' => 'full'],
                        ['label' => t('URL only'), 'value' => 'url'],
                    ])->value($this->fullGraphqlData ? 'full' : 'url')),
            ),
        );
    }

    /**
     * Returns the link types available to the field.
     *
     * @return array<string,BaseLinkType>
     */
    public function getLinkTypes(): array
    {
        return $this->linkResolver()->linkTypes();
    }

    private function linkResolver(): LinkResolver
    {
        return $this->_linkResolver ??= new LinkResolver(
            types: $this->types,
            typeSettings: $this->typeSettings,
            showLabelField: $this->showLabelField,
            advancedFields: $this->advancedFields,
            maxLength: $this->maxLength,
        );
    }

    #[Override]
    public function uiControl(FieldContext $context): Control
    {
        return $this->linkResolver()->control($context->path, $context->value);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
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

    /** @return array<string, BaseLinkType> */
    protected function configuredLinkTypesForSettings(): array
    {
        return $this->getLinkTypes();
    }

    #[Override]
    public function normalizeValue(mixed $value, ?ElementInterface $element): ?LinkData
    {
        $resolver = $this->linkResolver();

        if ($value instanceof LinkData && $element && $this->isPropagatingAcrossTranslations($element)) {
            return $resolver->localize($value, $element);
        }

        return $resolver->normalize($value);
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
                        'label' => t('URL Type'),
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

    /** @return list<Closure> */
    #[Override]
    public function getElementRules(ElementInterface $element): array
    {
        return [
            function (string $attribute, LinkData $value, Closure $fail) {
                $error = $this->linkResolver()->validate($value, $this->getUiLabel());

                if ($error !== null) {
                    $fail($error);
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

        return $this->linkResolver()->isEmpty($value);
    }

    public function getElementConditionRuleType(): string
    {
        return LinkFieldConditionRule::class;
    }

    #[Override]
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var LinkData|null $value */
        return (string) ($value?->getLink() ?? '');
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
    public function getContentGqlType(): Type
    {
        if (! $this->fullGraphqlData) {
            return parent::getContentGqlType();
        }

        return LinkDataType::generateType($this);
    }

    /** @return Type|array{name: string, type: Type, description: string|null} */
    #[Override]
    public function getContentGqlMutationArgumentType(): Type|array
    {
        if (! $this->fullGraphqlData) {
            return parent::getContentGqlMutationArgumentType();
        }

        return LinkDataInput::getType();
    }

    public function getReferenceTargetIds(ElementInterface $element): array
    {
        /** @var LinkData|null $value */
        $value = $element->getFieldValue($this->handle);

        return $this->linkResolver()->referenceTargetIds($value);
    }

    public function replaceReferences(ElementInterface $element, array $oldTargetIds, int $newTargetId): bool
    {
        /** @var LinkData|null $value */
        $value = $element->getFieldValue($this->handle);
        $newValue = $this->linkResolver()->replaceReference($value, $oldTargetIds, $newTargetId);

        if ($newValue === null) {
            return false;
        }

        $element->setFieldValue($this->handle, $newValue);

        return true;
    }
}
