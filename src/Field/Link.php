<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Concerns\ProvidesLinkField;
use CraftCms\Cms\Field\Conditions\LinkFieldConditionRule;
use CraftCms\Cms\Field\Contracts\InlineEditableFieldInterface;
use CraftCms\Cms\Field\Contracts\InputSchemaFieldInterface;
use CraftCms\Cms\Field\Contracts\MergeableFieldInterface;
use CraftCms\Cms\Field\Contracts\TracksReferencesFieldInterface;
use CraftCms\Cms\Field\Data\LinkData;
use CraftCms\Cms\Field\LinkTypes\BaseElementLinkType;
use CraftCms\Cms\Field\LinkTypes\BaseLinkType;
use CraftCms\Cms\Field\LinkTypes\Url as UrlType;
use CraftCms\Cms\Gql\GqlInputSchema;
use CraftCms\Cms\Gql\Types\Generators\LinkDataType;
use CraftCms\Cms\Gql\Types\Input\LinkData as LinkDataInput;
use CraftCms\Cms\Support\Arr;
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
class Link extends Field implements InlineEditableFieldInterface, InputSchemaFieldInterface, MergeableFieldInterface, TracksReferencesFieldInterface
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
        $control = $this->linkResolver()->control($context->path, $context->value);

        // Links to elements are limited to the elements the field’s link types can select, so a field that can link
        // to them only takes values copied from itself.
        foreach ($this->getLinkTypes() as $linkType) {
            if ($linkType instanceof BaseElementLinkType) {
                return $control->valueScope($this->uid ?? static::class);
            }
        }

        return $control;
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
        $html = (string) ($value?->getLink() ?? '');

        return $html === '' ? '' : Html::tag('craft-link', $html);
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

    /** @return array<string, mixed> */
    public function getInputSchema(): array
    {
        return GqlInputSchema::fromType($this->getContentGqlMutationArgumentType());
    }
}
