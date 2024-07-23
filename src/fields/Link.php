<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Event;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\RelationalFieldInterface;
use craft\base\RelationalFieldTrait;
use craft\events\RegisterComponentTypesEvent;
use craft\fields\conditions\TextFieldConditionRule;
use craft\fields\data\LinkData;
use craft\fields\linktypes\Asset;
use craft\fields\linktypes\BaseLinkType;
use craft\fields\linktypes\BaseTextLinkType;
use craft\fields\linktypes\Category;
use craft\fields\linktypes\Email as EmailType;
use craft\fields\linktypes\Entry;
use craft\fields\linktypes\Phone;
use craft\fields\linktypes\Url as UrlType;
use craft\helpers\Component;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\validators\ArrayValidator;
use craft\validators\StringValidator;
use yii\base\InvalidArgumentException;
use yii\db\Schema;

/**
 * Link represents a Link field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class Link extends Field implements InlineEditableFieldInterface, RelationalFieldInterface
{
    use RelationalFieldTrait;

    /**
     * @event DefineLinkOptionsEvent The event that is triggered when registering the link types for Link fields.
     * @see types()
     */
    public const EVENT_REGISTER_LINK_TYPES = 'registerLinkTypes';

    /** @deprecated in 5.3.0 */
    public const TYPE_URL = 'url';
    /** @deprecated in 5.3.0 */
    public const TYPE_TEL = 'tel';
    /** @deprecated in 5.3.0 */
    public const TYPE_EMAIL = 'email';

    private static array $_types;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Link');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'link';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return 'string|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @return array<string,BaseLinkType|string>
     * @phpstan-return array<string,class-string<BaseLinkType>>
     */
    private static function types(): array
    {
        if (!isset(self::$_types)) {
            /** @var array<BaseLinkType|string> $types */
            /** @phpstan-var class-string<BaseLinkType>[] $types */
            $types = [
                Asset::class,
                Category::class,
                EmailType::class,
                Entry::class,
                Phone::class,
            ];

            // Fire a registerLinkTypes event
            if (Event::hasHandlers(self::class, self::EVENT_REGISTER_LINK_TYPES)) {
                $event = new RegisterComponentTypesEvent([
                    'types' => $types,
                ]);
                Event::trigger(self::class, self::EVENT_REGISTER_LINK_TYPES, $event);
                $types = $event->types;
            }

            // URL *has* to be there
            $types[] = UrlType::class;

            self::$_types = array_combine(
                array_map(fn(string $type) => $type::id(), $types),
                $types,
            );
        }

        return self::$_types;
    }

    /**
     * @var array<string,BaseLinkType>
     * @see getLinkTypes())
     */
    private array $_linkTypes;

    /**
     * Returns the link types available to the field.
     *
     * @return array<string,BaseLinkType>
     */
    public function getLinkTypes(): array
    {
        if (!isset($this->_linkTypes)) {
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
        foreach ($this->getLinkTypes() as $id => $linkType) {
            if ($id !== UrlType::id() && $linkType->supports($value)) {
                return $id;
            }
        }

        return UrlType::id();
    }

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
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        if (isset($config['types'], $config['typeSettings'])) {
            // Filter out any unneeded type settings
            foreach (array_keys($config['typeSettings']) as $typeId) {
                if (!in_array($typeId, $config)) {
                    unset($config['typeSettings'][$typeId]);
                }
            }
        }

        if (array_key_exists('placeholder', $config)) {
            unset($config['placeholder']);
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['placeholder']);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['types'], ArrayValidator::class];
        $rules[] = [['types', 'maxLength'], 'required'];
        $rules[] = [['maxLength'], 'number', 'integerOnly' => true, 'min' => 10];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $types = self::types();
        $linkTypeOptions = array_map(fn(string $type) => [
            'label' => $type::displayName(),
            'value' => $type::id(),
        ], $types);

        // Sort them by label, with URL at the top
        $urlOption = $linkTypeOptions[UrlType::id()];
        unset($linkTypeOptions[UrlType::id()]);
        usort($linkTypeOptions, fn(array $a, array $b) => $a['label'] <=> $b['label']);
        $linkTypeOptions = [$urlOption, ...$linkTypeOptions];

        $html = Cp::checkboxSelectFieldHtml([
            'label' => Craft::t('app', 'Allowed Link Types'),
            'id' => 'types',
            'fieldClass' => 'mb-0',
            'name' => 'types',
            'options' => $linkTypeOptions,
            'values' => $this->types,
            'required' => true,
            'targetPrefix' => 'types-',
        ]);

        $linkTypes = $this->getLinkTypes();
        $view = Craft::$app->getView();

        foreach ($types as $typeId => $typeClass) {
            $linkType = $linkTypes[$typeId] ?? Component::createComponent($typeClass, BaseLinkType::class);
            $typeSettingsHtml = $view->namespaceInputs(fn() => $linkType->getSettingsHtml(), "typeSettings[$typeId]");
            if ($typeSettingsHtml) {
                $html .=
                    Html::beginTag('div', [
                        'id' => "types-$typeId",
                        'class' => array_keys(array_filter([
                            'pt-xl' => true,
                            'hidden' => !isset($linkTypes[$typeId]),
                        ])),
                    ]) .
                    Html::tag('hr') .
                    $typeSettingsHtml .
                    Html::endTag('div');
            }
        }

        return $html .
            Html::tag('hr') .
            Cp::textFieldHtml([
                'label' => Craft::t('app', 'Max Length'),
                'instructions' => Craft::t('app', 'The maximum length (in bytes) the field can hold.'),
                'id' => 'maxLength',
                'name' => 'maxLength',
                'type' => 'number',
                'min' => '10',
                'step' => '10',
                'value' => $this->maxLength,
                'errors' => $this->getErrors('maxLength'),
                'data' => ['error-key' => 'maxLength'],
            ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof LinkData) {
            return $value;
        }

        $linkTypes = $this->getLinkTypes();

        if (is_array($value)) {
            $typeId = $value['type'] ?? UrlType::id();
            $value = trim($value[$typeId]['value'] ?? '');

            if (!isset($linkTypes[$typeId])) {
                throw new InvalidArgumentException("Invalid link type: $typeId");
            }

            if (!$value) {
                return null;
            }

            $linkType = $linkTypes[$typeId];
            $value = $linkType->normalizeValue(str_replace(' ', '+', $value));
        } else {
            if (!$value) {
                return null;
            }

            $typeId = $this->resolveType($value);
            $linkType = $linkTypes[$typeId];
        }

        return new LinkData($value, $linkType);
    }

    /**
     * @inheritdoc
     */
    public function useFieldset(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $linkTypes = $this->getLinkTypes();
        /** @var LinkData|null $value */
        $valueTypeId = $value?->type ?? UrlType::id();
        $allowedTypeIds = in_array($valueTypeId, $this->types) ? $this->types : array_merge($this->types, [$valueTypeId]);
        $allowedTypeIds = array_filter($allowedTypeIds, fn(string $typeId) => isset($linkTypes[$typeId]));
        $id = $this->getInputId();

        $view = Craft::$app->getView();

        if (!$value) {
            // Override the initial value being set to null by CustomField::inputHtml()
            $view->setInitialDeltaValue($this->handle, [
                'type' => $valueTypeId,
                'value' => '',
            ]);
        }

        $typeInputName = "$this->handle[type]";

        if (count($allowedTypeIds) === 1) {
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
                'options' => array_map(fn(string $typeId) => [
                    'label' => $linkTypes[$typeId]::displayName(),
                    'value' => $linkTypes[$typeId]::id(),
                ], $allowedTypeIds),
                'value' => $valueTypeId,
                'inputAttributes' => [
                    'aria' => [
                        'label' => Craft::t('app', 'URL type'),
                    ],
                ],
                'toggle' => true,
                'targetPrefix' => "$id-",
            ]);
        }

        foreach ($allowedTypeIds as $typeId) {
            $containerId = "$id-$typeId";
            $nsContainerId = $view->namespaceInputId($containerId);
            $selected = $typeId === $valueTypeId;
            $typeValue = $selected ? $value?->serialize() : null;
            $isTextLink = is_subclass_of($linkTypes[$typeId], BaseTextLinkType::class);
            $innerHtml .=
                Html::beginTag('div', [
                    'id' => $containerId,
                    'class' => array_keys(array_filter([
                        'flex-grow' => true,
                        'hidden' => !$selected,
                        'text-link' => $isTextLink,
                    ])),
                ]) .
                $view->namespaceInputs(
                    fn() => $linkTypes[$typeId]->inputHtml($this, $typeValue, $nsContainerId),
                    "$this->handle[$typeId]",
                ) .
                Html::endTag('div');
        }

        return
            Html::beginTag('div', [
                'id' => $id,
                'class' => array_keys(array_filter([
                    'link-input' => true,
                ])),
            ]) .
            Html::tag('div', $innerHtml, [
                'class' => ['flex', 'flex-nowrap'],
            ]) .
            Html::endTag('div');
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                function(ElementInterface $element) {
                    /** @var LinkData $value */
                    $value = $element->getFieldValue($this->handle);
                    $linkTypes = $this->getLinkTypes();
                    $linkType = $linkTypes[$value->type];
                    $error = null;
                    if (!$linkType->validateValue($value->serialize(), $error)) {
                        /** @var string|null $error */
                        $element->addError("field:$this->handle", $error ?? Craft::t('yii', '{attribute} is invalid.', [
                            'attribute' => $this->getUiLabel(),
                        ]));
                        return;
                    }

                    $stringValidator = new StringValidator(['max' => $this->maxLength]);
                    if (!$stringValidator->validate($value->serialize(), $error)) {
                        $element->addError("field:$this->handle", $error);
                    }
                },
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getElementConditionRuleType(): array|string|null
    {
        return TextFieldConditionRule::class;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        /** @var LinkData|null $value */
        if (!$value) {
            return '';
        }
        $value = Html::encode((string)$value);
        return "<a href=\"$value\" target=\"_blank\">$value</a>";
    }

    /**
     * @inheritdoc
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
