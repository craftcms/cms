<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\models;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldLayoutComponent;
use craft\base\FieldLayoutElement;
use craft\errors\FieldNotFoundException;
use craft\fieldlayoutelements\BaseField;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * FieldLayoutTab model class.
 *
 * @property FieldLayoutElement[]|null $elements The tab’s layout elements
 * @property FieldLayout|null $layout The tab’s layout
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldLayoutTab extends FieldLayoutComponent
{
    /**
     * Creates a new field layout tab from the given config.
     *
     * @param array $config
     * @return self
     * @since 3.5.0
     */
    public static function createFromConfig(array $config): self
    {
        static::updateConfig($config);
        return new self($config);
    }

    /**
     * Returns the label HTML that should be displayed within field layout designers.
     *
     * @return string
     * @since 5.1.0
     */
    public function labelHtml(): string
    {
        return
            Html::tag('h3', Html::encode($this->name), [
                'class' => 'fld-tab__name',
            ]) .
            ($this->hasConditions() ? Html::tag('div', Cp::iconSvg('diamond'), [
                'class' => array_filter(array_merge(['cp-icon', 'puny', 'orange'])),
                'title' => Craft::t('app', 'This tab is conditional'),
                'aria' => ['label' => Craft::t('app', 'This tab is conditional')],
            ]) : '');
    }

    /**
     * Updates a field layout tab’s config to the new format.
     *
     * @param array $config
     * @since 3.5.0
     */
    public static function updateConfig(array &$config): void
    {
        if (!array_key_exists('fields', $config)) {
            return;
        }

        $config['elements'] = [];

        ArrayHelper::multisort($config['fields'], 'sortOrder');
        foreach ($config['fields'] as $fieldUid => $fieldConfig) {
            $config['elements'][] = [
                'type' => CustomField::class,
                'fieldUid' => $fieldUid,
                'required' => (bool)$fieldConfig['required'],
            ];
        }

        unset($config['fields']);
    }

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Layout ID
     */
    public ?int $layoutId = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var int|null Sort order
     */
    public ?int $sortOrder = null;

    /**
     * @var FieldLayout
     * @see getLayout()
     * @see setLayout()
     */
    private FieldLayout $_layout;

    /**
     * @var FieldLayoutElement[] The tab’s layout elements
     * @see getElements()
     * @see setElements()
     */
    private array $_elements = [];

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        // Config normalization
        if (array_key_exists('elements', $config)) {
            if (is_string($config['elements'])) {
                $config['elements'] = Json::decode($config['elements']);
            }
            if (!is_array($config['elements'])) {
                unset($config['elements']);
            }
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function fields(): array
    {
        $fields = parent::fields();
        unset($fields['sortOrder']);
        return $fields;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'layoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name'], 'string', 'max' => 255];
        $rules[] = [['sortOrder'], 'string', 'max' => 4];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function hasSettings()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): ?string
    {
        return Cp::textFieldHtml([
            'label' => Craft::t('app', 'Name'),
            'name' => 'name',
            'value' => $this->name,
            'required' => true,
        ]);
    }

    /**
     * Returns the field layout tab’s config.
     *
     * @return array
     * @since 3.5.0
     */
    public function getConfig(): array
    {
        if (!isset($this->uid)) {
            $this->uid = StringHelper::UUID();
        }

        $config = $this->toArray(['name', 'uid', 'userCondition', 'elementCondition']);
        $config['elements'] = $this->getElementConfigs();
        return $config;
    }

    /**
     * Returns the tab’s elements’ configs.
     *
     * @return array[]
     * @since 3.5.0
     */
    public function getElementConfigs(): array
    {
        $elementConfigs = [];
        foreach ($this->getElements() as $layoutElement) {
            if (!isset($layoutElement->uid)) {
                $layoutElement->uid = StringHelper::UUID();
            }
            $elementConfigs[] = ['type' => get_class($layoutElement)] + $layoutElement->toArray();
        }
        return $elementConfigs;
    }

    /**
     * Returns the tab’s layout.
     *
     * @return FieldLayout The tab’s layout.
     * @throws InvalidConfigException if [[layoutId]] is set but invalid
     */
    public function getLayout(): FieldLayout
    {
        if (isset($this->_layout)) {
            return $this->_layout;
        }

        if (!$this->layoutId) {
            throw new InvalidConfigException('Field layout tab is missing its field layout.');
        }

        if (($this->_layout = Craft::$app->getFields()->getLayoutById($this->layoutId)) === null) {
            throw new InvalidConfigException('Invalid layout ID: ' . $this->layoutId);
        }

        return $this->_layout;
    }

    /**
     * Sets the tab’s layout.
     *
     * @param FieldLayout $layout The tab’s layout.
     */
    public function setLayout(FieldLayout $layout): void
    {
        $this->_layout = $layout;
    }

    /**
     * Returns the tab’s layout elements.
     *
     * @return FieldLayoutElement[]
     * @since 4.0.0
     */
    public function getElements(): array
    {
        return $this->_elements ?? [];
    }

    /**
     * Sets the tab’s layout elements.
     *
     * @param array $elements
     * @phpstan-param array<FieldLayoutElement|array{type:class-string<FieldLayoutElement>}> $elements
     * @since 4.0.0
     */
    public function setElements(array $elements): void
    {
        $fieldsService = Craft::$app->getFields();
        $pluginsService = Craft::$app->getPlugins();
        $this->_elements = [];

        foreach ($elements as $layoutElement) {
            if (is_array($layoutElement)) {
                try {
                    $layoutElement = $fieldsService->createLayoutElement($layoutElement);
                } catch (FieldNotFoundException) {
                    // Skip quietly
                    continue;
                } catch (InvalidArgumentException|InvalidConfigException $e) {
                    Craft::warning('Invalid field layout element config: ' . $e->getMessage(), __METHOD__);
                    Craft::$app->getErrorHandler()->logException($e);
                    continue;
                }
            }

            // if layout element belongs to a plugin, ensure the plugin is installed
            $pluginHandle = $pluginsService->getPluginHandleByClass($layoutElement::class);
            if ($pluginHandle === null || $pluginsService->isPluginEnabled($pluginHandle)) {
                $layoutElement->setLayout($this->getLayout());
                $this->_elements[] = $layoutElement;
            }
        }

        // Clear caches
        if (isset($this->_layout)) {
            $this->_layout->reset();
        }
    }

    /**
     * Returns the tab’s HTML ID.
     *
     * @return string
     */
    public function getHtmlId(): string
    {
        $asciiName = isset($this->name) ? StringHelper::toKebabCase(StringHelper::toAscii($this->name, 'en')) : '';

        if ($asciiName === '') {
            // Use md5() as a fallback
            $asciiName = sprintf('tab-%s', md5($this->name));
        }

        // ensure unique tab id even if there are multiple tabs with the same name
        $tabOrder = StringHelper::pad((string)$this->sortOrder, 2, '0', 'left');

        return Html::id("tab$tabOrder-$asciiName");
    }

    /**
     * Returns whether the given element has any validation errors for the custom fields included in this tab.
     *
     * @param ElementInterface $element
     * @return bool
     * @since 3.4.0
     */
    public function elementHasErrors(ElementInterface $element): bool
    {
        if (!$element->hasErrors()) {
            return false;
        }

        foreach ($this->getElements() as $layoutElement) {
            if ($layoutElement instanceof BaseField && $element->hasErrors($layoutElement->attribute() . '.*')) {
                return true;
            }
        }

        return false;
    }
}
